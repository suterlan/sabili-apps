<?php

namespace App\Http\Controllers;

use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class CetakKartuController extends Controller
{
    public function download($id)
    {
        // Set Time Limit agar tidak timeout saat download gambar GDrive
        set_time_limit(300);

        $user = User::findOrFail($id);
        $fotoBase64 = null;

        if ($user->file_pas_foto) {
            try {
                $disk = Storage::disk('google');
                // Cek apakah file benar-benar ada
                if ($disk->exists($user->file_pas_foto)) {
                    $fileContent = $disk->get($user->file_pas_foto);
                    // Ambil Mime Type Asli (jpeg/png)
                    $mimeType = $disk->mimeType($user->file_pas_foto);
                    $fotoBase64 = 'data:' . $mimeType . ';base64,' . base64_encode($fileContent);
                }
            } catch (\Exception $e) {
                // Log error tapi jangan stop proses, biar PDF tetap terbit walau tanpa foto
                // \Log::error("Gagal ambil foto: " . $e->getMessage());
            }
        }

        // Generate QR Code format PNG, lalu encode ke Base64
        $qrRaw = QrCode::format('png')
            ->size(200) // Resolusi lebih besar biar tidak pecah
            ->margin(1) // Margin tipis
            ->generate('ID Anggota: ' . $user->id . ' | ' . $user->name);

        $qrBase64 = base64_encode($qrRaw);

        $pdf = Pdf::loadView('pdf.kartu-anggota', [
            'user' => $user,
            'foto' => $fotoBase64,
            'qrcode' => $qrBase64,
        ]);

        // Ukuran ID Card Standar (CR80): 85.6mm x 53.98mm
        // Konversi ke Point (1mm = 2.83pt) -> Sekitar 242pt x 153pt
        $pdf->setPaper([0, 0, 242.65, 153.07], 'landscape');

        // PENTING: Aktifkan opsi remote jika base64 gagal (opsional)
        $pdf->setOptions(['isRemoteEnabled' => true]);

        return $pdf->stream('Kartu-' . $user->name . '.pdf');

        //jika tidak berhasil coba cara ini:
        // return $pdf->stream(...); // Matikan dulu
        // return view('pdf.kartu-anggota', [
        //     'user' => $user,
        //     'foto' => $fotoBase64,
        //     'qrcode' => $qrcode,
        // ]);
    }
}

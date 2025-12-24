<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class GoogleDriveImageController extends Controller
{
    /**
     * Menampilkan gambar dari Google Drive dan convert HEIC jika perlu.
     */
    public function show($path)
    {
        // 1. Decode path (menangani spasi atau karakter khusus)
        $path = urldecode($path);

        $disk = Storage::disk('google');

        // 2. Cek apakah file ada
        if (! $disk->exists($path)) {
            abort(404);
        }

        // 3. Ambil konten file dan metadatanya
        // Kita gunakan ->get() bukan ->response() karena kita butuh memanipulasi datanya (convert)
        $fileContent = $disk->get($path);
        $mimeType = $disk->mimeType($path);
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        // 4. LOGIKA KONVERSI HEIC KE JPG
        // Browser tidak bisa baca HEIC, jadi kita ubah on-the-fly
        if ($extension === 'heic' || $extension === 'heif' || $mimeType === 'image/heic') {

            // Cek apakah server punya ekstensi Imagick
            if (extension_loaded('imagick')) {
                try {
                    $imagick = new \Imagick();

                    // Load gambar HEIC dari string content
                    $imagick->readImageBlob($fileContent);

                    // Set format ke JPEG
                    $imagick->setImageFormat('jpeg');

                    // Set kualitas (80 cukup bagus dan ringan)
                    $imagick->setCompressionQuality(80);

                    // Ambil hasil konversi
                    $fileContent = $imagick->getImageBlob();

                    // Update Mime Type header agar browser tahu ini sekarang JPG
                    $mimeType = 'image/jpeg';

                    // Bersihkan memori
                    $imagick->clear();
                    $imagick->destroy();
                } catch (\Exception $e) {
                    // Jika gagal convert, log errornya tapi tetap kirim file asli
                    // (Gambar mungkin broken di browser, tapi setidaknya file terkirim)
                    Log::error("Gagal convert HEIC: " . $path . " - Error: " . $e->getMessage());
                }
            } else {
                Log::warning("File HEIC ditemukan tapi ekstensi PHP Imagick tidak terinstall.");
            }
        }

        // 5. Buat Response Manual
        $response = response($fileContent, 200);

        // 6. Set Header Content-Type yang (mungkin) sudah diubah jadi image/jpeg
        $response->header('Content-Type', $mimeType);

        // 7. Optimasi Cache (PENTING AGAR TIDAK LOAD ULANG TERUS)
        // Cache selama 1 tahun (31536000 detik) karena file Google Drive jarang berubah namanya
        $seconds = 31536000;
        $response->header('Cache-Control', 'public, max-age=' . $seconds);
        $response->header('Pragma', 'public');
        $response->header('Expires', gmdate('D, d M Y H:i:s \G\M\T', time() + $seconds));

        return $response;
    }
}

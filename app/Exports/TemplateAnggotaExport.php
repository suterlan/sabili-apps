<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TemplateAnggotaExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            new TemplateSheet,
            new ReferensiPendampingSheet,
            new ReferensiAdminSheet,
        ];
    }
}

// === SHEET 1: FORM PENGISIAN DENGAN INSTRUKSI ===
class TemplateSheet implements FromCollection, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    public function collection()
    {
        return collect([]); // Data kosong, user hanya butuh format kolom
    }

    public function headings(): array
    {
        return [
            // BARIS 1: Judul Besar
            ['PANDUAN PENGISIAN FORMULIR IMPORT DATA ANGGOTA (PELAKU USAHA)'],

            // BARIS 2: Instruksi Utama
            ['PENTING: ketik NAMA WILAYAH dengan ejaan yang benar (Contoh: Jawa Barat, Kabupaten Bandung /Kota Bandung (untuk Kota), Leles, Pusakasari). Kolom "Ajukan" isi angka 1 untuk YA.'],

            // BARIS 3: Disclaimer Null
            ['PERHATIAN: Jika nama wilayah salah ketik atau tidak ditemukan, data wilayah akan dikosongkan (NULL) oleh sistem dan bisa dilengkapi manual nanti melalui aplikasi.'],

            // BARIS 4: Header Kolom Database (Mapping Import)
            [
                'Nama Lengkap',               // A
                'Email',                      // B
                'Password (Optional)',        // C
                'No HP',                      // D
                'NIK',                        // E
                'Tanggal Lahir (YYYY-MM-DD)', // F
                'NIB',                        // G
                'Merk Dagang',                // H
                'Alamat Lengkap',             // I

                // Kolom Relasi Penting
                'ID Pendamping (Lihat Sheet 2)', // J

                // Visual Info (Tidak masuk DB, hanya helper)
                'Nama Pendamping (Info)',     // K

                // Data Wilayah (Input Teks)
                'Provinsi',  // L
                'Kabupaten',    // M
                'Kecamatan',    // N
                'Desa',       // O

                // Data SiHalal
                'Akun SiHalal',               // P
                'Pass SiHalal',               // Q
                'Mitra Halal',                // R

                // === KOLOM BARU ===
                'Ajukan? (1=Ya, 0=Tidak)',           // S
                'ID Admin Verifikator (Lihat Sheet 3)', // T
            ],
        ];
    }

    public function title(): string
    {
        return 'Form Input Anggota';
    }

    public function styles(Worksheet $sheet)
    {
        // 1. Merge Cells untuk Judul & Instruksi (Kolom A sampai T)
        $sheet->mergeCells('A1:T1');
        $sheet->mergeCells('A2:T2');
        $sheet->mergeCells('A3:T3');

        // 2. Styling Judul (Baris 1) - Biru Tebal
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF2563EB']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // 3. Styling Instruksi (Baris 2 & 3) - Merah Muda Peringatan
        $sheet->getStyle('A2:A3')->applyFromArray([
            'font' => ['italic' => true, 'color' => ['argb' => 'FF7F1D1D']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFEE2E2']],
            'alignment' => ['wrapText' => true, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // Atur tinggi baris instruksi agar teks terbaca
        $sheet->getRowDimension(2)->setRowHeight(30);
        $sheet->getRowDimension(3)->setRowHeight(30);

        // 4. Styling Header Kolom (Baris 4)
        $sheet->getStyle('A4:T4')->getFont()->setBold(true);

        // Highlight Kolom ID Pendamping (Merah)
        $sheet->getStyle('J4')->getFont()->getColor()->setARGB('FFFF0000');

        // Highlight Kolom Wilayah (Biru) agar user notice harus input teks
        $sheet->getStyle('L4:O4')->getFont()->getColor()->setARGB('FF2563EB');

        // Style Kolom Baru (Hijau) - Menandakan Fitur Pengajuan
        $sheet->getStyle('S4:T4')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF16A34A']], // Hijau
        ]);
    }
}

// === SHEET 2: REFERENSI DATA PENDAMPING (AMAN DARI NULL) ===
class ReferensiPendampingSheet implements FromCollection, ShouldAutoSize, WithHeadings, WithTitle
{
    public function collection()
    {
        return User::query()
            // Eager load semua relasi wilayah agar query cepat
            ->with(['province', 'city', 'district', 'village'])
            ->where('role', 'pendamping')
            ->get()
            ->map(function ($user) {
                // LOGIKA SAFE NULL (Agar tidak error "Reading property on null")
                // Format: "Nama Wilayah / Kode Wilayah"

                $provName = $user->province?->name ?? 'Tidak Diketahui';
                $provCode = $user->provinsi ?? '-';

                $cityName = $user->city?->name ?? 'Tidak Diketahui';
                $cityCode = $user->kabupaten ?? '-';

                $distName = $user->district?->name ?? 'Tidak Diketahui';
                $distCode = $user->kecamatan ?? '-';

                $villName = $user->village?->name ?? 'Tidak Diketahui';
                $villCode = $user->desa ?? '-';

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'provinsi' => "$provName / $provCode",
                    'kabupaten' => "$cityName / $cityCode",
                    'kecamatan' => "$distName / $distCode",
                    'desa' => "$villName / $villCode",
                ];
            });
    }

    public function headings(): array
    {
        return [
            'ID PENDAMPING (COPY KE SHEET 1)',
            'NAMA PENDAMPING',
            'PROVINSI (NAMA / KODE)',
            'KABUPATEN (NAMA / KODE)',
            'KECAMATAN (NAMA / KODE)',
            'DESA (NAMA / KODE)',
        ];
    }

    public function title(): string
    {
        return 'Referensi Pendamping';
    }
}
// === SHEET 3: REFERENSI ADMIN (BARU) ===
class ReferensiAdminSheet implements FromCollection, ShouldAutoSize, WithHeadings, WithTitle
{
    public function collection()
    {
        // Asumsi role admin bernama 'admin' atau 'superadmin'
        // Sesuaikan dengan logic role di aplikasi Anda
        return User::query()
            ->whereIn('role', ['admin'])
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ];
            });
    }

    public function headings(): array
    {
        return [
            'ID ADMIN (COPY KE SHEET 1)',
            'NAMA ADMIN',
            'EMAIL',
            'ROLE',
        ];
    }

    public function title(): string
    {
        return 'Referensi Admin';
    }
}

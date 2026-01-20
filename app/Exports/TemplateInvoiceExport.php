<?php

namespace App\Exports;

use App\Models\Pengajuan;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TemplateInvoiceExport implements FromCollection, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    public function collection()
    {
        // 1. Mulai Query Dasar
        $query = Pengajuan::with(['user', 'pendamping'])
            ->whereIn('status_verifikasi', [
                Pengajuan::STATUS_SERTIFIKAT,
                Pengajuan::STATUS_FATWA
            ])
            ->whereNull('tagihan_id');

        // 2. CEK ROLE: Jika BUKAN superadmin, batasi hanya data miliknya sendiri
        // Sesuaikan 'super_admin' dengan nama role di database Anda
        if (! auth()->user()->isSuperAdmin()) {
            $query->where('verificator_id', auth()->id());
        }

        // PERUBAHAN 1: SORTING
        // Urutkan berdasarkan Status dulu, baru Nama Pendamping
        // Agar di Excel nanti statusnya tidak acak-acakan (belang-betong)
        $query->orderBy('status_verifikasi', 'desc')
            ->orderBy('pendamping_id', 'asc');

        // Ambil data terlebih dahulu
        $data = $query->get();

        // 3. LOGIKA GROUPING (Fix)
        // Array untuk menyimpan mapping: [pendamping_id => 'NOMOR_INVOICE_XYZ']
        $invoiceMap = [];

        return $data->map(function ($item) use (&$invoiceMap) {

            // Ambil ID Pendamping sebagai Key
            // Jika pendamping null (jarang terjadi), kita pakai ID pengajuan biar unik sendiri
            $key = $item->pendamping_id ?? 'single_' . $item->id;

            // Cek apakah pendamping ini sudah punya nomor invoice di batch export ini?
            if (! isset($invoiceMap[$key])) {
                // Jika BELUM, generate nomor baru dan simpan ke memory
                // Asumsi: auto_invoice_number menghasilkan string unik
                $invoiceMap[$key] = $item->auto_invoice_number;
            }

            // Ambil nomor yang sudah disimpan (agar sama untuk pendamping yang sama)
            $nomorInvoiceFixed = $invoiceMap[$key];

            // PERUBAHAN 2: Label Status
            // Kita ubah kode status jadi teks yang mudah dibaca Admin
            $labelStatus = match ($item->status_verifikasi) {
                Pengajuan::STATUS_FATWA => 'FATWA (Belum Sertifikat)',
                Pengajuan::STATUS_SERTIFIKAT => 'SERTIFIKAT (Sudah Terbit)',
                default => $item->status_verifikasi,
            };

            return [
                // PERUBAHAN 3: Menambahkan Kolom Status di paling kiri
                'status_info' => $labelStatus,

                'nik' => $item->user->nik . ' ',
                'nama_pelaku_usaha' => $item->user->name,
                'pendamping' => $item->pendamping->name ?? '-',
                // INI YANG BERUBAH: Menggunakan nomor yang sudah di-grouping
                'nomor_invoice' => $nomorInvoiceFixed,
                'via' => '',
                'total_nominal' => '',
                'link_pembayaran' => '',
            ];
        });
    }

    public function headings(): array
    {
        return [
            ['TEMPLATE IMPORT TERBIT INVOICE'],
            ['Catatan: Nomor Invoice otomatis disamakan berdasarkan Pendamping. Admin cukup isi Total Nominal.'],
            [''],
            [
                'STATUS (INFO)', // A -> Kolom Baru
                'NIK (JANGAN UBAH)', // B
                'NAMA PELAKU USAHA', // C
                'PENDAMPING', // D
                'NOMOR INVOICE (AUTO GROUP)', // E
                'VIA (PTSP / HALALMAX)', // F
                'TOTAL NOMINAL (WAJIB ISI)', // G
                'LINK PEMBAYARAN (OPSIONAL)', // H
            ],
        ];
    }

    public function title(): string
    {
        return 'Input Invoice';
    }

    public function styles(Worksheet $sheet)
    {
        // Header Merge (Sesuaikan lebarnya karena nambah 1 kolom jadi G)
        $sheet->mergeCells('A1:H1');
        $sheet->mergeCells('A2:H2');

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A4:H4')->getFont()->setBold(true);

        // Warna Header Tabel
        $sheet->getStyle('A4:H4')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFD1FAE5');

        // Opsional: Kasih warna beda dikit untuk kolom VIA (G4) biar admin sadar
        $sheet->getStyle('F4')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE0F2FE'); // Biru muda banget

        // Warna Kolom Nominal (G4 sekarang, karena geser 1 kolom)
        // Kita cari header yang isinya 'TOTAL NOMINAL' agar aman
        $sheet->getStyle('G4')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFFFFBEB'); // Kuning

        // Format Text
        $sheet->getStyle('B')->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT); // NIK
        $sheet->getStyle('E')->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT); // Invoice

        // PERUBAHAN 4: Conditional Formatting (Warna Warni Status)
        // Jika kolom A isinya 'FATWA...', kasih warna merah muda biar ngeh
        // Jika kolom A isinya 'SERTIFIKAT...', kasih warna biru muda
        // (Ini agak ribet coding manualnya di PHPSpreadsheet, 
        //  tapi label teks 'FATWA' vs 'SERTIFIKAT' di kolom A sudah cukup jelas).
    }
}

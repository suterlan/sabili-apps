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
            ->where('status_verifikasi', Pengajuan::STATUS_SERTIFIKAT)
            ->whereNull('tagihan_id');

        // 2. CEK ROLE: Jika BUKAN superadmin, batasi hanya data miliknya sendiri
        // Sesuaikan 'super_admin' dengan nama role di database Anda
        if (! auth()->user()->isSuperAdmin()) {
            $query->where('verificator_id', auth()->id());
        }

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

            return [
                'nik' => $item->user->nik . ' ',
                'nama_pelaku_usaha' => $item->user->name,
                'pendamping' => $item->pendamping->name ?? '-',

                // INI YANG BERUBAH: Menggunakan nomor yang sudah di-grouping
                'nomor_invoice' => $nomorInvoiceFixed,

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
                'NIK (JANGAN UBAH)', // A
                'NAMA PELAKU USAHA', // B
                'PENDAMPING', // C
                'NOMOR INVOICE (AUTO GROUP)', // D
                'TOTAL NOMINAL (WAJIB ISI)', // E
                'LINK PEMBAYARAN (OPSIONAL)', // F
            ],
        ];
    }

    public function title(): string
    {
        return 'Input Invoice';
    }

    public function styles(Worksheet $sheet)
    {
        // Header Styles
        $sheet->mergeCells('A1:F1');
        $sheet->mergeCells('A2:F2');

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A4:F4')->getFont()->setBold(true);

        // Warna Header Tabel
        $sheet->getStyle('A4:F4')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFD1FAE5'); // Hijau Muda

        // Kolom Nominal (E4) dikasih warna kuning biar sadar harus diisi
        $sheet->getStyle('E4')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFFFFBEB');

        // Format Kolom NIK & Nomor Invoice agar jadi Teks (biar angka 0 di depan tidak hilang)
        $sheet->getStyle('A')->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);
        $sheet->getStyle('D')->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);
    }
}

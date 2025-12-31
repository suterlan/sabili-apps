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
        // AMBIL DATA YANG SIAP DITAGIH
        // Syarat: Status = Sertifikat Diterbitkan DAN Belum punya tagihan
        return Pengajuan::with(['user', 'pendamping'])
            ->where('status_verifikasi', Pengajuan::STATUS_SERTIFIKAT)
            ->whereNull('tagihan_id')
            ->get()
            ->map(function ($item) {
                return [
                    'nik' => $item->user->nik . ' ', // Paksa string biar angka 0 tidak hilang
                    'nama_pelaku_usaha' => $item->user->name,
                    'pendamping' => $item->pendamping->name ?? '-',
                    // Panggil fungsi dari Model, jangan tulis manual disini
                    'nomor_invoice' => $item->auto_invoice_number,
                    'total_nominal' => '', // <--- ADMIN TINGGAL ISI INI
                    'link_pembayaran' => '', // <--- DAN INI
                ];
            });
    }

    public function headings(): array
    {
        return [
            ['TEMPLATE IMPORT TERBIT INVOICE'],
            ['Catatan: Hapuslah baris data jika tidak termasuk dalam kategori invoice. Jika ingin invoice grouping, silahkan (copy/paste) gunakan nomor invoice yang sama pada pendamping yang sama.'],
            [''], // Spasi
            [
                'NIK (JANGAN UBAH)', // A
                'NAMA PELAKU USAHA', // B
                'PENDAMPING', // C
                'NOMOR INVOICE (AUTO)', // D
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

<?php

namespace App\Exports;

use App\Models\Pengajuan;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping; // Wajib ada untuk mapping data
use Maatwebsite\Excel\Concerns\ShouldAutoSize; // Agar lebar kolom otomatis
use Maatwebsite\Excel\Concerns\Exportable;

class PengajuanExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    use Exportable;

    protected $startDate;
    protected $endDate;

    public function __construct($startDate, $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function query()
    {
        return Pengajuan::query()
            ->with(['user', 'pendamping', 'verificator'])
            // 1. Filter Rentang Tanggal
            ->whereDate('created_at', '>=', $this->startDate)
            ->whereDate('created_at', '<=', $this->endDate)
            // 2. Filter Status: HANYA YANG STATUSNYA INVOICE
            ->where('status_verifikasi', Pengajuan::STATUS_INVOICE)
            ->latest();
    }

    // Judul Header Kolom Excel
    public function headings(): array
    {
        return [
            'ID',
            'No Invoice',
            'Tanggal Masuk',
            'Nama Pelaku Usaha',
            'Kecamatan', // Contoh ambil data user
            'Nama Pendamping',
            'Nama Verifikator',
            'Status Terakhir',
            'Tanggal Verifikasi',
        ];
    }

    // Mapping Data Per Baris
    public function map($pengajuan): array
    {
        // Ambil data user dengan aman (null safety)
        $user = $pengajuan->user;

        return [
            $pengajuan->id,
            $pengajuan->auto_invoice_number, // Accessor invoice
            $pengajuan->created_at->translatedFormat('d F Y'), // Format tanggal Ind
            $user ? $user->name : '(User Terhapus)',
            $user ? $user->district->name : '-',
            $pengajuan->pendamping ? $pengajuan->pendamping->name : '-',
            $pengajuan->verificator ? $pengajuan->verificator->name : 'Belum Ada',
            $pengajuan->status_verifikasi,
            $pengajuan->verified_at ? $pengajuan->verified_at->translatedFormat('d F Y H:i') : '-',
        ];
    }
}

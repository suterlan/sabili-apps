<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\AnggotaResource;
use App\Filament\Resources\PengajuanResource;
use App\Models\Pengajuan;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 2;
    protected static ?string $pollingInterval = '15s';

    protected function getStats(): array
    {
        $user = Auth::user();

        // =====================================================================
        // 1. LOGIKA SUPER ADMIN (Global Overview)
        // =====================================================================
        if ($user->isSuperAdmin()) {
            return [
                // A. Antrian (Belum dipegang siapapun)
                Stat::make('Antrian Global', Pengajuan::whereNull('verificator_id')->count())
                    ->description('Menunggu verifikator')
                    ->descriptionIcon('heroicon-m-inbox-stack')
                    ->chart([10, 5, 20, 5, 30])
                    ->color('danger'),

                // B. Workload (Sedang dikerjakan: Revisi + Proses)
                Stat::make('Workload Admin', Pengajuan::whereNotNull('verificator_id')
                    ->whereNotIn('status_verifikasi', [
                        Pengajuan::STATUS_SERTIFIKAT, // Siap Invoice
                        Pengajuan::STATUS_INVOICE,    // Selesai
                        Pengajuan::STATUS_SELESAI     // Selesai
                    ])->count())
                    ->description('Sedang diverifikasi / Revisi')
                    ->descriptionIcon('heroicon-m-briefcase')
                    ->color('warning'),

                // C. Siap Invoice (Sertifikat Terbit) - POTENSI CUAN
                Stat::make('Siap Invoice', Pengajuan::where('status_verifikasi', Pengajuan::STATUS_SERTIFIKAT)->count())
                    ->description('Menunggu penerbitan tagihan')
                    ->descriptionIcon('heroicon-m-currency-dollar')
                    ->color('primary')
                    ->chart([2, 10, 5, 20]),

                // D. Selesai (Invoice + Lunas)
                Stat::make('Selesai / Tagihan', Pengajuan::whereIn('status_verifikasi', [
                    Pengajuan::STATUS_INVOICE,
                    Pengajuan::STATUS_SELESAI
                ])->count())
                    ->description('Output final')
                    ->descriptionIcon('heroicon-m-check-badge')
                    ->color('success'),
            ];
        }

        // =====================================================================
        // 2. LOGIKA ADMIN (Verifikator) - SESUAI TAB BARU
        // =====================================================================
        if ($user->isAdmin()) {
            // Query Dasar: Tugas Saya
            $myTasks = Pengajuan::where('verificator_id', $user->id);

            return [
                // TAB 1: ANTRIAN (Global, belum ada yg pegang)
                Stat::make('Antrian Masuk', Pengajuan::whereNull('verificator_id')->count())
                    ->description('Klik untuk klaim')
                    ->descriptionIcon('heroicon-m-inbox-arrow-down')
                    ->color('danger')
                    ->chart([7, 2, 10, 3, 15])
                    ->url(PengajuanResource::getUrl('index', ['activeTab' => 'antrian'])), // Link ke Tab Antrian

                // TAB 2: REVISI (Tugas Saya yg statusnya Revisi)
                Stat::make('Menunggu Revisi', (clone $myTasks)
                    ->whereIn('status_verifikasi', [
                        Pengajuan::STATUS_NIK_INVALID,
                        Pengajuan::STATUS_PENGAJUAN_DITOLAK,
                        Pengajuan::STATUS_UPLOAD_ULANG_FOTO,
                        Pengajuan::STATUS_UPLOAD_NIB,
                    ])->count())
                    ->description('Menunggu respon user')
                    ->descriptionIcon('heroicon-m-arrow-path')
                    ->color('warning')
                    ->url(PengajuanResource::getUrl('index', ['activeTab' => 'revisi'])), // Link ke Tab Revisi

                // TAB 3: PROSES (Tugas Saya yg sedang berjalan)
                Stat::make('Sedang Saya Proses', (clone $myTasks)
                    ->whereIn('status_verifikasi', [
                        Pengajuan::STATUS_MENUNGGU,
                        Pengajuan::STATUS_DIPROSES,
                        Pengajuan::STATUS_LOLOS_VERIFIKASI,
                        Pengajuan::STATUS_PENGAJUAN_DIKIRIM,
                    ])->count())
                    ->description('Verifikasi aktif')
                    ->descriptionIcon('heroicon-m-pencil-square')
                    ->color('info')
                    ->url(PengajuanResource::getUrl('index', ['activeTab' => 'proses'])), // Link ke Tab Proses

                // TAB 4: SIAP INVOICE (Sertifikat Terbit)
                Stat::make('Siap Invoice', (clone $myTasks) // Atau Global jika admin finance terpisah
                    ->where('status_verifikasi', Pengajuan::STATUS_SERTIFIKAT)
                    ->count())
                    ->description('Buat Tagihan Sekarang')
                    ->descriptionIcon('heroicon-m-banknotes')
                    ->color('success') // Hijau mencolok agar dikerjakan
                    ->chart([1, 1, 5, 2, 10])
                    ->url(PengajuanResource::getUrl('index', ['activeTab' => 'siap_invoice'])), // Link ke Tab Siap Invoice
            ];
        }

        // =====================================================================
        // 3. LOGIKA KOORDINATOR (Monitoring Wilayah)
        // =====================================================================
        if ($user->isKoordinator()) {
            $kodeKecamatan = $user->kecamatan;

            // Filter User berdasarkan Kecamatan
            $queryKecamatan = Pengajuan::whereHas('user', function (Builder $q) use ($kodeKecamatan) {
                $q->where('kecamatan', $kodeKecamatan);
            });

            return [
                Stat::make('Antrian Wilayah', (clone $queryKecamatan)->whereNull('verificator_id')->count())
                    ->description('Belum diklaim admin')
                    ->color('danger'),

                Stat::make('Total Diproses', (clone $queryKecamatan)
                    ->whereNotIn('status_verifikasi', [Pengajuan::STATUS_SELESAI, Pengajuan::STATUS_INVOICE])
                    ->whereNotNull('verificator_id')
                    ->count())
                    ->description('Sedang berjalan')
                    ->color('warning'),

                Stat::make('Selesai / Terbit', (clone $queryKecamatan)
                    ->whereIn('status_verifikasi', [Pengajuan::STATUS_INVOICE, Pengajuan::STATUS_SELESAI])
                    ->count())
                    ->description('Sukses')
                    ->color('success'),
            ];
        }

        // =====================================================================
        // 4. LOGIKA PENDAMPING (Monitoring Binaan)
        // =====================================================================
        if ($user->isPendamping()) {
            $myPengajuans = Pengajuan::where('pendamping_id', $user->id);

            return [
                // Kelompok 1: MASALAH (Revisi)
                Stat::make('Perlu Perbaikan', (clone $myPengajuans)
                    ->whereIn('status_verifikasi', [
                        Pengajuan::STATUS_NIK_INVALID,
                        Pengajuan::STATUS_UPLOAD_NIB,
                        Pengajuan::STATUS_UPLOAD_ULANG_FOTO,
                        Pengajuan::STATUS_PENGAJUAN_DITOLAK
                    ])->count())
                    ->description('Cek Tab Revisi')
                    ->color('danger')
                    ->icon('heroicon-m-exclamation-circle')
                    // Arahkan ke AnggotaResource, filter status revisi
                    ->url(AnggotaResource::getUrl('index')),

                // Kelompok 2: PROGRESS (Menunggu s/d Dikirim)
                Stat::make('Sedang Berjalan', (clone $myPengajuans)
                    ->whereIn('status_verifikasi', [
                        Pengajuan::STATUS_MENUNGGU,
                        Pengajuan::STATUS_DIPROSES,
                        Pengajuan::STATUS_LOLOS_VERIFIKASI,
                        Pengajuan::STATUS_PENGAJUAN_DIKIRIM
                    ])->count())
                    ->description('Menunggu Admin')
                    ->color('warning')
                    ->icon('heroicon-m-clock'),

                // Kelompok 3: SIAP INVOICE (Sertifikat Terbit)
                Stat::make('Sertifikat Terbit', (clone $myPengajuans)
                    ->where('status_verifikasi', Pengajuan::STATUS_SERTIFIKAT)
                    ->count())
                    ->description('Menunggu Invoice')
                    ->color('primary')
                    ->icon('heroicon-m-document-check'),

                // Kelompok 4: SELESAI (Tagihan Keluar / Lunas)
                Stat::make('Selesai & Tagihan', (clone $myPengajuans)
                    ->whereIn('status_verifikasi', [
                        Pengajuan::STATUS_INVOICE,
                        Pengajuan::STATUS_SELESAI
                    ])->count())
                    ->description('Proses Final')
                    ->color('success')
                    ->icon('heroicon-m-check-badge'),
            ];
        }

        return [];
    }
}

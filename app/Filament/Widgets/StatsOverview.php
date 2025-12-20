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
    // Agar widget ini update otomatis setiap 15 detik (opsional)
    protected static ?string $pollingInterval = '15s';

    protected function getStats(): array
    {
        $user = Auth::user();

        // ==========================================
        // LOGIKA ADMIN & SUPERADMIN
        // ==========================================
        if ($user->isAdmin() || $user->isSuperAdmin()) {
            return [
                // 1. Total Antrian (Belum ada verificator)
                Stat::make('Antrian Masuk', Pengajuan::whereNull('verificator_id')->count())
                    ->description('Menunggu untuk diklaim')
                    ->descriptionIcon('heroicon-m-inbox-arrow-down')
                    ->color('danger')
                    ->chart([7, 2, 10, 3, 15, 4, 17]) // Grafik dummy pemanis
                    // Arahkan ke Tab 'antrian' di PengajuanResource
                    ->url(PengajuanResource::getUrl('index', ['activeTab' => 'antrian'])),

                // 2. Sedang Diproses (Oleh Semua Admin)
                Stat::make('Dalam Proses Verifikasi', Pengajuan::whereNotNull('verificator_id')
                    ->where('status_verifikasi', '!=', Pengajuan::STATUS_SELESAI)
                    ->count())
                    ->description('Sedang dikerjakan tim')
                    ->descriptionIcon('heroicon-m-arrow-path')
                    ->color('warning'),

                // 3. Tugas Saya (Khusus Admin yang login)
                Stat::make('Tugas Saya', Pengajuan::where('verificator_id', $user->id)
                    ->where('status_verifikasi', '!=', Pengajuan::STATUS_SELESAI)
                    ->count())
                    ->description('Harus Anda selesaikan')
                    ->descriptionIcon('heroicon-m-user')
                    ->color('primary')
                    // Arahkan ke Tab 'tugas_saya'
                    ->url(PengajuanResource::getUrl('index', ['activeTab' => 'tugas_saya'])),
            ];
        }

        // ==========================================
        // 2. LOGIKA KOORDINATOR (BARU)
        // ==========================================
        if ($user->isKoordinator()) {
            $kodeKecamatan = $user->kecamatan;

            // Query Dasar: Pengajuan yang user-nya berasal dari kecamatan ini
            $queryKecamatan = Pengajuan::whereHas('user', function (Builder $query) use ($kodeKecamatan) {
                $query->where('kecamatan', $kodeKecamatan);
            });

            return [
                // A. Antrian Wilayah (Belum diklaim oleh siapapun, tapi berasal dari kecamatannya)
                Stat::make('Antrian Kecamatan', (clone $queryKecamatan)->whereNull('verificator_id')->count())
                    ->description('Menunggu diproses')
                    ->descriptionIcon('heroicon-m-inbox-stack')
                    ->color('danger')
                    ->chart([10, 5, 2, 8, 1, 15]),

                // B. Sedang Diproses (Di wilayahnya)
                Stat::make('Sedang Diproses', (clone $queryKecamatan)->where('status_verifikasi', Pengajuan::STATUS_DIPROSES)->count())
                    ->description('Sedang diverifikasi tim')
                    ->descriptionIcon('heroicon-m-arrow-path')
                    ->color('warning'),

                // C. Total Pelaku Usaha (Di wilayahnya)
                Stat::make('Total Pelaku Usaha', User::query()
                    ->where('role', 'pelaku_usaha') // Pastikan value role sesuai DB Anda
                    ->where('kecamatan', $kodeKecamatan)
                    ->count())
                    ->description('Terdaftar di wilayah ini')
                    ->descriptionIcon('heroicon-m-building-storefront')
                    ->color('primary'),
            ];
        }

        // ==========================================
        // LOGIKA PENDAMPING
        // ==========================================
        if ($user->isPendamping()) {
            // Ambil semua pengajuan milik pendamping ini
            $myPengajuans = Pengajuan::where('pendamping_id', $user->id);

            return [
                // 1. Total Binaan
                Stat::make('Total Binaan', $user->members()->count()) // Asumsi relasi members() ada di User model
                    ->description('Pelaku usaha terdaftar')
                    ->icon('heroicon-m-users')
                    ->color('primary')
                    // Klik masuk ke list anggota tanpa filter
                    ->url(AnggotaResource::getUrl('index')),

                // 2. Perlu Revisi / Tindakan (Penting!)
                Stat::make('Perlu Revisi', (clone $myPengajuans)
                    ->whereIn('status_verifikasi', [
                        Pengajuan::STATUS_NIK_INVALID,
                        Pengajuan::STATUS_UPLOAD_NIB,
                        Pengajuan::STATUS_UPLOAD_KK
                    ])->count())
                    ->description('Cek status Upload NIB/KK/Invalid')
                    ->descriptionIcon('heroicon-m-exclamation-triangle')
                    ->color('danger') // Merah biar eye-catching
                    // --- MAGIC LINK FILTER ---
                    // Ini akan otomatis mencentang filter di tabel Anggota
                    ->url(AnggotaResource::getUrl('index', [
                        'tableFilters' => [
                            'status_verifikasi' => [
                                'values' => [
                                    Pengajuan::STATUS_NIK_INVALID,
                                    Pengajuan::STATUS_UPLOAD_NIB,
                                    Pengajuan::STATUS_UPLOAD_KK
                                ]
                            ]
                        ]
                    ])),

                // 3. Selesai (Sertifikat Terbit)
                Stat::make('Sertifikat Terbit', (clone $myPengajuans)
                    ->where('status_verifikasi', Pengajuan::STATUS_SELESAI)
                    ->count())
                    ->description('Proses Verifikasi Selesai')
                    ->descriptionIcon('heroicon-m-check-badge')
                    ->color('success')
                    ->url(AnggotaResource::getUrl('index', [
                        'tableFilters' => [
                            'status_verifikasi' => [
                                'values' => [Pengajuan::STATUS_SELESAI]
                            ]
                        ]
                    ])),
            ];
        }

        return [];
    }
}

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
        // 1. LOGIKA SUPER ADMIN (HELICOPTER VIEW)
        // ==========================================
        // Super Admin melihat statistik global seluruh sistem
        if ($user->isSuperAdmin()) {
            return [
                // A. Total Backlog (Antrian yang belum dipegang siapapun)
                Stat::make('Total Antrian Sistem', Pengajuan::whereNull('verificator_id')->count())
                    ->description('Menunggu verifikator')
                    ->descriptionIcon('heroicon-m-inbox-stack')
                    ->chart([10, 5, 20, 5, 30]) // Dummy chart beban kerja
                    ->color('danger'),

                // B. Total Workload (Sedang dikerjakan oleh seluruh Admin)
                Stat::make('Sedang Diproses (Global)', Pengajuan::whereNotNull('verificator_id')
                    ->whereNotIn('status_verifikasi', [Pengajuan::STATUS_SELESAI, Pengajuan::STATUS_SERTIFIKAT])
                    ->count())
                    ->description('Active workload tim verifikator')
                    ->descriptionIcon('heroicon-m-briefcase')
                    ->chart([5, 10, 15, 10])
                    ->color('warning'),

                // C. Performance Output (Sertifikat Terbit)
                Stat::make('Total Sertifikat Terbit', Pengajuan::whereIn('status_verifikasi', [
                    Pengajuan::STATUS_SERTIFIKAT,
                    Pengajuan::STATUS_SELESAI
                ])->count())
                    ->description('Akumulasi kesuksesan')
                    ->descriptionIcon('heroicon-m-trophy')
                    ->chart([1, 5, 10, 20, 40])
                    ->color('success'),

                // D. Growth (Total Pelaku Usaha)
                Stat::make('Total Pelaku Usaha', User::where('role', 'member')->count()) // Sesuaikan value role di DB
                    ->description('User terdaftar')
                    ->descriptionIcon('heroicon-m-user-group')
                    ->color('primary')
                    ->url(AnggotaResource::getUrl('index')),
            ];
        }

        // ==========================================
        // LOGIKA ADMIN
        // ==========================================
        if ($user->isAdmin()) {
            return [
                // 1. Total Antrian (Belum ada verificator)
                Stat::make('Antrian Masuk', Pengajuan::whereNull('verificator_id')->count())
                    ->description('Menunggu untuk diklaim')
                    ->descriptionIcon('heroicon-m-inbox-arrow-down')
                    ->color('danger')
                    ->chart([7, 2, 10, 3, 15, 4, 17]) // Grafik dummy pemanis
                    // Arahkan ke Tab 'antrian' di PengajuanResource
                    ->url(PengajuanResource::getUrl('index', ['activeTab' => 'antrian'])),

                // 2. Tugas Saya (Sedang Dikerjakan) (Khusus Admin yang login)
                Stat::make('Tugas Saya (Aktif)', Pengajuan::where('verificator_id', $user->id)
                    ->where('status_verifikasi', '!=', Pengajuan::STATUS_SELESAI)
                    ->count())
                    ->description('Harus Anda selesaikan')
                    ->descriptionIcon('heroicon-m-user')
                    ->color('warning')
                    // Arahkan ke Tab 'tugas_saya'
                    ->url(PengajuanResource::getUrl('index', ['activeTab' => 'tugas_saya'])),

                // 3. [BARU] Tugas Selesai (History Saya)
                Stat::make('Tugas Selesai', Pengajuan::where('verificator_id', $user->id)
                    ->where('status_verifikasi', Pengajuan::STATUS_SELESAI)
                    ->count())
                    ->description('Total verifikasi berhasil Anda')
                    ->descriptionIcon('heroicon-m-clipboard-document-check')
                    ->color('success')
                    ->chart([2, 5, 10, 8, 15, 20]) // Chart dummy kenaikan kinerja
                    // Jika diklik, arahkan ke tab Semua Data dengan filter Status Selesai
                    ->url(PengajuanResource::getUrl('index', [
                        'activeTab' => 'semua',
                        // Opsional: Jika di Resource Anda ada Filter Status, ini akan otomatis ter-apply
                        'tableFilters' => [
                            'status_verifikasi' => [
                                'values' => [Pengajuan::STATUS_SELESAI]
                            ]
                        ]
                    ])),

                // 4. (Opsional) Total Sedang Diproses Tim Lain
                Stat::make('Diproses Tim Lain', Pengajuan::whereNotNull('verificator_id')
                    ->where('verificator_id', '!=', $user->id) // Bukan saya
                    ->where('status_verifikasi', '!=', Pengajuan::STATUS_SELESAI)
                    ->count())
                    ->description('Dikerjakan rekan admin lain')
                    ->color('gray'),

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

                // -------------------------------------------------------------
                // 2. PERLU REVISI (Prioritas Paling Tinggi untuk dilihat)
                // -------------------------------------------------------------
                Stat::make('Perlu Revisi', (clone $myPengajuans)
                    ->whereIn('status_verifikasi', [
                        Pengajuan::STATUS_NIK_INVALID,
                        Pengajuan::STATUS_UPLOAD_NIB,         // Konstanta baru (teks panjang)
                        Pengajuan::STATUS_UPLOAD_ULANG_FOTO,  // Konstanta baru
                        Pengajuan::STATUS_PENGAJUAN_DITOLAK
                    ])->count())
                    ->description('Cek status Upload NIB/KK/Invalid')
                    ->descriptionIcon('heroicon-m-exclamation-triangle')
                    ->icon('heroicon-m-exclamation-triangle')
                    ->color('danger') // Merah biar eye-catching
                    ->chart([5, 2, 5, 2])
                    // --- MAGIC LINK FILTER ---
                    // Ini akan otomatis mencentang filter di tabel Anggota
                    ->url(AnggotaResource::getUrl('index', ['tableFilters' => [
                        'status_verifikasi' => ['values' => [
                            Pengajuan::STATUS_NIK_INVALID,
                            Pengajuan::STATUS_UPLOAD_NIB,         // Konstanta baru (teks panjang)
                            Pengajuan::STATUS_UPLOAD_ULANG_FOTO,  // Konstanta baru
                            Pengajuan::STATUS_PENGAJUAN_DITOLAK
                        ]]
                    ]])),

                // -------------------------------------------------------------
                // 3. MENUNGGU & DIPROSES (Tahap Pengerjaan Admin)
                // -------------------------------------------------------------
                Stat::make('Dalam Pengerjaan', (clone $myPengajuans)
                    ->whereIn('status_verifikasi', [
                        Pengajuan::STATUS_MENUNGGU,
                        Pengajuan::STATUS_DIPROSES
                    ])
                    ->count())
                    ->description('Menunggu / Sedang Diverifikasi')
                    ->icon('heroicon-m-arrow-path')
                    ->color('warning') // Kuning
                    ->chart([10, 8, 5, 10]),

                // -------------------------------------------------------------
                // 4. LOLOS VERIFIKASI (Admin Selesai, Menunggu Sertifikat)
                // -------------------------------------------------------------
                Stat::make('Lolos Verifikasi & Dikirim', (clone $myPengajuans)
                    ->whereIn('status_verifikasi', [
                        Pengajuan::STATUS_LOLOS_VERIFIKASI,   // Konstanta baru
                        Pengajuan::STATUS_PENGAJUAN_DIKIRIM   // Konstanta baru
                    ])->count())
                    ->description('Menunggu penerbitan sertifikat')
                    ->icon('heroicon-m-paper-airplane')
                    ->color('info') // Biru Muda
                    ->chart([2, 4, 6, 8]),

                // -------------------------------------------------------------
                // 5. SERTIFIKAT TERBIT (Menunggu Invoice)
                // -------------------------------------------------------------
                Stat::make('Sertifikat Terbit', (clone $myPengajuans)
                    ->where('status_verifikasi', Pengajuan::STATUS_SERTIFIKAT)
                    ->count())
                    ->description('Menunggu tagihan/invoice')
                    ->icon('heroicon-m-document-check')
                    ->color('success') // Hijau
                    ->chart([5, 10, 8, 12]),

                // -------------------------------------------------------------
                // 6. TAHAP AKHIR (Invoice & Selesai)
                // -------------------------------------------------------------
                Stat::make('Selesai & Tagihan', (clone $myPengajuans)
                    ->whereIn('status_verifikasi', [
                        Pengajuan::STATUS_INVOICE,
                        Pengajuan::STATUS_SELESAI
                    ])->count())
                    ->description('Invoice keluar atau Selesai')
                    ->descriptionIcon('heroicon-m-check-badge')
                    ->icon('heroicon-m-check-badge')
                    ->color('success')
                    ->chart([2, 4, 8, 16, 20])
                    ->url(AnggotaResource::getUrl('index', [
                        'tableFilters' => [
                            'status_verifikasi' => [
                                'values' => [
                                    Pengajuan::STATUS_INVOICE,
                                    Pengajuan::STATUS_SELESAI
                                ]
                            ]
                        ]
                    ])),
            ];
        }

        return [];
    }
}

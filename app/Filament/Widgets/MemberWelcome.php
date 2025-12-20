<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class MemberWelcome extends BaseWidget
{
    // Atur urutan tampilan (paling atas)
    protected static ?int $sort = 1;

    // 'full' artinya mengambil lebar penuh layar
    protected int | string | array $columnSpan = 'full';

    protected function getColumns(): int
    {
        //paksa agar hanya 1 kolom
        return 1;
    }

    // --- TAMBAHKAN LOGIKA INI ---
    public static function canView(): bool
    {
        // Widget ini HANYA MUNCUL jika user adalah Pendamping
        // Admin & Superadmin akan return false (tidak lihat)
        return Auth::user()->isPendamping();
    }

    protected function getStats(): array
    {
        $user = Auth::user();

        // 1. Tentukan Warna & Pesan berdasarkan Status
        $status = $user->status ?? 'pending'; // Default pending jika null

        // Tentukan CSS Class berdasarkan status
        $customClass = match ($status) {
            'verified' => 'status-card-verified', // Kita buat class ini nanti
            'rejected' => 'status-card-rejected',
            default => 'status-card-pending',
        };

        $description = match ($status) {
            'verified' => 'Akun anda sudah aktif. Pastikan anda melengkapi profil agar bisa menambah pelaku usaha.',
            'rejected' => 'Akun Ditolak. Silakan hubungi Admin.',
            default => 'Menunggu persetujuan Admin.',
        };

        $icon = match ($status) {
            'verified' => 'heroicon-m-check-badge',
            'rejected' => 'heroicon-m-x-circle',
            default => 'heroicon-m-clock',
        };

        return [
            Stat::make('Status Akun', ucfirst($status))
                ->description($description)
                ->descriptionIcon($icon)
                // Masukkan Class Custom di sini
                ->extraAttributes([
                    'class' => $customClass . ' shadow-lg', // Tambah shadow biar cantik
                ]),
        ];
    }
}

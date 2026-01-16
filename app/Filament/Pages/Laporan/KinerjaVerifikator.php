<?php

namespace App\Filament\Pages\Laporan;

use Filament\Pages\Page;
use App\Filament\Widgets\StatsKinerjaVerifikator; // Import Widget

class KinerjaVerifikator extends Page
{
    protected static string $view = 'filament.pages.laporan.kinerja-verifikator';
    protected static ?string $navigationIcon = 'heroicon-o-chart-pie'; // Ikon Menu

    protected static ?string $navigationLabel = 'Kinerja Verifikator';
    protected static ?string $title = 'Rekapitulasi Kinerja';
    protected static ?string $slug = 'laporan/kinerja-verifikator';

    // Masukkan ke grup yang sama dengan Laporan Pengajuan
    protected static ?string $navigationGroup = 'Laporan';
    protected static ?int $navigationSort = 2; // Urutan menu (setelah Laporan Pengajuan)

    // Panggil Widget di sini (bisa di Header atau Footer, sama saja karena halamannya kosong)
    protected function getHeaderWidgets(): array
    {
        return [
            StatsKinerjaVerifikator::class,
        ];
    }

    // Hak Akses: Hanya Superadmin
    public static function canAccess(): bool
    {
        return auth()->user()->isSuperAdmin();
    }
}

<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class TopPendampingChart extends ChartWidget
{
    protected static ?string $heading = 'Top Pendamping (Jumlah Anggota)';
    protected static ?int $sort = 4; // Urutan tampilan

    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        // Ambil 5 Pendamping dengan jumlah anggota terbanyak
        $data = User::where('role', 'pendamping')
            ->withCount('anggotas') // Hitung relasi 'anggotas'
            ->orderByDesc('anggotas_count')
            ->limit(5)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Anggota Binaan',
                    'data' => $data->pluck('anggotas_count'),
                    'backgroundColor' => '#f59e0b', // Warna Amber
                ],
            ],
            'labels' => $data->pluck('name'),
        ];
    }

    protected function getType(): string
    {
        return 'bar'; // Bisa ganti 'line', 'pie', dll
    }

    // Hanya Tampil untuk Superadmin & Admin
    public static function canView(): bool
    {
        return Auth::user()->isSuperAdmin() || Auth::user()->isManajemen();
    }
}

<?php

namespace App\Filament\Widgets;

use App\Models\Pengajuan;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;          // <-- Class ini sekarang sudah ada
use Flowframe\Trend\TrendValue;     // <-- Class ini sekarang sudah ada
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PengajuanChart extends ChartWidget
{
    protected static ?int $sort = 3;
    protected static ?string $heading = 'Tren Pengajuan Masuk';
    protected int | string | array $columnSpan = 'full';
    // Agar grafik update otomatis setiap 15 detik (opsional)
    protected static ?string $pollingInterval = '15s';

    // =========================================================
    // 1. FILTER VISIBILITY (Hanya Admin & Koordinator)
    // =========================================================
    public static function canView(): bool
    {
        $user = auth()->user();

        // Tampilkan jika: Superadmin ATAU Admin ATAU Koordinator
        // Pendamping otomatis tidak akan melihat ini (return false)
        return $user->isSuperAdmin() || $user->isAdmin() || $user->isKoordinator();
    }

    // =========================================================
    // 2. LOGIC DATA (Admin = Global, Koordinator = Lokal)
    // =========================================================
    protected function getData(): array
    {
        $user = Auth::user();
        // 1. Buat Query Dasar
        $query = Pengajuan::query();

        // 2. Cek Role: Jika Koordinator, Filter per Kecamatan
        if ($user->isKoordinator()) {
            $kodeKecamatan = $user->kecamatan;

            // Filter hanya pengajuan dari user di kecamatan tersebut
            $query->whereHas('user', function (Builder $q) use ($kodeKecamatan) {
                $q->where('kecamatan', $kodeKecamatan);
            });
        }

        // Jika Admin/Superadmin, biarkan $query mengambil semua data (tanpa filter)
        // 3. Eksekusi menggunakan Trend Library
        $data = Trend::query($query)
            ->between(
                start: now()->subDays(30), // Data 30 hari terakhir
                end: now(),
            )
            ->perDay()
            ->count();

        // 4. Return format Chart.js
        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Pengajuan',
                    'data' => $data->map(fn(TrendValue $value) => $value->aggregate),
                    'borderColor' => '#3b82f6', // Warna Biru
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)', // Warna arsiran bawah
                    'fill' => true,
                    'tension' => 0.4, // Garis melengkung halus
                ],
            ],
            'labels' => $data->map(fn(TrendValue $value) => $value->date),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}

<?php

namespace App\Filament\Widgets;

use App\Models\Pengajuan;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PengajuanChart extends ChartWidget
{
    protected static ?int $sort = 3;

    // Kita buat judulnya dinamis agar admin tahu ini data wilayah siapa
    public function getHeading(): string
    {
        return 'Tren Pengajuan Masuk (30 Hari Terakhir)';
    }

    protected int | string | array $columnSpan = 'full';

    protected static ?string $pollingInterval = '15s';

    // =========================================================
    // 1. FILTER VISIBILITY
    // =========================================================
    public static function canView(): bool
    {
        $user = auth()->user();
        // Hanya Superadmin, Admin, dan Koordinator
        return $user->isSuperAdmin() || $user->isAdmin() || $user->isKoordinator();
    }

    // =========================================================
    // 2. LOGIC DATA (FILTER WILAYAH)
    // =========================================================
    protected function getData(): array
    {
        $user = Auth::user();

        // 1. Inisialisasi Query
        $query = Pengajuan::query();

        // 2. FILTER BERDASARKAN ROLE

        if ($user->isSuperAdmin()) {
            // A. SUPERADMIN: Tidak ada filter (Global)
            // Biarkan query kosong agar mengambil semua data
        } elseif ($user->isAdmin()) {
            // B. ADMIN: Filter berdasarkan Array assigned_districts
            if ($user->hasAssignedDistricts()) {
                $query->whereHas('user', function (Builder $q) use ($user) {
                    $q->whereIn('kecamatan', $user->assigned_districts);
                });
            } else {
                // Jika Admin belum punya wilayah tugas, grafik kosong
                $query->whereRaw('1 = 0');
            }
        } elseif ($user->isKoordinator()) {
            // C. KOORDINATOR: Filter berdasarkan 1 Kecamatan user
            $kodeKecamatan = $user->kecamatan;
            $query->whereHas('user', function (Builder $q) use ($kodeKecamatan) {
                $q->where('kecamatan', $kodeKecamatan);
            });
        }

        // 3. Eksekusi Trend (Sama untuk semua role, hanya query-nya yang beda isi)
        $data = Trend::query($query)
            ->between(
                start: now()->subDays(30),
                end: now(),
            )
            ->perDay()
            ->count();

        // 4. Return Data Chart
        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Pengajuan Baru',
                    'data' => $data->map(fn(TrendValue $value) => $value->aggregate),
                    'borderColor' => '#3b82f6', // Biru Filament
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)', // Arsiran transparan
                    'fill' => true,
                    'tension' => 0.4, // Curve halus
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

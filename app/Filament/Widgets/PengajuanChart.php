<?php

namespace App\Filament\Widgets;

use App\Models\Pengajuan;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Illuminate\Database\Eloquent\Builder;

class PengajuanChart extends ChartWidget
{
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $pollingInterval = '15s';
    public ?string $filter = 'month';

    public function getHeading(): string
    {
        return 'Tren Pengajuan Masuk';
    }

    protected function getFilters(): ?array
    {
        return [
            'week' => 'Minggu Ini',
            'month' => 'Bulan Ini',
            'year' => 'Tahun Ini',
        ];
    }

    public static function canView(): bool
    {
        $user = auth()->user();
        return $user->isSuperAdmin() || $user->isAdmin() || $user->isKoordinator();
    }

    protected function getData(): array
    {
        $user = auth()->user();
        $activeFilter = $this->filter;

        $startDate = match ($activeFilter) {
            'week' => now()->subDays(7),
            'month' => now()->subDays(30),
            'year' => now()->startOfYear(),
            default => now()->subDays(30),
        };
        $endDate = now();

        // 1. QUERY DASAR (Filter Wilayah/Role)
        $query = Pengajuan::query();

        if ($user->isSuperAdmin()) {
            // No filter
        } elseif ($user->isAdmin()) {
            // filter jika admin ditugaskan wilayah
            if ($user->hasAssignedDistricts()) {
                $query->whereHas('user', function (Builder $q) use ($user) {
                    $q->whereIn('kecamatan', $user->assigned_districts);
                });
            }
        } elseif ($user->isKoordinator()) {
            $kodeKecamatan = $user->kecamatan;
            $query->whereHas('user', function (Builder $q) use ($kodeKecamatan) {
                $q->where('kecamatan', $kodeKecamatan);
            });
        }

        // 2. AMBIL DATA TREND

        // A. Data Total (Semua Status)
        // Kita pakai clone() agar $query asli tidak berubah
        $dataTotal = Trend::query($query->clone())
            ->dateColumn('created_at') // <--- Explicit: Gunakan Tanggal Pembuatan
            ->between(start: $startDate, end: $endDate)
            ->perDay()
            ->count();

        // B. Data Khusus "Pengajuan Terkirim" (Status Dikirim)
        // Kita pakai clone() lagi lalu tambahkan where status
        $dataTerkirim = Trend::query($query->clone()->where('status_verifikasi', Pengajuan::STATUS_PENGAJUAN_DIKIRIM))
            ->dateColumn('verified_at') // date status diubah    
            ->between(start: $startDate, end: $endDate)
            ->perDay()
            ->count();

        // 3. RETURN MULTI DATASET
        return [
            'datasets' => [
                [
                    'label' => 'Total Masuk (Semua Status)',
                    'data' => $dataTotal->map(fn(TrendValue $value) => $value->aggregate),
                    'borderColor' => '#9ca3af', // Warna Abu-abu (Netral)
                    'backgroundColor' => 'rgba(156, 163, 175, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Status Pengajuan Terkirim',
                    'data' => $dataTerkirim->map(fn(TrendValue $value) => $value->aggregate),
                    'borderColor' => '#3b82f6', // Biru
                    'backgroundColor' => 'rgba(16, 185, 129, 0.2)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $dataTotal->map(fn(TrendValue $value) => $value->date),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}

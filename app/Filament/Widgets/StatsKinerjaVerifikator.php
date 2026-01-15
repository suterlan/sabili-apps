<?php

namespace App\Filament\Widgets;

use App\Models\Pengajuan;
use App\Models\User;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class StatsKinerjaVerifikator extends Widget
{
    protected static string $view = 'filament.widgets.stats-kinerja-verifikator';

    // Mengatur lebar widget agar penuh satu layar
    protected int | string | array $columnSpan = 'full';

    public function getViewData(): array
    {
        // 1. Ambil Semua Status yang mungkin ada (dari Model Pengajuan)
        // Pastikan Anda punya method getStatusVerifikasiOptions() di Model Pengajuan
        // Atau list manual array-nya disini
        $statuses = array_keys(Pengajuan::getStatusVerifikasiOptions());

        // 2. Query Agregat (Group by Verifikator & Status)
        // Hasil: [verificator_id, status, total]
        $rawStats = Pengajuan::query()
            ->select('verificator_id', 'status_verifikasi', DB::raw('count(*) as total'))
            ->groupBy('verificator_id', 'status_verifikasi')
            ->get();

        // 3. Ambil Data Verifikator (User dengan role admin/verifikator)
        $verificators = User::whereHas('pengajuansVerified')->get();

        // 4. Mapping Data untuk View
        // Kita buat struktur: $data[user_id][status] = total
        $matrix = [];
        foreach ($rawStats as $stat) {
            $verifId = $stat->verificator_id ?? 0; // 0 untuk yang belum diklaim
            $matrix[$verifId][$stat->status_verifikasi] = $stat->total;
        }

        return [
            'statuses' => $statuses,
            'verificators' => $verificators,
            'matrix' => $matrix,
            // Opsional: Hitung yang belum diklaim (verifikator_id = null)
            'unclaimed' => $matrix[0] ?? [],
        ];
    }
}

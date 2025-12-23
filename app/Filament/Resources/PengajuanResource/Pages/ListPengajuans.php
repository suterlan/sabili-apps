<?php

namespace App\Filament\Resources\PengajuanResource\Pages;

use App\Filament\Resources\PengajuanResource;
use App\Models\Pengajuan;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListPengajuans extends ListRecords
{
    protected static string $resource = PengajuanResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTabs(): array
    {
        $user = auth()->user();

        return [
            // TAB 1: ANTRIAN (Hanya Muncul 2 Teratas)
            'antrian' => Tab::make('Antrian Masuk')
                ->icon('heroicon-m-inbox-stack')
                // Badge tetap menghitung TOTAL antrian asli (misal: 100)
                ->badge(Pengajuan::whereNull('verificator_id')->count())
                ->modifyQueryUsing(function (Builder $query) {

                    // LANGKAH 1: Cari ID dari 5 data teratas (FIFO)
                    // Kita buat query terpisah (sub-query) agar tidak mengganggu paginator Filament
                    $antrianIds = Pengajuan::query()
                        ->whereNull('verificator_id')
                        ->orderBy('created_at', 'asc')
                        ->limit(5) // Batasi 5 di sini
                        ->pluck('id')
                        ->toArray();

                    // LANGKAH 2: Filter Query Utama Filament
                    // Jika data ada, filter whereIn ID. Jika kosong, biarkan query standar (hasil 0)
                    if (!empty($antrianIds)) {
                        return $query->whereIn('id', $antrianIds)
                            ->orderBy('created_at', 'asc');
                    }

                    // Jika tidak ada data, kembalikan query kosong
                    return $query->whereNull('verificator_id');
                }),

            // TAB 2: TUGAS SAYA (Hanya jika bukan Super Admin)
            'tugas_saya' => ! $user->isSuperAdmin()
                ? Tab::make('Tugas Saya')
                ->icon('heroicon-m-user')
                ->badge(Pengajuan::where('verificator_id', auth()->id())->count())
                ->modifyQueryUsing(
                    fn(Builder $query) =>
                    $query->where('verificator_id', $user->id)
                )
                : null, // Return null agar array filter membersihkannya nanti

            // TAB 3: SEMUA DATA
            'semua' => Tab::make('Semua Data')
                ->modifyQueryUsing(function (Builder $query) {
                    $user = auth()->user();

                    // 1. Jika Super Admin, tampilkan SEMUA (return query mentah)
                    if ($user->isSuperAdmin()) {
                        return $query;
                    }

                    // 2. Jika Admin Biasa, filter logic:
                    // (Verificator KOSONG) ATAU (Verificator adalah SAYA)
                    return $query->where(function (Builder $q) use ($user) {
                        $q->whereNull('verificator_id')
                            ->orWhere('verificator_id', $user->id);
                    });
                }),
        ];
    }

    // Opsional: Bersihkan nilai null dari array tabs (karena logika kondisional di atas)
    public function getTabsWithUrl(): array
    {
        return array_filter(parent::getTabs());
    }
}

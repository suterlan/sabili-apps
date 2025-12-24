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

        // PERBAIKAN: Gunakan array_filter() untuk membuang nilai null
        return array_filter([

            // TAB 1: ANTRIAN (Hanya Muncul 2 Teratas)
            'antrian' => Tab::make('Antrian Masuk')
                ->icon('heroicon-m-inbox-stack')
                ->badge(Pengajuan::whereNull('verificator_id')->count())
                ->modifyQueryUsing(function (Builder $query) {
                    // Logic ambil 5 ID teratas (FIFO)
                    $antrianIds = Pengajuan::query()
                        ->whereNull('verificator_id')
                        ->orderBy('created_at', 'asc')
                        ->limit(5)
                        ->pluck('id')
                        ->toArray();

                    if (!empty($antrianIds)) {
                        return $query->whereIn('id', $antrianIds)
                            ->orderBy('created_at', 'asc');
                    }

                    return $query->whereNull('verificator_id');
                }),

            // TAB 2: TUGAS SAYA (Hanya jika bukan Super Admin)
            // Jika Super Admin, ini akan return NULL. array_filter akan membuangnya.
            'tugas_saya' => ! $user->isSuperAdmin()
                ? Tab::make('Tugas Saya')
                ->icon('heroicon-m-user')
                ->badge(Pengajuan::where('verificator_id', auth()->id())->count())
                ->modifyQueryUsing(fn(Builder $query) => $query->where('verificator_id', $user->id))
                : null,

            // TAB 3: SEMUA DATA
            'semua' => Tab::make('Semua Data')
                ->modifyQueryUsing(function (Builder $query) {
                    $user = auth()->user();

                    if ($user->isSuperAdmin()) {
                        return $query;
                    }

                    return $query->where(function (Builder $q) use ($user) {
                        $q->whereNull('verificator_id')
                            ->orWhere('verificator_id', $user->id);
                    });
                }),
        ]);
    }
}

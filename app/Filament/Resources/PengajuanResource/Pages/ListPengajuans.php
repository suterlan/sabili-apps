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

        // 1. Inisialisasi array tabs dengan tab 'Antrian' (Tab dasar)
        $tabs = [
            'antrian' => Tab::make('Antrian Masuk')
                ->icon('heroicon-m-inbox-stack')
                ->badge(Pengajuan::whereNull('verificator_id')->count()) // Menghitung total antrian asli
                // 2. TAMBAHKAN 'Builder' SEBELUM VARIABEL $query
                ->modifyQueryUsing(
                    function (Builder $query) {
                        return $query
                            ->whereNull('verificator_id') // Hanya yang belum diklaim
                            ->orderBy('created_at', 'asc') // FIFO (Yang lama di atas)
                            ->take(5); // <--- KUNCINYA DI SINI (Hanya ambil 5)
                    }
                ),
        ];

        // 2. LOGIC KONDISIONAL:
        // Tab "Tugas Saya" HANYA dimasukkan jika user BUKAN Super Admin.
        // Asumsinya: Admin biasa butuh ini, Super Admin hanya memantau.
        if (! $user->isSuperAdmin()) {

            $tabs['tugas_saya'] = Tab::make('Tugas Saya')
                ->icon('heroicon-m-user')
                ->badge(Pengajuan::where('verificator_id', auth()->id())->count())
                // 3. LAKUKAN HAL YANG SAMA DI SINI
                ->modifyQueryUsing(
                    fn(Builder $query) => $query
                        ->where('verificator_id', $user->id)
                );
        }

        // 3. Tambahkan Tab 'Semua Data' di akhir
        $tabs['semua'] = Tab::make('Semua Data') // Opsional: Tab untuk melihat history
            ->modifyQueryUsing(fn($query) => $query);

        return $tabs;
    }
}

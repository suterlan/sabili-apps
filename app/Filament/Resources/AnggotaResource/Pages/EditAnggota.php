<?php

namespace App\Filament\Resources\AnggotaResource\Pages;

use App\Filament\Resources\AnggotaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Str;

class EditAnggota extends EditRecord
{
    protected static string $resource = AnggotaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    // --- TAMBAHKAN INI JUGA DI HALAMAN EDIT ---
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Jaga-jaga jika saat edit emailnya dikosongkan/dihapus
        if (empty($data['email'])) {
            $uniqueId = $data['nik'] ?? $data['phone'] ?? Str::random(10);
            $data['email'] = $uniqueId . '@sabili.local'; // Domain fiktif
        }

        return $data;
    }
}

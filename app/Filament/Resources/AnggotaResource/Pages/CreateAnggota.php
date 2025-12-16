<?php

namespace App\Filament\Resources\AnggotaResource\Pages;

use App\Filament\Resources\AnggotaResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateAnggota extends CreateRecord
{
    protected static string $resource = AnggotaResource::class;

    // --- TAMBAHKAN KODE INI ---
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // 1. Cek apakah email kosong?
        if (empty($data['email'])) {
            // 2. Jika kosong, buat email otomatis dari NIK atau No HP
            // Contoh: 320312345678@sabili.local
            $uniqueId = $data['nik'] ?? $data['phone'] ?? Str::random(10);
            $data['email'] = $uniqueId . '@sabili.local'; // Domain fiktif
        }

        return $data;
    }
}

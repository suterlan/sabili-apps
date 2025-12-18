<?php

namespace App\Filament\Resources\AnggotaResource\Pages;

use App\Filament\Resources\AnggotaResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateAnggota extends CreateRecord
{
    protected static string $resource = AnggotaResource::class;

    // =================================================================
    // 1. GATEKEEPER (PENJAGA PINTU)
    // Berjalan SAAT HALAMAN DIBUKA
    // =================================================================
    public function mount(): void
    {
        $user = auth()->user();

        // Cek: Pendamping & Profil Belum Lengkap
        if ($user->role === 'pendamping' && ! $user->isProfileComplete()) {

            Notification::make()
                ->danger()
                ->title('Akses Ditolak')
                ->body('Anda tidak diperbolehkan menambah anggota sebelum melengkapi profil.')
                ->send();

            $this->redirect($this->getResource()::getUrl('index'));
            return;
        }

        parent::mount();
    }

    // =================================================================
    // 2. DATA PROCESSOR (PENGOLAH DATA)
    // Berjalan SAAT TOMBOL CREATE DIKLIK
    // =================================================================
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Logika Email Otomatis Anda (Tetap aman disini)
        if (empty($data['email'])) {
            $uniqueId = $data['nik'] ?? $data['phone'] ?? Str::random(10);
            $data['email'] = $uniqueId . '@sabili.local';
        }

        return $data;
    }
}

<?php

namespace App\Filament\Resources\AnggotaResource\Pages;

use App\Filament\Resources\AnggotaResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
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

    // =================================================================
    //Ketika Pendamping membuat User baru, sistem otomatis membuatkan 1 record Pengajuan awal.
    // =================================================================
    protected function handleRecordCreation(array $data): Model
    {
        // 1. Buat User-nya dulu
        $user = static::getModel()::create($data);

        // 2. Otomatis buatkan tiket Pengajuan baru
        \App\Models\Pengajuan::create([
            'user_id' => $user->id,
            'pendamping_id' => auth()->id(), // Pendamping yg login
            'status_verifikasi' => 'Menunggu Verifikasi',
        ]);

        return $user;
    }

    // =================================================================
    // 4. UI CUSTOMIZATION (CUSTOM TOMBOL LOADING) - BARU DITAMBAHKAN
    // Mengatur tampilan tombol Simpan agar ada efek loading
    // =================================================================
    protected function getFormActions(): array
    {
        return [];
    }

    // Opsional: Redirect ke halaman list setelah sukses
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

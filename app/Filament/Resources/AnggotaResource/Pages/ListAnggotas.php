<?php

namespace App\Filament\Resources\AnggotaResource\Pages;

use App\Filament\Resources\AnggotaResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListAnggotas extends ListRecords
{
    protected static string $resource = AnggotaResource::class;

    protected function getHeaderActions(): array
    {
        // Ambil user yang sedang login
        $user = auth()->user();

        // Cek apakah dia pendamping & apakah profilnya BELUM lengkap
        $isProfileIncomplete = $user->isPendamping() && ! $user->isProfileComplete();

        // SKENARIO 1: PROFIL BELUM LENGKAP
        // Kita return Action Biasa (bukan CreateAction) yang icon-nya gembok
        if ($isProfileIncomplete) {
            return [
                Actions\Action::make('create_blocked') // Nama bebas, asal unik
                    ->label('Tambah Anggota') // Samakan labelnya
                    ->icon('heroicon-m-lock-closed') // Icon gembok
                    ->color('gray') // Warna abu-abu
                    ->tooltip('Lengkapi Profil & Dokumen Anda untuk membuka akses ini.')
                    ->action(function () {
                        // Logic saat diklik: Hanya muncul notifikasi
                        Notification::make()
                            ->warning()
                            ->title('Akses Dibatasi')
                            ->body('Mohon lengkapi Data Diri, Wilayah, Bank, dan Dokumen Anda di menu Profil sebelum menambah anggota.')
                            ->actions([
                                \Filament\Notifications\Actions\Action::make('GoToProfile')
                                    ->label('Lengkapi Sekarang')
                                    ->button()
                                    ->url(filament()->getProfileUrl())
                            ])
                            ->send();
                    }),
            ];
        }

        return [
            Actions\CreateAction::make(),
        ];
    }
}

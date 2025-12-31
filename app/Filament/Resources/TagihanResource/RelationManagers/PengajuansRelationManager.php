<?php

namespace App\Filament\Resources\TagihanResource\RelationManagers;

use App\Models\Pengajuan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Infolist;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists; // Tambahkan ini
use Filament\Notifications\Notification;

class PengajuansRelationManager extends RelationManager
{
    protected static string $relationship = 'pengajuans';

    // Judul Tab di halaman Edit Invoice
    protected static ?string $title = 'Rincian Pelaku Usaha';

    // Form ini bisa dikosongkan/disederhanakan karena kita tidak pakai Create/Edit disini
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('user.name')
                    ->disabled(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Nama Pelaku Usaha')
                    ->description(fn($record) => 'Merk Dagang: ' . $record->user->merk_dagang ?? '-')
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.nik')
                    ->label('NIK'),

                Tables\Columns\TextColumn::make('status_verifikasi')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        Pengajuan::STATUS_INVOICE,
                        Pengajuan::STATUS_SELESAI => 'success',
                        default => 'primary',
                    }),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Biasanya tidak perlu create pengajuan dari sini, karena alurnya dari depan
                // Tapi kalau butuh menambahkan pengajuan ke invoice ini secara manual:
                // Tables\Actions\AssociateAction::make(), 
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Detail'),

                Tables\Actions\DissociateAction::make()
                    ->label('Keluarkan dari Invoice') // Label lebih jelas daripada "Hapus"
                    ->icon('heroicon-m-arrow-right-start-on-rectangle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Keluarkan Pelaku Usaha?')
                    ->modalDescription('Pelaku usaha ini akan dilepas dari invoice ini. Statusnya akan dikembalikan menjadi "Sertifikat Diterbitkan" agar bisa diproses ulang.')

                    // LOGIC PENTING DISINI:
                    ->after(function (Pengajuan $record) {
                        // Kembalikan status mundur ke tahap sebelumnya
                        $record->update([
                            'status_verifikasi' => Pengajuan::STATUS_SERTIFIKAT
                        ]);

                        // Opsional: Kirim notifikasi
                        Notification::make()
                            ->title('Berhasil Dikeluarkan')
                            ->body("Data {$record->user->name} telah dilepas dari invoice ini dan status dikembalikan.")
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([

                    Tables\Actions\DissociateBulkAction::make()
                        ->label('Keluarkan Terpilih')
                        ->icon('heroicon-m-arrow-right-start-on-rectangle')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Keluarkan Data Terpilih?')
                        ->modalDescription('Semua pelaku usaha yang dipilih akan dilepas dari invoice ini dan statusnya dikembalikan ke "Sertifikat Diterbitkan".')

                        // LOGIKA PENTING DISINI:
                        ->after(function (\Illuminate\Database\Eloquent\Collection $records) {
                            // Loop semua record yang dipilih, update statusnya mundur
                            $records->each->update([
                                'status_verifikasi' => Pengajuan::STATUS_SERTIFIKAT
                            ]);

                            // Opsional: Notifikasi
                            Notification::make()
                                ->title('Update Massal Berhasil')
                                ->body($records->count() . ' data telah dikembalikan statusnya.')
                                ->success()
                                ->send();
                        }),

                ]),
            ]);
    }

    // INI YANG MEMBUAT TOMBOL VIEW BERISI DATA
    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Data Pelaku Usaha')
                    ->columns(2)
                    ->schema([
                        Infolists\Components\TextEntry::make('user.name')
                            ->label('Nama Lengkap')
                            ->weight('bold'),

                        Infolists\Components\TextEntry::make('user.nik')
                            ->label('NIK'),

                        Infolists\Components\TextEntry::make('user.merk_dagang')
                            ->label('Merk Dagang')
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('Status Pengajuan')
                    ->schema([
                        Infolists\Components\TextEntry::make('status_verifikasi')
                            ->badge()
                            ->color(fn(string $state): string => match ($state) {
                                Pengajuan::STATUS_INVOICE,
                                Pengajuan::STATUS_SELESAI => 'success',
                                default => 'primary',
                            }),

                        Infolists\Components\TextEntry::make('pendamping.name')
                            ->label('Pendamping')
                            ->icon('heroicon-m-user'),
                    ])->columns(2),
            ]);
    }
}

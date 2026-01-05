<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SertifikatResource\Pages;
use App\Models\Pengajuan;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class SertifikatResource extends Resource
{
    protected static ?string $model = Pengajuan::class;

    protected static ?string $navigationLabel = 'Sertifikat';
    protected static ?string $modelLabel = 'Sertifikat Halal';
    protected static ?string $navigationIcon = 'heroicon-o-document-arrow-down';
    protected static ?int $navigationSort = 10;

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        return parent::getEloquentQuery()
            // 1. Filter Wajib: Status Sertifikat & File Ada
            ->where('status_verifikasi', Pengajuan::STATUS_SERTIFIKAT)
            ->whereNotNull('file_sertifikat')

            // 2. Filter Hak Akses
            ->where(function (Builder $query) use ($user) {
                // A. Jika Super Admin -> Bebas lihat semua
                if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
                    return $query;
                }

                // B. Jika User Biasa (Pendamping ATAU Verifikator)
                // Kita bungkus dalam closure lagi agar logic OR terisolasi
                return $query->where(function ($subQuery) use ($user) {
                    $subQuery
                        ->where('pendamping_id', $user->id)    // Hak Akses Pendamping
                        ->orWhere('verificator_id', $user->id); // Hak Akses Admin (Verifikator)
                });
            })
            ->latest('updated_at');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Pelaku Usaha')
                    ->description(fn(Pengajuan $record) => $record->user->merk_dagang ?? '-')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Tanggal Terbit')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status_verifikasi')
                    ->label('Status')
                    ->badge()
                    ->color('success')
                    ->formatStateUsing(fn() => 'Siap Download'),
            ])
            ->actions([
                Tables\Actions\Action::make('download')
                    ->label('Unduh PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('primary')
                    ->button()
                    ->url(fn(Pengajuan $record) => Storage::url($record->file_sertifikat))
                    ->openUrlInNewTab(),
            ])
            ->checkIfRecordIsSelectableUsing(fn() => false);
    }

    // ... getPages, canCreate, canDelete (return false) sama seperti sebelumnya ...
    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageSertifikats::route('/'),
        ];
    }
    public static function canCreate(): bool
    {
        return false;
    }
    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }
    public static function canDeleteAny(): bool
    {
        return false;
    }
}

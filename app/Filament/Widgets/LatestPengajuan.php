<?php

namespace App\Filament\Widgets;

use App\Models\Pengajuan;
use App\Filament\Resources\PengajuanResource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class LatestPengajuan extends BaseWidget
{
    // Judul Widget
    protected static ?string $heading = 'Antrian Pengajuan Terbaru';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user->isSuperAdmin() || $user->isAdmin();
    }

    // 2. QUERY UTAMA DENGAN FILTER WILAYAH
    protected function getTableQuery(): Builder
    {
        $user = auth()->user();

        $query = Pengajuan::query()
            // Filter Dasar: Status Menunggu & Belum diklaim siapapun
            ->where('status_verifikasi', Pengajuan::STATUS_MENUNGGU)
            ->whereNull('verificator_id');

        // LOGIC FILTER WILAYAH
        // Jika user adalah SUPERADMIN, dia melihat semua (bypass filter ini)
        // Jika user adalah ADMIN BIASA, terapkan filter kecamatan
        if (! $user->isSuperAdmin()) {

            if ($user->hasAssignedDistricts()) {
                // Filter Pengajuan dimana User (Pelaku Usaha)-nya
                // memiliki kecamatan yang ada di daftar tugas admin ini
                $query->whereHas('user', function (Builder $q) use ($user) {
                    $q->whereIn('kecamatan', $user->assigned_districts);
                });
            }
        }

        return $query
            ->latest('created_at')
            ->limit(5);
    }

    protected function getTableColumns(): array
    {
        return [
            // Kolom 1: Pelaku Usaha
            Tables\Columns\TextColumn::make('user.name')
                ->label('Pelaku Usaha')
                ->description(fn(Pengajuan $record) => $record->user->merk_dagang ?? '-')
                ->weight('bold'),

            // Kolom 2: Pendamping
            Tables\Columns\TextColumn::make('pendamping.name')
                ->label('Pendamping')
                ->icon('heroicon-m-user-group')
                ->color('gray'),

            // Kolom 3: Waktu (Since)
            Tables\Columns\TextColumn::make('created_at')
                ->label('Masuk Antrian')
                ->since()
                ->sortable()
                ->color('secondary')
                ->size(Tables\Columns\TextColumn\TextColumnSize::ExtraSmall)
                ->alignEnd(),
        ];
    }

    // Aksi Klik: Langsung ke Edit untuk Klaim
    protected function getTableRecordUrlUsing(): ?\Closure
    {
        return function () {
            // Arahkan ke URL Index Resource, lalu tambahkan query string activeTab
            // Pastikan key 'antrian' sesuai dengan key array di getTabs() pada ListPengajuans
            return PengajuanResource::getUrl('index') . '?activeTab=antrian';
        };
    }

    protected function isTablePaginationEnabled(): bool
    {
        return false;
    }
}

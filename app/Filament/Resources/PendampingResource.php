<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PendampingResource\Pages;
use App\Models\User;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PendampingResource extends Resource
{
    protected static ?string $model = User::class;

    // Bedakan Label dan Slug agar tidak bentrok dengan UserResource biasa
    protected static ?string $navigationLabel = 'Pendamping';
    protected static ?string $modelLabel = 'Monitoring Pendamping';
    protected static ?string $slug = 'monitoring-pendamping';
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?int $navigationSort = 2; // Urutan menu

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Pendamping')
                    ->searchable()
                    ->sortable(),

                // Menampilkan Wilayah (Format Kode ke Nama)
                Tables\Columns\TextColumn::make('kecamatan')
                    ->label('Wilayah Kecamatan')
                    ->formatStateUsing(
                        fn($state) =>
                        \Laravolt\Indonesia\Models\District::where('code', $state)->first()?->name ?? '-'
                    )
                    ->sortable(),

                // --- INI FITUR UTAMANYA: MENGHITUNG RELASI ---
                Tables\Columns\TextColumn::make('anggota_binaan_count')
                    ->counts('anggotaBinaan') // Menghitung jumlah data dari relasi anggotaBinaan
                    ->label('Total Binaan')
                    ->badge()
                    ->color(fn($state) => $state > 0 ? 'success' : 'danger') // Merah jika 0, Hijau jika ada
                    ->sortable(),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Kontak'),
            ])
            ->defaultSort('anggota_binaan_count', 'desc') // Urutkan dari yang terbanyak
            ->actions([
                // Kita hilangkan tombol Edit/Delete agar menu ini murni untuk monitoring
                // Jika ingin melihat detail, bisa tambahkan ViewAction
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        // 1. FILTER WAJIB: HANYA TAMPILKAN ROLE PENDAMPING
        $query->where('role', 'pendamping');

        // 2. FILTER UNTUK KOORDINATOR
        // Koordinator hanya melihat Pendamping di Kecamatan dia
        if ($user->isKoordinator()) {
            $query->where('kecamatan', $user->kecamatan);
        }

        return $query;
    }

    // --- MANAJEMEN HAK AKSES ---

    public static function canViewAny(): bool
    {
        // Menu ini bisa dilihat oleh: Superadmin, Admin, Koordinator
        return Auth::user()->isSuperAdmin()
            || Auth::user()->isAdmin()
            || Auth::user()->isKoordinator();
    }

    // Matikan fitur Create (Input pendamping tetap dari menu User biasa)
    public static function canCreate(): bool
    {
        return false;
    }

    // Matikan fitur Edit (Edit data pendamping dari menu User biasa)
    // Hapus method ini jika Anda ingin bisa edit dari sini juga
    // public static function canEdit($record): bool
    // {
    //    return false;
    // }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPendampings::route('/'),
        ];
    }
}

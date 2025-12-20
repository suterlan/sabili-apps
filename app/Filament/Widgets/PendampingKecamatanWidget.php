<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class PendampingKecamatanWidget extends BaseWidget
{
    protected static ?string $heading = 'Kinerja Pendamping (Wilayah Anda)';
    protected static ?int $sort = 3;
    // full width
    protected int | string | array $columnSpan = 'full';

    // 3. LOGIC VISIBILITY: Hanya tampil untuk Koordinator
    public static function canView(): bool
    {
        // Pastikan user login & memiliki role koordinator
        // Menggunakan helper isKoordinator() yang ada di model User Anda
        return auth()->user() && auth()->user()->isKoordinator();
    }

    public function table(Table $table): Table
    {
        $kodeKecamatan = auth()->user()->kecamatan;


        return $table
            ->query(
                User::query()
                    // 1. Filter Role Pendamping
                    ->where('role', 'pendamping')
                    // 2. Filter Pendamping yang berdomisili/bertugas di kecamatan Koordinator
                    ->where('kecamatan', $kodeKecamatan)
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Pendamping')
                    ->weight('bold')
                    ->searchable(),

                // Hitung berapa Pelaku Usaha yang didampingi dia (Total)
                Tables\Columns\TextColumn::make('anggotas_count')
                    ->label('Total Binaan')
                    ->counts('anggotas') // Menggunakan relasi hasMany 'anggotas' di User Model
                    ->badge()
                    ->color('info'),

                // Hitung berapa pengajuan dari binaannya yang SUDAH SELESAI
                Tables\Columns\TextColumn::make('sertifikat_terbit')
                    ->label('Sertifikat Terbit')
                    ->getStateUsing(function ($record) {
                        // $record adalah user Pendamping
                        // Kita cari semua pengajuan milik anggota-anggotanya
                        return \App\Models\Pengajuan::whereHas('user', function ($q) use ($record) {
                            $q->where('pendamping_id', $record->id);
                        })->where('status_verifikasi', 'Selesai')->count();
                    })
                    ->badge()
                    ->color('success'),
            ])
            ->paginated(false);
    }
}

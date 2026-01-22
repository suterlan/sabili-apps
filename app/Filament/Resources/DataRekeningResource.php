<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DataRekeningResource\Pages;
use App\Filament\Resources\DataRekeningResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use pxlrbt\FilamentExcel\Columns\Column;

class DataRekeningResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    // Grouping menu di sidebar
    protected static ?string $navigationGroup = 'Keuangan';
    protected static ?string $navigationLabel = 'Data Rekening';

    protected static ?string $pluralModelLabel = 'Data Rekening';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // 1. Nama Pendamping
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Pendamping')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                // 2. Nama Bank
                Tables\Columns\TextColumn::make('nama_bank') // Sesuaikan dengan kolom DB Anda (nama_bank / bank)
                    ->label('Bank')
                    ->badge() // Biar cantik dikasih warna
                    ->color(fn(string $state): string => match ($state) {
                        'BCA' => 'info',
                        'BRI' => 'primary',
                        'Mandiri' => 'warning',
                        'BNI' => 'success',
                        default => 'gray',
                    })
                    ->searchable(),

                // 3. Nomor Rekening
                Tables\Columns\TextColumn::make('nomor_rekening')
                    ->label('No. Rekening')
                    ->copyable() // Agar admin keuangan mudah copy
                    ->searchable(),

                // 4. Akun LinkAja
                Tables\Columns\TextColumn::make('akun_linkaja')
                    ->label('LinkAja')
                    ->icon('heroicon-m-device-phone-mobile')
                    ->placeholder('-')
                    ->copyable()
                    ->searchable(),
            ])
            ->filters([
                // Filter berdasarkan Bank (Opsional)
                Tables\Filters\SelectFilter::make('nama_bank')
                    ->label('Filter Bank')
                    ->options([
                        'BCA' => 'BCA',
                        'BRI' => 'BRI',
                        'Mandiri' => 'Mandiri',
                        'BNI' => 'BNI',
                        'BSI' => 'BSI'
                    ]),
            ])
            ->actions([
                // Tidak perlu aksi edit/delete jika hanya untuk view
                // Tables\Actions\EditAction::make(), 
            ])
            ->headerActions([
                // === FITUR EXPORT EXCEL ===
                ExportAction::make()
                    ->label('Export Excel')
                    ->color('success')
                    ->exports([
                        ExcelExport::make('table')->fromTable()
                            ->withFilename('Data_Rekening_Pendamping_' . date('Y-m-d'))
                            ->withColumns([
                                Column::make('name')->heading('Nama Pendamping'),
                                Column::make('nama_bank')->heading('Bank'),
                                Column::make('nomor_rekening')->heading('Nomor Rekening'),
                                Column::make('akun_linkaja')->heading('Nomor LinkAja'),
                            ]),
                    ]),
            ])
            ->bulkActions([
                // Export Bulk (Jika ingin export yang dicentang saja)
                Tables\Actions\BulkActionGroup::make([
                    \pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction::make(),
                ]),
            ])
            ->recordUrl(null) // Matikan klik baris (agar tidak dikira bisa diedit)
            ->striped()
            ->defaultSort('nama', 'asc');
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDataRekenings::route('/'),
        ];
    }

    // Matikan fitur Create (Karena data ini dari pendaftaran)
    public static function canCreate(): bool
    {
        return false;
    }
}

<?php

namespace App\Filament\Pages\Laporan;

use App\Models\Pengajuan;
use App\Models\User; // Import User
use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter; // Import SelectFilter
use Filament\Tables\Enums\FiltersLayout;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Illuminate\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use pxlrbt\FilamentExcel\Columns\Column;

class LaporanPengajuan extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static string $view = 'filament.pages.laporan.laporan-pengajuan';
    protected static ?string $navigationGroup = 'Laporan';
    protected static ?string $title = 'Laporan Pengajuan';
    protected ?string $subheading = 'Laporan harian dan monitoring kinerja verifikator.';

    public function table(Table $table): Table
    {
        return $table
            ->query(Pengajuan::query()->latest('verified_at')) // Urutkan dari yang terbaru
            ->columns([
                Tables\Columns\TextColumn::make('verified_at')
                    ->label('Tgl. Verifikasi')
                    ->date('d/m/Y H:i') // Tambah jam agar lebih detail
                    ->sortable()
                    ->placeholder('Belum Verifikasi'),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Pelaku Usaha')
                    ->searchable()
                    ->description(fn(Pengajuan $record) => $record->user->merk_dagang), // Merk dagang jadi deskripsi kecil di bawah nama

                Tables\Columns\TextColumn::make('pendamping.name')
                    ->label('Pendamping')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true), // Opsional disembunyikan agar tidak penuh

                // 1. TAMBAHAN KOLOM VERIFIKATOR
                Tables\Columns\TextColumn::make('verificator.name')
                    ->label('Verifikator (Admin)')
                    ->icon('heroicon-m-user-circle')
                    ->color('primary')
                    ->placeholder('Belum Diklaim')
                    ->searchable(),

                Tables\Columns\TextColumn::make('status_verifikasi')
                    ->label('Status')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        // Merah (Error/Masalah) perlu revisi
                        Pengajuan::STATUS_NIK_INVALID,
                        Pengajuan::STATUS_UPLOAD_NIB,
                        Pengajuan::STATUS_UPLOAD_ULANG_FOTO,
                        Pengajuan::STATUS_PENGAJUAN_DITOLAK => 'danger',

                        // Kuning (Dalam proses)
                        Pengajuan::STATUS_MENUNGGU,
                        Pengajuan::STATUS_DIPROSES => 'warning',
                        // Biru (Masih dalam proses tapi selesai diverifikasi, menunggu sertifikat)
                        Pengajuan::STATUS_LOLOS_VERIFIKASI,
                        Pengajuan::STATUS_PENGAJUAN_DIKIRIM => 'info',

                        // Hijau (Berhasil, menunggu invoice)
                        Pengajuan::STATUS_SERTIFIKAT,
                        // invoice/tagihan muncul
                        Pengajuan::STATUS_INVOICE,
                        // semua proses selesai
                        Pengajuan::STATUS_SELESAI => 'success',

                        default => 'primary',
                    })
                    ->sortable(),
            ])
            // ---------------------------------------------------------------
            // FILTER STATUS SESUAI CONSTANTA MODEL
            // ---------------------------------------------------------------
            ->filters([
                // A. Filter Status (Mengambil langsung dari Model)
                SelectFilter::make('status_verifikasi')
                    ->label('Filter Status')
                    ->options(Pengajuan::getStatusVerifikasiOptions()) // <-- INI YANG DIUBAH
                    ->multiple()
                    ->searchable(), // Tambahkan search karena opsi statusnya lumayan banyak

                // B. Filter Verifikator
                SelectFilter::make('verificator_id')
                    ->label('Filter Admin / Verifikator')
                    ->relationship(
                        name: 'verificator',
                        titleAttribute: 'name',
                        // Tambahkan logika filter query di sini:
                        modifyQueryUsing: fn(Builder $query) => $query->where('role', 'admin')
                    )
                    ->searchable()
                    ->preload(),

                // C. Filter Tanggal (Custom)
                Filter::make('periode')
                    ->form([
                        Grid::make(2)
                            ->schema([
                                DatePicker::make('verified_from')
                                    ->label('Dari Tanggal')
                                    ->default(now()->startOfDay()) // Default hari ini (Solusi Laporan Harian)
                                    ->native(false)
                                    ->displayFormat('d M Y')
                                    ->closeOnDateSelection()
                                    ->prefixIcon('heroicon-m-calendar-days')
                                    ->maxDate(now()),

                                DatePicker::make('verified_until')
                                    ->label('Sampai Tanggal')
                                    ->default(now()->endOfDay()) // Default hari ini
                                    ->native(false)
                                    ->displayFormat('d M Y')
                                    ->closeOnDateSelection()
                                    ->prefixIcon('heroicon-m-calendar-days')
                                    ->maxDate(now()),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['verified_from'],
                                fn(Builder $query, $date): Builder => $query->where(
                                    'verified_at',
                                    '>=',
                                    // Ubah string tanggal menjadi object Carbon jam 00:00:00
                                    Carbon::parse($date)->startOfDay()
                                ),
                            )
                            ->when(
                                $data['verified_until'],
                                fn(Builder $query, $date): Builder => $query->where(
                                    'verified_at',
                                    '<=',
                                    // Ubah string tanggal menjadi object Carbon jam 23:59:59
                                    Carbon::parse($date)->endOfDay()
                                ),
                            );
                    })
                    // Memberi info di atas tabel filter apa yang aktif
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['verified_from'] ?? null) {
                            $indicators['verified_from'] = 'Dari: ' . Carbon::parse($data['verified_from'])->format('d M Y');
                        }

                        if ($data['verified_until'] ?? null) {
                            $indicators['verified_until'] = 'Sampai: ' . Carbon::parse($data['verified_until'])->format('d M Y');
                        }

                        return $indicators;
                    }),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filtersFormWidth('full') // Agar muat banyak filter berjejer

            ->headerActions([
                ExportAction::make()
                    ->label('Export Excel')
                    ->color('success')
                    ->icon('heroicon-m-arrow-down-tray')
                    ->exports([
                        ExcelExport::make()
                            // =======================================================
                            // Paksa Export menggunakan query tabel yang sudah difilter
                            // =======================================================
                            ->modifyQueryUsing(fn($query) => $this->getFilteredTableQuery())
                            ->withColumns([
                                Column::make('verified_at')->heading('Tgl. Verifikasi'),
                                Column::make('user.name')->heading('Pelaku Usaha'),
                                Column::make('user.merk_dagang')->heading('Merk Dagang'),
                                Column::make('pendamping.name')->heading('Pendamping'),
                                Column::make('verificator.name')->heading('Verifikator'), // Tambah di Excel
                                Column::make('status_verifikasi')->heading('Status'),
                            ])
                            // Filename dinamis agar file tidak tertukar
                            ->withFilename(fn($resource) => 'laporan-pengajuan-' . date('Y-m-d-H-i'))
                    ]),
            ]);
    }

    public static function canAccess(): bool
    {
        return auth()->user()->isSuperAdmin();
    }
}

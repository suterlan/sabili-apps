<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TagihanResource\Pages;
use App\Filament\Resources\TagihanResource\RelationManagers;
use App\Models\Pengajuan;
use App\Models\Tagihan;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use ZipArchive;

class TagihanResource extends Resource
{
    protected static ?string $model = Tagihan::class;

    // Ganti ikon agar sesuai konteks keuangan
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    // Grouping menu di sidebar
    protected static ?string $navigationGroup = 'Keuangan';

    protected static ?int $navigationSort = -1;
    protected static ?string $pluralModelLabel = 'Tagihan';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nomor_invoice')
                    ->required()
                    ->maxLength(255)
                    ->disabled(),

                Forms\Components\DatePicker::make('tanggal_terbit')
                    ->required(),

                Forms\Components\TextInput::make('total_nominal')
                    ->label('Nominal Satuan (Per Orang)')
                    ->prefix('Rp')
                    ->numeric()
                    ->disabled()
                    ->helperText('Ini adalah nominal per PU.'),

                Forms\Components\TextInput::make('link_pembayaran')
                    ->columnSpanFull()
                    ->url()
                    ->suffixIcon('heroicon-m-link'),

                Forms\Components\Select::make('status_pembayaran')
                    ->options([
                        'BELUM DIBAYAR' => 'Belum Dibayar',
                        'DIBAYAR' => 'Lunas',
                    ])
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // 1. NOMOR INVOICE
                Tables\Columns\TextColumn::make('nomor_invoice')
                    ->searchable()
                    ->weight(FontWeight::Bold)
                    ->copyable(),

                // 2. JUMLAH ITEM (Berapa orang dalam 1 invoice)
                Tables\Columns\TextColumn::make('pengajuans_count')
                    ->counts('pengajuans')
                    ->label('Jml. Pengajuan')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn($state) => $state . ' Orang'),

                // 3. TOTAL NOMINAL (Kalkulasi: Satuan x Jumlah Orang)
                Tables\Columns\TextColumn::make('grand_total')
                    ->label('Total Tagihan')
                    ->money('IDR')
                    ->sortable(query: function (Builder $query, string $direction) {
                        // Custom sort logic jika perlu, atau gunakan sort by total_nominal biasa
                        return $query->orderBy('total_nominal', $direction);
                    })
                    ->getStateUsing(function (Tagihan $record) {
                        // Menghitung Unit Price * Jumlah Relasi
                        return $record->total_nominal * $record->pengajuans()->count();
                    })
                    ->weight(FontWeight::Bold),

                // 4. STATUS
                Tables\Columns\TextColumn::make('status_pembayaran')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'BELUM DIBAYAR', 'UNPAID' => 'danger',
                        'DIBAYAR', 'PAID' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('tanggal_terbit')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status_pembayaran')
                    ->options([
                        'BELUM DIBAYAR' => 'Belum Dibayar',
                        'DIBAYAR' => 'Sudah Lunas',
                    ]),
            ])
            ->actions([
                // TOMBOL CEPAT: SET LUNAS
                Action::make('set_lunas')
                    ->label('Konfirmasi Lunas')
                    ->icon('heroicon-m-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Konfirmasi Pembayaran')
                    ->modalDescription('Apakah Anda yakin invoice ini sudah dibayar? Status semua pengajuan terkait akan diubah menjadi SELESAI.')
                    ->action(function (Tagihan $record) {
                        // 1. Update Status Invoice
                        $record->update(['status_pembayaran' => 'DIBAYAR']);

                        // 2. Update Status SEMUA Pengajuan di dalamnya menjadi SELESAI
                        $record->pengajuans()->update([
                            'status_verifikasi' => Pengajuan::STATUS_SELESAI
                        ]);

                        Notification::make()
                            ->title('Pembayaran Dikonfirmasi')
                            ->body('Invoice lunas dan status pengajuan telah diperbarui.')
                            ->success()
                            ->send();
                    })
                    // Sembunyikan tombol jika sudah dibayar
                    ->visible(fn(Tagihan $record) => $record->status_pembayaran !== 'DIBAYAR'),

                Tables\Actions\EditAction::make()->icon('heroicon-m-list-bullet')->label('Rincian'),

                Tables\Actions\Action::make('pdf')
                    ->label('Download PDF')
                    ->icon('heroicon-m-arrow-down-tray')
                    ->color('primary')
                    ->action(function (Tagihan $record) {
                        // Load data relasi agar tidak query berulang di view
                        $record->load(['pengajuans.user']);

                        // Generate PDF
                        $pdf = Pdf::loadView('pdf.invoice', ['tagihan' => $record]);

                        // Download dengan nama file custom
                        return response()->streamDownload(function () use ($pdf) {
                            echo $pdf->output();
                        }, 'Invoice-' . $record->nomor_invoice . '.pdf');
                    }),
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make(),
                // ]),

                Tables\Actions\BulkActionGroup::make([
                    // 2. BULK DOWNLOAD ZIP (BARU)
                    Tables\Actions\BulkAction::make('download_zip')
                        ->label('Download PDF (ZIP)')
                        ->icon('heroicon-o-archive-box-arrow-down')
                        ->color('primary')
                        ->action(function (Collection $records) {
                            // Nama file ZIP sementara
                            $zipFileName = 'Invoices-' . date('Y-m-d-His') . '.zip';
                            $zipPath = storage_path('app/public/' . $zipFileName);

                            $zip = new ZipArchive;

                            if ($zip->open($zipPath, ZipArchive::CREATE) === TRUE) {

                                foreach ($records as $record) {
                                    // Load relasi agar query ringan
                                    $record->load(['pengajuans.user']);

                                    // Render PDF ke string (binary)
                                    $pdfContent = Pdf::loadView('pdf.invoice', ['tagihan' => $record])->output();

                                    // Nama file di dalam ZIP
                                    // Sanitasi nama file agar aman
                                    $safeInvoiceNum = str_replace('/', '-', $record->nomor_invoice);
                                    $fileName = "Invoice-{$safeInvoiceNum}.pdf";

                                    // Masukkan ke ZIP
                                    $zip->addFromString($fileName, $pdfContent);
                                }

                                $zip->close();
                            }

                            // Return download response
                            return response()->download($zipPath)->deleteFileAfterSend(true);
                        })
                        ->deselectRecordsAfterCompletion(),
                ])
                    ->label('Download Invoice')
                    ->icon('heroicon-m-arrow-down-tray')
                    ->color('info'),
            ]);
    }
    public static function getRelations(): array
    {
        return [
            // Kita akan buat ini di langkah selanjutnya
            RelationManagers\PengajuansRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTagihans::route('/'),
            'edit' => Pages\EditTagihan::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        // Menu ini bisa dilihat oleh: Superadmin, Admin
        return Auth::user()->isSuperAdmin()
            || Auth::user()->isAdmin();
    }

    public static function getEloquentQuery(): Builder
    {
        // 1. Ambil query standar
        $query = parent::getEloquentQuery();
    
    // 2. Ambil user yang login
        /** @var \App\Models\User $user */
        $user = auth()->user();

        // 3. Jika Super Admin, tampilkan semua (bypass filter)
        if ($user->isSuperAdmin()) {
            return $query;
        }

        // 4. Jika Admin Biasa, filter data
        // Tampilkan Tagihan DIMANA tagihan tersebut MEMILIKI relasi pengajuans
        // YANG kolom 'verificator_id'-nya adalah user yang sedang login.
        return $query->whereHas('pengajuans', function (Builder $q) use ($user) {
            $q->where('verificator_id', $user->id);
        });
    }
}

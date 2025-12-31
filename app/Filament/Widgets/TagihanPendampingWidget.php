<?php

namespace App\Filament\Widgets;

use App\Models\Tagihan;
use Filament\Tables\Actions\Action;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Columns\TextColumn;
use Filament\Support\Enums\FontWeight;

class TagihanPendampingWidget extends BaseWidget
{
    // Mengatur agar widget ini tampil agak lebar (opsional)
    protected int | string | array $columnSpan = 'full';

    // Urutan tampilan di dashboard
    protected static ?int $sort = 3;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                // FILTER LOGIC:
                // 1. Ambil data Tagihan
                // 2. Filter hanya milik Pendamping yang sedang login
                // 3. Prioritaskan yang BELUM DIBAYAR di urutan atas
                Tagihan::query()
                    ->with(['pengajuan.user']) // Eager load biar ringan
                    ->withCount('pengajuans')
                    ->where('pendamping_id', Auth::id())
            )
            ->heading('Tagihan Binaan (Invoice Aktif)')
            ->defaultSort('created_at', 'desc')
            ->columns([
                // 1. TANGGAL TERBIT
                TextColumn::make('tanggal_terbit')
                    ->date('d M Y')
                    ->sortable(),

                // 2. NOMOR INVOICE
                TextColumn::make('nomor_invoice')
                    ->label('No. Invoice')
                    ->weight(FontWeight::Bold)
                    ->copyable()
                    ->searchable(),

                // 3. JUMLAH PENGAJUAN (Pengganti Kolom Nama)
                TextColumn::make('pengajuans_count')
                    ->label('Keterangan')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn($state) => $state . ' Pelaku Usaha'),

                // 4. TOTAL TAGIHAN (SUDAH DIKALIKAN)
                TextColumn::make('total_nominal')
                    ->label('Total Tagihan')
                    ->weight(FontWeight::Bold)
                    // LOGIKA BARU: Nominal DB (Satuan) * Jumlah Orang
                    ->formatStateUsing(function ($state, Tagihan $record) {
                        $grandTotal = $state * $record->pengajuans_count;
                        return 'Rp ' . number_format($grandTotal, 0, ',', '.');
                    })
                    ->sortable(),

                // 5. STATUS
                TextColumn::make('status_pembayaran')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'BELUM DIBAYAR', 'UNPAID' => 'danger',
                        'DIBAYAR', 'PAID' => 'success',
                        default => 'gray',
                    }),

                // 6. LINK PEMBAYARAN (FITUR UTAMA)
                TextColumn::make('link_pembayaran')
                    ->label('Link Bayar')
                    ->icon('heroicon-m-link')
                    ->formatStateUsing(fn($state) => $state ? 'Buka Link' : 'Belum ada')
                    ->url(fn(Tagihan $record) => $record->link_pembayaran)
                    ->openUrlInNewTab()
                    ->copyable() // Biar pendamping bisa copy dan kirim ke WA
                    ->copyMessage('Link pembayaran disalin')
                    ->copyableState(fn(Tagihan $record) => $record->link_pembayaran)
                    ->color('primary')
                    ->weight(FontWeight::Bold),
            ])
            // Filter cepat di atas tabel widget
            ->filters([
                Tables\Filters\SelectFilter::make('status_pembayaran')
                    ->options([
                        'BELUM DIBAYAR' => 'Belum Dibayar',
                        'DIBAYAR' => 'Sudah Lunas',
                    ])
                    ->default('BELUM DIBAYAR'), // Default tampilkan yang belum bayar saja biar fokus
            ])
            ->actions([
                // ACTION BARU: Tombol "Lihat Detail" untuk melihat siapa saja yg ada di invoice ini
                Action::make('rincian')
                    ->label('Lihat rincian')
                    ->icon('heroicon-m-list-bullet')
                    ->color('gray')
                    ->modalHeading('Rincian Invoice')
                    ->modalSubmitAction(false) // Hilangkan tombol simpan
                    ->modalContent(function (Tagihan $record) {
                        // Kita render list manual sederhana di dalam modal
                        return view('filament.components.modal-list-pengajuan', [
                            'tagihan' => $record,
                            'items' => $record->pengajuans()->with('user')->get(),
                            'count'   => $record->pengajuans_count // Bawa jumlah orang
                        ]);
                    })
                    // Tampilkan tombol ini HANYA jika isinya lebih dari 1
                    ->visible(fn(Tagihan $record) => $record->pengajuans_count > 1),
            ]);
    }

    // Logic agar Widget ini HANYA MUNCUL untuk Pendamping
    public static function canView(): bool
    {
        return Auth::user()->isPendamping();
    }
}

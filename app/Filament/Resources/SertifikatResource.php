<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SertifikatResource\Pages;
use App\Models\Pengajuan;
use App\Models\Tagihan;
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
            ->whereIn('status_verifikasi', [Pengajuan::STATUS_SERTIFIKAT, Pengajuan::STATUS_INVOICE, Pengajuan::STATUS_SELESAI])
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
                    ->color(fn($state) => match ($state) {
                        // Biru (Berhasil, menunggu invoice)
                        Pengajuan::STATUS_SERTIFIKAT => 'info',
                        // invoice/tagihan muncul
                        Pengajuan::STATUS_INVOICE,
                        // semua proses selesai
                        Pengajuan::STATUS_SELESAI => 'success',

                        default => 'primary',
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('download')
                    ->label('Unduh PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->button()
                    ->openUrlInNewTab()

                    // 1. LOGIKA WARNA:
                    // Jika Lunas = Primary (Biru), Jika Belum = Gray (Abu-abu seolah disabled)
                    ->color(function (Pengajuan $record) {
                        $statusBayar = $record->tagihan?->status_pembayaran;
                        // Jika Superadmin, selalu aktif (Primary)
                        if (auth()->user()->isSuperAdmin()) return 'primary';

                        return $statusBayar === Tagihan::STATUS_DIBAYAR ? 'primary' : 'gray';
                    })

                    // 2. LOGIKA URL:
                    // Jika Lunas = Link File, Jika Belum = NULL (Tidak ada link)
                    ->url(function (Pengajuan $record) {
                        $statusBayar = $record->tagihan?->status_pembayaran;
                        // Jika Superadmin, selalu dapat link
                        if (auth()->user()->isSuperAdmin()) {
                            return Storage::url($record->file_sertifikat);
                        }

                        return $statusBayar === Tagihan::STATUS_DIBAYAR
                            ? Storage::url($record->file_sertifikat)
                            : null; // Link mati
                    })

                    // 3. LOGIKA AKSI (BACKUP):
                    // Jika user memaksa klik tombol abu-abu, kita kasih notifikasi (Opsional tapi bagus untuk UX)
                    ->action(function (Pengajuan $record) {
                        $statusBayar = $record->tagihan?->status_pembayaran;

                        // Jika belum lunas dan bukan superadmin
                        if ($statusBayar !== Tagihan::STATUS_DIBAYAR && !auth()->user()->isSuperAdmin()) {
                            \Filament\Notifications\Notification::make()
                                ->warning()
                                ->title('Akses Dibatasi')
                                ->body('Invoice belum lunas. Silakan selesaikan pembayaran invoice terlebih dahulu.')
                                ->send();
                        }
                    })

                    // 4. LOGIKA TOOLTIP:
                    // Sekarang pasti muncul karena tombol tidak benar-benar disabled secara HTML
                    ->tooltip(function (Pengajuan $record) {
                        if (auth()->user()->isSuperAdmin()) return 'Unduh Sertifikat';

                        $statusBayar = $record->tagihan?->status_pembayaran;

                        if ($statusBayar !== Tagihan::STATUS_DIBAYAR) {
                            return 'Belum dapat diunduh. Silakan selesaikan pembayaran untuk mengunduh.';
                        }

                        return 'Siap Unduh';
                    }),
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

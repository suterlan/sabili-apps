<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PengajuanResource\Pages;
use App\Filament\Resources\PengajuanResource\RelationManagers;
use App\Models\Pengajuan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Filament\Infolists\Components\Group as InfolistGroup;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\Grid as InfolistGrid;
use Filament\Infolists\Components\Actions as InfolistActions;
use Filament\Infolists\Components\Actions\Action;
use Filament\Infolists\Components\ImageEntry;
use Illuminate\Support\Facades\Storage;

class PengajuanResource extends Resource
{
    protected static ?string $model = Pengajuan::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Pengajuan';
    protected static ?string $modelLabel = 'Progres Pengajuan';
    protected static ?string $pluralModelLabel = 'Progres Pengajuan';
    protected static ?string $slug = 'pengajuan';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            // Auto refresh setiap 5 detik agar jika ada antrian baru masuk/diklaim orang lain, tabel update
            ->poll('5s')

            // Agar admin tahu ini pengajuan punya siapa
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Pelaku Usaha')
                    ->searchable(),

                Tables\Columns\TextColumn::make('pendamping.name')
                    ->label('Pendamping')
                    ->color('gray'),

                Tables\Columns\TextColumn::make('status_verifikasi')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        // Merah (Error/Masalah)
                        Pengajuan::STATUS_NIK_INVALID,
                        Pengajuan::STATUS_UPLOAD_NIB,
                        Pengajuan::STATUS_UPLOAD_ULANG_FOTO,
                        Pengajuan::STATUS_PENGAJUAN_DITOLAK => 'danger',

                        // Kuning (Butuh Tindakan User)
                        Pengajuan::STATUS_MENUNGGU,
                        Pengajuan::STATUS_DIPROSES => 'warning',

                        // Biru (Proses Admin)
                        Pengajuan::STATUS_LOLOS_VERIFIKASI,
                        Pengajuan::STATUS_PENGAJUAN_DIKIRIM => 'info',

                        // Hijau (Berhasil)
                        Pengajuan::STATUS_SERTIFIKAT,
                        Pengajuan::STATUS_INVOICE,
                        Pengajuan::STATUS_SELESAI => 'success',

                        default => 'primary',
                    })
                    ->sortable(),

                // Kolom Verifikator (Siapa yang sedang mengerjakan)
                Tables\Columns\TextColumn::make('verificator.name')
                    ->label('Verifikator')
                    ->placeholder('Belum Diklaim')
                    ->icon('heroicon-m-user'),
            ])
            ->recordUrl(null) // Matikan fungsi klik baris
            ->actions([
                // LOGIC KLAIM TUGAS (Sama seperti sebelumnya, tapi sekarang update model Pengajuan)
                Tables\Actions\Action::make('claim_task')
                    ->label('Proses')
                    ->icon('heroicon-m-hand-raised')
                    ->color('primary')
                    ->visible(function (Pengajuan $record, $livewire) {
                        // Syarat 1: Belum ada verifikator
                        $belumDiklaim = is_null($record->verificator_id);

                        // Syarat 2: BUKAN di tab 'semua'
                        // Kita cek apakah $livewire punya properti activeTab, lalu cek isinya
                        $bukanTabHistory = isset($livewire->activeTab) && $livewire->activeTab !== 'semua';

                        return $belumDiklaim && $bukanTabHistory;
                    })
                    ->action(function (Pengajuan $record) {
                        // 1. Update Data
                        $record->update([
                            'verificator_id' => auth()->id(),
                            'status_verifikasi' => Pengajuan::STATUS_DIPROSES,
                        ]);

                        // 2. Kirim Notifikasi
                        Notification::make()
                            ->success() // Warna Hijau
                            ->title('Tugas Berhasil Diklaim')
                            ->body('Pengajuan ini telah masuk ke daftar "Tugas Saya".')
                            ->send();
                    }),

                // Action Detail pada Tabel
                \Filament\Tables\Actions\ViewAction::make()
                    ->label('Detail & Foto')
                    ->slideOver()
                    ->color('info')

                    ->visible(function (Pengajuan $record) {
                        // Syarat 1: User login adalah verifikatornya
                        $isMyTask = auth()->id() === $record->verificator_id || auth()->user()->isSuperAdmin();
                        return $isMyTask;
                    }),

                // LOGIC UPDATE STATUS
                Tables\Actions\Action::make('update_status')
                    ->label('Verifikasi')
                    ->icon('heroicon-m-clipboard-document-check')
                    ->color('warning')
                    ->slideOver() // 1. Ubah jadi SlideOver agar tidak menumpuk/overflow
                    ->stickyModalHeader() // Header slideover selalu terlihat
                    ->stickyModalFooter() // Footer slideover selalu terlihat
                    ->modalWidth('2xl') // Sesuaikan lebar slideover

                    ->visible(function (Pengajuan $record, $livewire) {
                        // Syarat 1: User login adalah verifikatornya
                        $isMyTask = auth()->id() === $record->verificator_id;

                        // Syarat 2: BUKAN di tab 'semua'
                        $bukanTabHistory = isset($livewire->activeTab) && $livewire->activeTab !== 'semua';

                        return $isMyTask && $bukanTabHistory;
                    })

                    ->form([
                        // --- BAGIAN 3: FORM INPUT VERIFIKASI ---
                        Section::make('Keputusan Verifikasi')
                            ->schema([
                                // Tampilkan nama user sekilas agar tidak salah orang
                                Placeholder::make('info_user')
                                    ->label('Pelaku Usaha')
                                    ->content(fn($record) => $record->user->name . ' (' . $record->user->merk_dagang . ')'),

                                Select::make('status_verifikasi')
                                    ->label('Status Baru')
                                    ->options(Pengajuan::getStatusVerifikasiOptions())
                                    ->required()
                                    ->native(false)
                                    ->live(),

                                Textarea::make('catatan_revisi')
                                    ->label('Catatan / Alasan Penolakan')
                                    ->placeholder('Contoh: Foto NIB buram, mohon upload ulang.')
                                    ->rows(3)
                                    ->required(fn($get) => in_array($get('status_verifikasi'), [
                                        Pengajuan::STATUS_NIK_INVALID,
                                        Pengajuan::STATUS_PENGAJUAN_DITOLAK,
                                        Pengajuan::STATUS_UPLOAD_ULANG_FOTO,
                                        Pengajuan::STATUS_UPLOAD_NIB
                                    ])), // Wajib isi catatan jika statusnya Revisi/Tolak
                            ])
                    ])
                    ->action(function (Pengajuan $record, array $data) {
                        // 1. Update Data
                        $record->update($data);

                        // 2. Kirim Notifikasi
                        Notification::make()
                            ->success()
                            ->title('Status Diperbarui')
                            ->body("Status berhasil diubah menjadi: {$data['status_verifikasi']}")
                            ->send();
                    }),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    // --- MANAJEMEN HAK AKSES ---
    public static function canViewAny(): bool
    {
        // Menu ini bisa dilihat oleh: Superadmin, Admin
        return Auth::user()->isSuperAdmin()
            || Auth::user()->isAdmin();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPengajuans::route('/'),
            'edit' => Pages\EditPengajuan::route('/{record}/edit'),
        ];
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([

                // =========================================================
                // BAGIAN 1: DATA TEKS LENGKAP
                // =========================================================
                \Filament\Infolists\Components\Group::make([

                    // KIRI: DATA PRIBADI
                    \Filament\Infolists\Components\Section::make('Data Pribadi')
                        ->icon('heroicon-o-user')
                        ->schema([
                            \Filament\Infolists\Components\TextEntry::make('user.name')
                                ->label('Nama Lengkap')
                                ->weight('bold')
                                ->size(\Filament\Infolists\Components\TextEntry\TextEntrySize::Large),

                            \Filament\Infolists\Components\TextEntry::make('user.nik')
                                ->label('NIK')
                                ->copyable()
                                ->icon('heroicon-m-identification'),

                            \Filament\Infolists\Components\TextEntry::make('user.tanggal_lahir')
                                ->label('Tanggal Lahir')
                                ->date('d F Y'),

                            \Filament\Infolists\Components\TextEntry::make('user.phone')
                                ->label('No. WhatsApp')
                                ->url(fn($state) => 'https://wa.me/' . preg_replace('/^0/', '62', $state), true)
                                ->color('success')
                                ->icon('heroicon-m-phone'),
                        ])->columnSpan(1),

                    // KANAN: ALAMAT
                    \Filament\Infolists\Components\Section::make('Alamat & Lokasi')
                        ->icon('heroicon-o-map-pin')
                        ->schema([
                            \Filament\Infolists\Components\TextEntry::make('user.address')
                                ->label('Alamat')
                                ->columnSpanFull(),
                            \Filament\Infolists\Components\TextEntry::make('user.province.name')->label('Provinsi'),
                            \Filament\Infolists\Components\TextEntry::make('user.city.name')->label('Kab/Kota'),
                            \Filament\Infolists\Components\TextEntry::make('user.district.name')->label('Kecamatan'),
                            \Filament\Infolists\Components\TextEntry::make('user.village.name')->label('Desa/Kel'),
                        ])->columnSpan(1),

                    // BAWAH: LEGALITAS
                    \Filament\Infolists\Components\Section::make('Legalitas & Usaha')
                        ->icon('heroicon-o-briefcase')
                        ->schema([
                            \Filament\Infolists\Components\TextEntry::make('user.merk_dagang')
                                ->label('Merk Dagang')
                                ->badge()
                                ->color('info'),

                            \Filament\Infolists\Components\TextEntry::make('user.nomor_nib')
                                ->label('Nomor NIB')
                                ->copyable(),

                            \Filament\Infolists\Components\TextEntry::make('user.mitra_halal')
                                ->label('Mitra Halal')
                                ->badge()
                                ->color(fn(string $state): string => match ($state) {
                                    'YA' => 'success',
                                    default => 'gray',
                                }),

                            \Filament\Infolists\Components\TextEntry::make('user.pendamping.name')
                                ->label('Pendamping')
                                ->placeholder('-')
                                ->icon('heroicon-m-user-group'),
                        ])
                        ->columns(4)
                        ->columnSpanFull(),

                    // --- [BARU] INFORMASI AKUN SIHALAL PENDAMPING ---
                    \Filament\Infolists\Components\Section::make('Akun SiHalal Pendamping')
                        ->icon('heroicon-o-key')
                        ->schema([
                            // Pastikan ganti 'sihalal_username' sesuai nama kolom di database Anda
                            \Filament\Infolists\Components\TextEntry::make('sihalal_username_view')
                                ->label('Username / Email')
                                ->icon('heroicon-m-at-symbol')

                                ->state(function ($record) {
                                    // Ambil data secara manual agar string terbaca jelas oleh clipboard
                                    return $record->user->pendamping?->akun_halal;
                                })

                                ->copyable() // Bisa dicopy
                                ->copyMessage('Username disalin')
                                ->fontFamily(\Filament\Support\Enums\FontFamily::Mono) // Font seperti koding agar jelas
                                ->placeholder('Belum diatur'),

                            // Pastikan ganti 'sihalal_password' sesuai nama kolom di database Anda
                            \Filament\Infolists\Components\TextEntry::make('sihalal_password_view')
                                ->label('Password')
                                ->icon('heroicon-m-lock-closed')
                                ->state(function ($record) {
                                    // Ambil data secara manual agar string terbaca jelas oleh clipboard
                                    return $record->user->pendamping?->pass_akun_halal;
                                })
                                ->copyable() // Bisa dicopy
                                ->copyMessage('Password disalin')
                                ->fontFamily(\Filament\Support\Enums\FontFamily::Mono)
                                ->placeholder('Belum diatur')
                                ->color('danger'),
                        ])
                        ->columns(2)
                        ->columnSpanFull()
                        // Hanya muncul jika User punya pendamping
                        ->visible(fn($record) => $record->user->pendamping_id !== null),

                ])
                    ->columns(2)
                    ->columnSpanFull(),

                // =========================================================
                // BAGIAN 2: DOKUMEN FOTO (CARD STYLE & DOWNLOAD FIX)
                // =========================================================
                \Filament\Infolists\Components\Section::make('Dokumentasi Foto')
                    ->description('Preview foto dokumentasi usaha.')
                    ->schema([
                        \Filament\Infolists\Components\Grid::make([
                            'default' => 1,
                            'md' => 2,
                        ])->schema([

                            // --- KARTU 1: FOTO PRODUK ---
                            \Filament\Infolists\Components\Group::make([
                                \Filament\Infolists\Components\ImageEntry::make('user.file_foto_produk')
                                    ->hiddenLabel()
                                    ->state(function ($record) {
                                        $path = $record->user->file_foto_produk;

                                        // Jika file tidak ada, kembalikan null
                                        if (!$path) return null;

                                        // Return URL ke route proxy yang kita buat di langkah 1
                                        return route('drive.image', ['path' => $path]);
                                    })
                                    ->height(250)
                                    ->width('100%')
                                    ->extraImgAttributes(['class' => 'object-cover w-full h-full rounded-t-lg', 'loading' => 'lazy']),

                                // Menggunakan Actions Container
                                \Filament\Infolists\Components\Actions::make([
                                    // PENTING: Gunakan Namespace Action Infolist
                                    \Filament\Infolists\Components\Actions\Action::make('download_produk')
                                        ->label('Download Foto Produk')
                                        ->icon('heroicon-m-arrow-down-tray')
                                        ->color('primary')
                                        // Logic Download: Mengarahkan ke URL file asli di storage
                                        ->action(function ($record) {
                                            // Ini akan memicu download langsung di browser user
                                            return Storage::disk('google')->download($record->user->file_foto_produk);
                                        }),
                                ])->fullWidth(),
                            ])
                                ->extraAttributes(['class' => 'bg-white border border-gray-200 rounded-lg shadow-sm flex flex-col']),

                            // --- KARTU 2: FOTO BERSAMA ---
                            \Filament\Infolists\Components\Group::make([
                                \Filament\Infolists\Components\ImageEntry::make('user.file_foto_bersama')
                                    ->hiddenLabel()
                                    ->state(function ($record) {
                                        $path = $record->user->file_foto_bersama;

                                        // Jika file tidak ada, kembalikan null
                                        if (!$path) return null;

                                        // Return URL ke route proxy yang kita buat di langkah 1
                                        return route('drive.image', ['path' => $path]);
                                    })
                                    ->height(250)->width('100%')
                                    ->extraImgAttributes(['class' => 'object-cover w-full h-full rounded-t-lg']),

                                \Filament\Infolists\Components\Actions::make([
                                    \Filament\Infolists\Components\Actions\Action::make('download_bersama')
                                        ->label('Download Foto Bersama')
                                        ->icon('heroicon-m-arrow-down-tray')
                                        ->color('primary')
                                        ->action(function ($record) {
                                            // Ini akan memicu download langsung di browser user
                                            return Storage::disk('google')->download($record->user->file_foto_bersama);
                                        })
                                ])->fullWidth(),
                            ])
                                ->extraAttributes(['class' => 'bg-white border border-gray-200 rounded-lg shadow-sm flex flex-col']),

                            // --- KARTU 3: TEMPAT USAHA ---
                            \Filament\Infolists\Components\Group::make([
                                \Filament\Infolists\Components\ImageEntry::make('user.file_foto_usaha')
                                    ->hiddenLabel()
                                    ->state(function ($record) {
                                        $path = $record->user->file_foto_usaha;

                                        // Jika file tidak ada, kembalikan null
                                        if (!$path) return null;

                                        // Return URL ke route proxy yang kita buat di langkah 1
                                        return route('drive.image', ['path' => $path]);
                                    })
                                    ->height(250)->width('100%')
                                    ->extraImgAttributes(['class' => 'object-cover w-full h-full rounded-t-lg']),

                                \Filament\Infolists\Components\Actions::make([
                                    \Filament\Infolists\Components\Actions\Action::make('download_usaha')
                                        ->label('Download Tempat Usaha')
                                        ->icon('heroicon-m-arrow-down-tray')
                                        ->color('primary')
                                        ->action(function ($record) {
                                            // Ini akan memicu download langsung di browser user
                                            return Storage::disk('google')->download($record->user->file_foto_usaha);
                                        })
                                ])->fullWidth(),
                            ])
                                ->extraAttributes(['class' => 'bg-white border border-gray-200 rounded-lg shadow-sm flex flex-col']),

                            // --- KARTU 4: KTP ---
                            \Filament\Infolists\Components\Group::make([
                                \Filament\Infolists\Components\ImageEntry::make('user.file_ktp')
                                    ->hiddenLabel()
                                    ->state(function ($record) {
                                        $path = $record->user->file_ktp;

                                        // Jika file tidak ada, kembalikan null
                                        if (!$path) return null;

                                        // Return URL ke route proxy yang kita buat di langkah 1
                                        return route('drive.image', ['path' => $path]);
                                    })
                                    ->height(250)->width('100%')
                                    ->extraImgAttributes(['class' => 'object-cover w-full h-full rounded-t-lg']),

                                \Filament\Infolists\Components\Actions::make([
                                    \Filament\Infolists\Components\Actions\Action::make('download_ktp')
                                        ->label('Download Foto KTP')
                                        ->icon('heroicon-m-arrow-down-tray')
                                        ->color('primary')
                                        ->action(function ($record) {
                                            // Ini akan memicu download langsung di browser user
                                            return Storage::disk('google')->download($record->user->file_ktp);
                                        })
                                ])->fullWidth(),
                            ])
                                ->extraAttributes(['class' => 'bg-white border border-gray-200 rounded-lg shadow-sm flex flex-col']),

                        ]),
                    ]),
            ]);
    }
}

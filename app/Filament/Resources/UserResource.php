<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Laravolt\Indonesia\Models\Province;
use Laravolt\Indonesia\Models\City;
use Laravolt\Indonesia\Models\District;
use Laravolt\Indonesia\Models\Village;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Support\Collection;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Infolists;
use Filament\Infolists\Components\Actions\Action;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry; // Jika ada foto
use Illuminate\Support\Facades\Storage;
use Filament\Infolists\Components\Grid as InfolistGrid;       // <--- PENTING: Pakai Alias
use Filament\Infolists\Components\Group as InfolistGroup;     // <--- PENTING: Pakai Alias
use Filament\Infolists\Components\Section as InfolistSection;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users'; // Saya ganti icon biar lebih pas
    protected static ?string $navigationGroup = 'Manajemen Sistem'; // Opsional: Biar rapi

    public static function form(Form $form): Form
    {
        return $form
            ->columns(3) // Kita bagi layar jadi 3 bagian grid
            ->schema([

                // ============================================================
                // KOLOM KIRI (SPAN 2) - Wilayah & Dokumen
                // ============================================================
                Group::make()
                    ->columnSpan(['lg' => 2]) // Ambil 2 bagian dari 3
                    ->schema([

                        // 1. DATA WILAYAH
                        Section::make('Wilayah Kerja / Domisili')
                            ->icon('heroicon-o-map')
                            ->schema([
                                Forms\Components\Textarea::make('address')
                                    ->label('Alamat Lengkap')
                                    ->rows(2)
                                    ->columnSpanFull(),

                                Forms\Components\Select::make('provinsi')
                                    ->label('Provinsi')
                                    ->options(Province::pluck('name', 'code'))
                                    ->searchable()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set) {
                                        $set('kabupaten', null);
                                        $set('kecamatan', null);
                                        $set('desa', null);
                                    }),

                                Forms\Components\Select::make('kabupaten')
                                    ->label('Kabupaten / Kota')
                                    ->options(function (Get $get) {
                                        $prov = $get('provinsi');
                                        if (!$prov) return Collection::empty();
                                        return City::where('province_code', $prov)->pluck('name', 'code');
                                    })
                                    ->searchable()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set) {
                                        $set('kecamatan', null);
                                        $set('desa', null);
                                    }),

                                Forms\Components\Select::make('kecamatan')
                                    ->label('Kecamatan')
                                    ->options(function (Get $get) {
                                        $kab = $get('kabupaten');
                                        if (!$kab) return Collection::empty();
                                        return District::where('city_code', $kab)->pluck('name', 'code');
                                    })
                                    ->searchable()
                                    ->live()
                                    ->afterStateUpdated(fn(Set $set) => $set('desa', null))
                                    ->required(),

                                Forms\Components\Select::make('desa')
                                    ->label('Desa / Kelurahan')
                                    ->options(function (Get $get) {
                                        $kec = $get('kecamatan');
                                        if (!$kec) return Collection::empty();
                                        return Village::where('district_code', $kec)->pluck('name', 'code');
                                    })
                                    ->searchable(),
                            ])->columns(2),

                        // 2. DOKUMEN PENDAMPING (Panggil Schema Statis)
                        Group::make(User::getDokumenPendampingFormSchema())
                            ->visible(fn(Get $get) => $get('role') === 'pendamping'),

                        // 3. DATA TAMBAHAN (Read Only)
                        Section::make('Informasi Bank & Pendidikan')
                            ->icon('heroicon-o-academic-cap')
                            ->collapsible()
                            ->collapsed() // Tutup default biar gak menuhin layar
                            ->visible(fn(Get $get) => $get('role') === 'pendamping')
                            ->schema([
                                Forms\Components\TextInput::make('nama_bank')->disabled(),
                                Forms\Components\TextInput::make('nomor_rekening')->disabled(),
                                Forms\Components\TextInput::make('pendidikan_terakhir')->disabled(),
                                Forms\Components\TextInput::make('nama_instansi')->label('Sekolah/Kampus')->disabled(),
                            ])->columns(2),
                    ]),

                // ============================================================
                // KOLOM KANAN (SPAN 1) - Akun & Login
                // ============================================================
                Group::make()
                    ->columnSpan(['lg' => 1]) // Ambil 1 bagian dari 3
                    ->schema([

                        Section::make('Akun Pengguna')
                            ->icon('heroicon-o-user-circle')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->label('Nama Lengkap'),

                                Forms\Components\TextInput::make('email')
                                    ->email()
                                    ->unique(ignoreRecord: true)
                                    ->required(),

                                Forms\Components\TextInput::make('phone')
                                    ->label('No HP')
                                    ->tel(),

                                Forms\Components\TextInput::make('password')
                                    ->password()
                                    ->dehydrateStateUsing(fn($state) => Hash::make($state))
                                    ->dehydrated(fn($state) => filled($state))
                                    ->required(fn(string $context): bool => $context === 'create'),

                                // SELECT ROLE
                                Forms\Components\Select::make('role')
                                    ->label('Role User')
                                    ->live() // PENTING: Agar form dokumen di kiri langsung muncul saat dipilih
                                    ->options(function () {
                                        $user = Auth::user();
                                        // Logika opsi role Anda...
                                        if ($user && $user->isSuperAdmin()) {
                                            return [
                                                'admin' => 'Admin',
                                                'koordinator' => 'Koordinator Kecamatan',
                                                'pendamping' => 'Pendamping',
                                            ];
                                        }
                                        return [
                                            'koordinator' => 'Koordinator Kecamatan',
                                            'pendamping' => 'Pendamping',
                                        ];
                                    })
                                    ->required()
                                    ->default('pendamping'),
                            ]),
                    ]),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('email'),
                Tables\Columns\TextColumn::make('phone'),
                Tables\Columns\TextColumn::make('role')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'superadmin' => 'gray', // Tambahan warna buat superadmin
                        'admin' => 'danger',
                        'pendamping' => 'warning', // Pendamping warna kuning
                        'member' => 'success',
                        default => 'primary',
                    }),
                // --- KOLOM BARU: STATUS ---
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'verified' => 'success', // Hijau
                        'rejected' => 'danger',  // Merah
                        'pending' => 'warning',  // Kuning
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => ucfirst($state)) // Huruf besar awal
                    ->icon(fn(string $state): string => match ($state) {
                        'verified' => 'heroicon-o-check-circle',
                        'rejected' => 'heroicon-o-x-circle',
                        'pending' => 'heroicon-o-clock',
                    }),
                Tables\Columns\TextColumn::make('created_at')->date(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->color('info'),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),

                // --- TOMBOL AKSI VERIFIKASI ---
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('verify')
                        ->label('Verifikasi Akun')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->requiresConfirmation() // Minta konfirmasi biar gak salah klik
                        ->action(fn(User $record) => $record->update(['status' => 'verified']))
                        ->visible(fn(User $record) => $record->status !== 'verified'), // Sembunyi jika sudah verify

                    Tables\Actions\Action::make('reject')
                        ->label('Tolak Akun')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn(User $record) => $record->update(['status' => 'rejected']))
                        ->visible(fn(User $record) => $record->status !== 'rejected'),
                ])
                    ->label('Ubah Status')
                    ->icon('heroicon-m-ellipsis-vertical')
                    // Aksi ini hanya boleh dilihat Superadmin/Admin
                    ->visible(fn() => Auth::user()->isSuperAdmin() || Auth::user()->isAdmin()),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        // 1. Filter Dasar: Jangan tampilkan Member di menu ini
        $query->where('role', '!=', 'member');

        // 2. Jika Superadmin: Lihat Semua (kecuali sesama superadmin biar aman)
        if ($user->isSuperAdmin()) {
            return $query->where('role', '!=', 'superadmin'); // Opsional
        }

        // 3. Jika Admin: Lihat Koordinator & Pendamping
        if ($user->isAdmin()) {
            return $query->whereIn('role', ['koordinator', 'pendamping']);
        }

        // 4. Jika KOORDINATOR (Logika Baru)
        if ($user->role === 'koordinator') {
            // Hanya lihat PENDAMPING
            $query->where('role', 'pendamping');

            // DAN hanya yang berada di KECAMATAN yang sama dengannya
            $query->where('kecamatan', $user->kecamatan);

            return $query;
        }

        // Default (Pendamping tidak bisa lihat menu ini, ditangani di canViewAny)
        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        $user = Auth::user();
        return $user->isSuperAdmin() || $user->isAdmin() || $user->isKoordinator();
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // Grid Utama dibagi 3 bagian
                InfolistGrid::make(3)
                    ->schema([

                        // ========================================================
                        // KOLOM KIRI (SPAN 2): DATA & DOKUMEN
                        // ========================================================
                        InfolistGroup::make([

                            // 1. DATA WILAYAH & DOMISILI
                            InfolistSection::make('Wilayah Kerja / Domisili')
                                ->icon('heroicon-o-map')
                                ->schema([
                                    TextEntry::make('address')->label('Alamat Lengkap')->columnSpanFull(),
                                    // Relasi Wilayah (Pastikan relasi di Model User sudah ada)
                                    TextEntry::make('province.name')->label('Provinsi'),
                                    TextEntry::make('city.name')->label('Kabupaten/Kota'),
                                    TextEntry::make('district.name')->label('Kecamatan'),
                                    TextEntry::make('village.name')->label('Desa/Kelurahan'),
                                ])->columns(2),

                            // 2. INFORMASI BANK & PENDIDIKAN (Khusus Pendamping)
                            InfolistSection::make('Informasi Bank & Pendidikan')
                                ->icon('heroicon-o-academic-cap')
                                ->visible(fn($record) => $record->role === 'pendamping')
                                ->schema([
                                    TextEntry::make('nama_bank')->label('Bank'),
                                    TextEntry::make('nomor_rekening')->label('No. Rekening')->copyable(),
                                    TextEntry::make('pendidikan_terakhir')->badge()->color('info'),
                                    TextEntry::make('nama_instansi')->label('Sekolah/Kampus'),
                                ])->columns(2),

                            // 3. BERKAS DOKUMEN (GOOGLE DRIVE)
                            // Ini bagian yang disesuaikan dengan kode Form Upload Anda
                            InfolistSection::make('Berkas Dokumen Pendamping')
                                ->icon('heroicon-o-folder-open')
                                ->visible(fn($record) => $record->role === 'pendamping')
                                ->schema([

                                    // A. PAS FOTO (Tampil Kotak)
                                    ImageEntry::make('file_pas_foto')
                                        ->label('Pas Foto')
                                        ->disk('google')         // WAJIB: Sesuai disk upload
                                        ->visibility('private')  // WAJIB: Agar generate signed URL
                                        ->height(200)
                                        ->extraImgAttributes([
                                            'class' => 'rounded-lg shadow-md object-cover aspect-square', // Kotak
                                            'loading' => 'lazy',
                                            'referrerpolicy' => 'no-referrer', // Agar tidak pecah di localhost
                                        ])
                                        // Tombol Download/Buka Tab Baru (Safety Net jika gambar pecah)
                                        ->hintAction(
                                            Action::make('open_pas_foto')
                                                ->icon('heroicon-m-arrow-top-right-on-square')
                                                ->url(function ($record) {
                                                    // LOGIKA AMAN: Coba ambil URL, jika gagal (file hilang), kembalikan null
                                                    try {
                                                        if ($record->file_pas_foto) {
                                                            return Storage::disk('google')->url($record->file_pas_foto);
                                                        }
                                                    } catch (\Exception $e) {
                                                        return null; // File tidak ditemukan, tombol tidak ada link
                                                    }
                                                    return null;
                                                })
                                                ->openUrlInNewTab()
                                                // Sembunyikan tombol jika URL null (file hilang)
                                                ->visible(fn($action) => $action->getUrl() !== null)
                                        ),

                                    // B. BUKU REKENING
                                    ImageEntry::make('file_buku_rekening')
                                        ->label('Buku Rekening')
                                        ->disk('google')
                                        ->visibility('private')
                                        ->height(200)
                                        ->extraImgAttributes([
                                            'class' => 'rounded-lg shadow-md border border-gray-200',
                                            'loading' => 'lazy',
                                            'referrerpolicy' => 'no-referrer',
                                        ])
                                        ->hintAction(
                                            Action::make('open_rekening')
                                                ->icon('heroicon-m-arrow-top-right-on-square')
                                                ->url(function ($record) {
                                                    try {
                                                        if ($record->file_buku_rekening) {
                                                            return Storage::disk('google')->url($record->file_buku_rekening);
                                                        }
                                                    } catch (\Exception $e) {
                                                        return null;
                                                    }
                                                    return null;
                                                })
                                                ->openUrlInNewTab()
                                                ->visible(fn($action) => $action->getUrl() !== null)
                                        ),

                                    // C. KTP
                                    ImageEntry::make('file_ktp')
                                        ->label('KTP')
                                        ->disk('google')
                                        ->visibility('private')
                                        ->height(200)
                                        ->extraImgAttributes([
                                            'class' => 'rounded-lg shadow-md border border-gray-200',
                                            'loading' => 'lazy',
                                            'referrerpolicy' => 'no-referrer',
                                        ])
                                        ->hintAction(
                                            Action::make('open_ktp')
                                                ->icon('heroicon-m-arrow-top-right-on-square')
                                                ->url(function ($record) {
                                                    try {
                                                        if ($record->file_ktp) {
                                                            return Storage::disk('google')->url($record->file_ktp);
                                                        }
                                                    } catch (\Exception $e) {
                                                        return null;
                                                    }
                                                    return null;
                                                })
                                                ->openUrlInNewTab()
                                                ->visible(fn($action) => $action->getUrl() !== null)
                                        ),

                                    // D. IJAZAH
                                    ImageEntry::make('file_ijazah')
                                        ->label('Ijazah Terakhir')
                                        ->disk('google')
                                        ->visibility('private')
                                        ->height(200)
                                        ->extraImgAttributes([
                                            'class' => 'rounded-lg shadow-md border border-gray-200',
                                            'loading' => 'lazy',
                                            'referrerpolicy' => 'no-referrer',
                                        ])
                                        ->hintAction(
                                            Action::make('open_ijazah')
                                                ->icon('heroicon-m-arrow-top-right-on-square')
                                                ->url(function ($record) {
                                                    try {
                                                        if ($record->file_ijazah) {
                                                            return Storage::disk('google')->url($record->file_ijazah);
                                                        }
                                                    } catch (\Exception $e) {
                                                        return null;
                                                    }
                                                    return null;
                                                })
                                                ->openUrlInNewTab()
                                                ->visible(fn($action) => $action->getUrl() !== null)
                                        ),

                                ])->columns(2), // Tampil 2 kolom (Kiri-Kanan)

                        ])->columnSpan(['lg' => 2]),

                        // ========================================================
                        // KOLOM KANAN (SPAN 1): AKUN PENGGUNA
                        // ========================================================
                        InfolistGroup::make([
                            InfolistSection::make('Akun Pengguna')
                                ->icon('heroicon-o-user-circle')
                                ->schema([
                                    TextEntry::make('name')
                                        ->label('Nama Lengkap')
                                        ->weight('bold')
                                        ->size(TextEntry\TextEntrySize::Large),

                                    TextEntry::make('email')
                                        ->icon('heroicon-m-envelope')
                                        ->copyable(),

                                    TextEntry::make('phone')
                                        ->label('No. HP / WA')
                                        ->icon('heroicon-m-phone')
                                        ->url(fn($state) => 'https://wa.me/' . preg_replace('/^0/', '62', $state), true)
                                        ->color('success'),

                                    TextEntry::make('role')
                                        ->label('Role')
                                        ->badge()
                                        ->color(fn(string $state): string => match ($state) {
                                            'superadmin' => 'gray',
                                            'admin' => 'danger',
                                            'pendamping' => 'warning',
                                            'member' => 'success',
                                            default => 'primary',
                                        }),

                                    TextEntry::make('status')
                                        ->badge()
                                        ->color(fn(string $state): string => match ($state) {
                                            'verified' => 'success',
                                            'rejected' => 'danger',
                                            default => 'warning',
                                        }),

                                    TextEntry::make('created_at')
                                        ->label('Terdaftar')
                                        ->since()
                                        ->color('gray'),
                                ]),
                        ])->columnSpan(['lg' => 1]),
                    ]),
            ]);
    }
}

<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Infolists\Components\Grid as InfolistGrid;
use Filament\Infolists\Components\Group as InfolistGroup;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\HtmlString;       // <--- PENTING: Pakai Alias
use Laravolt\Indonesia\Models\City;     // <--- PENTING: Pakai Alias
use Laravolt\Indonesia\Models\District;
use Laravolt\Indonesia\Models\Province;
use Laravolt\Indonesia\Models\Village;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users'; // Saya ganti icon biar lebih pas

    protected static ?string $navigationGroup = 'Manajemen Sistem'; // Opsional: Biar rapi

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                // KITA BUNGKUS SEMUA DALAM TABS
                Tabs::make('User Data')
                    ->tabs([

                        // ====================================================
                        // TAB 1: AKUN & LOGIN (Data Paling Penting)
                        // ====================================================
                        Tabs\Tab::make('Akun & Login')
                            ->icon('heroicon-o-user-circle')
                            ->schema([
                                Forms\Components\Grid::make(2)->schema([
                                    Forms\Components\TextInput::make('name')
                                        ->label('Nama Lengkap')
                                        ->required(),

                                    Forms\Components\TextInput::make('email')
                                        ->email()
                                        ->unique(ignoreRecord: true)
                                        ->required(),

                                    Forms\Components\TextInput::make('phone')
                                        ->label('No HP / WA')
                                        ->tel(),

                                    Forms\Components\Select::make('role')
                                        ->label('Role User')
                                        ->live()
                                        ->options(function () {
                                            $user = Auth::user();
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

                                Forms\Components\Section::make('Keamanan')
                                    ->description('Isi hanya jika ingin mengubah password user.')
                                    ->schema([
                                        Forms\Components\TextInput::make('password')
                                            ->password()
                                            ->revealable()
                                            // Hash password sebelum disimpan
                                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                                            // Hanya update jika field diisi (penting untuk Edit)
                                            ->dehydrated(fn ($state) => filled($state))
                                            // Wajib hanya saat Create
                                            ->required(fn (string $context): bool => $context === 'create'),
                                    ]),
                            ]),

                        // ====================================================
                        // TAB 2: DATA WILAYAH (Jarang diedit Admin)
                        // ====================================================
                        Tabs\Tab::make('Wilayah & Domisili')
                            ->icon('heroicon-o-map')
                            ->schema([
                                Forms\Components\Grid::make(2)->schema([
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
                                        ->options(
                                            fn (Get $get) => $get('provinsi') ? City::where('province_code', $get('provinsi'))->pluck('name', 'code') : []
                                        )
                                        ->searchable()
                                        ->live()
                                        ->afterStateUpdated(function (Set $set) {
                                            $set('kecamatan', null);
                                            $set('desa', null);
                                        }),

                                    Forms\Components\Select::make('kecamatan')
                                        ->label('Kecamatan')
                                        ->options(
                                            fn (Get $get) => $get('kabupaten') ? District::where('city_code', $get('kabupaten'))->pluck('name', 'code') : []
                                        )
                                        ->searchable()
                                        ->live()
                                        ->afterStateUpdated(fn (Set $set) => $set('desa', null)),

                                    Forms\Components\Select::make('desa')
                                        ->label('Desa / Kelurahan')
                                        ->options(
                                            fn (Get $get) => $get('kecamatan') ? Village::where('district_code', $get('kecamatan'))->pluck('name', 'code') : []
                                        )
                                        ->searchable(),
                                ]),

                                Forms\Components\Textarea::make('address')
                                    ->label('Alamat Lengkap (Jalan, RT/RW)')
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ]),

                        // ====================================================
                        // TAB 3: DOKUMEN (Hanya muncul jika Role = Pendamping)
                        // ====================================================
                        Tabs\Tab::make('Dokumen & Berkas')
                            ->icon('heroicon-o-folder')
                            // Tab ini hilang otomatis jika bukan pendamping
                            ->hidden(fn (Get $get) => $get('role') !== 'pendamping')
                            ->schema([

                                // Data Bank (Read Only buat Admin agar tidak salah edit)
                                Forms\Components\Section::make('Info Bank')
                                    ->schema([
                                        Forms\Components\TextInput::make('nama_bank'),
                                        Forms\Components\TextInput::make('nomor_rekening'),
                                    ])->columns(2),

                                // Panggil Schema Dokumen Statis Anda
                                Forms\Components\Group::make(User::getDokumenPendampingFormSchema())
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpanFull() // Agar Tabs memenuhi lebar modal/halaman
                    ->persistTabInQueryString(), // Agar pas refresh tetap di tab yg sama
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
                    ->color(fn (string $state): string => match ($state) {
                        'superadmin' => 'gray', // Tambahan warna buat superadmin
                        'admin' => 'danger',
                        'pendamping' => 'warning', // Pendamping warna kuning
                        'member' => 'success',
                        default => 'primary',
                    }),
                // --- KOLOM BARU: STATUS ---
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'verified' => 'success', // Hijau
                        'rejected' => 'danger',  // Merah
                        'pending' => 'warning',  // Kuning
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)) // Huruf besar awal
                    ->icon(fn (string $state): string => match ($state) {
                        'verified' => 'heroicon-o-check-circle',
                        'rejected' => 'heroicon-o-x-circle',
                        'pending' => 'heroicon-o-clock',
                    }),
                Tables\Columns\TextColumn::make('created_at')->date(),
            ])
            ->actions([
                // --- FITUR PENUGASAN WILAYAH (TOMBOL TERPISAH) ---
                Tables\Actions\Action::make('assign_territory')
                    ->label('Tugaskan Wilayah')
                    ->icon('heroicon-m-map')
                    ->color('info')
                    ->modalWidth('lg')
                    ->visible(fn (User $record) => $record->isAdmin()) // Hanya muncul untuk role admin
                    ->mountUsing(function (User $record, \Filament\Forms\Form $form) {
                        // Load data yang sudah tersimpan
                        $form->fill([
                            'assigned_districts' => $record->assigned_districts,
                        ]);
                    })
                    ->form([
                        Select::make('assigned_districts')
                            ->label('Pilih Kecamatan')
                            ->multiple() // Bisa pilih banyak
                            ->searchable()
                            ->preload() // Preload data kecamatan
                            // Ambil data dari Laravolt District
                            // code sebagai key, name sebagai label (Kode Cianjur: 3203)
                            ->options(District::where('city_code', '3203')->pluck('name', 'code'))
                            ->helperText('Admin ini hanya akan melihat pengajuan dari kecamatan yang dipilih.'),
                    ])
                    ->action(function (User $record, array $data) {
                        $record->update([
                            'assigned_districts' => $data['assigned_districts'],
                        ]);

                        Notification::make()
                            ->title('Penugasan Berhasil')
                            ->success()
                            ->send();
                    }),

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
                        ->action(fn (User $record) => $record->update(['status' => 'verified']))
                        ->visible(fn (User $record) => $record->status !== 'verified'), // Sembunyi jika sudah verify

                    Tables\Actions\Action::make('reject')
                        ->label('Tolak Akun')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn (User $record) => $record->update(['status' => 'rejected']))
                        ->visible(fn (User $record) => $record->status !== 'rejected'),
                ])
                    ->label('Ubah Status')
                    ->icon('heroicon-m-ellipsis-vertical')
                    // Aksi ini hanya boleh dilihat Superadmin/Admin
                    ->visible(fn () => Auth::user()->isSuperAdmin() || Auth::user()->isAdmin()),
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

        return $user->isSuperAdmin() || $user->isAdmin();
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // Grid Utama: 3 Kolom
                InfolistGrid::make(3)
                    ->schema([

                        // ========================================================
                        // KOLOM KIRI (SPAN 2)
                        // ========================================================
                        InfolistGroup::make([

                            // 1. DATA WILAYAH
                            InfolistSection::make('Wilayah Kerja / Domisili')
                                ->icon('heroicon-o-map')
                                ->schema([
                                    TextEntry::make('address')->label('Alamat Lengkap')->columnSpanFull(),
                                    TextEntry::make('province.name')->label('Provinsi'),
                                    TextEntry::make('city.name')->label('Kabupaten/Kota'),
                                    TextEntry::make('district.name')->label('Kecamatan'),
                                    TextEntry::make('village.name')->label('Desa/Kelurahan'),
                                ])->columns(2),

                            // 2. INFORMASI BANK
                            InfolistSection::make('Informasi Bank & Pendidikan')
                                ->icon('heroicon-o-academic-cap')
                                ->visible(fn ($record) => $record->role === 'pendamping')
                                ->schema([
                                    TextEntry::make('nama_bank')->label('Bank'),
                                    TextEntry::make('nomor_rekening')->label('No. Rekening')->copyable(),
                                    TextEntry::make('pendidikan_terakhir')->badge()->color('info'),
                                    TextEntry::make('nama_instansi')->label('Sekolah/Kampus'),
                                ])->columns(2),

                        ])->columnSpan(['lg' => 2]),

                        // ========================================================
                        // KOLOM KANAN (SPAN 1): AKUN
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
                                        ->url(fn ($state) => 'https://wa.me/'.preg_replace('/^0/', '62', $state), true)
                                        ->color('success'),

                                    TextEntry::make('role')
                                        ->badge()
                                        ->color('warning'),

                                    TextEntry::make('status')
                                        ->badge(),

                                    TextEntry::make('created_at')
                                        ->label('Terdaftar')
                                        ->since()
                                        ->color('gray'),
                                ]),
                        ])->columnSpan(['lg' => 1]),

                    ]), // tutup grid utama

                // ========================================================
                // BAGIAN BAWAH: DOKUMEN (Menggunakan Route Proxy)
                // ========================================================
                InfolistSection::make('Berkas Dokumen Pendamping')
                    ->icon('heroicon-o-folder-open')
                    ->visible(fn ($record) => $record->role === 'pendamping')
                    ->schema([
                        // Panggil helper function getProxyImageEntry
                        self::getProxyImageEntry('file_pas_foto', 'Pas Foto'),
                        self::getProxyImageEntry('file_buku_rekening', 'Buku Rekening'),
                        self::getProxyImageEntry('file_ktp', 'KTP'),
                        self::getProxyImageEntry('file_ijazah', 'Ijazah'),
                    ])
                    ->columns([
                        'default' => 1,
                        'sm' => 2, // Tampil 2 kolom agar rapi
                        'xl' => 4,
                    ]),
            ]);
    }

    /**
     * Helper untuk menampilkan Gambar via Proxy Route di Infolist
     * Copas fungsi ini ke dalam class UserResource
     */
    protected static function getProxyImageEntry(string $field, string $label): TextEntry
    {
        return TextEntry::make($field)
            ->label($label)
            ->formatStateUsing(fn ($state) => empty($state) ? '-' : new HtmlString("
                <div class='relative group overflow-hidden rounded-lg border border-gray-200 shadow-sm bg-gray-50'>
                    <img src='".route('drive.image', ['path' => $state])."' 
                         alt='$label' 
                         class='w-full h-48 object-cover transition-transform duration-500 group-hover:scale-105' 
                         loading='lazy'>
                    
                    <a href='".route('drive.image', ['path' => $state])."' 
                       target='_blank' 
                       class='absolute inset-0 flex items-center justify-center bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity duration-300 text-white font-bold tracking-wide no-underline'>
                       <svg xmlns='http://www.w3.org/2000/svg' class='h-6 w-6 mr-2' fill='none' viewBox='0 0 24 24' stroke='currentColor'>
                          <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M15 12a3 3 0 11-6 0 3 3 0 016 0z' />
                          <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z' />
                       </svg>
                       LIHAT
                    </a>
                </div>
            "))
            ->columnSpan(1);
    }
}

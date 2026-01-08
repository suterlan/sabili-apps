<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PendampingResource\Pages;
use App\Models\User;
use Carbon\Carbon;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;       // <--- PENTING: Pakai Alias
use Filament\Forms\Form;     // <--- PENTING: Pakai Alias
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Infolists\Components\Grid as InfolistGrid;
use Filament\Infolists\Components\Group as InfolistGroup;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
// Import Model Wilayah Laravolt
use Laravolt\Indonesia\Models\City;
use Laravolt\Indonesia\Models\District;
use Laravolt\Indonesia\Models\Province;
use Laravolt\Indonesia\Models\Village;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class PendampingResource extends Resource
{
    protected static ?string $model = User::class;

    // Bedakan Label dan Slug agar tidak bentrok dengan UserResource biasa
    protected static ?string $navigationLabel = 'Pendamping';

    protected static ?string $modelLabel = 'Monitoring Pendamping';

    protected static ?string $pluralModelLabel = 'Monitoring Pendamping';

    protected static ?string $slug = 'monitoring-pendamping';

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'Master Data';

    // protected static ?int $navigationSort = 2; // Urutan menu

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // KITA BUNGKUS SEMUA DALAM TABS SEPERTI USER RESOURCE
                Tabs::make('User Data')
                    ->tabs([

                        // ====================================================
                        // TAB 1: AKUN & LOGIN (TANPA PASSWORD)
                        // ====================================================
                        Tabs\Tab::make('Akun & Login')
                            ->icon('heroicon-o-user-circle')
                            ->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('name')
                                        ->label('Nama Lengkap')
                                        ->required(),

                                    TextInput::make('email')
                                        ->email()
                                        ->unique(ignoreRecord: true)
                                        ->required(),

                                    TextInput::make('phone')
                                        ->label('No HP / WA')
                                        ->tel(),

                                    Select::make('role')
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

                                // Tambahan Khusus Pendamping (Akses SiHalal)
                                Section::make('Akses SiHalal')
                                    ->description('Informasi akun BPJPH / SiHalal')
                                    ->schema([
                                        Grid::make(2)->schema([
                                            TextInput::make('akun_halal')->label('Username SiHalal'),
                                            TextInput::make('pass_akun_halal')->label('Password SiHalal'),
                                        ]),
                                    ]),
                            ]),

                        // ====================================================
                        // TAB 2: DATA WILAYAH (Dependent Select)
                        // ====================================================
                        Tabs\Tab::make('Wilayah & Domisili')
                            ->icon('heroicon-o-map')
                            ->schema([
                                Grid::make(2)->schema([
                                    Select::make('provinsi') // Pastikan nama kolom di DB 'provinsi' atau 'province_id'
                                        ->label('Provinsi')
                                        ->options(Province::pluck('name', 'code'))
                                        ->searchable()
                                        ->live()
                                        ->afterStateUpdated(function (Set $set) {
                                            $set('kabupaten', null);
                                            $set('kecamatan', null);
                                            $set('desa', null);
                                        }),

                                    Select::make('kabupaten')
                                        ->label('Kabupaten / Kota')
                                        ->options(fn(Get $get) => $get('provinsi') ? City::where('province_code', $get('provinsi'))->pluck('name', 'code') : [])
                                        ->searchable()
                                        ->live()
                                        ->afterStateUpdated(function (Set $set) {
                                            $set('kecamatan', null);
                                            $set('desa', null);
                                        }),

                                    Select::make('kecamatan')
                                        ->label('Kecamatan')
                                        ->options(fn(Get $get) => $get('kabupaten') ? District::where('city_code', $get('kabupaten'))->pluck('name', 'code') : [])
                                        ->searchable()
                                        ->live()
                                        ->afterStateUpdated(fn(Set $set) => $set('desa', null)),

                                    Select::make('desa')
                                        ->label('Desa / Kelurahan')
                                        ->options(fn(Get $get) => $get('kecamatan') ? Village::where('district_code', $get('kecamatan'))->pluck('name', 'code') : [])
                                        ->searchable(),
                                ]),

                                Textarea::make('address')
                                    ->label('Alamat Lengkap (Jalan, RT/RW)')
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ]),

                        // ====================================================
                        // TAB 3: DOKUMEN
                        // ====================================================
                        Tabs\Tab::make('Dokumen & Berkas')
                            ->icon('heroicon-o-folder')
                            ->hidden(fn(Get $get) => $get('role') !== 'pendamping')
                            ->schema([
                                // Info Bank
                                Section::make('Info Bank & Pendidikan')
                                    ->schema([
                                        TextInput::make('nama_bank'),
                                        TextInput::make('nomor_rekening'),
                                        TextInput::make('pendidikan_terakhir'),
                                        TextInput::make('nama_instansi')->label('Instansi Pendidikan'),
                                    ])->columns(2),

                                // Panggil Schema Dokumen Statis dari Model User
                                Group::make(User::getDokumenPendampingFormSchema())
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpanFull()
                    ->persistTabInQueryString(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Pendamping')
                    ->searchable()
                    ->sortable(),

                // Menampilkan Wilayah (Format Kode ke Nama)
                Tables\Columns\TextColumn::make('kecamatan')
                    ->label('Wilayah Kecamatan')
                    ->formatStateUsing(
                        fn($state) => \Laravolt\Indonesia\Models\District::where('code', $state)->first()?->name ?? '-'
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
            ->headerActions([
                // TOMBOL EXPORT (Download Semua / Sesuai Filter)
                ExportAction::make()
                    ->label('Export Excel')
                    ->color('success')
                    ->exports([
                        ExcelExport::make()
                            // --- TAMBAHKAN INI (Filter Wajib) ---
                            ->modifyQueryUsing(function ($query) {
                                return $query->where('role', 'pendamping');
                            })
                            ->withFilename('Data_Pendamping_' . date('Y-m-d'))
                            ->withColumns([
                                // Definisikan Kolom Custom agar Rapi
                                Column::make('name')->heading('Nama Lengkap'),
                                Column::make('email')->heading('Email'),
                                Column::make('phone')->heading('No HP/WA'),
                                Column::make('role')->heading('Role'),

                                // Data Wilayah (Ambil dari relasi)
                                Column::make('province.name')->heading('Provinsi'),
                                Column::make('city.name')->heading('Kabupaten/Kota'),
                                Column::make('district.name')->heading('Kecamatan'),
                                Column::make('village.name')->heading('Desa/Kelurahan'),
                                Column::make('address')->heading('Alamat Lengkap'),
                                Column::make('pendidikan_terakhir')->heading('Pendidikan Terakhir'),

                                // Data Bank (Penting untuk Laporan Keuangan)
                                Column::make('nama_bank')->heading('Bank'),
                                Column::make('nomor_rekening')->heading('No Rekening')->formatStateUsing(fn($state) => ' ' . $state),
                                Column::make('nama_instansi')->heading('Instansi'),

                                Column::make('akun_halal')->heading('Akun SiHalal'),
                                Column::make('pass_akun_halal')->heading('Password Akun SiHalal'),

                                Column::make('created_at')->heading('Tanggal Daftar')->formatStateUsing(fn($state) => Carbon::parse($state)->format('d-m-Y H:i')),
                            ]),
                    ]),

            ])
            ->actions([
                // Kita hilangkan tombol Edit/Delete agar menu ini murni untuk monitoring
                // Jika ingin melihat detail, bisa tambahkan ViewAction
                Tables\Actions\ViewAction::make()
                    ->label('Detail')
                    ->color('info'),

                // =========================================================
                // 2. TOMBOL EDIT (RESTRICTED)
                // =========================================================
                Tables\Actions\EditAction::make()
                    ->label('Edit')
                    ->color('warning')
                    ->slideOver()
                    // Hanya Tampil untuk Admin & SuperAdmin
                    ->visible(fn() => Auth::user()->isAdmin() || Auth::user()->isSuperAdmin() || Auth::user()->isManajemen()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    // Opsional: Export yang dicentang saja
                    \pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction::make(),
                ])
                    ->visible(fn() => Auth::user()->isSuperAdmin()),
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
            || Auth::user()->isManajemen()
            || Auth::user()->isAdmin()
            || Auth::user()->isKoordinator();
    }

    // Edit dikontrol via Action Button di table, tapi method ini harus true/false based on logic
    public static function canEdit($record): bool
    {
        return Auth::user()->isAdmin() || Auth::user()->isSuperAdmin() || Auth::user()->isManajemen();
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

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfolistGrid::make(3)
                    ->schema([
                        // KOLOM KIRI (Data Wilayah & Bank)
                        InfolistGroup::make([
                            InfolistSection::make('Wilayah Kerja / Domisili')
                                ->icon('heroicon-o-map')
                                ->schema([
                                    TextEntry::make('address')->label('Alamat Lengkap')->columnSpanFull(),
                                    TextEntry::make('province.name')->label('Provinsi'),
                                    TextEntry::make('city.name')->label('Kabupaten/Kota'),
                                    TextEntry::make('district.name')->label('Kecamatan'),
                                    TextEntry::make('village.name')->label('Desa/Kelurahan'),
                                ])->columns(2),

                            InfolistSection::make('Informasi Bank & Pendidikan')
                                ->icon('heroicon-o-academic-cap')
                                ->visible(fn($record) => $record->role === 'pendamping')
                                ->schema([
                                    TextEntry::make('nama_bank')->label('Bank'),
                                    TextEntry::make('nomor_rekening')->label('No. Rekening')->copyable(),
                                    TextEntry::make('pendidikan_terakhir')->badge()->color('info'),
                                    TextEntry::make('nama_instansi')->label('Sekolah/Kampus'),
                                ])->columns(2),
                        ])->columnSpan(['default' => 3, 'lg' => 2]),

                        // KOLOM KANAN (Profil & Akun)
                        InfolistGroup::make([
                            InfolistSection::make('Profil Akun')
                                ->icon('heroicon-o-user')
                                ->schema([
                                    TextEntry::make('name')
                                        ->label('Nama')
                                        ->weight('bold')
                                        ->size(TextEntry\TextEntrySize::Large),
                                    TextEntry::make('email')
                                        ->icon('heroicon-m-envelope')
                                        ->copyable(),
                                    TextEntry::make('phone')
                                        ->label('WhatsApp')
                                        ->icon('heroicon-m-phone')
                                        ->url(fn($state) => 'https://wa.me/' . preg_replace('/^0/', '62', $state), true)
                                        ->color('success'),
                                    InfolistGrid::make(2)->schema([
                                        TextEntry::make('role')->badge()->color('warning'),
                                        TextEntry::make('status')->badge(),
                                    ]),
                                    TextEntry::make('created_at')
                                        ->label('Terdaftar')
                                        ->since()
                                        ->size(TextEntry\TextEntrySize::Small)
                                        ->color('gray'),
                                ]),

                            InfolistSection::make('Akses SiHalal')
                                ->icon('heroicon-o-key')
                                ->schema([
                                    TextEntry::make('akun_halal')->label('Username')->copyable()->weight('medium'),
                                    TextEntry::make('pass_akun_halal')->label('Password')->copyable()->fontFamily('mono')->color('danger'),
                                ]),
                        ])->columnSpan(['default' => 3, 'lg' => 1]),
                    ]),

                // ========================================================
                // BAGIAN BAWAH: DOKUMEN (Menggunakan Route Proxy)
                // ========================================================
                InfolistSection::make('Berkas Dokumen Pendamping')
                    ->icon('heroicon-o-folder-open')
                    ->visible(fn($record) => $record->role === 'pendamping')
                    ->schema([
                        // Panggil helper function yang kita buat di bawah
                        self::getProxyImageEntry('file_pas_foto', 'Pas Foto'),
                        self::getProxyImageEntry('file_buku_rekening', 'Buku Rekening'),
                        self::getProxyImageEntry('file_ktp', 'KTP'),
                        self::getProxyImageEntry('file_ijazah', 'Ijazah'),
                    ])
                    ->columns([
                        'default' => 1,
                        'sm' => 2,
                        'xl' => 4,
                    ]),
            ]);
    }

    /**
     * Helper untuk menampilkan Gambar via Proxy Route di Infolist
     * Menggunakan TextEntry + HTML agar lebih ringan dan fleksibel.
     */
    protected static function getProxyImageEntry(string $field, string $label): TextEntry
    {
        return TextEntry::make($field)
            ->label($label)
            ->formatStateUsing(fn($state) => empty($state) ? '-' : new HtmlString("
                <div class='relative group overflow-hidden rounded-lg border border-gray-200 shadow-sm bg-gray-50'>
                    <img src='" . route('drive.image', ['path' => $state]) . "' 
                         alt='$label' 
                         class='w-full h-48 object-cover transition-transform duration-500 group-hover:scale-105' 
                         loading='lazy'>
                    
                    <a href='" . route('drive.image', ['path' => $state]) . "' 
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

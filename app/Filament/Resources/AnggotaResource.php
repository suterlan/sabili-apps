<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AnggotaResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth; // Sudah ada
use Illuminate\Support\Facades\Hash; // <--- INI YANG KURANG TADI
use Illuminate\Support\Str;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class AnggotaResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationLabel = 'Anggota';
    protected static ?string $modelLabel = 'Anggota';
    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // --- SECTION 1: DATA DIRI UTAMA ---
                Forms\Components\Section::make('Informasi Anggota')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Lengkap')
                            ->required()
                            ->live(onBlur: true) // Agar nama folder update saat ketik nama
                            ->maxLength(255),

                        Forms\Components\TextInput::make('phone')
                            ->label('No HP / WA')
                            ->tel()
                            ->required(),

                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email(),

                        Forms\Components\TextInput::make('phone')
                            ->label('No HP / WA')
                            ->tel()
                            ->required(),

                        Forms\Components\Textarea::make('address')
                            ->label('Alamat KTP')
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('alamat_domisili')
                            ->label('Alamat Domisili')
                            ->columnSpanFull(),
                    ])->columns(2),

                // --- SECTION 2: DOKUMEN & FILE ---
                Forms\Components\Section::make('Berkas Dokumen Anggota')
                    ->schema([

                        // 1. PAS FOTO
                        Forms\Components\FileUpload::make('file_pas_foto')
                            ->label('Pas Foto')
                            ->disk('google')
                            // LOGIKA FOLDER BARU:
                            ->directory(
                                fn($get) =>
                                'dokumen_anggota_' . Str::slug(Auth::user()->name) . '_' . Auth::id() .
                                    '/' . Str::slug($get('name') ?? 'temp') . '/foto'
                            )
                            ->image()
                            ->avatar()
                            ->imageEditor()
                            ->circleCropper()
                            ->maxSize(2048)
                            ->visibility('private')
                            ->downloadable()
                            ->required(),

                        // 2. KTP
                        Forms\Components\FileUpload::make('file_ktp')
                            ->label('Scan KTP')
                            ->disk('google')
                            ->directory(
                                fn($get) =>
                                'dokumen_anggota_' . Str::slug(Auth::user()->name) . '_' . Auth::id() .
                                    '/' . Str::slug($get('name') ?? 'temp') . '/ktp'
                            )
                            ->visibility('private')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'application/pdf'])
                            ->maxSize(2048)
                            ->downloadable(),

                        // 3. IJAZAH
                        Forms\Components\FileUpload::make('file_ijazah')
                            ->label('Scan Ijazah')
                            ->disk('google')
                            ->directory(
                                fn($get) =>
                                'dokumen_anggota_' . Str::slug(Auth::user()->name) . '_' . Auth::id() .
                                    '/' . Str::slug($get('name') ?? 'temp') . '/ijazah'
                            )
                            ->visibility('private')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'application/pdf'])
                            ->maxSize(2048)
                            ->downloadable(),

                        // 4. BUKU REKENING
                        Forms\Components\FileUpload::make('file_buku_rekening')
                            ->label('Foto Buku Rekening')
                            ->disk('google')
                            ->directory(
                                fn($get) =>
                                'dokumen_anggota_' . Str::slug(Auth::user()->name) . '_' . Auth::id() .
                                    '/' . Str::slug($get('name') ?? 'temp') . '/rekening'
                            )
                            ->visibility('private')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'application/pdf'])
                            ->maxSize(2048)
                            ->downloadable(),

                    ])->columns(2),

                // --- SECTION 3: DATA TAMBAHAN (Bank & Pendidikan) ---
                Forms\Components\Section::make('Data Pendukung')
                    ->collapsed() // Default tertutup biar rapi
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('nama_bank')
                                ->label('Nama Bank')
                                ->placeholder('Contoh: BRI / BCA'),

                            Forms\Components\TextInput::make('nomor_rekening')
                                ->label('Nomor Rekening')
                                ->numeric(),
                        ]),

                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\Select::make('pendidikan_terakhir')
                                ->options([
                                    'SD' => 'SD',
                                    'SMP' => 'SMP',
                                    'SMA' => 'SMA',
                                    'D3' => 'D3',
                                    'S1' => 'S1',
                                    'S2' => 'S2'
                                ]),

                            Forms\Components\TextInput::make('nama_instansi')
                                ->label('Sekolah / Kampus'),
                        ]),
                    ]),

                // --- HIDDEN FIELDS ---
                Forms\Components\Hidden::make('pendamping_id')->default(fn() => \Illuminate\Support\Facades\Auth::id()),
                Forms\Components\Hidden::make('role')->default('member'),
                Forms\Components\Hidden::make('password')
                    ->default(fn() => \Illuminate\Support\Facades\Hash::make('12345678')) // Default password anggota
                    // KUNCI PERBAIKANNYA DI SINI:
                    // Hanya simpan (dehydrate) password ke database saat proses 'create'.
                    // Saat proses 'edit', field ini akan diabaikan (password lama tetap aman).
                    ->dehydrated(fn(string $context): bool => $context === 'create'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Anggota')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('phone')
                    ->label('No HP'),

                // KOLOM BARU: PENDAMPING
                // Menampilkan nama pendamping dari relasi
                Tables\Columns\TextColumn::make('pendamping.name')
                    ->label('Pendamping')
                    ->badge() // Opsional: Pakai style badge biar beda
                    ->color('warning')
                    ->sortable()
                    ->searchable()
                    // Kolom ini HANYA MUNCUL untuk Superadmin & Admin
                    // Pendamping tidak perlu lihat (karena pasti namanya sendiri)
                    ->visible(fn() => Auth::user()->isSuperAdmin() || Auth::user()->isAdmin()),

                Tables\Columns\TextColumn::make('created_at')
                    ->date()
                    ->label('Terdaftar'),
            ])
            ->filters([
                // Filter Opsional: Agar Admin bisa memfilter list berdasarkan Pendamping
                Tables\Filters\SelectFilter::make('pendamping_id')
                    ->label('Filter per Pendamping')
                    ->relationship('pendamping', 'name', function (Builder $query) {
                        return $query->where('role', 'pendamping');
                    })
                    ->searchable()
                    ->preload()
                    ->visible(fn() => Auth::user()->isSuperAdmin() || Auth::user()->isAdmin()),
            ])
            ->actions([
                // --- TOMBOL CETAK KARTU ---
                Tables\Actions\Action::make('cetak_kartu')
                    ->label('Cetak Kartu')
                    ->icon('heroicon-o-identification') // Icon kartu ID
                    ->color('primary')
                    // Arahkan ke Route yang tadi dibuat
                    ->url(fn(User $record) => route('cetak.kartu', $record))
                    ->openUrlInNewTab(), // Buka di tab baru biar tidak close dashboard

                Tables\Actions\EditAction::make(),
                // Superadmin/Admin boleh delete atau tidak? 
                // Jika tidak boleh delete, tambahkan ->visible(...) di sini juga.
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                    // TAMBAHKAN INI: Tombol Export Excel
                    ExportBulkAction::make()
                        ->label('Export Excel')
                        ->icon('heroicon-o-arrow-down-tray'),
                ]),
            ])
            ->headerActions([
                ExportAction::make()
                    ->label('Download Laporan')
                    ->color('success')
                    ->visible(fn() => Auth::user()->isSuperAdmin() || Auth::user()->isAdmin()), // Hanya admin yg boleh download
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // 1. Pastikan cuma ambil member
        $query->where('role', 'member');

        $user = Auth::user();

        // 2. Jika Superadmin / Admin -> Lihat Semua
        if ($user && ($user->isSuperAdmin() || $user->isAdmin())) {
            return $query;
        }

        // 3. Jika Pendamping -> Hanya lihat miliknya
        return $query->where('pendamping_id', Auth::id());
    }

    public static function canViewAny(): bool
    {
        // Menu ini HANYA boleh diakses oleh:
        // 1. Superadmin
        // 2. Admin
        // 3. Pendamping
        // (Member biasa TIDAK BOLEH akses, meskipun mereka tidak bisa login panel, 
        //  ini adalah lapisan keamanan ganda).

        $user = Auth::user();

        return $user && (
            $user->isSuperAdmin() ||
            $user->isAdmin() ||
            $user->isPendamping()
        );
    }

    public static function canCreate(): bool
    {
        $user = Auth::user();

        // 1. Jika bukan pendamping (misal Admin), tidak bisa create (sesuai request sebelumnya)
        if (! $user->isPendamping()) {
            return false;
        }

        // 2. LOGIC BARU: Cek status verifikasi
        // Hanya boleh create jika statusnya 'verified'
        return $user->isVerified();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAnggotas::route('/'),
            'create' => Pages\CreateAnggota::route('/create'),
            'edit' => Pages\EditAnggota::route('/{record}/edit'),
        ];
    }
}

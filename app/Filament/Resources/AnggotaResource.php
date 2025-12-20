<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AnggotaResource\Pages;
use App\Models\Pengajuan;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\Placeholder;
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
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Support\Collection;
use Laravolt\Indonesia\Models\Province;
use Laravolt\Indonesia\Models\City;
use Laravolt\Indonesia\Models\District;
use Laravolt\Indonesia\Models\Village;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class AnggotaResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationLabel = 'Pelaku Usaha';
    protected static ?string $modelLabel = 'Pelaku Usaha';
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $pluralModelLabel = 'Pelaku Usaha';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // --- SECTION 1: DATA DIRI PELAKU USAHA ---
                Forms\Components\Section::make('Data Diri Pelaku Usaha')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Pelaku Usaha')
                            ->required()
                            ->live(onBlur: true) // Agar nama folder update otomatis
                            ->maxLength(255),

                        Forms\Components\TextInput::make('nik')
                            ->label('NIK (Nomor Induk Kependudukan)')
                            ->numeric()
                            ->length(16)
                            ->required(),

                        Forms\Components\DatePicker::make('tanggal_lahir')
                            ->label('Tanggal Lahir')
                            ->required(),

                        Forms\Components\TextInput::make('phone')
                            ->label('Nomor HP / WA')
                            ->tel()
                            ->required(),

                        // Email & Password Email
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('email')
                                ->label('Alamat Email (Opsional)')
                                ->email(),

                            Forms\Components\TextInput::make('pass_email')
                                ->label('Password Email')
                                ->password()
                                ->revealable(), // Bisa diintip passwordnya
                        ]),
                    ])->columns(2),

                // --- SECTION 2: ALAMAT LENGKAP ---
                Forms\Components\Section::make('Alamat Domisili')
                    ->schema([
                        Forms\Components\Textarea::make('address')
                            ->label('Alamat Jalan / RT RW')
                            ->columnSpanFull()
                            ->required(),

                        // --- CHAINING DROPDOWN WILAYAH ---
                        Forms\Components\Grid::make(2)->schema([

                            // 1. PROVINSI
                            Forms\Components\Select::make('provinsi')
                                ->label('Provinsi')
                                ->options(Province::pluck('name', 'code')) // Ambil Nama & Kode
                                ->searchable()
                                ->live() // Aktifkan interaksi real-time
                                ->afterStateUpdated(function (Set $set) {
                                    // Reset anak-anaknya jika provinsi berubah
                                    $set('kabupaten', null);
                                    $set('kecamatan', null);
                                    $set('desa', null);
                                })
                                ->required(),

                            // 2. KABUPATEN / KOTA
                            Forms\Components\Select::make('kabupaten')
                                ->label('Kabupaten / Kota')
                                ->options(function (Get $get) {
                                    $provinsiCode = $get('provinsi');
                                    if (!$provinsiCode) {
                                        return Collection::empty();
                                    }
                                    // Ambil Kota berdasarkan Kode Provinsi
                                    return City::where('province_code', $provinsiCode)->pluck('name', 'code');
                                })
                                ->searchable()
                                ->live()
                                ->afterStateUpdated(function (Set $set) {
                                    $set('kecamatan', null);
                                    $set('desa', null);
                                })
                                ->required(),

                            // 3. KECAMATAN
                            Forms\Components\Select::make('kecamatan')
                                ->label('Kecamatan')
                                ->options(function (Get $get) {
                                    $kabupatenCode = $get('kabupaten');
                                    if (!$kabupatenCode) {
                                        return Collection::empty();
                                    }
                                    return District::where('city_code', $kabupatenCode)->pluck('name', 'code');
                                })
                                ->searchable()
                                ->live()
                                ->afterStateUpdated(function (Set $set) {
                                    $set('desa', null);
                                })
                                ->required(),

                            // 4. DESA / KELURAHAN
                            Forms\Components\Select::make('desa')
                                ->label('Desa / Kelurahan')
                                ->options(function (Get $get) {
                                    $kecamatanCode = $get('kecamatan');
                                    if (!$kecamatanCode) {
                                        return Collection::empty();
                                    }
                                    return Village::where('district_code', $kecamatanCode)->pluck('name', 'code');
                                })
                                ->searchable()
                                ->required(),
                        ]),
                    ]),

                // --- SECTION 3: DATA USAHA ---
                Forms\Components\Section::make('Data Legalitas & Usaha')
                    ->schema([
                        Forms\Components\TextInput::make('merk_dagang')
                            ->label('Merk / Jenis Dagangan')
                            ->required(),

                        Forms\Components\TextInput::make('nomor_nib')
                            ->label('Nomor NIB'),

                        Forms\Components\TextInput::make('mitra_halal')
                            ->default('SABILI')
                            ->readOnly(),
                    ])->columns(2),

                // --- SECTION 4: UPLOAD DOKUMEN ---
                Forms\Components\Section::make('Berkas Dokumen & Foto')
                    ->description('Format: JPG/PNG. Maksimal 8MB per file. Nama file akan otomatis dirapikan.')
                    ->schema([

                        // 1. COMPONENT UNTUK PREVIEW (Menampilkan Gambar Saat Ini)
                        Placeholder::make('preview_ktp')
                            ->label('Preview KTP Saat Ini')
                            ->content(function ($get) {
                                // Ambil datanya
                                $filePath = $get('file_ktp');

                                // Panggil fungsi (sekarang fungsi ini sudah pintar menangani array/string)
                                $base64 = self::getBase64Image($filePath);

                                // Jika ada gambar, tampilkan. Jika null, tampilkan teks.
                                if ($base64) {
                                    return new HtmlString('
                                        <div class="w-full flex justify-center p-4 bg-gray-100 rounded-lg border border-gray-300">
                                            <img src="' . $base64 . '" 
                                                class="max-h-64 rounded-md shadow-sm object-contain" 
                                                alt="Preview KTP">
                                        </div>
                                    ');
                                } else {
                                    return new HtmlString('<span class="text-gray-500 italic">Belum ada foto atau foto tidak dapat dimuat.</span>');
                                }
                            }),

                        // 1. FILE KTP
                        Forms\Components\FileUpload::make('file_ktp')
                            ->label('Ganti/Upload KTP')
                            ->helperText('Biarkan kosong jika tidak ingin mengubah foto.')
                            ->disk('google')
                            ->visibility('private')
                            ->image()

                            // Direktori: dokumen_anggota_budi/agus
                            ->directory(fn($get) => 'dokumen_anggota_' . Str::slug(Auth::user()->name) . '/' . Str::slug($get('name') ?? 'temp'))
                            // Rename: ktp_agus_172812.jpg
                            ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, $get) {
                                $name = Str::slug($get('name') ?? 'tanpa-nama');
                                return 'ktp_' . $name . '_' . time() . '.' . $file->getClientOriginalExtension();
                            })
                            // Optimasi
                            ->imageResizeMode('cover')
                            ->imageResizeTargetWidth('1024')
                            ->maxSize(8192)
                            ->downloadable()
                            // Wajib diisi HANYA saat Buat Baru. Saat Edit, boleh kosong.
                            ->required(fn($livewire) => $livewire instanceof CreateRecord),

                        // 2. Preview Foto NIB
                        Placeholder::make('preview_nib')
                            ->label('Preview Foto NIB Saat Ini')
                            ->content(function ($get) {
                                $filePath = $get('file_foto_nib');
                                $base64 = self::getBase64Image($filePath);

                                if ($base64) {
                                    return new HtmlString('
                                        <div class="w-full flex justify-center p-4 bg-gray-100 rounded-lg border border-gray-300">
                                            <img src="' . $base64 . '" 
                                                class="max-h-64 rounded-md shadow-sm object-contain" 
                                                alt="Preview Foto NIB">
                                        </div>
                                    ');
                                } else {
                                    return new HtmlString('<span class="text-gray-500 italic">Belum ada foto atau foto tidak dapat dimuat.</span>');
                                }
                            }),
                        // 2. FOTO NIB
                        Forms\Components\FileUpload::make('file_foto_nib')
                            ->label('Ganti/Upload Foto Dokumen NIB')
                            ->helperText('Biarkan kosong jika tidak ingin mengubah foto.')
                            ->disk('google')
                            ->image()
                            ->visibility('private')
                            ->directory(fn($get) => 'dokumen_anggota_' . Str::slug(Auth::user()->name) . '/' . Str::slug($get('name') ?? 'temp'))
                            // Rename: nib_agus_172812.jpg
                            ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, $get) {
                                $name = Str::slug($get('name') ?? 'tanpa-nama');
                                return 'nib_' . $name . '_' . time() . '.' . $file->getClientOriginalExtension();
                            })
                            ->imageResizeMode('cover')
                            ->imageResizeTargetWidth('1024')
                            ->maxSize(8192)
                            ->downloadable(),

                        // 3. Preview FOTO PRODUK
                        Placeholder::make('preview_foto_produk')
                            ->label('Preview Foto Produk Saat Ini')
                            ->content(function ($get) {
                                $filePath = $get('file_foto_produk');
                                $base64 = self::getBase64Image($filePath);

                                if ($base64) {
                                    return new HtmlString('
                                        <div class="w-full flex justify-center p-4 bg-gray-100 rounded-lg border border-gray-300">
                                            <img src="' . $base64 . '" 
                                                class="max-h-64 rounded-md shadow-sm object-contain" 
                                                alt="Preview Foto Produk">
                                        </div>
                                    ');
                                } else {
                                    return new HtmlString('<span class="text-gray-500 italic">Belum ada foto atau foto tidak dapat dimuat.</span>');
                                }
                            }),
                        // 3. FOTO PRODUK
                        Forms\Components\FileUpload::make('file_foto_produk')
                            ->label('Ganti/Upload Foto Produk')
                            ->helperText('Biarkan kosong jika tidak ingin mengubah foto.')
                            ->disk('google')
                            ->image()
                            ->visibility('private')
                            ->directory(fn($get) => 'dokumen_anggota_' . Str::slug(Auth::user()->name) . '/' . Str::slug($get('name') ?? 'temp'))
                            // Rename: produk_agus_172812.jpg
                            ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, $get) {
                                $name = Str::slug($get('name') ?? 'tanpa-nama');
                                return 'produk_' . $name . '_' . time() . '.' . $file->getClientOriginalExtension();
                            })
                            ->imageResizeTargetWidth('800')
                            ->maxSize(8192)
                            ->downloadable()
                            ->required(fn($livewire) => $livewire instanceof CreateRecord),

                        // 4. Preview FOTO TEMPAT USAHA
                        Placeholder::make('preview_foto_usaha')
                            ->label('Preview Foto Tempat Usaha Saat Ini')
                            ->content(function ($get) {
                                $filePath = $get('file_foto_usaha');
                                $base64 = self::getBase64Image($filePath);

                                if ($base64) {
                                    return new HtmlString('
                                        <div class="w-full flex justify-center p-4 bg-gray-100 rounded-lg border border-gray-300">
                                            <img src="' . $base64 . '" 
                                                class="max-h-64 rounded-md shadow-sm object-contain" 
                                                alt="Preview Foto Tempat Usaha">
                                        </div>
                                    ');
                                } else {
                                    return new HtmlString('<span class="text-gray-500 italic">Belum ada foto atau foto tidak dapat dimuat.</span>');
                                }
                            }),
                        // 4. FOTO TEMPAT USAHA
                        Forms\Components\FileUpload::make('file_foto_usaha')
                            ->label('Ganti/Upload Foto Tempat Usaha (Tampak Depan)')
                            ->helperText('Biarkan kosong jika tidak ingin mengubah foto.')
                            ->disk('google')
                            ->image()
                            ->visibility('private')
                            ->directory(fn($get) => 'dokumen_anggota_' . Str::slug(Auth::user()->name) . '/' . Str::slug($get('name') ?? 'temp'))
                            // Rename: tempat_usaha_agus_172812.jpg
                            ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, $get) {
                                $name = Str::slug($get('name') ?? 'tanpa-nama');
                                return 'tempat_usaha_' . $name . '_' . time() . '.' . $file->getClientOriginalExtension();
                            })
                            ->imageResizeTargetWidth('1024')
                            ->maxSize(8192)
                            ->downloadable()
                            ->required(fn($livewire) => $livewire instanceof CreateRecord),

                        // 5. Preview FOTO BERSAMA PENDAMPING
                        Placeholder::make('preview_foto_bersama')
                            ->label('Preview Foto Pelaku Usaha dengan Pendamping Saat Ini')
                            ->content(function ($get) {
                                $filePath = $get('file_foto_bersama');
                                $base64 = self::getBase64Image($filePath);

                                if ($base64) {
                                    return new HtmlString('
                                        <div class="w-full flex justify-center p-4 bg-gray-100 rounded-lg border border-gray-300">
                                            <img src="' . $base64 . '" 
                                                class="max-h-64 rounded-md shadow-sm object-contain" 
                                                alt="Preview Foto Bersama Pendamping">
                                        </div>
                                    ');
                                } else {
                                    return new HtmlString('<span class="text-gray-500 italic">Belum ada foto atau foto tidak dapat dimuat.</span>');
                                }
                            }),
                        // 5. FOTO BERSAMA PENDAMPING
                        Forms\Components\FileUpload::make('file_foto_bersama')
                            ->label('Ganti/Upload Foto Pelaku Usaha dgn Pendamping')
                            ->helperText('Biarkan kosong jika tidak ingin mengubah foto.')
                            ->disk('google')
                            ->image()
                            ->visibility('private')
                            ->directory(fn($get) => 'dokumen_anggota_' . Str::slug(Auth::user()->name) . '/' . Str::slug($get('name') ?? 'temp'))
                            // Rename: foto_bersama_agus_172812.jpg
                            ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, $get) {
                                $name = Str::slug($get('name') ?? 'tanpa-nama');
                                return 'foto_bersama_' . $name . '_' . time() . '.' . $file->getClientOriginalExtension();
                            })
                            ->imageResizeTargetWidth('1024')
                            ->maxSize(8192)
                            ->downloadable()
                            ->required(fn($livewire) => $livewire instanceof CreateRecord),

                    ])->columns(2),

                // --- HIDDEN FIELDS ---
                Forms\Components\Hidden::make('pendamping_id')->default(fn() => Auth::id()),
                Forms\Components\Hidden::make('role')->default('member'),
                Forms\Components\Hidden::make('password')
                    ->default(fn() => Hash::make('12345678'))
                    ->dehydrated(fn(string $context): bool => $context === 'create'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Pelaku Usaha')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('merk_dagang')
                    ->label('Usaha/Merk')
                    ->searchable(),

                Tables\Columns\TextColumn::make('phone')
                    ->label('No HP')
                    ->toggleable(isToggledHiddenByDefault: true), // sembunyiin default

                // --- KOLOM DESA (Dikonversi dari Kode ke Nama) ---
                Tables\Columns\TextColumn::make('village.name')
                    ->label('Desa / Kel')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable()
                    ->searchable(),

                // --- KOLOM KECAMATAN (Dikonversi dari Kode ke Nama) ---
                Tables\Columns\TextColumn::make('district.name')
                    ->label('Kecamatan')
                    ->sortable()
                    ->searchable(),

                // Ini agar Pendamping tahu apakah orang ini sudah diajukan atau belum
                Tables\Columns\TextColumn::make('latestPengajuan.status_verifikasi')
                    ->label('Status')
                    ->badge()
                    ->placeholder('Belum Diajukan') // Jika null
                    ->color(fn($state) => match ($state) {
                        // Abu-abu
                        Pengajuan::STATUS_MENUNGGU => 'gray',

                        // Merah (Error/Masalah)
                        Pengajuan::STATUS_NIK_TERDAFTAR,
                        Pengajuan::STATUS_NIK_INVALID => 'danger',

                        // Kuning (Butuh Tindakan User)
                        Pengajuan::STATUS_UPLOAD_NIB,
                        Pengajuan::STATUS_UPLOAD_KK => 'warning',

                        // Biru (Proses Admin)
                        Pengajuan::STATUS_DIPROSES,
                        Pengajuan::STATUS_INVOICE => 'info',

                        // Hijau (Berhasil)
                        Pengajuan::STATUS_SERTIFIKAT,
                        Pengajuan::STATUS_SELESAI => 'success',

                        default => 'primary',
                    }),

                // KOLOM BARU: PENDAMPING
                // Menampilkan nama pendamping dari relasi
                Tables\Columns\TextColumn::make('pendamping.name')
                    ->label('Pendamping')
                    ->badge() // Opsional: Pakai style badge biar beda
                    ->color('warning')
                    ->sortable()
                    ->searchable()
                    // Kolom ini HANYA MUNCUL untuk Superadmin, Admin & Koordinator
                    // Pendamping tidak perlu lihat (karena pasti namanya sendiri)
                    ->visible(fn() => Auth::user()->isSuperAdmin() || Auth::user()->isAdmin() || Auth::user()->isKoordinator()),
            ])
            ->filters([
                // --- 1. FILTER STATUS (PENTING AGAR WIDGET BISA DIKLIK) ---
                \Filament\Tables\Filters\SelectFilter::make('status_verifikasi')
                    ->label('Status Verifikasi')
                    ->multiple() // Agar bisa filter banyak status sekaligus (misal: semua revisi)
                    ->options(Pengajuan::getStatusVerifikasiOptions())
                    ->query(function (Builder $query, array $data) {
                        // Logic khusus karena status ada di tabel relasi 'pengajuans', bukan di 'users'
                        if (!empty($data['values'])) {
                            $query->whereHas('latestPengajuan', function ($q) use ($data) {
                                $q->whereIn('status_verifikasi', $data['values']);
                            });
                        }
                    }),

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
                // Tombol View (Detail)
                Tables\Actions\ViewAction::make()
                    ->label('') // Opsional ganti label
                    ->color('info'),  // Opsional ganti warna

                Tables\Actions\EditAction::make()->label('')
                    ->hidden(fn() => auth()->user()->isKoordinator()),
                // Superadmin/Admin boleh delete atau tidak? 
                // Jika tidak boleh delete, tambahkan ->visible(...) di sini juga.
                Tables\Actions\DeleteAction::make()->label('')
                    ->hidden(fn() => auth()->user()->isKoordinator()),

                // --- ACTION AJUKAN VERIFIKASI ---
                Tables\Actions\Action::make('ajukan_verifikasi')
                    ->label('Ajukan')
                    ->icon('heroicon-m-paper-airplane')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Ajukan Verifikasi?')
                    ->modalDescription('Pastikan data pelaku usaha sudah diperbaiki dan lengkap sebelum diajukan ulang.')
                    ->modalSubmitActionLabel('Ya, Ajukan')

                    // LOGIC VISIBILITY:
                    // Tombol ini HANYA muncul jika:
                    // 1. Belum pernah diajukan sama sekali (latestPengajuan == null)
                    // 2. ATAU Pengajuan terakhir sudah selesai/ditolak (Boleh ajukan ulang)
                    // 3. HANYA untuk Pendamping yang bersangkutan dan superadmin/admin TIDAK BISA pakai tombol ini
                    ->visible(function (User $record) {
                        $currentUser = Auth::user();

                        // Sarat 3: Jika user BUKAN salah satu dari keduanya, sembunyikan tombol
                        if (! ($currentUser->isPendamping() || $currentUser->isSuperAdmin())) {
                            return false;
                        }

                        //ambil status pengajuan terakhir
                        $status = $record->latestPengajuan?->status_verifikasi;

                        // Syarat 1: Belum pernah diajukan (Status Null)
                        if (is_null($status)) {
                            return true;
                        }

                        // Syarat 2: Boleh ajukan ulang HANYA JIKA statusnya 'Selesai' atau 'Invalid' (Ditolak)
                        // Status lain seperti 'Menunggu', 'Diproses', 'Upload NIB' tidak boleh diajukan ulang (harus diselesaikan dulu)
                        return in_array($status, [
                            Pengajuan::STATUS_SELESAI,          // Boleh ajukan lagi kalau sudah selesai (misal perpanjangan)
                            Pengajuan::STATUS_NIK_INVALID,      // Gagal, perlu revisi
                            Pengajuan::STATUS_NIK_TERDAFTAR,    // Gagal
                            Pengajuan::STATUS_UPLOAD_NIB,       // Revisi dokumen
                            Pengajuan::STATUS_UPLOAD_KK,        // Revisi dokumen
                        ]);
                        // Catatan: Status 'Menunggu', 'Diproses', 'Invoice' TIDAK ADA di sini,
                        // jadi tombol akan hilang (hidden) agar tidak double submit.
                    })

                    // LOGIC ACTION:
                    ->action(function (User $record) {
                        // Kita buat PENGAJUAN BARU (Record baru)
                        // Agar tercatat di history dan masuk ke antrian paling belakang (atau sesuaikan kebijakan)
                        Pengajuan::create([
                            'user_id' => $record->id,
                            'pendamping_id' => auth()->id(),
                            'status_verifikasi' => Pengajuan::STATUS_MENUNGGU,
                            'created_at' => now(),
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Berhasil Diajukan')
                            ->body('Data telah masuk kembali ke antrian verifikasi Admin.')
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->hidden(fn() => auth()->user()->isKoordinator()),

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
        $user = Auth::user();

        // 1. Pastikan cuma ambil member
        $query->where('role', 'member');

        // 2. Jika Superadmin / Admin -> Lihat Semua
        if ($user && ($user->isSuperAdmin() || $user->isAdmin())) {
            return $query;
        }

        // 3. Pendamping: Hanya lihat anggota binaannya sendiri
        if ($user->isPendamping()) {
            return $query->where('pendamping_id', $user->id);
        }

        // 4. KOORDINATOR (Logika Baru)
        if ($user->isKoordinator()) {
            // Koordinator melihat anggota berdasarkan KECAMATAN
            // Anggota yang kecamatannya SAMA dengan kecamatan Koordinator
            return $query->where('kecamatan', $user->kecamatan);
        }

        return $query;
    }

    public static function canViewAny(): bool
    {
        // Menu ini HANYA boleh diakses oleh:
        // 1. Superadmin
        // 2. Admin
        // 3. Pendamping
        // 4. Koordinator
        // (Member biasa TIDAK BOLEH akses, meskipun mereka tidak bisa login panel, 
        //  ini adalah lapisan keamanan ganda).

        $user = Auth::user();

        return $user && (
            $user->isSuperAdmin() ||
            $user->isAdmin() ||
            $user->isPendamping() ||
            $user->isKoordinator()
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

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([

                // =========================================================
                // BAGIAN 1: DATA TEKS (Menggunakan Grid 2 Kolom)
                // =========================================================
                Group::make([

                    // KIRI: DATA PRIBADI
                    Section::make('Data Pribadi')
                        ->icon('heroicon-o-user')
                        ->schema([
                            TextEntry::make('name')
                                ->label('Nama Lengkap')
                                ->weight('bold')
                                ->size(TextEntry\TextEntrySize::Large),

                            TextEntry::make('nik')
                                ->label('NIK')
                                ->copyable()
                                ->icon('heroicon-m-identification'),

                            TextEntry::make('tanggal_lahir')
                                ->label('Tanggal Lahir')
                                ->date('d F Y'),

                            TextEntry::make('phone')
                                ->label('No. WhatsApp')
                                ->url(fn($state) => 'https://wa.me/' . preg_replace('/^0/', '62', $state), true)
                                ->color('success')
                                ->icon('heroicon-m-phone'),
                        ])->columnSpan(1), // Ambil 1 kolom

                    // KANAN: ALAMAT
                    Section::make('Alamat & Lokasi')
                        ->icon('heroicon-o-map-pin')
                        ->schema([
                            TextEntry::make('address')
                                ->label('Alamat')
                                ->columnSpanFull(),

                            TextEntry::make('province.name')->label('Provinsi'),
                            TextEntry::make('city.name')->label('Kab/Kota'),
                            TextEntry::make('district.name')->label('Kecamatan'),
                            TextEntry::make('village.name')->label('Desa/Kel'),
                        ])->columnSpan(1), // Ambil 1 kolom

                    // BAWAH: LEGALITAS (Full Width di dalam grid data)
                    Section::make('Legalitas & Usaha')
                        ->icon('heroicon-o-briefcase')
                        ->schema([
                            TextEntry::make('merk_dagang')
                                ->label('Merk Dagang')
                                ->badge()
                                ->color('info'),

                            TextEntry::make('nomor_nib')
                                ->label('Nomor NIB')
                                ->copyable(),

                            TextEntry::make('mitra_halal')
                                ->label('Mitra Halal')
                                ->badge()
                                ->color(fn(string $state): string => match ($state) {
                                    'YA' => 'success',
                                    default => 'gray',
                                }),

                            TextEntry::make('pendamping.name')
                                ->label('Pendamping')
                                ->icon('heroicon-m-user-group'),
                        ])
                        ->columns(4) // Isinya dibagi 4 agar memanjang kesamping
                        ->columnSpanFull(), // Section ini ambil lebar penuh

                ])
                    ->columns(2) // Layout utama data teks dibagi 2 (Kiri/Kanan)
                    ->columnSpanFull(), // PENTING: Paksa grup ini ambil lebar penuh layar


                // =========================================================
                // BAGIAN 2: DOKUMEN FOTO (Di Bawah Sendiri - 2 KOLOM)
                // =========================================================
                Section::make('Dokumen & Foto')
                    ->description('Klik ikon panah untuk membuka file asli.')
                    ->schema([
                        // 1. KTP
                        ImageEntry::make('file_ktp')
                            ->label('KTP')
                            ->disk(null)
                            ->state(fn($record) => self::getBase64Image($record->file_ktp))
                            ->extraImgAttributes(['class' => 'max-w-full h-auto max-h-72 object-contain rounded-lg shadow-md border border-gray-200'])
                            ->hintAction(self::getOpenAction('file_ktp')),

                        // 2. FOTO BERSAMA
                        ImageEntry::make('file_foto_bersama')
                            ->label('Foto Bersama')
                            ->disk(null)
                            ->state(fn($record) => self::getBase64Image($record->file_foto_bersama))
                            ->extraImgAttributes(['class' => 'max-w-full h-auto max-h-72 object-contain rounded-lg shadow-md border border-gray-200'])
                            ->hintAction(self::getOpenAction('file_foto_bersama')),

                        // 3. TEMPAT USAHA
                        ImageEntry::make('file_foto_usaha')
                            ->label('Tempat Usaha')
                            ->disk(null)
                            ->state(fn($record) => self::getBase64Image($record->file_foto_usaha))
                            ->extraImgAttributes(['class' => 'max-w-full h-auto max-h-72 object-contain rounded-lg shadow-md border border-gray-200'])
                            ->hintAction(self::getOpenAction('file_foto_usaha')),

                        // 4. FOTO PRODUK
                        ImageEntry::make('file_foto_produk')
                            ->label('Foto Produk')
                            ->disk(null)
                            ->state(fn($record) => self::getBase64Image($record->file_foto_produk))
                            ->extraImgAttributes(['class' => 'max-w-full h-auto max-h-72 object-contain rounded-lg shadow-md border border-gray-200'])
                            ->hintAction(self::getOpenAction('file_foto_produk')),

                        // 5. NIB
                        ImageEntry::make('file_foto_nib')
                            ->label('Dokumen NIB')
                            ->disk(null)
                            ->state(fn($record) => self::getBase64Image($record->file_foto_nib))
                            ->extraImgAttributes(['class' => 'max-w-full h-auto max-h-72 object-contain rounded-lg shadow-md border border-gray-200'])
                            ->hintAction(self::getOpenAction('file_foto_nib')),
                    ])
                    // --- UPDATE GRID DI SINI ---
                    ->columns([
                        'default' => 1, // HP tetap 1 kolom tumpuk
                        'md' => 2,      // Tablet & Desktop jadi 2 kolom
                    ])
                    ->columnSpanFull(),

            ]);
    }

    // --- HELPER FUNCTIONS AGAR KODE LEBIH BERSIH & RAPI ---
    // (Tambahkan fungsi ini di dalam Class Resource Anda, di bawah method infolist)

    protected static function getBase64Image($path)
    {
        // 1. Validasi Input
        if (! $path) return null;
        if (is_array($path)) $path = array_shift($path);
        if (! is_string($path)) return null;

        try {
            $disk = \Illuminate\Support\Facades\Storage::disk('google');

            if ($disk->exists($path)) {
                // Ambil konten raw file
                $content = $disk->get($path);

                // Ambil ekstensi
                $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

                // --- PERBAIKAN UTAMA DISINI (FIX PNG & JPG) ---
                // Jangan percaya 100% pada $disk->mimeType(), sering meleset di GDrive.
                // Kita tentukan manual berdasarkan ekstensi agar browser tidak bingung.
                $mime = match ($extension) {
                    'png' => 'image/png',
                    'jpg', 'jpeg' => 'image/jpeg',
                    'webp' => 'image/webp',
                    'gif' => 'image/gif',
                    default => $disk->mimeType($path) // Fallback ke deteksi driver
                };

                // --- LOGIKA KHUSUS HEIC (Tetap pertahankan) ---
                if ($extension === 'heic' || $mime === 'image/heic' || $mime === 'image/heif') {
                    if (extension_loaded('imagick')) {
                        try {
                            $imagick = new \Imagick();
                            $imagick->readImageBlob($content);
                            $imagick->setImageFormat('jpeg');
                            $content = $imagick->getImageBlob();
                            $mime = 'image/jpeg';
                            $imagick->clear();
                            $imagick->destroy();
                        } catch (\Exception $e) {
                            // Jika convert gagal, biarkan apa adanya (atau return null)
                        }
                    }
                }

                // Return string Base64 yang valid
                return 'data:' . $mime . ';base64,' . base64_encode($content);
            }
        } catch (\Exception $e) {
            // Log error jika perlu: Log::error($e->getMessage());
            return null;
        }

        return null;
    }

    protected static function getOpenAction($columnName)
    {
        return Action::make('open_' . $columnName)
            ->icon('heroicon-m-arrow-top-right-on-square')
            ->tooltip('Buka file asli')
            ->url(fn($record) => $record->$columnName ? Storage::disk('google')->url($record->$columnName) : null)
            ->openUrlInNewTab()
            ->visible(fn($record) => $record->$columnName);
    }
}

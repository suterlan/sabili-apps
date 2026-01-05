<?php

namespace App\Filament\Resources;

use App\Exports\TemplateAnggotaExport;
use App\Filament\Resources\AnggotaResource\Pages;
use App\Imports\ImportAnggota;
use App\Models\Pengajuan;
use App\Models\User;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Form;
use Filament\Forms\Get; // Sudah ada
use Filament\Forms\Set; // <--- INI YANG KURANG TADI
use Filament\Infolists\Components\Actions\Action;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Laravolt\Indonesia\Models\City;
use Laravolt\Indonesia\Models\District; // Import Wizard
use Laravolt\Indonesia\Models\Province;
use Laravolt\Indonesia\Models\Village;
use Maatwebsite\Excel\Facades\Excel;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class AnggotaResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationLabel = 'Pelaku Usaha';

    protected static ?string $modelLabel = 'Pelaku Usaha';

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $pluralModelLabel = 'Pelaku Usaha';

    protected static ?string $navigationGroup = 'Master Data';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                // WRAPPER UTAMA: WIZARD
                Wizard::make([

                    // --- STEP 1: DATA DIRI ---
                    Wizard\Step::make('Data Diri')
                        ->icon('heroicon-m-user')
                        ->description('Identitas Pemilik')
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->label('Nama Pelaku Usaha')
                                ->required()
                                ->live(onBlur: true)
                                ->maxLength(255),

                            Forms\Components\TextInput::make('nik')
                                ->label('NIK')
                                ->mask('9999999999999999') // Angka '9' artinya digit angka (0-9). Ulangi 16 kali.
                                ->placeholder('Masukkan 16 digit NIK')
                                ->required(),

                            Forms\Components\DatePicker::make('tanggal_lahir')
                                ->label('Tanggal Lahir')
                                ->required(),

                            Forms\Components\TextInput::make('phone')
                                ->label('Nomor HP / WA')
                                ->tel() // Memunculkan keyboard angka di HP
                                ->maxLength(13) // Batas wajar no HP Indonesia
                                ->minLength(10) // Minimal digit
                                ->placeholder('Contoh: 08123456789')

                                // --- PENTING: Script ini memblokir huruf/simbol saat mengetik ---
                                ->extraAttributes([
                                    'oninput' => "this.value = this.value.replace(/[^0-9]/g, '')",
                                ])
                                ->regex('/^(\+62|62|0)8[1-9][0-9]{6,10}$/')
                                ->validationAttribute('Nomor HP') // Supaya pesan errornya enak dibaca
                                ->required(),

                            Forms\Components\Grid::make(2)->schema([
                                Forms\Components\TextInput::make('email')
                                    ->label('Email (Opsional)')
                                    ->email()
                                    ->unique(table: 'users', column: 'email', ignoreRecord: true)
                                    ->helperText('Email harus unik.'),

                                Forms\Components\TextInput::make('pass_email')
                                    ->label('Password Email')
                                    ->password()
                                    ->revealable(),
                            ]),
                        ]),

                    // --- STEP 2: ALAMAT (Livewire Berat Disini) ---
                    Wizard\Step::make('Domisili')
                        ->icon('heroicon-m-map-pin')
                        ->description('Alamat Lengkap')
                        ->schema([
                            Forms\Components\Textarea::make('address')
                                ->label('Alamat Jalan / RT RW')
                                ->columnSpanFull()
                                ->required(),

                            Forms\Components\Grid::make(2)->schema([
                                // 1. PROVINSI
                                Forms\Components\Select::make('provinsi')
                                    ->label('Provinsi')
                                    ->options(Province::pluck('name', 'code'))
                                    ->searchable()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set) {
                                        $set('kabupaten', null);
                                        $set('kecamatan', null);
                                        $set('desa', null);
                                    })
                                    ->required(),

                                // 2. KABUPATEN
                                Forms\Components\Select::make('kabupaten')
                                    ->label('Kabupaten / Kota')
                                    ->options(function (Get $get) {
                                        $provinsiCode = $get('provinsi');
                                        if (! $provinsiCode) {
                                            return Collection::empty();
                                        }

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
                                        if (! $kabupatenCode) {
                                            return Collection::empty();
                                        }

                                        return District::where('city_code', $kabupatenCode)->pluck('name', 'code');
                                    })
                                    ->searchable()
                                    ->live()
                                    ->afterStateUpdated(fn (Set $set) => $set('desa', null))
                                    ->required(),

                                // 4. DESA
                                Forms\Components\Select::make('desa')
                                    ->label('Desa / Kelurahan')
                                    ->options(function (Get $get) {
                                        $kecamatanCode = $get('kecamatan');
                                        if (! $kecamatanCode) {
                                            return Collection::empty();
                                        }

                                        return Village::where('district_code', $kecamatanCode)->pluck('name', 'code');
                                    })
                                    ->searchable()
                                    ->required(),
                            ]),
                        ]),

                    // --- STEP 3: DATA USAHA ---
                    Wizard\Step::make('Usaha')
                        ->icon('heroicon-m-briefcase')
                        ->description('Info Legalitas')
                        ->schema([
                            Forms\Components\TextInput::make('merk_dagang')
                                ->label('Merk / Jenis Dagangan')
                                ->required(),

                            Forms\Components\TextInput::make('nomor_nib')
                                ->label('Nomor NIB'),

                            Forms\Components\TextInput::make('mitra_halal')
                                ->default('SABILI')
                                ->readOnly(),
                        ]),

                    // --- STEP 4: DOKUMEN (Berat Upload Disini) ---
                    Wizard\Step::make('Dokumen')
                        ->icon('heroicon-m-document-arrow-up')
                        ->description('Upload Foto')
                        ->schema([
                            Forms\Components\Section::make('Berkas Dokumen & Foto')
                                ->description('Otomatis dikompresi sistem. Maksimal 8MB per file.')
                                ->schema([

                                    // 1. KTP
                                    // Parameter ke-5 (isSmall) dan ke-6 (isRequired) pakai default
                                    self::getUploadGroup('file_ktp', 'KTP', 'ktp', $form),

                                    // 2. NIB (BISA PDF atau IMAGE)
                                    // Parameter 5: false (ukuran normal/tidak small)
                                    // Parameter 6: false (TIDAK WAJIB / OPTIONAL)
                                    // Param 7 (Allow PDF): TRUE <<-- Ini kuncinya
                                    self::getUploadGroup('file_foto_nib', 'NIB', 'nib', $form, false, false, true),

                                    // 3. PRODUK
                                    self::getUploadGroup('file_foto_produk', 'Produk', 'produk', $form, true),

                                    // 4. TEMPAT USAHA
                                    self::getUploadGroup('file_foto_usaha', 'Tempat Usaha', 'tempat_usaha', $form),

                                    // 5. BERSAMA PENDAMPING
                                    self::getUploadGroup('file_foto_bersama', 'Foto Bersama', 'foto_bersama', $form),

                                ])->columns(2),
                        ]),

                ]) // End Wizard
                    ->columnSpanFull() // Agar Wizard lebar penuh
                    ->skippable(false) // User harus isi urut (cegah error validasi)
                    ->persistStepInQueryString() // Agar kalau di-refresh tetap di step yang sama
                    // ->submitAction(new \Illuminate\Support\HtmlString('<button type="submit">Simpan</button>')) // Default sudah ada tombol submit di step akhir
                    // --- DISINI KITA PINDAHKAN LOGIKA TOMBOL SIMPAN ---
                    ->submitAction(
                        Action::make('simpan')
                            ->label('Simpan Data')
                            ->icon('heroicon-m-check')
                            ->color('primary') // Warna tombol
                            ->keyBindings(['mod+s']) // Shortcut Ctrl+S
                            ->submit('create') // Perintah untuk submit form

                            // --- CUSTOM LOADING SEPERTI PERMINTAAN ANDA ---
                            ->extraAttributes([
                                'class' => 'w-full', // Agar tombol terlihat penuh dan gagah (opsional)
                                'wire:loading.attr' => 'disabled',           // Matikan saat loading
                                'wire:loading.class' => 'opacity-50 cursor-wait', // Efek visual pudar
                                'wire:target' => 'create',                   // Target method
                            ])
                    ),

                // HIDDEN FIELDS (Diluar Wizard agar tetap ter-submit)
                Forms\Components\Hidden::make('pendamping_id')->default(fn () => Auth::id()),
                Forms\Components\Hidden::make('role')->default('member'),
                Forms\Components\Hidden::make('password')
                    ->default(fn () => Hash::make('12345678'))
                    ->dehydrated(fn (string $context): bool => $context === 'create'),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
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
                    ->color(fn ($state) => match ($state) {
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
                    ->searchable(),

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
                    ->visible(fn () => Auth::user()->isSuperAdmin() || Auth::user()->isAdmin() || Auth::user()->isKoordinator()),
            ])
            ->filters([
                // --- 1. FILTER STATUS (PENTING AGAR WIDGET BISA DIKLIK) ---
                \Filament\Tables\Filters\SelectFilter::make('status_verifikasi')
                    ->label('Status Verifikasi')
                    ->multiple() // Agar bisa filter banyak status sekaligus (misal: semua revisi)
                    ->options(Pengajuan::getStatusVerifikasiOptions())
                    ->query(function (Builder $query, array $data) {
                        // Logic khusus karena status ada di tabel relasi 'pengajuans', bukan di 'users'
                        if (! empty($data['values'])) {
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
                    ->visible(fn () => Auth::user()->isSuperAdmin() || Auth::user()->isAdmin()),

                Tables\Filters\Filter::make('wilayah_belum_lengkap')
                    ->label('Wilayah Belum Lengkap')
                    ->query(fn (Builder $query): Builder => $query->where(function ($q) {
                        // Cari yang provinsi ATAU kabupatennya kosong
                        $q->whereNull('provinsi')
                            ->orWhereNull('kabupaten')
                            ->orWhereNull('kecamatan')
                            ->orWhereNull('desa');
                    }))
                    ->indicator('Data Wilayah Kosong'),
            ])
            ->actions([
                // Tombol View (Detail)
                Tables\Actions\ViewAction::make()
                    ->label('') // Opsional ganti label
                    ->color('info'),  // Opsional ganti warna

                Tables\Actions\EditAction::make()->label('')
                    ->hidden(fn () => auth()->user()->isKoordinator()),
                // Superadmin/Admin boleh delete atau tidak?
                // Jika tidak boleh delete, tambahkan ->visible(...) di sini juga.
                Tables\Actions\DeleteAction::make()->label('')
                    ->hidden(fn () => auth()->user()->isKoordinator()),

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

                        // ambil status pengajuan terakhir
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
                            Pengajuan::STATUS_UPLOAD_ULANG_FOTO,    // Gagal
                            Pengajuan::STATUS_UPLOAD_NIB,       // Revisi dokumen
                            Pengajuan::STATUS_PENGAJUAN_DITOLAK,        // Revisi dokumen
                        ]);
                        // Catatan: Status 'Menunggu', 'Diproses', 'Invoice' TIDAK ADA di sini,
                        // jadi tombol akan hilang (hidden) agar tidak double submit.
                    })

                    // LOGIC ACTION:
                    ->action(function (User $record) {
                        // Kita buat PENGAJUAN BARU (Record baru)
                        // Agar tercatat di history dan masuk ke antrian paling belakang (atau sesuaikan kebijakan)

                        // Tentukan siapa pendampingnya
                        // Jika user login adalah Super Admin, gunakan pendamping asli user tersebut
                        // Jika bukan (berarti pendamping itu sendiri), gunakan ID yang login
                        $pendampingId = auth()->user()->isSuperAdmin()
                            ? $record->pendamping_id
                            : auth()->id();

                        Pengajuan::create([
                            'user_id' => $record->id,
                            'pendamping_id' => $pendampingId,
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
                        ->hidden(fn () => auth()->user()->isKoordinator()),

                    // TAMBAHKAN INI: Tombol Export Excel
                    ExportBulkAction::make()
                        ->label('Export Excel')
                        ->icon('heroicon-o-arrow-down-tray'),
                ]),
            ])
            ->headerActions([
                // GRUP TOMBOL IMPORT & EXPORT
                \Filament\Tables\Actions\ActionGroup::make([

                    // 1. ACTION DOWNLOAD TEMPLATE
                    \Filament\Tables\Actions\Action::make('download_template')
                        ->label('Download Template')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('info')
                        ->action(fn () => Excel::download(new TemplateAnggotaExport, 'Template_Import_Pelaku_Usaha.xlsx')),

                    // 2. ACTION IMPORT EXCEL
                    \Filament\Tables\Actions\Action::make('import_excel')
                        ->label('Import Data Excel')
                        ->icon('heroicon-o-arrow-up-tray')
                        ->color('success')
                        ->form([
                            \Filament\Forms\Components\Group::make()->schema([
                                Placeholder::make('note')
                                    ->label('Panduan Pengisian')
                                    ->content(new HtmlString("
                                        <div class='p-4 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-800'>
                                            <p class='font-bold mb-2 flex items-center gap-2'>
                                                <svg class='w-5 h-5' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'></path></svg>
                                                Instruksi Import:
                                            </p>
                                            <ul class='list-disc pl-5 space-y-1 text-blue-700'>
                                                <li>Pastikan menggunakan <b>Template Terbaru</b>.</li>
                                                <li><b>Sheet 1:</b> Isi data anggota.</li>
                                                <li><b>Sheet 2:</b> Lihat ID Pendamping, lalu copy ke Sheet 1 Kolom J.</li>
                                                <li><b>Sheet 3:</b> Lihat <b>ID Admin</b> untuk penugasan verifikasi.</li>
                                                <li><b>Pengajuan Otomatis:</b> Isi kolom (Ajukan) dengan angka <b>1</b>, dan isi (ID Admin) jika ingin langsung membuat pengajuan.</li>
                                                <li><b>Wilayah:</b> ketik <b>NAMA WILAYAH</b> (Contoh: <i>Jawa Barat, Bandung</i>). <br><span class='text-xs text-blue-600'>*Sistem otomatis mencari kodenya.</span></li>
                                                <li><b>Perhatikan:</b> Kabupaten dan Kota berbeda (Contoh: Kabupaten Bandung / Kota Bandung)</li>
                                                <li><b>Penting:</b> Jika nama wilayah salah ketik, data wilayah akan dikosongkan (NULL) dan harus diedit manual nanti.</li>
                                            </ul>
                                        </div>
                                    ")),

                                FileUpload::make('attachment')
                                    ->label('Upload File Excel')
                                    ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'])
                                    ->disk('local')
                                    ->directory('temp-imports')
                                    ->required()
                                    ->columnSpanFull(),
                            ]),
                        ])
                        ->action(function (array $data) {
                            // 1. Ambil path file menggunakan Storage Facade (Lebih Aman)
                            // Ini otomatis menyesuaikan path C:\laragon\... tanpa kita rakit manual
                            $filePath = Storage::disk('local')->path($data['attachment']);

                            // 2. Cek apakah file benar-benar ada sebelum import
                            if (! file_exists($filePath)) {
                                \Filament\Notifications\Notification::make()
                                    ->title('File Tidak Ditemukan')
                                    ->body('Sistem tidak dapat menemukan file di: '.$filePath)
                                    ->danger()
                                    ->send();

                                return;
                            }

                            try {
                                // 1. Inisialisasi Class Import ke dalam variabel
                                $import = new ImportAnggota;

                                // 2. Lakukan Import menggunakan object tersebut
                                Excel::import($import, $filePath);

                                // 3. Ambil Hasil Hitungan
                                $jumlahUser = $import->getUsersCount();
                                $jumlahPengajuan = $import->getPengajuanCount();

                                Notification::make()
                                    ->title('Import Selesai')
                                    ->body("Berhasil menambahkan <b>{$jumlahUser}</b> Pelaku Usaha baru dan membuat <b>{$jumlahPengajuan}</b> Pengajuan.")
                                    ->success()
                                    ->persistent() // Agar user sempat membacanya
                                    ->send();

                                // Hapus file temp
                                // 4. Hapus file setelah sukses (Gunakan Storage Facade juga)
                                Storage::disk('local')->delete($data['attachment']);

                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Gagal Import')
                                    ->body('Terjadi kesalahan: '.$e->getMessage())
                                    ->danger()
                                    ->persistent()
                                    ->send();
                            }
                        }),

                ])
                    ->label('Menu Import')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->color('primary')
                    ->visible(fn () => auth()->user()->isSuperAdmin()) // Sesuaikan dengan helper role Anda
                    ->button(),

                // TOMBOL EXPORT Excel
                ExportAction::make()
                    ->label('Export Data PU')
                    ->color('success')
                    ->visible(fn () => auth()->user()->isSuperAdmin() || auth()->user()->isAdmin())
                    ->exports([
                        ExcelExport::make()
                            // --- TAMBAHKAN INI (Filter Wajib) ---
                            ->modifyQueryUsing(function ($query) {
                                return $query->where('role', 'member');
                            })
                            ->withFilename('Data_Pelaku_Usaha_'.date('Y-m-d'))
                            ->withColumns([
                                Column::make('name')->heading('Nama Pelaku Usaha'), // Sesuaikan nama field DB
                                Column::make('nik')->heading('NIK')->formatStateUsing(fn ($state) => ' '.$state),
                                // Trik: Tambah spasi di depan agar Excel tidak mengubahnya jadi 3.23E+15
                                Column::make('tanggal_lahir')->heading('Tanggal Lahir')->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->format('d-m-Y') : '-'),
                                Column::make('phone')->heading('No. HP/WhatsApp'),

                                // Wilayah
                                Column::make('address')->heading('Alamat'),
                                Column::make('province.name')->heading('Provinsi'),
                                Column::make('city.name')->heading('Kabupaten'),
                                Column::make('district.name')->heading('Kecamatan'),
                                Column::make('district.name')->heading('Kecamatan'),
                                Column::make('village.name')->heading('Desa'),
                                Column::make('village.name')->heading('Desa'),

                                // Legalitas Usaha
                                Column::make('merk_dagang')->heading('Merek Dagang'),
                                Column::make('mitra_halal')->heading('Mitra Halal'),
                                Column::make('akun_halal')->heading('Username/Email Akun SiHalal'),
                                Column::make('pass_akun_halal')->heading('Password Akun SiHalal'),

                                Column::make('latestPengajuan.status_verifikasi')->heading('Status Verifikasi'),
                                // Jika ada relasi ke Pendamping
                                Column::make('pendamping.name')->heading('Nama Pendamping'),
                            ]),
                    ]),
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
                // BAGIAN 1: DATA TEKS (Sama seperti sebelumnya)
                // =========================================================
                Group::make([
                    // KIRI: DATA PRIBADI
                    Section::make('Data Pribadi')
                        ->icon('heroicon-o-user')
                        ->schema([
                            TextEntry::make('name')->label('Nama Lengkap')->weight('bold')->size(TextEntry\TextEntrySize::Large),
                            TextEntry::make('nik')->label('NIK')->copyable()->icon('heroicon-m-identification'),
                            TextEntry::make('tanggal_lahir')->label('Tanggal Lahir')->date('d F Y'),
                            TextEntry::make('phone')
                                ->label('No. WhatsApp')
                                ->url(fn ($state) => 'https://wa.me/'.preg_replace('/^0/', '62', $state), true)
                                ->color('success')
                                ->icon('heroicon-m-phone'),
                        ])->columnSpan(1),

                    // KANAN: ALAMAT
                    Section::make('Alamat & Lokasi')
                        ->icon('heroicon-o-map-pin')
                        ->schema([
                            TextEntry::make('address')->label('Alamat')->columnSpanFull(),
                            TextEntry::make('province.name')->label('Provinsi'),
                            TextEntry::make('city.name')->label('Kab/Kota'),
                            TextEntry::make('district.name')->label('Kecamatan'),
                            TextEntry::make('village.name')->label('Desa/Kel'),
                        ])->columnSpan(1),

                    // BAWAH: LEGALITAS
                    Section::make('Legalitas & Usaha')
                        ->icon('heroicon-o-briefcase')
                        ->schema([
                            TextEntry::make('merk_dagang')->label('Merk Dagang')->badge()->color('info'),
                            TextEntry::make('nomor_nib')->label('Nomor NIB')->copyable(),
                            TextEntry::make('mitra_halal')->label('Mitra Halal')->badge()
                                ->color(fn (string $state): string => match ($state) {
                                    'YA' => 'success',
                                    default => 'gray'
                                }),
                            TextEntry::make('pendamping.name')->label('Pendamping')->icon('heroicon-m-user-group'),
                        ])
                        ->columns(4)
                        ->columnSpanFull(),

                ])->columns(2)->columnSpanFull(),

                // =========================================================
                // BAGIAN 2: DOKUMEN FOTO (OPTIMIZED PROXY)
                // =========================================================
                Section::make('Dokumen & Foto')
                    ->description('Klik gambar atau ikon panah untuk melihat ukuran penuh.')
                    ->schema([

                        // 1. KTP
                        ImageEntry::make('file_ktp')
                            ->label('KTP')
                            ->disk(null) // Non-aktifkan disk agar tidak mencari file lokal
                            // STATE: Isi state langsung dengan URL Proxy
                            ->state(fn ($record) => $record->file_ktp ? route('drive.image', ['path' => $record->file_ktp]) : null)
                            // ACTION: Klik gambar buka tab baru
                            ->url(fn ($state) => $state)
                            ->openUrlInNewTab()
                            ->extraImgAttributes(['class' => 'max-w-full h-auto max-h-72 object-contain rounded-lg shadow-md border border-gray-200 bg-gray-50'])
                            // BUTTON POJOK: Download/Open
                            ->hintAction(
                                Action::make('open_ktp')
                                    ->icon('heroicon-o-arrow-top-right-on-square')
                                    ->url(fn ($record) => $record->file_ktp ? route('drive.image', ['path' => $record->file_ktp]) : null, true)
                            ),

                        // 2. FOTO BERSAMA
                        ImageEntry::make('file_foto_bersama')
                            ->label('Foto Bersama')
                            ->disk(null)
                            ->state(fn ($record) => $record->file_foto_bersama ? route('drive.image', ['path' => $record->file_foto_bersama]) : null)
                            ->url(fn ($state) => $state)
                            ->openUrlInNewTab()
                            ->extraImgAttributes(['class' => 'max-w-full h-auto max-h-72 object-contain rounded-lg shadow-md border border-gray-200 bg-gray-50'])
                            ->hintAction(
                                Action::make('open_bersama')
                                    ->icon('heroicon-o-arrow-top-right-on-square')
                                    ->url(fn ($record) => $record->file_foto_bersama ? route('drive.image', ['path' => $record->file_foto_bersama]) : null, true)
                            ),

                        // 3. TEMPAT USAHA
                        ImageEntry::make('file_foto_usaha')
                            ->label('Tempat Usaha')
                            ->disk(null)
                            ->state(fn ($record) => $record->file_foto_usaha ? route('drive.image', ['path' => $record->file_foto_usaha]) : null)
                            ->url(fn ($state) => $state)
                            ->openUrlInNewTab()
                            ->extraImgAttributes(['class' => 'max-w-full h-auto max-h-72 object-contain rounded-lg shadow-md border border-gray-200 bg-gray-50'])
                            ->hintAction(
                                Action::make('open_usaha')
                                    ->icon('heroicon-o-arrow-top-right-on-square')
                                    ->url(fn ($record) => $record->file_foto_usaha ? route('drive.image', ['path' => $record->file_foto_usaha]) : null, true)
                            ),

                        // 4. FOTO PRODUK
                        ImageEntry::make('file_foto_produk')
                            ->label('Foto Produk')
                            ->disk(null)
                            ->state(fn ($record) => $record->file_foto_produk ? route('drive.image', ['path' => $record->file_foto_produk]) : null)
                            ->url(fn ($state) => $state)
                            ->openUrlInNewTab()
                            ->extraImgAttributes(['class' => 'max-w-full h-auto max-h-72 object-contain rounded-lg shadow-md border border-gray-200 bg-gray-50'])
                            ->hintAction(
                                Action::make('open_produk')
                                    ->icon('heroicon-o-arrow-top-right-on-square')
                                    ->url(fn ($record) => $record->file_foto_produk ? route('drive.image', ['path' => $record->file_foto_produk]) : null, true)
                            ),

                        // 5. NIB
                        ImageEntry::make('file_foto_nib')
                            ->label('Dokumen NIB')
                            ->disk(null)
                            ->state(fn ($record) => $record->file_foto_nib ? route('drive.image', ['path' => $record->file_foto_nib]) : null)
                            ->url(fn ($state) => $state)
                            ->openUrlInNewTab()
                            ->extraImgAttributes(['class' => 'max-w-full h-auto max-h-72 object-contain rounded-lg shadow-md border border-gray-200 bg-gray-50'])
                            ->hintAction(
                                Action::make('open_nib')
                                    ->icon('heroicon-o-arrow-top-right-on-square')
                                    ->url(fn ($record) => $record->file_foto_nib ? route('drive.image', ['path' => $record->file_foto_nib]) : null, true)
                            ),

                    ])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ])
                    ->columnSpanFull(),
            ]);
    }

    // --- HELPER FUNCTION AGAR KODE RAPI (REUSABLE COMPONENT) ---
    // Saya memindahkan logika upload yang berulang ke fungsi ini agar schema form bersih
    public static function getUploadGroup($field, $label, $prefix, $form, $isSmall = false, $isRequired = true, $allowPdf = false)
    {
        return Forms\Components\Group::make([
            Forms\Components\Placeholder::make('preview_'.$prefix)
                ->hidden(fn ($record) => empty($record?->$field))
                ->content(fn ($record) => new \Illuminate\Support\HtmlString(
                    // Logic: Cek ekstensi file, jika PDF tampilkan icon, jika gambar tampilkan preview
                    (Str::endsWith($record->$field ?? '', '.pdf'))
                        ? "
                    <div class='mb-2 p-3 border rounded bg-gray-50 flex items-center gap-4'>
                         <div class='bg-red-100 text-red-600 p-2 rounded'>
                            <svg class='w-8 h-8' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 2H7a2 2 0 00-2 2v14a2 2 0 002 2z'></path></svg>
                         </div>
                        <div class='text-xs text-gray-500'>
                            <p class='font-bold text-gray-700'>Dokumen PDF</p>
                            <a href='".route('drive.image', ['path' => $record->$field ?? ''])."' target='_blank' class='text-primary-600 underline font-bold'>Download / Lihat PDF</a>
                        </div>
                    </div>
                    "
                        : "
                    <div class='mb-2 p-2 border rounded bg-gray-50 flex items-center gap-4'>
                        <img src='".route('drive.image', ['path' => $record->$field ?? ''])."' style='height: 80px; border-radius: 4px; object-fit: cover;' loading='lazy'>
                        <div class='text-xs text-gray-500'>
                            <p class='font-bold text-success-600'>✓ Terupload</p>
                            <a href='".route('drive.image', ['path' => $record->$field ?? ''])."' target='_blank' class='text-primary-600 underline'>Lihat Penuh</a>
                        </div>
                    </div>
                    "
                )),

            // --- COMPONENT UPLOAD ---
            Forms\Components\FileUpload::make($field)
                ->label(fn ($record) => empty($record?->$field) ? "Upload $label" : "Ganti $label")
                ->disk('google')
                ->visibility('private')
                ->fetchFileInformation(false)
                ->maxSize(8192) // 8MB
                ->uploadingMessage('Mengupload...')
                ->formatStateUsing(fn () => null)
                ->dehydrated(fn ($state) => filled($state))

                // 1. LOGIC DIREKTORI (Sesuai Permintaan)
                // Folder: dokumen_anggota_{nama_pendamping}/{nama_pelaku_usaha}
                ->directory(fn (Get $get) => 'dokumen_anggota_'.Str::slug(Auth::user()->name).'/'.Str::slug($get('name') ?? 'temp'))

                // 2. LOGIC NAMA FILE
                // File: prefix_nama-pelaku-usaha_timestamp.ext
                ->getUploadedFileNameForStorageUsing(fn ($file, Get $get) => $prefix.'_'.Str::slug($get('name') ?? 'tanpa-nama').'_'.time().'.'.$file->getClientOriginalExtension())

                // 3. LOGIC ALLOW PDF VS IMAGE ONLY
                ->when(
                    $allowPdf,
                    // Jika PDF diperbolehkan
                    fn ($component) => $component
                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'])
                        ->helperText('Boleh PDF atau Foto (JPG/PNG).'),
                    // Jika Hanya Gambar (Default)
                    fn ($component) => $component
                        ->image()
                        ->imageResizeTargetWidth($isSmall ? '800' : '1024')
                        ->helperText('Format JPG/PNG.')
                )

                // 4. LOGIC WAJIB (Hanya saat create)
                ->required(
                    fn ($livewire) => $isRequired && ($livewire instanceof \Filament\Resources\Pages\CreateRecord)
                ),
        ])->columnSpan(1);
    }
}

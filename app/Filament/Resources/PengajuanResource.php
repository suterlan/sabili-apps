<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PengajuanResource\Pages;
use App\Models\Pengajuan;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
            // Auto refresh setiap 10 detik agar jika ada antrian baru masuk/diklaim orang lain, tabel update
            ->poll('10s')

            // Agar admin tahu ini pengajuan punya siapa
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Pelaku Usaha')
                    ->searchable(),

                Tables\Columns\TextColumn::make('user.district.name')
                    ->label('Kecamatan')
                    ->sortable(),

                Tables\Columns\TextColumn::make('pendamping.name')
                    ->label('Pendamping')
                    ->color('gray'),

                Tables\Columns\TextColumn::make('status_verifikasi')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        // Merah (Error/Masalah) perlu revisi
                        Pengajuan::STATUS_NIK_INVALID,
                        Pengajuan::STATUS_UPLOAD_NIB,
                        Pengajuan::STATUS_UPLOAD_ULANG_FOTO,
                        Pengajuan::STATUS_PENGAJUAN_DITOLAK => 'danger',

                        // Kuning (Dalam proses)
                        Pengajuan::STATUS_MENUNGGU,
                        Pengajuan::STATUS_DIPROSES => 'warning',
                        // Biru (Masih dalam proses tapi selesai diverifikasi, menunggu sertifikat)
                        Pengajuan::STATUS_LOLOS_VERIFIKASI,
                        Pengajuan::STATUS_PENGAJUAN_DIKIRIM => 'info',

                        // Hijau (Berhasil, menunggu invoice)
                        Pengajuan::STATUS_SERTIFIKAT,
                        // invoice/tagihan muncul
                        Pengajuan::STATUS_INVOICE,
                        // semua proses selesai
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
                // LOGIC KLAIM TUGAS
                Tables\Actions\Action::make('claim_task')
                    ->label('Proses')
                    ->icon('heroicon-m-hand-raised')
                    ->color('primary')
                    ->visible(function (Pengajuan $record, $livewire) {
                        // --- ATURAN BARU ---
                        // 1. Jika Super Admin, tombol HILANG (Return False)
                        if (auth()->user()->isSuperAdmin()) {
                            return false;
                        }

                        // Syarat 2: Belum ada verifikator
                        $belumDiklaim = is_null($record->verificator_id);

                        // Syarat 3: BUKAN di tab 'semua'
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
                            ->body('Pengajuan ini telah masuk ke daftar "Proses"')
                            ->send();
                    }),

                // Action Detail pada Tabel
                \Filament\Tables\Actions\ViewAction::make()
                    ->label('Detail')
                    ->slideOver()
                    ->color('info')

                    ->visible(function (Pengajuan $record) {
                        // Syarat 1: User login adalah verifikatornya
                        $isMyTask = auth()->id() === $record->verificator_id || auth()->user()->isSuperAdmin();

                        return $isMyTask;
                    }),

                // AKSI BATALKAN KLAIM (UNCLAIM)
                Tables\Actions\Action::make('cancel_claim')
                    ->label('Batalkan')
                    ->icon('heroicon-m-arrow-uturn-left') // Ikon putar balik
                    ->color('danger') // Merah (Hati-hati)
                    ->requiresConfirmation()
                    ->modalHeading('Lepaskan Tugas?')
                    ->modalDescription(
                        fn(Pengajuan $record) => auth()->user()->isSuperAdmin()
                            ? 'Anda akan melepas tugas milik ' . ($record->verificator->name ?? 'Admin') . '. Data kembali ke antrian.'
                            : 'Tugas akan dikembalikan ke antrian umum.'
                    )->modalSubmitActionLabel('Ya, Lepaskan')

                    // --- LOGIKA SIAPA YANG BOLEH LIHAT ---
                    ->visible(function (Pengajuan $record, $livewire) {
                        $user = auth()->user();

                        // 1. SYARAT MUTLAK: Harus sudah ada yang klaim
                        if (is_null($record->verificator_id)) {
                            return false;
                        }

                        // 2. Jangan batalkan jika sudah SELESAI/SERTIFIKAT (Bahaya!)
                        if (in_array($record->status_verifikasi, [
                            Pengajuan::STATUS_SELESAI,
                            Pengajuan::STATUS_SERTIFIKAT,
                            Pengajuan::STATUS_INVOICE,
                        ])) {
                            return false;
                        }

                        // Ambil Tab yang sedang aktif
                        $activeTab = $livewire->activeTab ?? null;

                        // --- SKENARIO SUPER ADMIN ---
                        // Super Admin hanya bisa membatalkan lewat tab 'semua' (karena gak punya tab tugas_saya)
                        if ($user->isSuperAdmin()) {
                            return $activeTab === 'semua';
                        }

                        // --- SKENARIO ADMIN BIASA ---
                        // Admin hanya boleh membatalkan di tab 'proses'
                        // Dan pastikan itu tugas miliknya sendiri
                        if ($activeTab === 'proses') {
                            return $record->verificator_id === $user->id;
                        }

                        // Jika Admin melihat tab 'semua', sembunyikan tombol ini biar aman/bersih
                        return false;
                    })
                    // --- LOGIKA EKSEKUSI ---
                    ->action(function (Pengajuan $record) {
                        $oldVerificator = $record->verificator->name ?? 'Admin';

                        $record->update([
                            'verificator_id' => null, // Hapus pemilik
                            'status_verifikasi' => Pengajuan::STATUS_MENUNGGU, // Reset status ke awal
                        ]);

                        // Notifikasi beda pesan buat Super Admin
                        if (auth()->user()->isSuperAdmin()) {
                            \Filament\Notifications\Notification::make()
                                ->warning()
                                ->title('Force Unclaim Berhasil')
                                ->body("Tugas dari $oldVerificator telah dilepas ke antrian.")
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->warning()
                                ->title('Klaim Dibatalkan')
                                ->body('Tugas dikembalikan ke antrian umum.')
                                ->send();
                        }
                    }),

                // =========================================================
                // ACTION EDIT DATA ANGGOTA (PELAKU USAHA) (Email, Pass, NIB, File NIB, Akun Sihalal, Pass akun sihalal, merk dagang)
                // =========================================================
                Tables\Actions\Action::make('edit_user_data')
                    ->label('Edit')
                    ->icon('heroicon-m-pencil-square')
                    ->color('primary')
                    ->modalWidth('2xl')

                    // Tampilkan tombol ini hanya untuk Verifikator yang sedang memegang tugas ini
                    ->visible(function (Pengajuan $record, $livewire) {
                        $isMyTask = auth()->id() === $record->verificator_id;
                        $bukanTabHistory = isset($livewire->activeTab) && $livewire->activeTab !== 'semua';

                        return $isMyTask && $bukanTabHistory;
                    })
                    // 2. ISI DATA AWAL (PRE-FILL)
                    ->mountUsing(function (Forms\ComponentContainer $form, Pengajuan $record) {
                        $form->fill([
                            'merk_dagang' => $record->user->merk_dagang, // BARU
                            'email' => $record->user->email,
                            'pass_email' => $record->user->pass_email, // Tampilkan password yang bisa dibaca
                            'akun_halal' => $record->user->akun_halal, // BARU
                            'pass_akun_halal' => $record->user->pass_akun_halal, // BARU
                            'nomor_nib' => $record->user->nomor_nib,
                        ]);
                    })
                    ->form([
                        Forms\Components\Section::make('Informasi Usaha & Akun')
                            ->schema([
                                // --- MERK DAGANG (BARU) ---
                                Forms\Components\TextInput::make('merk_dagang')
                                    ->label('Merk Dagang')
                                    ->required()
                                    ->columnSpanFull(), // Agar lebar penuh

                                // --- GROUP AKUN EMAIL ---
                                Forms\Components\Group::make([
                                    // --- EMAIL ---
                                    Forms\Components\TextInput::make('email')
                                        ->label('Email Akun')
                                        ->email()
                                        ->required()
                                        ->unique('users', 'email', ignoreRecord: true, modifyRuleUsing: function ($rule, Pengajuan $record) {
                                            return $rule->ignore($record->user_id);
                                        }),

                                    // --- PASSWORD (PASS EMAIL) ---
                                    // Kita ubah pass_email, nanti di backend otomatis update password hash juga
                                    Forms\Components\TextInput::make('pass_email')
                                        ->label('Password / Pass Email')
                                        ->password()
                                        ->revealable()
                                        ->dehydrated(fn($state) => filled($state)),
                                ])->columns(2),

                                // --- GROUP AKUN SIHALAL (BARU) ---
                                Forms\Components\Group::make([
                                    Forms\Components\TextInput::make('akun_halal')
                                        ->label('Email/User SiHalal')
                                        ->email(),

                                    Forms\Components\TextInput::make('pass_akun_halal')
                                        ->label('Password SiHalal')
                                        ->password()
                                        ->revealable(),
                                ])->columns(2),
                            ]),

                        Forms\Components\Section::make('Dokumen NIB')
                            ->schema([
                                // --- NOMOR NIB ---
                                Forms\Components\TextInput::make('nomor_nib')
                                    ->label('Nomor NIB')
                                    ->numeric()
                                    ->required(),

                                // --- PREVIEW FILE (Persis seperti Create) ---
                                Forms\Components\Placeholder::make('preview_nib')
                                    ->label('Preview File Saat Ini')
                                    ->hidden(fn(Pengajuan $record) => empty($record->user->file_foto_nib))
                                    ->content(fn(Pengajuan $record) => new \Illuminate\Support\HtmlString(
                                        (Str::endsWith($record->user->file_foto_nib ?? '', '.pdf'))
                                            ?
                                            "<div class='mb-2 p-3 border rounded bg-gray-50 flex items-center gap-4'>
                                                <div class='bg-red-100 text-red-600 p-2 rounded'>
                                                    <svg class='w-8 h-8' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 2H7a2 2 0 00-2 2v14a2 2 0 002 2z'></path></svg>
                                                </div>
                                                <div class='text-xs text-gray-500'>
                                                    <p class='font-bold text-gray-700'>Dokumen PDF</p>
                                                    <a href='" . route('drive.image', ['path' => $record->user->file_foto_nib ?? '']) . "' target='_blank' class='text-primary-600 underline font-bold'>Download / Lihat PDF</a>
                                                </div>
                                            </div>"
                                            :
                                            "<div class='mb-2 p-2 border rounded bg-gray-50 flex items-center gap-4'>
                                                <img src='" . route('drive.image', ['path' => $record->user->file_foto_nib ?? '']) . "' style='height: 80px; border-radius: 4px; object-fit: cover;' loading='lazy'>
                                                <div class='text-xs text-gray-500'>
                                                    <p class='font-bold text-success-600'>✓ Terupload</p>
                                                    <a href='" . route('drive.image', ['path' => $record->user->file_foto_nib ?? '']) . "' target='_blank' class='text-primary-600 underline'>Lihat Penuh</a>
                                                </div>
                                            </div>"
                                    )),

                                // --- UPLOAD FILE NIB ---
                                Forms\Components\FileUpload::make('file_foto_nib')
                                    ->label(fn(Pengajuan $record) => $record->user->file_foto_nib ? 'Ganti Dokumen NIB' : 'Upload Dokumen NIB')
                                    ->helperText('Format: PDF atau JPG/PNG (Maks 5MB).')
                                    ->disk('google')
                                    ->visibility('private')
                                    ->fetchFileInformation(false)
                                    ->uploadingMessage('Mengupload...') // Feedback visual
                                    ->maxSize(5120) // 5MB
                                    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'])
                                    // OOptimasi
                                    ->imageResizeTargetWidth('1024')

                                    // --- PERBAIKAN KUNCI 1: HILANGKAN LOADING ---
                                    // Paksa visualnya kosong agar Filament tidak mencoba fetch gambar dari GDrive ke dropzone
                                    ->formatStateUsing(fn() => null)

                                    // --- LOGIC DIREKTORI (Auth = Pendamping, Record->User = Pelaku Usaha) ---
                                    ->directory(function (Pengajuan $record) {
                                        return 'dokumen_anggota_' . Str::slug(auth()->user()->name) . '/' . Str::slug($record->user->name ?? 'temp');
                                    })

                                    // --- LOGIC PENAMAAN FILE ---
                                    ->getUploadedFileNameForStorageUsing(function ($file, Pengajuan $record) {
                                        $prefix = 'NIB';
                                        $namaPelakuUsaha = Str::slug($record->user->name ?? 'tanpa-nama');

                                        return $prefix . '_' . $namaPelakuUsaha . '_' . time() . '.' . $file->getClientOriginalExtension();
                                    }),
                            ]),
                    ])
                    // 3. PROSES SIMPAN
                    ->action(function (Pengajuan $record, array $data) {
                        $updateData = [
                            'merk_dagang' => $data['merk_dagang'], // BARU
                            'email'       => $data['email'],
                            'nomor_nib'   => $data['nomor_nib'],
                            'akun_halal'  => $data['akun_halal'], // BARU
                            'pass_akun_halal' => $data['pass_akun_halal'], // BARU
                        ];

                        // --- PERBAIKAN KUNCI 2: LOGIC SAVE FILE ---
                        // Cek apakah key 'file_foto_nib' ada di array $data
                        // Karena kita pakai 'dehydrated', key ini HANYA akan ada jika user upload file baru.
                        if (! empty($data['file_foto_nib'])) {
                            $updateData['file_foto_nib'] = $data['file_foto_nib'];
                        }

                        // Jika pass_email diisi, update pass_email DAN password (hash)
                        if (! empty($data['pass_email'])) {
                            $updateData['pass_email'] = $data['pass_email'];
                            $updateData['password'] = bcrypt($data['pass_email']);
                        }

                        // Eksekusi Update ke tabel User
                        $record->user->update($updateData);

                        Notification::make()
                            ->success()
                            ->title('Data Anggota Diperbarui')
                            ->body('Akun Email, Akun SiHalal, dan dokumen berhasil disimpan.')
                            ->send();
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

                        // Cek Tab
                        $tab = $livewire->activeTab ?? '';

                        // Button muncul di tab 'siap_invoice' ATAU tab kerjaan standar
                        // Tapi JANGAN muncul di tab 'selesai' atau 'semua' (history)
                        // Dan jangan muncul jika status sudah Invoice/Selesai (sudah final di tahap ini)
                        $isTabAllowed = in_array($tab, ['tugas_saya', 'revisi', 'proses', 'dikirim', 'siap_invoice']);

                        $statusFinal = in_array($record->status_verifikasi, [
                            Pengajuan::STATUS_INVOICE,
                            Pengajuan::STATUS_SELESAI,
                        ]);

                        return $isMyTask && $isTabAllowed && ! $statusFinal;
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
                                    // Menggunakan opsi yang sudah difilter (Tanpa Invoice/Selesai)
                                    ->options(function ($livewire) {
                                        $currentTab = $livewire->activeTab ?? '';
                                        // JIKA DI TAB 'SIAP INVOICE'
                                        // Opsinya khusus: Lanjut ke Invoice atau Langsung Selesai
                                        if ($currentTab === 'siap_invoice') {
                                            return [
                                                Pengajuan::STATUS_INVOICE => 'Invoice Diajukan',
                                                Pengajuan::STATUS_SELESAI => 'Selesai / Lunas',
                                            ];
                                        }

                                        // JIKA DI TAB LAIN (Antrian, Tugas Saya, Revisi, Proses, Dikirim)
                                        // Gunakan opsi standar verifikator (tanpa invoice/selesai)
                                        return Pengajuan::getOpsiManualVerifikator();
                                    })
                                    ->required()
                                    ->native(false)
                                    ->live() // Penting agar form di bawahnya bisa responsif
                                    ->afterStateUpdated(function ($state, \Filament\Forms\Set $set) {
                                        // Opsional: Reset catatan jika status berubah bukan revisi
                                        if (! in_array($state, Pengajuan::getStatRevisi())) {
                                            $set('catatan_revisi', null);
                                        }

                                        // Reset file jika batal pilih sertifikat (opsional, UX choice)
                                        if ($state !== Pengajuan::STATUS_SERTIFIKAT) {
                                            $set('file_sertifikat', null);
                                        }
                                    }),

                                // -------------------------------------------------------------
                                // [BARU] FIELD UPLOAD SERTIFIKAT
                                // -------------------------------------------------------------
                                FileUpload::make('file_sertifikat')
                                    ->label('Upload File Sertifikat (PDF)')
                                    ->directory('sertifikat-halal') // Folder penyimpanan di storage
                                    ->acceptedFileTypes(['application/pdf'])
                                    ->maxSize(5120) // 5MB
                                    ->downloadable()
                                    ->openable()
                                    ->columnSpanFull()
                                    // Hanya Tampil jika status = SERTIFIKAT_TERBIT
                                    ->visible(fn(Get $get) => $get('status_verifikasi') === Pengajuan::STATUS_SERTIFIKAT)
                                    // Wajib diisi jika status = SERTIFIKAT_TERBIT
                                    ->required(fn(Get $get) => $get('status_verifikasi') === Pengajuan::STATUS_SERTIFIKAT)
                                    ->validationMessages([
                                        'required' => 'Wajib upload sertifikat halal',
                                    ]),

                                // -------------------------------------------------------------
                                // FIELD KHUSUS INVOICE (Hanya muncul jika pilih status INVOICE / SELESAI)
                                // -------------------------------------------------------------
                                Section::make('Data Tagihan (Invoice)')
                                    ->description('Lengkapi data pembayaran untuk pelaku usaha.')
                                    ->icon('heroicon-m-banknotes')
                                    ->schema([
                                        Grid::make(2)->schema([
                                            TextInput::make('nomor_invoice')
                                                ->label('No. Invoice')
                                                ->default(function (Pengajuan $record) {
                                                    // Panggil fungsi yang sama persis dengan Excel
                                                    // Hasilnya PASTI: INV-20240530-00123 (Sesuai ID record ini)
                                                    return $record->auto_invoice_number;
                                                })
                                                ->required()
                                                ->readOnly(), // Agar admin tidak ubah-ubah format

                                            DatePicker::make('tanggal_terbit')
                                                ->label('Tanggal Invoice')
                                                ->default(now())
                                                ->required(),
                                        ]),

                                        TextInput::make('total_nominal')
                                            ->label('Total Tagihan (Rp)')
                                            ->placeholder('Nominal per PU')
                                            ->prefix('Rp')
                                            ->numeric()
                                            ->required(),

                                        TextInput::make('link_pembayaran')
                                            ->label('Link Pembayaran (Opsional)')
                                            ->placeholder('https://...')
                                            ->url()
                                            ->columnSpanFull(),

                                        // Hidden field default value
                                        Hidden::make('status_pembayaran')->default('BELUM DIBAYAR'),
                                    ])
                                    // LOGIC TAMPIL:
                                    // Muncul jika di tab 'siap_invoice' DAN (Status Invoice ATAU Status Selesai)
                                    ->visible(function (Get $get, $livewire) {
                                        $currentTab = $livewire->activeTab ?? '';
                                        $status = $get('status_verifikasi');

                                        // Cek apakah statusnya Invoice atau Selesai
                                        $isInvoiceOrFinish = in_array($status, [
                                            Pengajuan::STATUS_INVOICE,
                                            Pengajuan::STATUS_SELESAI
                                        ]);

                                        return $currentTab === 'siap_invoice' && $isInvoiceOrFinish;
                                    }),

                                // -------------------------------------------------------------
                                // FIELD CATATAN REVISI (Standard)
                                // -------------------------------------------------------------
                                Textarea::make('catatan_revisi')
                                    ->label('Catatan / Alasan Penolakan')
                                    ->placeholder('Contoh: Foto NIB buram, mohon upload ulang.')
                                    ->rows(3)
                                    ->visible(
                                        fn(Get $get, $livewire) => ($livewire->activeTab ?? '') !== 'siap_invoice' &&
                                            in_array($get('status_verifikasi'), Pengajuan::getStatRevisi())
                                    )
                                    ->required(fn(Get $get) => in_array($get('status_verifikasi'), Pengajuan::getStatRevisi())),

                            ]),
                    ])
                    ->action(function (Pengajuan $record, array $data, Action $action) {
                        // 1. VALIDASI DATA PELAKU USAHA (USER)
                        // Hanya dijalankan jika Admin memilih status "Sertifikat Diterbitkan"
                        if ($data['status_verifikasi'] === Pengajuan::STATUS_SERTIFIKAT) {

                            $user = $record->user; // Mengambil model User
                            $missing = [];

                            // Cek Dokumen Legalitas & Akun (Wajib)
                            if (empty($user->nomor_nib))       $missing[] = 'Nomor NIB';
                            if (empty($user->file_foto_nib))   $missing[] = 'Foto/File NIB';
                            if (empty($user->akun_halal))      $missing[] = 'Akun SiHalal';
                            if (empty($user->pass_akun_halal)) $missing[] = 'Password SiHalal';

                            // Cek Data Produk (Wajib untuk Sertifikat)
                            if (empty($user->merk_dagang))      $missing[] = 'Merk Dagang';
                            if (empty($user->file_foto_produk)) $missing[] = 'Foto Produk';
                            if (empty($user->file_foto_usaha))  $missing[] = 'Foto Tempat Usaha';
                            if (empty($user->file_foto_bersama))  $missing[] = 'Foto Bersama Pendamping';

                            // JIKA ADA DATA KOSONG -> STOP PROSES
                            if (count($missing) > 0) {
                                Notification::make()
                                    ->danger()
                                    ->title('Data Pelaku Usaha Belum Lengkap')
                                    ->body('Mohon lengkapi: ' . implode(', ', $missing) . ' terlebih dulu sebelum menerbitkan sertifikat.')
                                    ->persistent()
                                    ->persistent()
                                    ->send();

                                // STOP PROSES: Jangan tutup modal, biarkan user memperbaiki/batal
                                $action->halt();
                                return;
                            }
                        }

                        // 2. PROSES UPDATE DATABASE (Jika Lolos Validasi)
                        DB::transaction(function () use ($record, $data) {

                            $tagihanId = null; // Variabel penampung ID

                            // -------------------------------------------------------------
                            // PERBAIKAN 2: LOGIKA PENYIMPANAN TAGIHAN
                            // -------------------------------------------------------------
                            // Jalankan jika status INVOICE atau SELESAI
                            // DAN pastikan data invoice (total_nominal) benar-benar diisi di form
                            $isInvoiceAction = in_array($data['status_verifikasi'], [
                                Pengajuan::STATUS_INVOICE,
                                Pengajuan::STATUS_SELESAI
                            ]);

                            // Cek 'total_nominal' untuk memastikan form invoice tadi muncul dan diisi
                            if ($isInvoiceAction && !empty($data['total_nominal'])) {
                                // Cari berdasarkan 'nomor_invoice'. 
                                // Jika ketemu -> Update datanya. Jika tidak ketemu -> Buat baru.
                                $tagihan = \App\Models\Tagihan::updateOrCreate(
                                    ['nomor_invoice' => $data['nomor_invoice']], // Kunci pencarian (Unique)
                                    [
                                        'pendamping_id'     => $record->pendamping_id,
                                        'total_nominal'     => $data['total_nominal'],
                                        'link_pembayaran'   => $data['link_pembayaran'] ?? null,
                                        'tanggal_terbit'    => $data['tanggal_terbit'],
                                        // Jika langsung SELESAI, apakah otomatis LUNAS?
                                        // Biasanya tetap 'BELUM DIBAYAR' dulu agar user melakukan konfirmasi bayar terpisah
                                        // atau bisa diubah logic-nya di sini jika mau otomatis lunas.
                                        'status_pembayaran' => 'BELUM DIBAYAR',
                                    ]
                                );
                                // Simpan ID tagihan untuk relasi
                                $tagihanId = $tagihan->id;
                            }
                            // B. Bersihkan data invoice dari array $data sebelum update ke tabel pengajuan
                            // (Agar tidak error "Column not found" di tabel pengajuan)
                            $updateData = collect($data)
                                ->except([
                                    'nomor_invoice',
                                    'total_nominal',
                                    'link_pembayaran',
                                    'tanggal_terbit',
                                    'status_pembayaran'
                                ])
                                ->toArray();

                            // Note: 'file_sertifikat' otomatis MASUK di $updateData karena tidak di-except.
                            // Filament otomatis menangani pemindahan file tmp ke directory tujuan.

                            // -------------------------------------------------------------
                            // C. INJEKSI TAGIHAN ID (INI YANG KURANG TADI)
                            // -------------------------------------------------------------
                            // Jika ada tagihan yang dibuat, masukkan ID-nya ke update data pengajuan
                            if ($tagihanId) {
                                $updateData['tagihan_id'] = $tagihanId;
                            }

                            // ========================================================
                            // [BARU] UPDATE TIMESTAMP VERIFIED_AT
                            // ========================================================
                            // Setiap kali verifikator menekan tombol simpan (approve/revisi/tolak),
                            // kita catat waktu keputusannya.
                            $updateData['verified_at'] = now();

                            // Update Status Pengajuan
                            $record->update($updateData);
                        });

                        // Notifikasi
                        $pesan = 'Status diperbarui.';
                        if ($data['status_verifikasi'] === Pengajuan::STATUS_SERTIFIKAT) {
                            $pesan = 'Sertifikat berhasil diterbitkan & diupload.';
                        } elseif ($data['status_verifikasi'] === Pengajuan::STATUS_INVOICE) {
                            $pesan = 'Status diperbarui & Invoice diterbitkan.';
                        } elseif ($data['status_verifikasi'] === Pengajuan::STATUS_SELESAI) {
                            $pesan = 'Pengajuan Selesai & Invoice tercatat.';
                        }

                        // 2. Kirim Notifikasi
                        Notification::make()
                            ->success()
                            ->title('Status Diperbarui')
                            ->body($pesan)
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
                                ->label('No. HP/WhatsApp')
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

                    // -------------------------------------------------------------
                    // [BARU] INFORMASI AKUN SIHALAL (PELAKU USAHA)
                    // -------------------------------------------------------------
                    \Filament\Infolists\Components\Section::make('Akun SiHalal (Pelaku Usaha)')
                        ->icon('heroicon-o-finger-print')
                        ->description('Kredensial login SiHalal milik Pelaku Usaha.')
                        ->schema([
                            \Filament\Infolists\Components\TextEntry::make('user.akun_halal')
                                ->label('Username / Email')
                                ->icon('heroicon-m-at-symbol')
                                ->copyable()
                                ->placeholder('Belum diatur'),

                            \Filament\Infolists\Components\TextEntry::make('user.pass_akun_halal')
                                ->label('Password')
                                ->icon('heroicon-m-lock-closed')
                                ->fontFamily(\Filament\Support\Enums\FontFamily::Mono) // Font koding agar jelas
                                ->copyable()
                                ->color('primary')
                                ->placeholder('Belum diatur'),
                        ])
                        ->columns(2)
                        ->columnSpanFull(),

                    // --- [BARU] INFORMASI AKUN SIHALAL PENDAMPING ---
                    \Filament\Infolists\Components\Section::make('Akun SiHalal (Pendamping)')
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

                            // --- KARTU 1: KTP ---
                            \Filament\Infolists\Components\Group::make([
                                \Filament\Infolists\Components\ImageEntry::make('user.file_ktp')
                                    ->hiddenLabel()
                                    ->state(function ($record) {
                                        $path = $record->user->file_ktp;

                                        // Jika file tidak ada, kembalikan null
                                        if (! $path) {
                                            return null;
                                        }

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
                                        }),
                                ])->fullWidth(),
                            ])
                                ->extraAttributes(['class' => 'bg-white border border-gray-200 rounded-lg shadow-sm flex flex-col']),

                            // --- KARTU 2: FOTO PRODUK ---
                            \Filament\Infolists\Components\Group::make([
                                \Filament\Infolists\Components\ImageEntry::make('user.file_foto_produk')
                                    ->hiddenLabel()
                                    ->state(function ($record) {
                                        $path = $record->user->file_foto_produk;

                                        // Jika file tidak ada, kembalikan null
                                        if (! $path) {
                                            return null;
                                        }

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

                            // --- KARTU 3: FOTO BERSAMA ---
                            \Filament\Infolists\Components\Group::make([
                                \Filament\Infolists\Components\ImageEntry::make('user.file_foto_bersama')
                                    ->hiddenLabel()
                                    ->state(function ($record) {
                                        $path = $record->user->file_foto_bersama;

                                        // Jika file tidak ada, kembalikan null
                                        if (! $path) {
                                            return null;
                                        }

                                        // Return URL ke route proxy yang kita buat di langkah 1
                                        return route('drive.image', ['path' => $path]);
                                    })
                                    ->height(250)->width('100%')
                                    ->extraImgAttributes(['class' => 'object-cover w-full h-full rounded-t-lg']),

                                \Filament\Infolists\Components\Actions::make([
                                    \Filament\Infolists\Components\Actions\Action::make('download_bersama')
                                        ->label('Download Foto Bersama Pendamping')
                                        ->icon('heroicon-m-arrow-down-tray')
                                        ->color('primary')
                                        ->action(function ($record) {
                                            // Ini akan memicu download langsung di browser user
                                            return Storage::disk('google')->download($record->user->file_foto_bersama);
                                        }),
                                ])->fullWidth(),
                            ])
                                ->extraAttributes(['class' => 'bg-white border border-gray-200 rounded-lg shadow-sm flex flex-col']),

                            // --- KARTU 4: TEMPAT USAHA ---
                            \Filament\Infolists\Components\Group::make([
                                \Filament\Infolists\Components\ImageEntry::make('user.file_foto_usaha')
                                    ->hiddenLabel()
                                    ->state(function ($record) {
                                        $path = $record->user->file_foto_usaha;

                                        // Jika file tidak ada, kembalikan null
                                        if (! $path) {
                                            return null;
                                        }

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
                                        }),
                                ])->fullWidth(),
                            ])
                                ->extraAttributes(['class' => 'bg-white border border-gray-200 rounded-lg shadow-sm flex flex-col']),

                        ]),
                    ]),
            ]);
    }
}

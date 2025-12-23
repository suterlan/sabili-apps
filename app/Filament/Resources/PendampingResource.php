<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PendampingResource\Pages;
use App\Models\User;
use Filament\Infolists\Components\Actions\Action;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Filament\Infolists\Components\ImageEntry; // Jika ada foto
use Filament\Infolists\Components\Grid as InfolistGrid;       // <--- PENTING: Pakai Alias
use Filament\Infolists\Components\Group as InfolistGroup;     // <--- PENTING: Pakai Alias
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;

class PendampingResource extends Resource
{
    protected static ?string $model = User::class;

    // Bedakan Label dan Slug agar tidak bentrok dengan UserResource biasa
    protected static ?string $navigationLabel = 'Pendamping';
    protected static ?string $modelLabel = 'Monitoring Pendamping';
    protected static ?string $pluralModelLabel = 'Monitoring Pendamping';
    protected static ?string $slug = 'monitoring-pendamping';
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?int $navigationSort = 2; // Urutan menu

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Pendamping')
                    ->searchable()
                    ->sortable(),

                // Menampilkan Wilayah (Format Kode ke Nama)
                Tables\Columns\TextColumn::make('kecamatan')
                    ->label('Wilayah Kecamatan')
                    ->formatStateUsing(
                        fn($state) =>
                        \Laravolt\Indonesia\Models\District::where('code', $state)->first()?->name ?? '-'
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
            ->actions([
                // Kita hilangkan tombol Edit/Delete agar menu ini murni untuk monitoring
                // Jika ingin melihat detail, bisa tambahkan ViewAction
                Tables\Actions\ViewAction::make()
                    ->label('Detail')
                    ->color('info'),
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
            || Auth::user()->isAdmin()
            || Auth::user()->isKoordinator();
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
                                ->visible(fn($record) => $record->role === 'pendamping')
                                ->schema([
                                    TextEntry::make('nama_bank')->label('Bank'),
                                    TextEntry::make('nomor_rekening')->label('No. Rekening')->copyable(),
                                    TextEntry::make('pendidikan_terakhir')->badge()->color('info'),
                                    TextEntry::make('nama_instansi')->label('Sekolah/Kampus'),
                                ])->columns(2),

                        ])->columnSpan(['default' => 3, 'lg' => 2]),

                        // ========================================================
                        // KOLOM KANAN (SPAN 1) - Akun & SiHalal
                        // ========================================================
                        InfolistGroup::make([

                            // 1. AKUN PENGGUNA (Profil Singkat)
                            InfolistSection::make('Profil Akun')
                                ->icon('heroicon-o-user')
                                ->schema([
                                    TextEntry::make('name')
                                        ->label('Nama') // Label dipersingkat agar tidak sempit
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

                                    InfolistGrid::make(2)->schema([ // Grid kecil di dalam agar rapi
                                        TextEntry::make('role')->badge()->color('warning'),
                                        TextEntry::make('status')->badge(),
                                    ]),

                                    TextEntry::make('created_at')
                                        ->label('Terdaftar')
                                        ->since()
                                        ->size(TextEntry\TextEntrySize::Small)
                                        ->color('gray'),
                                ]),

                            // 2. AKUN SIHALAL (DIPISAH SESUAI REQUEST)
                            InfolistSection::make('Akses SiHalal')
                                ->icon('heroicon-o-key')
                                // ->color('primary') // Warna header berbeda biar mencolok
                                ->schema([
                                    TextEntry::make('akun_halal')
                                        ->label('Username')
                                        ->icon('heroicon-m-user')
                                        ->copyable()
                                        ->weight('medium'),

                                    TextEntry::make('pass_akun_halal')
                                        ->label('Password')
                                        ->icon('heroicon-m-lock-closed') // Icon gembok
                                        ->copyable()
                                        ->fontFamily('mono') // Font monospace agar huruf l/1/I jelas
                                        ->color('danger'), // Warna merah agar hati-hati
                                ]),

                        ])->columnSpan(['default' => 3, 'lg' => 1]), // Ambil 1 bagian (Sisa)

                    ]), // End Grid Utama

                // ========================================================
                // BAGIAN BAWAH: DOKUMEN (FULL WIDTH / LEBAR PENUH)
                // ========================================================
                // Section ini ditaruh di LUAR Grid agar bisa panjang ke samping
                InfolistSection::make('Berkas Dokumen Pendamping')
                    ->icon('heroicon-o-folder-open')
                    ->visible(fn($record) => $record->role === 'pendamping')
                    ->schema([
                        // A. PAS FOTO (Pakai Base64)
                        ImageEntry::make('file_pas_foto')
                            ->label('Pas Foto')
                            ->disk(null) // Matikan disk agar membaca state base64
                            ->state(fn($record) => self::getBase64Image($record->file_pas_foto))
                            ->extraImgAttributes(['class' => 'max-w-full h-auto max-h-64 object-cover aspect-square rounded-lg shadow-md border border-gray-200'])
                            ->hintAction(self::getOpenAction('file_pas_foto')),

                        // B. BUKU REKENING (Pakai Base64)
                        ImageEntry::make('file_buku_rekening')
                            ->label('Buku Rekening')
                            ->disk(null)
                            ->state(fn($record) => self::getBase64Image($record->file_buku_rekening))
                            ->extraImgAttributes(['class' => 'max-w-full h-auto max-h-64 object-contain rounded-lg shadow-md border border-gray-200'])
                            ->hintAction(self::getOpenAction('file_buku_rekening')),

                        // C. KTP (Pakai Base64)
                        ImageEntry::make('file_ktp')
                            ->label('KTP')
                            ->disk(null)
                            ->state(fn($record) => self::getBase64Image($record->file_ktp))
                            ->extraImgAttributes(['class' => 'max-w-full h-auto max-h-64 object-contain rounded-lg shadow-md border border-gray-200'])
                            ->hintAction(self::getOpenAction('file_ktp')),

                        // D. IJAZAH (Pakai Base64)
                        ImageEntry::make('file_ijazah')
                            ->label('Ijazah Terakhir')
                            ->disk(null)
                            ->state(fn($record) => self::getBase64Image($record->file_ijazah))
                            ->extraImgAttributes(['class' => 'max-w-full h-auto max-h-64 object-contain rounded-lg shadow-md border border-gray-200'])
                            ->hintAction(self::getOpenAction('file_ijazah')),
                    ])
                    ->columns([
                        'default' => 1,
                        'sm' => 2, // Tampil 2 kolom agar rapi
                        'md' => 2,
                        'xl' => 4, // Tampil 4 kolom di layar besar
                    ])
                    ->columnSpanFull(),
            ]);
    }

    // --- PASTIKAN 2 FUNGSI INI ADA DI BAWAH INFOLIST (DALAM CLASS RESOURCE) ---

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

<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Forms;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Laravolt\Indonesia\Models\City;
use Laravolt\Indonesia\Models\District;
use Laravolt\Indonesia\Models\Province;
use Laravolt\Indonesia\Models\Village;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile; // Pastikan import ini ada
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\FileUpload;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'address',
        'role',
        'pendamping_id',
        // Kolom Baru Pendamping
        'pass_email_pendamping',
        'alamat_domisili',
        'akun_halal',
        'pass_akun_halal',
        'pendidikan_terakhir',
        'nama_instansi',
        'nama_bank',
        'nomor_rekening',
        'file_ktp',
        'file_ijazah',
        'file_pas_foto',
        'file_buku_rekening',
        'status',
        // Pelaku Usaha Fields
        'nik',
        'tanggal_lahir',
        'desa',
        'kecamatan',
        'kabupaten',
        'provinsi',
        'pass_email',
        'merk_dagang',
        'nomor_nib',
        'mitra_halal',
        'file_foto_produk',
        'file_foto_bersama',
        'file_foto_nib',
        'file_foto_usaha',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Relasi: User ini punya banyak anggota
    public function anggotas()
    {
        return $this->hasMany(User::class, 'pendamping_id');
    }

    // Relasi: User ini milik siapa (jika dia anggota)
    public function pendamping()
    {
        return $this->belongsTo(User::class, 'pendamping_id');
    }

    // Helper untuk cek role
    public function isSuperAdmin()
    {
        return $this->role === 'superadmin';
    }

    public function isKoordinator()
    {
        return $this->role === 'koordinator';
    }

    // Helper cek admin
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isPendamping()
    {
        return $this->role === 'pendamping';
    }

    // IZIN AKSES PANEL
    public function canAccessPanel(Panel $panel): bool
    {
        // Superadmin, Admin, dan Pendamping boleh login
        return $this->isSuperAdmin() || $this->isAdmin() || $this->isPendamping() || $this->isKoordinator();
    }

    // Helper untuk mengecek apakah user sudah diverifikasi
    public function isVerified()
    {
        return $this->status === 'verified';
    }

    // Relasi: User (Pendamping) memiliki banyak User lain (Anggota)
    public function anggotaBinaan()
    {
        return $this->hasMany(User::class, 'pendamping_id', 'id');
    }

    public function province()
    {
        return $this->belongsTo(Province::class, 'provinsi', 'code');
    }

    public function city()
    {
        return $this->belongsTo(City::class, 'kabupaten', 'code');
    }

    public function district()
    {
        return $this->belongsTo(District::class, 'kecamatan', 'code');
    }

    public function village()
    {
        return $this->belongsTo(Village::class, 'desa', 'code');
    }

    // User bisa punya banyak pengajuan (Histori)
    public function pengajuans()
    {
        return $this->hasMany(Pengajuan::class);
    }

    // Helper untuk mengambil pengajuan terbaru (Current active application)
    public function latestPengajuan()
    {
        return $this->hasOne(Pengajuan::class)->latestOfMany();
    }

    // Relasi untuk mengambil pelaku usaha yang didampingi oleh pendamping ini
    public function members()
    {
        // Asumsi: Pelaku usaha punya kolom 'pendamping_id' di tabel users
        // (Jika Anda pakai struktur relasi yang berbeda, sesuaikan di sini)
        // Jika tidak ada kolom pendamping_id di tabel users, 
        // Anda bisa hitung lewat tabel Pengajuan:

        return $this->hasMany(Pengajuan::class, 'pendamping_id');
    }

    /**
     * Cek apakah profil Pendamping sudah lengkap.
     */
    public function isProfileComplete(): bool
    {
        // 1. Daftar kolom text yang wajib diisi
        $requiredFields = [
            'phone',
            'address',
            'alamat_domisili',
            'akun_halal',
            'pass_akun_halal',
            'provinsi',
            'kabupaten',
            'kecamatan',
            'desa',
            'pendidikan_terakhir',
            'nama_instansi',
            'nama_bank',
            'nomor_rekening',
        ];

        // 2. Daftar kolom file/dokumen yang wajib diupload
        $requiredFiles = [
            'file_pas_foto',
            'file_ktp',
            'file_ijazah',       // Opsional: uncomment jika wajib
            'file_buku_rekening' // Opsional: uncomment jika wajib
        ];

        // Cek Kolom Text
        foreach ($requiredFields as $field) {
            if (empty($this->$field)) {
                return false;
            }
        }

        // Cek Kolom File (biasanya string path, tidak boleh null/kosong)
        foreach ($requiredFiles as $file) {
            if (empty($this->$file)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Mendefinisikan schema form untuk upload dokumen pendamping.
     */
    public static function getDokumenPendampingFormSchema()
    {
        return [
            Section::make('Berkas Dokumen Pendamping')
                ->description('Size maximal 10MB per file.')
                ->icon('heroicon-o-folder-open')
                ->collapsible()
                ->collapsed()
                ->schema([

                    // --- BARIS 1: PAS FOTO & BUKU REKENING ---
                    Group::make([

                        // KOLOM KIRI: PAS FOTO
                        Group::make([
                            // 1a. Preview Pas Foto
                            Placeholder::make('preview_pas_foto')
                                ->label('Pas Foto Saat Ini')
                                ->content(fn($get) => self::renderPreview($get('file_pas_foto'), true)),

                            // 1b. Upload Pas Foto
                            FileUpload::make('file_pas_foto')
                                ->label('Ganti/Upload Pas Foto')
                                ->helperText('Kosongkan jika tidak ubah.')
                                ->disk('google')
                                ->visibility('private')
                                ->image()
                                ->avatar() // Khusus Pas Foto
                                ->imageResizeMode('cover')
                                ->imageCropAspectRatio('1:1')
                                ->imageResizeTargetWidth('500')
                                ->maxSize(10240)
                                ->downloadable()

                                // TRIK ANTI-LOADING
                                ->dehydrated(fn($state) => filled($state))
                                ->required(fn($record) => $record === null) // Wajib cuma saat Create

                                // DIRECTORY & RENAME
                                ->directory(fn($get, $record) => self::generateDirectory($get, $record))
                                ->getUploadedFileNameForStorageUsing(
                                    fn(TemporaryUploadedFile $file) =>
                                    'pas_foto_' . now()->timestamp . '.' . $file->getClientOriginalExtension()
                                ),
                        ]),

                        // KOLOM KANAN: BUKU REKENING
                        Group::make([
                            // 2a. Preview Rekening
                            Placeholder::make('preview_buku_rekening')
                                ->label('Rekening Saat Ini')
                                ->content(fn($get) => self::renderPreview($get('file_buku_rekening'))),

                            // 2b. Upload Rekening
                            FileUpload::make('file_buku_rekening')
                                ->label('Ganti/Upload Rekening')
                                ->helperText('Kosongkan jika tidak ubah.')
                                ->disk('google')
                                ->visibility('private')
                                ->image()
                                ->imageResizeTargetWidth('1024')
                                ->maxSize(10240)
                                ->downloadable()

                                // TRIK ANTI-LOADING
                                ->dehydrated(fn($state) => filled($state))
                                ->required(fn($record) => $record === null)

                                // DIRECTORY & RENAME
                                ->directory(fn($get, $record) => self::generateDirectory($get, $record))
                                ->getUploadedFileNameForStorageUsing(
                                    fn(TemporaryUploadedFile $file) =>
                                    'rekening_' . now()->timestamp . '.' . $file->getClientOriginalExtension()
                                ),
                        ]),

                    ])->columns(2), // Split jadi 2 kolom


                    // --- BARIS 2: KTP & IJAZAH ---
                    Group::make([

                        // KOLOM KIRI: KTP
                        Group::make([
                            // 3a. Preview KTP
                            Placeholder::make('preview_ktp')
                                ->label('KTP Saat Ini')
                                ->content(fn($get) => self::renderPreview($get('file_ktp'))),

                            // 3b. Upload KTP
                            FileUpload::make('file_ktp')
                                ->label('Ganti/Upload KTP')
                                ->helperText('Kosongkan jika tidak ubah.')
                                ->disk('google')
                                ->visibility('private')
                                ->image()
                                ->imageResizeMode('cover')
                                ->imageCropAspectRatio('16:9')
                                ->imageResizeTargetWidth('1024')
                                ->maxSize(10240)
                                ->downloadable()

                                // TRIK ANTI-LOADING
                                ->dehydrated(fn($state) => filled($state))
                                ->required(fn($record) => $record === null)

                                // DIRECTORY & RENAME
                                ->directory(fn($get, $record) => self::generateDirectory($get, $record))
                                ->getUploadedFileNameForStorageUsing(
                                    fn(TemporaryUploadedFile $file) =>
                                    'ktp_' . now()->timestamp . '.' . $file->getClientOriginalExtension()
                                ),
                        ]),

                        // KOLOM KANAN: IJAZAH
                        Group::make([
                            // 4a. Preview Ijazah
                            Placeholder::make('preview_ijazah')
                                ->label('Ijazah Saat Ini')
                                ->content(fn($get) => self::renderPreview($get('file_ijazah'))),

                            // 4b. Upload Ijazah
                            FileUpload::make('file_ijazah')
                                ->label('Ganti/Upload Ijazah')
                                ->helperText('Kosongkan jika tidak ubah.')
                                ->disk('google')
                                ->visibility('private')
                                ->image()
                                ->imageResizeTargetWidth('1024')
                                ->maxSize(10240)
                                ->downloadable()

                                // TRIK ANTI-LOADING
                                ->dehydrated(fn($state) => filled($state))
                                ->required(fn($record) => $record === null)

                                // DIRECTORY & RENAME
                                ->directory(fn($get, $record) => self::generateDirectory($get, $record))
                                ->getUploadedFileNameForStorageUsing(
                                    fn(TemporaryUploadedFile $file) =>
                                    'ijazah_' . now()->timestamp . '.' . $file->getClientOriginalExtension()
                                ),
                        ]),

                    ])->columns(2),

                ]),
        ];
    }

    /**
     * Helper agar tidak perlu menulis ulang logika direktori 4 kali
     */
    protected static function generateDirectory($get, $record)
    {
        $u = $record ?? Auth::user();
        // Fallback logic yang aman
        $name = $u->name ?? $get('name') ?? 'user';
        $id = $u->id ?? 'new';

        return 'dokumen_pendamping_' . Str::slug($name) . '_' . $id;
    }

    /**
     * Helper untuk merender HTML Preview (mengurangi duplikasi kode)
     */
    protected static function renderPreview($filePath, $isAvatar = false)
    {
        $base64 = self::getBase64Image($filePath);

        if ($base64) {
            // STYLE 1: MODE AVATAR (BULAT)
            if ($isAvatar) {
                return new HtmlString('
                <div class="flex justify-center w-full mb-2">
                    <div class="h-32 w-32 rounded-full overflow-hidden border-2 border-gray-300 shadow-sm ring-2 ring-gray-100">
                        <img src="' . $base64 . '" 
                             class="h-full w-full object-cover" 
                             alt="Avatar Preview">
                    </div>
                </div>
            ');
            }

            // STYLE 2: MODE DOCUMENT (KOTAK)
            return new HtmlString('
            <div class="w-full flex justify-center p-2 bg-gray-50 rounded-lg border border-gray-200">
                <img src="' . $base64 . '" 
                     class="max-h-48 rounded shadow-sm object-contain" 
                     alt="Document Preview">
            </div>
        ');
        }

        // Tampilan jika kosong
        return new HtmlString('<div class="text-xs text-gray-400 italic text-center p-2">Belum ada file.</div>');
    }

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
}

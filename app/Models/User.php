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
use Filament\Forms\Get;

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
            'tanggal_lahir' => 'date', // atau 'immutable_date'
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
     * Helper Reusable untuk Upload dengan Proxy Preview
     * Bisa dipakai di UserResource (Admin) maupun EditProfile (User)
     */
    /**
     * Helper Reusable untuk Upload dengan Proxy Preview
     */
    public static function getUploadComponent(string $field, string $label, string $prefix, bool $isRequired = true)
    {
        return \Filament\Forms\Components\Group::make([
            // 1. PREVIEW GAMBAR (Custom Preview via Placeholder)
            // Bagian ini sudah benar, menampilkan gambar via Route Proxy
            \Filament\Forms\Components\Placeholder::make('preview_' . $field)
                ->hidden(fn($record) => empty($record?->$field))
                ->content(fn($record) => new \Illuminate\Support\HtmlString("
                    <div class='mb-2 p-2 border rounded bg-gray-50 flex items-center gap-4'>
                        <img src='" . route('drive.image', ['path' => $record->$field ?? '']) . "' 
                             style='height: 80px; width: 80px; object-fit: cover; border-radius: 8px;' 
                             loading='lazy' class='shadow-sm'>
                        <div class='text-xs text-gray-500'>
                            <p class='font-bold text-success-600'>✓ Terupload</p>
                            <a href='" . route('drive.image', ['path' => $record->$field ?? '']) . "' 
                               target='_blank' 
                               class='text-primary-600 underline hover:text-primary-500'>
                               Lihat Penuh
                            </a>
                        </div>
                    </div>
                ")),

            // 2. INPUT FILE (FileUpload)
            \Filament\Forms\Components\FileUpload::make($field)
                ->label(fn($record) => empty($record?->$field) ? "Upload $label" : "Ganti $label")
                ->disk('google')
                ->visibility('private')
                ->image()
                ->maxSize(5120) // 5MB
                ->imageResizeTargetWidth('1024')

                // --- BAGIAN PENTING AGAR LOADING HILANG ---
                ->fetchFileInformation(false) // 1. Jangan ambil metadata
                ->uploadingMessage('Mengupload...') // 2. Pesan upload

                // 3. TRIK KUNCI: Kosongkan state visual agar Filament tidak mencoba me-load preview di dalam kotak
                ->formatStateUsing(fn() => null)

                // 4. Mencegah error database: Hanya simpan jika user benar-benar mengupload file baru
                // (Karena statenya kita null-kan di atas, kita harus jaga di sini)
                ->dehydrated(fn($state) => filled($state))
                // ------------------------------------------

                ->directory(fn(\Filament\Forms\Get $get) => 'dokumen_pendamping_' . \Illuminate\Support\Str::slug($get('name') ?? 'temp'))
                ->getUploadedFileNameForStorageUsing(fn($file) => $prefix . '_' . time() . '.' . $file->getClientOriginalExtension())

                // Logic Required
                ->required(fn($record) => $isRequired && empty($record?->$field)),
        ])->columnSpan(1);
    }

    /**
     * Schema Gabungan untuk Dokumen Pendamping
     */
    public static function getDokumenPendampingFormSchema(): array
    {
        return [
            \Filament\Forms\Components\Section::make('Berkas Dokumen Pendamping')
                ->description('Dokumen legalitas dan identitas pendamping.')
                ->schema([
                    // PANGGIL HELPER BARU DI SINI
                    self::getUploadComponent('file_pas_foto', 'Pas Foto', 'foto'),
                    self::getUploadComponent('file_ktp', 'KTP', 'ktp'),
                    self::getUploadComponent('file_buku_rekening', 'Buku Rekening', 'rekening'),
                    self::getUploadComponent('file_ijazah', 'Ijazah', 'ijazah'),
                ])
                ->columns(2),
        ];
    }
}

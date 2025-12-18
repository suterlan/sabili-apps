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
use Laravolt\Indonesia\Models\City;
use Laravolt\Indonesia\Models\District;
use Laravolt\Indonesia\Models\Province;
use Laravolt\Indonesia\Models\Village;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile; // Pastikan import ini ada

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
            'pass_email_pendamping',
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

    public static function getDokumenPendampingFormSchema()
    {
        return [
            Forms\Components\Section::make('Berkas Dokumen Pendamping')
                ->description('Size maximal 10MB per file.')
                ->icon('heroicon-o-folder-open')
                ->collapsible()
                ->collapsed()
                ->schema([
                    Forms\Components\Group::make([
                        // 1. PAS FOTO
                        Forms\Components\FileUpload::make('file_pas_foto')
                            ->label('Pas Foto')
                            ->disk('google')
                            ->visibility('private')
                            // Logika Direktori: dokumen_pendamping_budi_1
                            ->directory(function ($get, $record) {
                                $u = $record ?? Auth::user();
                                $name = $u->name ?? $get('name') ?? 'user';
                                $id = $u->id ?? 'new';
                                return 'dokumen_pendamping_' . Str::slug($name) . '_' . $id;
                            })
                            // Logika Rename File: pas_foto_1709823.jpg
                            ->getUploadedFileNameForStorageUsing(
                                fn(TemporaryUploadedFile $file): string =>
                                'pas_foto_' . now()->timestamp . '.' . $file->getClientOriginalExtension()
                            )
                            ->image()
                            ->avatar()
                            ->imageResizeMode('cover')
                            ->imageCropAspectRatio('1:1')
                            ->imageResizeTargetWidth('500')
                            ->maxSize(10240)
                            ->downloadable()
                            ->openable()
                            ->required(),

                        // 2. BUKU REKENING
                        Forms\Components\FileUpload::make('file_buku_rekening')
                            ->label('Foto Buku Rekening')
                            ->disk('google')
                            ->visibility('private')
                            ->directory(function ($get, $record) {
                                $u = $record ?? Auth::user();
                                $name = $u->name ?? $get('name') ?? 'user';
                                $id = $u->id ?? 'new';
                                return 'dokumen_pendamping_' . Str::slug($name) . '_' . $id;
                            })
                            // Logika Rename File: rekening_1709823.jpg
                            ->getUploadedFileNameForStorageUsing(
                                fn(TemporaryUploadedFile $file): string =>
                                'rekening_' . now()->timestamp . '.' . $file->getClientOriginalExtension()
                            )
                            ->image()
                            ->imageResizeTargetWidth('1024')
                            ->maxSize(10240)
                            ->downloadable()
                            ->openable()
                            ->required(),
                    ])->columns(2),

                    Forms\Components\Group::make([
                        // 3. KTP
                        Forms\Components\FileUpload::make('file_ktp')
                            ->label('Foto KTP')
                            ->disk('google')
                            ->visibility('private')
                            ->directory(function ($get, $record) {
                                $u = $record ?? Auth::user();
                                $name = $u->name ?? $get('name') ?? 'user';
                                $id = $u->id ?? 'new';
                                return 'dokumen_pendamping_' . Str::slug($name) . '_' . $id;
                            })
                            // Logika Rename File: ktp_1709823.jpg
                            ->getUploadedFileNameForStorageUsing(
                                fn(TemporaryUploadedFile $file): string =>
                                'ktp_' . now()->timestamp . '.' . $file->getClientOriginalExtension()
                            )
                            ->image()
                            ->imageResizeMode('cover')
                            ->imageCropAspectRatio('16:9')
                            ->imageResizeTargetWidth('1024')
                            ->maxSize(10240)
                            ->downloadable()
                            ->openable()
                            ->required(),

                        // 4. IJAZAH
                        Forms\Components\FileUpload::make('file_ijazah')
                            ->label('Foto Ijazah Terakhir')
                            ->disk('google')
                            ->visibility('private')
                            ->directory(function ($get, $record) {
                                $u = $record ?? Auth::user();
                                $name = $u->name ?? $get('name') ?? 'user';
                                $id = $u->id ?? 'new';
                                return 'dokumen_pendamping_' . Str::slug($name) . '_' . $id;
                            })
                            // Logika Rename File: ijazah_1709823.jpg
                            ->getUploadedFileNameForStorageUsing(
                                fn(TemporaryUploadedFile $file): string =>
                                'ijazah_' . now()->timestamp . '.' . $file->getClientOriginalExtension()
                            )
                            ->image()
                            ->imageResizeTargetWidth('1024')
                            ->maxSize(10240)
                            ->downloadable()
                            ->openable()
                            ->required(),
                    ])->columns(2),
                ]),
        ];
    }
}

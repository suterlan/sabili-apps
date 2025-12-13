<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

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
        // Kolom Baru
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
        'status'
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
        return $this->isSuperAdmin() || $this->isAdmin() || $this->isPendamping();
    }

    // Helper untuk mengecek apakah user sudah diverifikasi
    public function isVerified()
    {
        return $this->status === 'verified';
    }
}

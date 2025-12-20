<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pengajuan extends Model
{
    protected $guarded = [];

    // DEFINISI STATUS
    const STATUS_MENUNGGU = 'Menunggu Verifikasi';
    const STATUS_NIK_TERDAFTAR = 'NIK sudah Terdaftar NIB';
    const STATUS_UPLOAD_NIB = 'Silahkan Upload foto NIB';
    const STATUS_NIK_INVALID = 'Data NIK Tidak Valid';
    const STATUS_UPLOAD_KK = 'Silahkan Upload KK';
    const STATUS_DIPROSES = 'Pengajuan Diproses';
    const STATUS_SERTIFIKAT = 'Sertifikat Diterbitkan';
    const STATUS_INVOICE = 'Invoice Diajukan';
    const STATUS_SELESAI = 'Selesai';

    public static function getStatusVerifikasiOptions(): array
    {
        return [
            self::STATUS_MENUNGGU => 'Menunggu Verifikasi',
            self::STATUS_NIK_TERDAFTAR => 'NIK sudah Terdaftar NIB',
            self::STATUS_UPLOAD_NIB => 'Silahkan Upload foto NIB',
            self::STATUS_NIK_INVALID => 'Data NIK Tidak Valid',
            self::STATUS_UPLOAD_KK => 'Silahkan Upload KK',
            self::STATUS_DIPROSES => 'Pengajuan Diproses',
            self::STATUS_SERTIFIKAT => 'Sertifikat Diterbitkan',
            self::STATUS_INVOICE => 'Invoice Diajukan',
            self::STATUS_SELESAI => 'Selesai',
        ];
    }

    // RELASI
    public function user() // Pelaku Usaha
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function pendamping()
    {
        return $this->belongsTo(User::class, 'pendamping_id');
    }

    public function verificator()
    {
        return $this->belongsTo(User::class, 'verificator_id');
    }
}

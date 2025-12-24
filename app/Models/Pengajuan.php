<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pengajuan extends Model
{
    protected $guarded = [];

    // DEFINISI STATUS
    const STATUS_MENUNGGU = 'Menunggu Verifikasi';
    const STATUS_DIPROSES = 'Pengajuan Diproses';

    const STATUS_LOLOS_VERIFIKASI = 'Lolos Verifikasi';

    const STATUS_UPLOAD_NIB = 'Silahkan Upload foto NIB (NIK sudah terdaftar NIB)';
    const STATUS_NIK_INVALID = 'Data NIK Tidak Valid';
    const STATUS_UPLOAD_ULANG_FOTO = 'Silahkan Upload Ulang Foto';

    const STATUS_PENGAJUAN_DIKIRIM = 'Pengajuan Dikirim';
    const STATUS_PENGAJUAN_DITOLAK = 'Pengajuan Ditolak';

    const STATUS_SERTIFIKAT = 'Sertifikat Diterbitkan';
    const STATUS_INVOICE = 'Invoice Diajukan';
    const STATUS_SELESAI = 'Selesai';

    public static function getStatusVerifikasiOptions(): array
    {
        return [
            self::STATUS_MENUNGGU => 'Menunggu Verifikasi',
            self::STATUS_DIPROSES => 'Pengajuan Diproses',

            self::STATUS_LOLOS_VERIFIKASI => 'Lolos Verifikasi',

            self::STATUS_UPLOAD_NIB => 'Silahkan Upload foto NIB (NIK sudah terdaftar NIB)',
            self::STATUS_NIK_INVALID => 'Data NIK Tidak Valid',
            self::STATUS_UPLOAD_ULANG_FOTO => 'Silahkan Upload Ulang Foto',

            self::STATUS_PENGAJUAN_DIKIRIM => 'Pengajuan Dikirim',
            self::STATUS_PENGAJUAN_DITOLAK => 'Pengajuan Ditolak',

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

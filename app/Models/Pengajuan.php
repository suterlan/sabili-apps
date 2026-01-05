<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pengajuan extends Model
{
    // Field:
    // user_id
    // pendamping_id
    // verificator_id
    // status_verifikasi
    // catatan_revisi
    // file_sertifikat (new added)
    // verified_at

    protected $guarded = [];

    protected $casts = [
        'verified_at' => 'datetime', // Casting agar terbaca sebagai objek Carbon
    ];

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

    // --- TAMBAHAN BARU: Opsi Khusus Manual Verifikasi ---
    public static function getOpsiManualVerifikator(): array
    {
        $opsi = self::getStatusVerifikasiOptions();

        // Hapus opsi yang dikelola oleh Sistem / Excel Import
        unset($opsi[self::STATUS_INVOICE]); // Invoice dibuat otomatis via Import
        unset($opsi[self::STATUS_SELESAI]); // Selesai otomatis setelah bayar / konfirmasi

        return $opsi;
    }

    public static function getStatRevisi(): array
    {
        return [
            self::STATUS_NIK_INVALID,
            self::STATUS_UPLOAD_NIB,
            self::STATUS_UPLOAD_ULANG_FOTO,
            self::STATUS_PENGAJUAN_DITOLAK,
        ];
    }

    public static function getStatProses(): array
    {
        return [
            self::STATUS_MENUNGGU,
            self::STATUS_DIPROSES,
            self::STATUS_LOLOS_VERIFIKASI,
        ];
    }

    public static function getStatDikirim(): array
    {
        return [
            self::STATUS_PENGAJUAN_DIKIRIM,
        ];
    }

    public static function getStatSiapInvoice(): array
    {
        return [
            self::STATUS_SERTIFIKAT,
        ];
    }

    public static function getStatInvoiceSelesai(): array
    {
        return [
            self::STATUS_INVOICE,
            self::STATUS_SELESAI,
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

    public function tagihan()
    {
        return $this->belongsTo(Tagihan::class, 'tagihan_id');
    }

    public function getAutoInvoiceNumberAttribute()
    {
        // Format: INV - TAHUNBULANTANGGAL - ID_PENGAJUAN (5 Digit)
        // Contoh: INV-20240520-00123
        // Menggunakan ID menjamin unik dan konsisten (tidak akan bentrok antar user)
        return 'INV-' . date('Ymd') . '-' . str_pad($this->id, 5, '0', STR_PAD_LEFT);
    }
}

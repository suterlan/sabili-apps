<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Tagihan extends Model
{
    protected $fillable = [
        'nomor_invoice',
        'pendamping_id',
        'total_nominal',
        'link_pembayaran',
        'status_pembayaran', // DIBAYAR, BELUM DIBAYAR
        'tanggal_terbit',
    ];

    const STATUS_DIBAYAR = 'DIBAYAR';
    const STATUS_BELUM_DIBAYAR = 'BELUM DIBAYAR';

    // RELASI UTAMA (1 Invoice bisa punya Banyak Pengajuan)
    // Gunakan ini jika ingin meloop semua produk dalam 1 invoice
    public function pengajuans(): HasMany
    {
        return $this->hasMany(Pengajuan::class, 'tagihan_id');
    }

    // RELASI HELPER (Ambil 1 saja untuk Tampilan Tabel/Widget)
    // Kita ambil pengajuan yang paling baru atau paling lama sebagai "Perwakilan" nama
    public function pengajuan(): HasOne
    {
        return $this->hasOne(Pengajuan::class, 'tagihan_id')->latestOfMany();
    }

    // Relasi ke Pendamping
    public function pendamping()
    {
        return $this->belongsTo(User::class, 'pendamping_id');
    }
}

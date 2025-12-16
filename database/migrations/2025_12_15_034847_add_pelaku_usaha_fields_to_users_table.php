<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Data Diri Pelaku Usaha
            $table->string('nik')->nullable();
            $table->date('tanggal_lahir')->nullable();

            // Data Wilayah
            $table->string('desa')->nullable();
            $table->string('kecamatan')->nullable();
            $table->string('kabupaten')->nullable();
            $table->string('provinsi')->nullable();

            // Data Akun Email Pelaku Usaha (Simpan password email jika diminta)
            $table->string('pass_email')->nullable();

            // Data Usaha
            $table->string('merk_dagang')->nullable(); // Merk / Jenis Dagangan
            $table->string('nomor_nib')->nullable();
            $table->string('mitra_halal')->nullable(); // Nama Mitra Lembaga Halal

            // File Tambahan (File KTP sudah ada sebelumnya)
            $table->string('file_foto_produk')->nullable();
            $table->string('file_foto_bersama')->nullable(); // Foto Pelaku usaha dgn Pendamping
            $table->string('file_foto_nib')->nullable();
            $table->string('file_foto_usaha')->nullable(); // Tampak depan tempat usaha
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
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
                'file_foto_usaha'
            ]);
        });
    }
};

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
            // Data Pribadi & Akun External
            $table->string('pass_email_pendamping')->nullable(); // Hati-hati: Plain text (Sesuai request)
            $table->string('alamat_domisili')->nullable();
            $table->string('akun_halal')->nullable();
            $table->string('pass_akun_halal')->nullable();

            // Pendidikan
            $table->string('pendidikan_terakhir')->nullable();
            $table->string('nama_instansi')->nullable();

            // Bank
            $table->string('nama_bank')->nullable();
            $table->string('nomor_rekening')->nullable();

            // Uploads (Menyimpan path file)
            $table->string('file_ktp')->nullable();
            $table->string('file_ijazah')->nullable();
            $table->string('file_pas_foto')->nullable();
            $table->string('file_buku_rekening')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
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
                'file_buku_rekening'
            ]);
        });
    }
};

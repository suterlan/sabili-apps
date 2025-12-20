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
        Schema::create('pengajuans', function (Blueprint $table) {
            $table->id();
            // RELASI
            // Siapa Pelaku Usahanya?
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // Siapa Pendamping yang input? (Opsional, buat tracking kinerja pendamping)
            $table->foreignId('pendamping_id')->nullable()->constrained('users');

            // Siapa Admin Verifikatornya?
            $table->foreignId('verificator_id')->nullable()->constrained('users');

            // DATA PROSES
            $table->string('status_verifikasi')->default('Menunggu Verifikasi')->index();
            $table->text('catatan_revisi')->nullable(); // Jika ada perbaikan
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengajuans');
    }
};

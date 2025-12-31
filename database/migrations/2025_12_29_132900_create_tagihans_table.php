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
        Schema::create('tagihans', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_invoice')->unique(); // Kunci utama invoice
            $table->foreignId('pendamping_id')->constrained('users'); // Siapa yang ditagih
            $table->decimal('total_nominal', 15, 2);
            $table->string('link_pembayaran')->nullable();
            $table->string('status_pembayaran')->default('BELUM DIBAYAR'); // DIBAYAR, BELUM DIBAYAR
            $table->date('tanggal_terbit');
            $table->timestamps();
        });

        // Tambahkan kolom tagihan_id ke tabel pengajuans
        Schema::table('pengajuans', function (Blueprint $table) {
            $table->foreignId('tagihan_id')->nullable()->constrained('tagihans')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tagihans');
    }
};

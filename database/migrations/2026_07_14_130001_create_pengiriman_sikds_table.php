<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pelacakan pengiriman data ke SIKD Teman Desa (fondasi M4).
 * Satu baris = satu upaya sinkronisasi per desa/tahun/kategori.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengiriman_sikds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('desa_id')->constrained('desas')->restrictOnDelete();
            $table->foreignId('tahun_anggaran_id')->constrained('tahun_anggarans')->restrictOnDelete();
            $table->string('kategori');            // App\Enums\KategoriDataSikd
            $table->string('jalur')->default('api'); // api | zip (fallback)
            $table->string('status')->default('antri'); // App\Enums\StatusPengirimanSikd
            $table->unsignedTinyInteger('percobaan')->default(0);
            $table->string('pesan_gagal', 500)->nullable();
            $table->timestamp('terkirim_pada')->nullable();
            $table->timestamps();

            $table->index(['desa_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengiriman_sikds');
    }
};

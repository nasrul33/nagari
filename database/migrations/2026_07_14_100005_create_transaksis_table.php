<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaksis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('desa_id')->constrained('desas')->cascadeOnDelete();
            $table->foreignId('tahun_anggaran_id')->constrained('tahun_anggarans')->restrictOnDelete();
            $table->foreignId('akun_id')->constrained('akuns')->restrictOnDelete();
            $table->foreignId('apbdes_id')->nullable()->constrained('apbdes')->restrictOnDelete();
            $table->date('tanggal');
            $table->string('uraian');
            $table->decimal('jumlah', 18, 2);
            $table->string('status')->default('draft'); // App\Enums\StatusTransaksi
            $table->string('nomor_spp')->nullable();
            $table->string('nomor_spm')->nullable();
            $table->foreignId('spm_ditandatangani_oleh')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('nomor_rekomendasi_camat')->nullable();
            $table->timestamps();

            $table->index(['desa_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaksis');
    }
};

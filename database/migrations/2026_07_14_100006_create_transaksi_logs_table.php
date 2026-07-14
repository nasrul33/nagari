<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Log transisi state per transaksi — termasuk percobaan yang DITOLAK
 * (role salah / state dilompati), sesuai aturan skill spp-spm-workflow.
 * Melengkapi audit trail owen-it/laravel-auditing yang hanya merekam
 * perubahan model yang berhasil.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaksi_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaksi_id')->constrained('transaksis')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('dari_status');
            $table->string('ke_status');
            $table->boolean('berhasil');
            $table->string('alasan')->nullable(); // diisi saat ditolak
            $table->timestamp('created_at');

            $table->index(['transaksi_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaksi_logs');
    }
};

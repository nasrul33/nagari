<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jejak audit sinkronisasi offline (M5) — mencatat tiap item yang di-sync,
 * termasuk konflik yang kalah (versi lama ditolak) dan draft terkunci yang
 * ditolak. Append-only, per tenant.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sinkronisasi_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('desa_id')->constrained('desas')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('transaksi_id')->nullable()->constrained('transaksis')->nullOnDelete();
            $table->uuid('uuid');
            $table->string('hasil');       // App\Enums\HasilSinkronisasi
            $table->string('keterangan', 500)->nullable();
            $table->timestamp('created_at');

            $table->index(['desa_id', 'created_at']);
            $table->index('uuid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sinkronisasi_logs');
    }
};

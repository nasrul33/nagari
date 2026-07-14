<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Log transisi append-only: kaskade delete DB akan memusnahkan jejak audit
 * tanpa menyentuh event model — FK diganti restrict (temuan T-5 audit M2).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaksi_logs', function (Blueprint $table) {
            $table->dropForeign(['transaksi_id']);
            $table->foreign('transaksi_id')->references('id')->on('transaksis')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('transaksi_logs', function (Blueprint $table) {
            $table->dropForeign(['transaksi_id']);
            $table->foreign('transaksi_id')->references('id')->on('transaksis')->cascadeOnDelete();
        });
    }
};

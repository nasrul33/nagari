<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Temuan T-7 audit M2: apbdes.desa_id dibiarkan nullable setelah backfill —
 * baris NULL jadi data yatim yang lolos dari scope tenant dan laporan.
 */
return new class extends Migration
{
    public function up(): void
    {
        $yatim = DB::table('apbdes')->whereNull('desa_id')->count();

        if ($yatim > 0) {
            throw new RuntimeException(
                "Ada {$yatim} baris apbdes tanpa desa_id — perbaiki data yatim ini dulu sebelum migrasi."
            );
        }

        Schema::table('apbdes', function (Blueprint $table) {
            $table->foreignId('desa_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('apbdes', function (Blueprint $table) {
            $table->foreignId('desa_id')->nullable()->change();
        });
    }
};

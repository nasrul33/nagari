<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Denormalisasi desa_id ke apbdes untuk scoping tenant langsung —
 * scoping lewat relasi tahun_anggaran rawan bocor di query langsung.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('apbdes', function (Blueprint $table) {
            $table->foreignId('desa_id')->nullable()->after('id')->constrained('desas')->cascadeOnDelete();
        });

        DB::statement(<<<'SQL'
            UPDATE apbdes
            SET desa_id = (
                SELECT tahun_anggarans.desa_id
                FROM tahun_anggarans
                WHERE tahun_anggarans.id = apbdes.tahun_anggaran_id
            )
        SQL);
    }

    public function down(): void
    {
        Schema::table('apbdes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('desa_id');
        });
    }
};

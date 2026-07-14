<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom pendukung sinkronisasi offline (M5).
 * - uuid: kunci idempotensi yang di-generate KLIEN (bukan server) — sync ulang
 *   draft yang sama tidak menggandakan baris.
 * - client_updated_at: timestamp edit sisi klien dari versi yang tersimpan;
 *   dipakai resolusi konflik "versi terbaru menang" antar perangkat.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaksis', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->unique()->after('id');
            // string, BUKAN timestamp: kolom timestamp MySQL default memangkas
            // sub-detik sehingga presisi milidetik yang jadi dasar resolusi
            // konflik hilang di prod (temuan T-3 audit). Disimpan ISO mentah,
            // dibandingkan via Carbon::parse.
            $table->string('client_updated_at')->nullable()->after('nomor_rekomendasi_camat');
        });
    }

    public function down(): void
    {
        Schema::table('transaksis', function (Blueprint $table) {
            $table->dropColumn(['uuid', 'client_updated_at']);
        });
    }
};

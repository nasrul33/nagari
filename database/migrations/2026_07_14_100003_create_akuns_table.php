<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bagan akun (COA) 5 level per Permendagri 113/2014 & 20/2018.
 * Data di-seed secara global (BUKAN per tenant) dan dikunci —
 * lihat .claude/skills/coa-desa/SKILL.md dan CoaSeeder.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('akuns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('akuns')->restrictOnDelete();
            $table->string('kode')->unique(); // kodefikasi resmi, mis. "4.1.1.01"
            $table->string('nama');
            $table->unsignedTinyInteger('level'); // 1=Akun ... 5=Rincian Objek
            $table->boolean('is_locked')->default(true); // baris seeded resmi tidak boleh diubah
            $table->timestamps();

            $table->index('level');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('akuns');
    }
};

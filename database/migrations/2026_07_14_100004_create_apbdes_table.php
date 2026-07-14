<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('apbdes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tahun_anggaran_id')->constrained('tahun_anggarans')->cascadeOnDelete();
            $table->foreignId('akun_id')->constrained('akuns')->restrictOnDelete();
            $table->string('uraian');
            $table->decimal('jumlah_anggaran', 18, 2);
            $table->timestamps();

            $table->unique(['tahun_anggaran_id', 'akun_id', 'uraian']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('apbdes');
    }
};

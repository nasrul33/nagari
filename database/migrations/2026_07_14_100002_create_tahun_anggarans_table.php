<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tahun_anggarans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('desa_id')->constrained('desas')->cascadeOnDelete();
            $table->unsignedSmallInteger('tahun');
            $table->string('status')->default('draft'); // draft | aktif | ditutup
            $table->timestamps();

            $table->unique(['desa_id', 'tahun']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tahun_anggarans');
    }
};

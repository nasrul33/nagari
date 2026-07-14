<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('desas', function (Blueprint $table) {
            $table->id();
            $table->string('kode_desa')->unique(); // kode wilayah Kemendagri
            $table->string('nama');
            $table->string('kecamatan');
            $table->string('kabupaten');
            $table->string('provinsi');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('desas');
    }
};

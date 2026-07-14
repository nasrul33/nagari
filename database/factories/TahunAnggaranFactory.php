<?php

namespace Database\Factories;

use App\Models\Desa;
use App\Models\TahunAnggaran;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TahunAnggaran> */
class TahunAnggaranFactory extends Factory
{
    protected $model = TahunAnggaran::class;

    public function definition(): array
    {
        return [
            'desa_id' => Desa::factory(),
            'tahun' => (int) date('Y'),
            'status' => 'aktif',
        ];
    }
}

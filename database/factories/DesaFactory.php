<?php

namespace Database\Factories;

use App\Models\Desa;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Desa> */
class DesaFactory extends Factory
{
    protected $model = Desa::class;

    public function definition(): array
    {
        return [
            'kode_desa' => $this->faker->unique()->numerify('13.##.##.2###'),
            'nama' => 'Nagari '.$this->faker->lastName(),
            'kecamatan' => 'Kecamatan '.$this->faker->lastName(),
            'kabupaten' => 'Kabupaten '.$this->faker->lastName(),
            'provinsi' => 'Sumatera Barat',
        ];
    }
}

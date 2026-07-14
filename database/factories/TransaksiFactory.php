<?php

namespace Database\Factories;

use App\Enums\StatusTransaksi;
use App\Models\Akun;
use App\Models\TahunAnggaran;
use App\Models\Transaksi;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Transaksi> */
class TransaksiFactory extends Factory
{
    protected $model = Transaksi::class;

    public function definition(): array
    {
        return [
            'tahun_anggaran_id' => TahunAnggaran::factory(),
            'desa_id' => fn (array $attrs) => TahunAnggaran::findOrFail($attrs['tahun_anggaran_id'])->desa_id,
            'akun_id' => fn () => Akun::where('level', 1)->firstOrFail()->id,
            'apbdes_id' => null,
            'tanggal' => $this->faker->dateTimeThisYear(),
            'uraian' => $this->faker->sentence(4),
            'jumlah' => $this->faker->numberBetween(500_000, 50_000_000),
            'status' => StatusTransaksi::Draft,
        ];
    }
}

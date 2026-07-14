<?php

namespace Database\Seeders;

use App\Enums\PeranDesa;
use App\Models\Desa;
use App\Models\TahunAnggaran;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Data demo untuk pengembangan lokal — JANGAN dijalankan di produksi.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $desa = Desa::firstOrCreate(
            ['kode_desa' => '13.01.01.2001'],
            [
                'nama' => 'Nagari Contoh',
                'kecamatan' => 'Kecamatan Contoh',
                'kabupaten' => 'Kabupaten Contoh',
                'provinsi' => 'Sumatera Barat',
            ],
        );

        TahunAnggaran::firstOrCreate(
            ['desa_id' => $desa->id, 'tahun' => (int) date('Y')],
            ['status' => 'aktif'],
        );

        $akun = [
            PeranDesa::KepalaDesa->value => 'kades@demo.test',
            PeranDesa::SekretarisDesa->value => 'sekdes@demo.test',
            PeranDesa::KaurKeuangan->value => 'kaur@demo.test',
            PeranDesa::Bpd->value => 'bpd@demo.test',
        ];

        foreach ($akun as $peran => $email) {
            User::firstOrCreate(
                ['email' => $email],
                [
                    'desa_id' => $desa->id,
                    'name' => PeranDesa::from($peran)->label().' Demo',
                    'password' => 'password',
                ],
            )->syncRoles([$peran]);
        }
    }
}

<?php

namespace Database\Seeders;

use App\Enums\LevelAkun;
use App\Models\Akun;
use Illuminate\Database\Seeder;

/**
 * Seeder bagan akun (COA) per Permendagri 113/2014 & 20/2018.
 *
 * STATUS (lihat .claude/skills/coa-desa/SKILL.md): baru KERANGKA level 1.
 * Daftar kode rekening lengkap level 2–5 (Kelompok/Jenis/Objek/Rincian Objek)
 * WAJIB diambil dari lampiran resmi Permendagri — JANGAN dikarang.
 * Setelah daftar resmi tersedia, tempel di skill coa-desa lalu perluas
 * method kerangkaResmi() di bawah.
 */
class CoaSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->kerangkaResmi() as $kode => $nama) {
            Akun::firstOrCreate(
                ['kode' => $kode],
                [
                    'parent_id' => null,
                    'nama' => $nama,
                    'level' => LevelAkun::Akun,
                    'is_locked' => true,
                ],
            );
        }
    }

    /**
     * 5 kategori akun utama (level 1) — satu-satunya bagian kodefikasi
     * yang sudah terkonfirmasi di skill coa-desa.
     *
     * @return array<string, string>
     */
    private function kerangkaResmi(): array
    {
        return [
            '1' => 'Aset',
            '2' => 'Kewajiban',
            '3' => 'Kekayaan Bersih',
            '4' => 'Pendapatan',
            '5' => 'Belanja',
        ];
    }
}

<?php

namespace App\Console\Commands;

use App\Enums\PeranDesa;
use App\Models\Desa;
use App\Models\TahunAnggaran;
use App\Models\User;
use Database\Seeders\CoaSeeder;
use Database\Seeders\PeranSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Onboarding tenant baru (tenant = desa).
 *
 * COA TIDAK di-seed per tenant — bagan akun global dan terkunci
 * (CLAUDE.md / skill coa-desa); command ini hanya memastikan seeder
 * global idempoten sudah jalan.
 */
class BuatDesaBaru extends Command
{
    protected $signature = 'desa:baru
        {--kode= : Kode wilayah desa (Kemendagri), mis. 13.01.01.2001}
        {--nama= : Nama desa/nagari}
        {--kecamatan= : Nama kecamatan}
        {--kabupaten= : Nama kabupaten}
        {--provinsi= : Nama provinsi}
        {--tahun= : Tahun anggaran pertama (default: tahun berjalan)}';

    protected $description = 'Onboarding desa (tenant) baru: buat desa, tahun anggaran, dan 4 akun perangkat';

    public function handle(): int
    {
        $kode = $this->option('kode') ?? $this->ask('Kode wilayah desa');
        $nama = $this->option('nama') ?? $this->ask('Nama desa/nagari');
        $kecamatan = $this->option('kecamatan') ?? $this->ask('Kecamatan');
        $kabupaten = $this->option('kabupaten') ?? $this->ask('Kabupaten');
        $provinsi = $this->option('provinsi') ?? $this->ask('Provinsi');
        $tahun = (int) ($this->option('tahun') ?: date('Y'));

        if (blank($kode) || blank($nama) || blank($kecamatan) || blank($kabupaten) || blank($provinsi)) {
            $this->error('Semua data desa wajib diisi.');

            return self::FAILURE;
        }

        if (Desa::where('kode_desa', $kode)->exists()) {
            $this->error("Desa dengan kode [{$kode}] sudah terdaftar.");

            return self::FAILURE;
        }

        // Prasyarat global yang idempoten: peran RBAC + kerangka COA
        (new PeranSeeder)->run();
        (new CoaSeeder)->run();

        // Domain email berbasis kode_desa (unik nasional) — nama desa sering
        // berulang lintas kabupaten (temuan T-4 audit M2).
        // PERHATIAN (temuan B-4 audit): alamat ini identifier login semata,
        // BUKAN mailbox yang bisa menerima surat. Jangan pernah membangun
        // fitur reset-password-via-email di atasnya tanpa verifikasi
        // kepemilikan alamat sungguhan.
        $domain = str_replace('.', '-', $kode).'.desa.id';

        $emails = collect(PeranDesa::cases())
            ->map(fn (PeranDesa $p) => str_replace('_', '.', $p->value).'@'.$domain);

        if ($bentrok = User::whereIn('email', $emails)->pluck('email')->first()) {
            $this->error("Email [{$bentrok}] sudah terpakai — onboarding dibatalkan.");

            return self::FAILURE;
        }

        $kredensial = [];

        DB::transaction(function () use ($kode, $nama, $kecamatan, $kabupaten, $provinsi, $tahun, $domain, &$kredensial) {
            $desa = Desa::create([
                'kode_desa' => $kode,
                'nama' => $nama,
                'kecamatan' => $kecamatan,
                'kabupaten' => $kabupaten,
                'provinsi' => $provinsi,
            ]);

            TahunAnggaran::create([
                'desa_id' => $desa->id,
                'tahun' => $tahun,
                'status' => 'aktif',
            ]);

            foreach (PeranDesa::cases() as $peran) {
                $password = Str::password(16);
                $email = str_replace('_', '.', $peran->value).'@'.$domain;

                $user = User::create([
                    'desa_id' => $desa->id,
                    'name' => $peran->label().' '.$nama,
                    'email' => $email,
                    'password' => $password,
                ]);

                // Password onboarding bersifat sementara — sistem memaksa
                // penggantian pada login pertama (temuan T-8 audit M2).
                $user->forceFill(['must_change_password' => true])->save();
                $user->assignRole($peran->value);

                $kredensial[] = [$peran->label(), $email, $password];
            }
        });

        $this->info("Desa [{$nama}] berhasil di-onboard (tahun anggaran {$tahun}).");
        $this->table(['Peran', 'Email', 'Password sementara'], $kredensial);
        $this->warn('Simpan kredensial di atas sekarang — password tidak bisa dilihat lagi, dan wajib diganti saat serah terima.');

        return self::SUCCESS;
    }
}

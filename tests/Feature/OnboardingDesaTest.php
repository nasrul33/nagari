<?php

use App\Enums\PeranDesa;
use App\Models\Akun;
use App\Models\Desa;
use App\Models\TahunAnggaran;
use App\Models\User;

it('meng-onboard desa baru lengkap dengan tahun anggaran dan 4 akun perangkat', function () {
    $this->artisan('desa:baru', [
        '--kode' => '13.02.03.2004',
        '--nama' => 'Nagari Uji',
        '--kecamatan' => 'Kecamatan Uji',
        '--kabupaten' => 'Kabupaten Uji',
        '--provinsi' => 'Sumatera Barat',
        '--tahun' => 2026,
    ])->assertSuccessful();

    $desa = Desa::where('kode_desa', '13.02.03.2004')->firstOrFail();

    expect(TahunAnggaran::where('desa_id', $desa->id)->where('tahun', 2026)->where('status', 'aktif')->exists())->toBeTrue()
        ->and(User::where('desa_id', $desa->id)->count())->toBe(4);

    foreach (PeranDesa::cases() as $peran) {
        $user = User::where('desa_id', $desa->id)
            ->get()
            ->first(fn (User $u) => $u->hasRole($peran->value));

        expect($user)->not->toBeNull("tidak ada user ber-peran {$peran->value}");
    }

    // COA global ikut ter-seed idempoten, tidak digandakan per tenant
    expect(Akun::count())->toBe(5);
});

it('onboarding kedua kali tidak menggandakan COA global', function () {
    foreach ([['13.01.01.2001', 'Nagari Satu'], ['13.01.01.2002', 'Nagari Dua']] as [$kode, $nama]) {
        $this->artisan('desa:baru', [
            '--kode' => $kode,
            '--nama' => $nama,
            '--kecamatan' => 'Kec',
            '--kabupaten' => 'Kab',
            '--provinsi' => 'Prov',
        ])->assertSuccessful();
    }

    expect(Akun::count())->toBe(5)
        ->and(Desa::count())->toBe(2);
});

it('menolak kode desa yang sudah terdaftar', function () {
    Desa::factory()->create(['kode_desa' => '13.09.09.2009']);

    $this->artisan('desa:baru', [
        '--kode' => '13.09.09.2009',
        '--nama' => 'Duplikat',
        '--kecamatan' => 'Kec',
        '--kabupaten' => 'Kab',
        '--provinsi' => 'Prov',
    ])->assertFailed();

    expect(Desa::where('kode_desa', '13.09.09.2009')->count())->toBe(1);
});

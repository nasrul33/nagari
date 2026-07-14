<?php

use App\Enums\PeranDesa;
use App\Models\Desa;
use App\Models\TahunAnggaran;
use Database\Seeders\CoaSeeder;

beforeEach(function () {
    $this->seed(CoaSeeder::class);
    $this->desa = Desa::factory()->create();
    TahunAnggaran::factory()->for($this->desa)->create();
});

it('menampilkan halaman entri offline untuk Kaur Keuangan', function () {
    $this->actingAs(userDenganPeran(PeranDesa::KaurKeuangan, $this->desa))
        ->get(route('transaksi.offline'))
        ->assertOk()
        ->assertSee('Draft Transaksi Offline')
        ->assertSee('offlineDraft('); // komponen Alpine terpasang
});

it('menolak peran selain Kaur Keuangan', function (PeranDesa $peran) {
    $this->actingAs(userDenganPeran($peran, $this->desa))
        ->get(route('transaksi.offline'))
        ->assertForbidden();
})->with([
    'Kades' => PeranDesa::KepalaDesa,
    'Sekdes' => PeranDesa::SekretarisDesa,
    'BPD' => PeranDesa::Bpd,
]);

it('tamu diarahkan ke login', function () {
    $this->get(route('transaksi.offline'))->assertRedirect(route('login'));
});

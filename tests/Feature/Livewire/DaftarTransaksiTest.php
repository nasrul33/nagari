<?php

use App\Enums\PeranDesa;
use App\Livewire\Transaksi\DaftarTransaksi;
use App\Models\Desa;
use App\Models\TahunAnggaran;
use App\Models\Transaksi;
use Database\Seeders\CoaSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(CoaSeeder::class);

    $this->desa = Desa::factory()->create();
    $this->kaur = userDenganPeran(PeranDesa::KaurKeuangan, $this->desa);
});

function buatTransaksiUntuk(Desa $desa, string $uraian): Transaksi
{
    return Transaksi::factory()
        ->for($desa)
        ->for(TahunAnggaran::factory()->for($desa)->create())
        ->create(['uraian' => $uraian]);
}

it('hanya menampilkan transaksi desa milik user', function () {
    buatTransaksiUntuk($this->desa, 'Belanja ATK desa sendiri');
    buatTransaksiUntuk(Desa::factory()->create(), 'Belanja desa tetangga');

    Livewire::actingAs($this->kaur)
        ->test(DaftarTransaksi::class)
        ->assertSee('Belanja ATK desa sendiri')
        ->assertDontSee('Belanja desa tetangga');
});

it('bisa memfilter berdasarkan status', function () {
    buatTransaksiUntuk($this->desa, 'Masih draft');

    Livewire::actingAs($this->kaur)
        ->test(DaftarTransaksi::class)
        ->set('status', 'draft')
        ->assertSee('Masih draft')
        ->set('status', 'selesai')
        ->assertDontSee('Masih draft');
});

it('menampilkan tombol transaksi baru hanya untuk Kaur Keuangan', function () {
    Livewire::actingAs($this->kaur)
        ->test(DaftarTransaksi::class)
        ->assertSee('Transaksi Baru');

    Livewire::actingAs(userDenganPeran(PeranDesa::Bpd, $this->desa))
        ->test(DaftarTransaksi::class)
        ->assertDontSee('Transaksi Baru');
});

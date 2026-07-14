<?php

use App\Enums\PeranDesa;
use App\Enums\StatusTransaksi;
use App\Livewire\Transaksi\BuatTransaksi;
use App\Models\Akun;
use App\Models\Desa;
use App\Models\TahunAnggaran;
use App\Models\Transaksi;
use Database\Seeders\CoaSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(CoaSeeder::class);

    $this->desa = Desa::factory()->create();
    $this->kaur = userDenganPeran(PeranDesa::KaurKeuangan, $this->desa);
    $this->tahunAnggaran = TahunAnggaran::factory()->for($this->desa)->create();
});

it('hanya Kaur Keuangan yang boleh membuka form', function (PeranDesa $peran) {
    $this->actingAs(userDenganPeran($peran, $this->desa))
        ->get(route('transaksi.buat'))
        ->assertForbidden();
})->with([
    'Kades' => PeranDesa::KepalaDesa,
    'Sekdes' => PeranDesa::SekretarisDesa,
    'BPD' => PeranDesa::Bpd,
]);

it('Kaur Keuangan bisa membuat transaksi draft', function () {
    $akun = Akun::where('kode', '5')->firstOrFail();

    Livewire::actingAs($this->kaur)
        ->test(BuatTransaksi::class)
        ->set('tahun_anggaran_id', $this->tahunAnggaran->id)
        ->set('akun_id', $akun->id)
        ->set('tanggal', '2026-07-14')
        ->set('uraian', 'Belanja ATK kantor desa')
        ->set('jumlah', '1500000')
        ->call('simpan')
        ->assertHasNoErrors();

    $transaksi = Transaksi::firstOrFail();

    expect($transaksi->status)->toBe(StatusTransaksi::Draft)
        ->and($transaksi->desa_id)->toBe($this->desa->id)
        ->and($transaksi->uraian)->toBe('Belanja ATK kantor desa');
});

it('menolak input yang tidak lengkap', function () {
    Livewire::actingAs($this->kaur)
        ->test(BuatTransaksi::class)
        ->call('simpan')
        ->assertHasErrors(['tahun_anggaran_id', 'akun_id', 'uraian', 'jumlah']);

    expect(Transaksi::count())->toBe(0);
});

it('menolak tahun anggaran milik desa lain', function () {
    $tahunDesaLain = TahunAnggaran::factory()->create();

    $komponen = Livewire::actingAs($this->kaur)
        ->test(BuatTransaksi::class)
        ->set('tahun_anggaran_id', $tahunDesaLain->id)
        ->set('akun_id', Akun::where('kode', '5')->firstOrFail()->id)
        ->set('tanggal', '2026-07-14')
        ->set('uraian', 'Curang lintas desa')
        ->set('jumlah', '1000');

    expect(fn () => $komponen->call('simpan'))
        ->toThrow(ModelNotFoundException::class);

    expect(Transaksi::count())->toBe(0);
});

<?php

use App\Enums\PeranDesa;
use App\Enums\StatusTransaksi;
use App\Livewire\Dashboard;
use App\Models\Akun;
use App\Models\Apbdes;
use App\Models\Desa;
use App\Models\TahunAnggaran;
use App\Models\Transaksi;
use Database\Seeders\CoaSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(CoaSeeder::class);

    $this->desa = Desa::factory()->create();
    $this->ta = TahunAnggaran::factory()->for($this->desa)->create(['tahun' => 2026, 'status' => 'aktif']);
    $this->kaur = userDenganPeran(PeranDesa::KaurKeuangan, $this->desa);

    $this->akunPendapatan = Akun::where('kode', '4')->firstOrFail();
    $this->akunBelanja = Akun::where('kode', '5')->firstOrFail();
});

function transaksiDashboard(Desa $desa, TahunAnggaran $ta, Akun $akun, StatusTransaksi $status, float $jumlah, string $tanggal = '2026-03-10'): Transaksi
{
    return Transaksi::factory()->for($desa)->for($ta)->create([
        'akun_id' => $akun->id,
        'status' => $status,
        'jumlah' => $jumlah,
        'tanggal' => $tanggal,
    ]);
}

it('tamu diarahkan ke login', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

it('memilih tahun anggaran aktif terbaru secara default', function () {
    TahunAnggaran::factory()->for($this->desa)->create(['tahun' => 2024, 'status' => 'ditutup']);

    Livewire::actingAs($this->kaur)
        ->test(Dashboard::class)
        ->assertSet('tahun_anggaran_id', $this->ta->id);
});

it('menghitung realisasi hanya dari transaksi dicairkan/selesai, terpisah per kategori COA', function () {
    transaksiDashboard($this->desa, $this->ta, $this->akunPendapatan, StatusTransaksi::Selesai, 2_000_000);
    transaksiDashboard($this->desa, $this->ta, $this->akunBelanja, StatusTransaksi::Dicairkan, 750_000);
    transaksiDashboard($this->desa, $this->ta, $this->akunBelanja, StatusTransaksi::Selesai, 250_000);
    // dalam proses — TIDAK dihitung realisasi
    transaksiDashboard($this->desa, $this->ta, $this->akunBelanja, StatusTransaksi::SppDiajukan, 9_000_000);
    transaksiDashboard($this->desa, $this->ta, $this->akunBelanja, StatusTransaksi::Draft, 5_000_000);

    Livewire::actingAs($this->kaur)
        ->test(Dashboard::class)
        ->assertViewHas('totalPendapatan', 2_000_000.0)
        ->assertViewHas('totalBelanja', 1_000_000.0)
        ->assertViewHas('perStatus', fn ($perStatus) => $perStatus['selesai'] === 2
            && $perStatus['dicairkan'] === 1
            && $perStatus['spp_diajukan'] === 1
            && $perStatus['draft'] === 1);
});

it('mengambil anggaran per kategori dari APBDes', function () {
    Apbdes::create([
        'tahun_anggaran_id' => $this->ta->id,
        'akun_id' => $this->akunBelanja->id,
        'uraian' => 'Total belanja dianggarkan',
        'jumlah_anggaran' => 10_000_000,
    ]);
    Apbdes::create([
        'tahun_anggaran_id' => $this->ta->id,
        'akun_id' => $this->akunPendapatan->id,
        'uraian' => 'Pendapatan dianggarkan',
        'jumlah_anggaran' => 12_000_000,
    ]);

    Livewire::actingAs($this->kaur)
        ->test(Dashboard::class)
        ->assertViewHas('anggaranBelanja', 10_000_000.0)
        ->assertViewHas('anggaranPendapatan', 12_000_000.0);
});

it('menyusun tren bulanan realisasi untuk grafik', function () {
    transaksiDashboard($this->desa, $this->ta, $this->akunPendapatan, StatusTransaksi::Selesai, 1_000_000, '2026-01-15');
    transaksiDashboard($this->desa, $this->ta, $this->akunBelanja, StatusTransaksi::Dicairkan, 400_000, '2026-01-20');
    transaksiDashboard($this->desa, $this->ta, $this->akunBelanja, StatusTransaksi::Selesai, 600_000, '2026-06-05');
    transaksiDashboard($this->desa, $this->ta, $this->akunBelanja, StatusTransaksi::Draft, 999_999, '2026-06-06');

    Livewire::actingAs($this->kaur)
        ->test(Dashboard::class)
        ->assertViewHas('tren', fn ($tren) => count($tren['labels']) === 12
            && $tren['pendapatan'][0] === 1_000_000.0
            && $tren['belanja'][0] === 400_000.0
            && $tren['belanja'][5] === 600_000.0
            && array_sum($tren['belanja']) === 1_000_000.0);
});

it('tidak mencampur angka desa lain (isolasi tenant)', function () {
    $desaB = Desa::factory()->create();
    $taB = TahunAnggaran::factory()->for($desaB)->create(['tahun' => 2026]);
    transaksiDashboard($desaB, $taB, $this->akunBelanja, StatusTransaksi::Selesai, 777_000_000);

    transaksiDashboard($this->desa, $this->ta, $this->akunBelanja, StatusTransaksi::Selesai, 100_000);

    Livewire::actingAs($this->kaur)
        ->test(Dashboard::class)
        ->assertViewHas('totalBelanja', 100_000.0);
});

it('menolak tahun anggaran desa lain dari URL', function () {
    $taDesaLain = TahunAnggaran::factory()->create(['tahun' => 2027]);

    Livewire::actingAs($this->kaur)
        ->withQueryParams(['tahun_anggaran_id' => $taDesaLain->id])
        ->test(Dashboard::class)
        ->assertSet('tahun_anggaran_id', fn ($nilai) => $nilai !== $taDesaLain->id);
});

it('mengganti tahun anggaran memperbarui angka dan mengirim event grafik', function () {
    transaksiDashboard($this->desa, $this->ta, $this->akunBelanja, StatusTransaksi::Selesai, 500_000);

    $ta2027 = TahunAnggaran::factory()->for($this->desa)->create(['tahun' => 2027, 'status' => 'draft']);
    transaksiDashboard($this->desa, $ta2027, $this->akunBelanja, StatusTransaksi::Selesai, 300_000, '2027-02-01');

    Livewire::actingAs($this->kaur)
        ->test(Dashboard::class)
        ->assertViewHas('totalBelanja', 500_000.0)
        ->set('tahun_anggaran_id', $ta2027->id)
        ->assertViewHas('totalBelanja', 300_000.0)
        ->assertDispatched('dashboard-diperbarui');
});

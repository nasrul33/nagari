<?php

use App\Actions\Workflow\AjukanSpp;
use App\Enums\PeranDesa;
use App\Enums\StatusTransaksi;
use App\Exceptions\TransisiDitolakException;
use App\Models\Akun;
use App\Models\Apbdes;
use App\Models\Desa;
use App\Models\TahunAnggaran;
use App\Models\Transaksi;
use App\Models\User;
use Database\Seeders\CoaSeeder;
use Database\Seeders\PeranSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/*
 * Kriteria selesai M2 (PLAN.md): user tenant A tidak bisa akses data
 * tenant B lewat endpoint manapun. Tenant = desa.
 */

beforeEach(function () {
    $this->seed(PeranSeeder::class);
    $this->seed(CoaSeeder::class);

    $this->desaA = Desa::factory()->create();
    $this->desaB = Desa::factory()->create();

    $this->taA = TahunAnggaran::factory()->for($this->desaA)->create();
    $this->taB = TahunAnggaran::factory()->for($this->desaB)->create();

    $this->transaksiA = Transaksi::factory()->for($this->desaA)->for($this->taA)->create();
    $this->transaksiB = Transaksi::factory()->for($this->desaB)->for($this->taB)->create();

    $this->kaurA = userDenganPeran(PeranDesa::KaurKeuangan, $this->desaA);
});

it('global scope membatasi query Transaksi ke desa user yang login', function () {
    $this->actingAs($this->kaurA);

    expect(Transaksi::count())->toBe(1)
        ->and(Transaksi::first()->id)->toBe($this->transaksiA->id)
        ->and(Transaksi::find($this->transaksiB->id))->toBeNull()
        ->and(Transaksi::withoutGlobalScope('desa')->count())->toBe(2);
});

it('global scope membatasi query TahunAnggaran dan Apbdes', function () {
    Apbdes::create([
        'tahun_anggaran_id' => $this->taA->id,
        'akun_id' => Akun::where('kode', '5')->firstOrFail()->id,
        'uraian' => 'Anggaran A',
        'jumlah_anggaran' => 1_000_000,
    ]);
    Apbdes::create([
        'tahun_anggaran_id' => $this->taB->id,
        'akun_id' => Akun::where('kode', '5')->firstOrFail()->id,
        'uraian' => 'Anggaran B',
        'jumlah_anggaran' => 2_000_000,
    ]);

    $this->actingAs($this->kaurA);

    expect(TahunAnggaran::count())->toBe(1)
        ->and(TahunAnggaran::first()->id)->toBe($this->taA->id)
        ->and(Apbdes::count())->toBe(1)
        ->and(Apbdes::first()->uraian)->toBe('Anggaran A');
});

it('menolak Apbdes dengan desa_id yang tidak konsisten dengan tahun anggarannya', function () {
    // user desa A membuat Apbdes menunjuk tahun anggaran desa B — trait
    // mengisi desa_id = A, induknya B → harus ditolak, bukan dibetulkan diam-diam
    $this->actingAs($this->kaurA);

    expect(fn () => Apbdes::create([
        'tahun_anggaran_id' => $this->taB->id,
        'akun_id' => Akun::where('kode', '5')->firstOrFail()->id,
        'uraian' => 'Lintas tenant',
        'jumlah_anggaran' => 1_000,
    ]))->toThrow(LogicException::class, 'tidak sama dengan desa tahun anggaran');
});

it('Apbdes mewarisi desa_id dari tahun anggaran induknya', function () {
    $apbdes = Apbdes::create([
        'tahun_anggaran_id' => $this->taB->id,
        'akun_id' => Akun::where('kode', '4')->firstOrFail()->id,
        'uraian' => 'Pendapatan B',
        'jumlah_anggaran' => 500_000,
    ]);

    expect($apbdes->desa_id)->toBe($this->desaB->id);
});

it('desa_id terisi otomatis saat user login membuat data', function () {
    $this->actingAs($this->kaurA);

    $ta = TahunAnggaran::create(['tahun' => 2030, 'status' => 'draft']);

    expect($ta->desa_id)->toBe($this->desaA->id);
});

it('konteks console/seeder tanpa auth tidak di-scope', function () {
    expect(Transaksi::count())->toBe(2)
        ->and(TahunAnggaran::count())->toBe(2);
});

it('COA tetap global — tidak digandakan per tenant', function () {
    $this->actingAs($this->kaurA);

    expect(Akun::count())->toBe(5);
});

it('Action workflow menolak dan mencatat pelaku dari desa lain apa pun perannya', function () {
    expect(fn () => app(AjukanSpp::class)->handle($this->transaksiB, $this->kaurA, ['nomor_spp' => 'SPP-X']))
        ->toThrow(TransisiDitolakException::class, 'Isolasi desa');

    expect($this->transaksiB->refresh()->status)->toBe(StatusTransaksi::Draft)
        ->and($this->transaksiB->logs()->where('berhasil', false)->count())->toBe(1);
});

it('endpoint detail transaksi desa lain menghasilkan 404 (bukan bocor)', function () {
    $this->actingAs($this->kaurA)
        ->get(route('transaksi.detail', $this->transaksiB))
        ->assertNotFound();
});

it('menolak keras injeksi desa_id tenant lain via mass assignment', function () {
    $this->actingAs($this->kaurA);

    expect(fn () => TahunAnggaran::create([
        'desa_id' => $this->desaB->id, // suntikan lintas tenant
        'tahun' => 2031,
        'status' => 'draft',
    ]))->toThrow(LogicException::class, 'Isolasi desa');

    expect(TahunAnggaran::withoutGlobalScope('desa')->where('tahun', 2031)->exists())->toBeFalse();
});

it('user login tanpa desa_id tidak melihat data tenant mana pun (fail-closed)', function () {
    $tanpaDesa = User::factory()->create(['desa_id' => null]);

    $this->actingAs($tanpaDesa);

    expect(Transaksi::count())->toBe(0)
        ->and(TahunAnggaran::count())->toBe(0)
        ->and(Transaksi::find($this->transaksiA->id))->toBeNull();
});

it('transaksi tidak bisa dihapus — jejak log audit dipertahankan', function () {
    app(AjukanSpp::class)->handle($this->transaksiA, $this->kaurA, ['nomor_spp' => 'SPP-1']);

    expect(fn () => $this->transaksiA->delete())
        ->toThrow(LogicException::class, 'tidak boleh dihapus');

    // kaskade DB juga sudah diganti restrict — delete raw pun gagal
    expect(fn () => DB::table('transaksis')->where('id', $this->transaksiA->id)->delete())
        ->toThrow(QueryException::class);

    expect($this->transaksiA->logs()->count())->toBe(1);
});

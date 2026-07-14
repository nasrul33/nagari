<?php

use App\Enums\PeranDesa;
use App\Models\Akun;
use App\Models\Desa;
use App\Models\TahunAnggaran;
use App\Models\Transaksi;
use Database\Seeders\CoaSeeder;
use Database\Seeders\PeranSeeder;
use Illuminate\Support\Str;

/*
 * Endpoint HTTP sinkronisasi offline: POST /sync/transaksi.
 */

beforeEach(function () {
    $this->seed(PeranSeeder::class);
    $this->seed(CoaSeeder::class);

    $this->desa = Desa::factory()->create();
    $this->ta = TahunAnggaran::factory()->for($this->desa)->create();
    $this->kaur = userDenganPeran(PeranDesa::KaurKeuangan, $this->desa);
    $this->akun = Akun::where('kode', '5')->firstOrFail();
});

function payloadItem(array $override = []): array
{
    $t = test();

    return array_merge([
        'uuid' => (string) Str::uuid(),
        'tahun_anggaran_id' => $t->ta->id,
        'akun_id' => $t->akun->id,
        'tanggal' => '2026-07-15',
        'uraian' => 'Draft via endpoint',
        'jumlah' => 750_000,
        'client_updated_at' => '2026-07-15T08:00:00+00:00',
    ], $override);
}

it('tamu tidak boleh mengakses endpoint sync', function () {
    $this->postJson(route('sync.transaksi'), ['items' => []])
        ->assertUnauthorized();
});

it('Kaur Keuangan bisa menyinkronkan dan menerima hasil per item', function () {
    $item = payloadItem();

    $this->actingAs($this->kaur)
        ->postJson(route('sync.transaksi'), ['items' => [$item]])
        ->assertOk()
        ->assertJsonPath('results.0.hasil', 'dibuat')
        ->assertJsonPath('results.0.uuid', $item['uuid'])
        ->assertJsonStructure(['server_time', 'results' => [['uuid', 'hasil', 'transaksi_id', 'keterangan']]]);

    expect(Transaksi::where('uuid', $item['uuid'])->exists())->toBeTrue();
});

it('menolak peran selain Kaur Keuangan dengan 403', function () {
    $this->actingAs(userDenganPeran(PeranDesa::SekretarisDesa, $this->desa))
        ->postJson(route('sync.transaksi'), ['items' => [payloadItem()]])
        ->assertForbidden();

    expect(Transaksi::count())->toBe(0);
});

it('memvalidasi bentuk payload items', function () {
    $this->actingAs($this->kaur)
        ->postJson(route('sync.transaksi'), ['items' => 'bukan-array'])
        ->assertStatus(422);
});

it('menerima batch kosong tanpa error', function () {
    $this->actingAs($this->kaur)
        ->postJson(route('sync.transaksi'), ['items' => []])
        ->assertOk()
        ->assertJsonPath('results', []);
});

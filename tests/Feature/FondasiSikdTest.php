<?php

use App\Actions\Sikd\AntrikanPengirimanSikd;
use App\Enums\KategoriDataSikd;
use App\Enums\PeranDesa;
use App\Enums\StatusPengirimanSikd;
use App\Jobs\KirimKeSikd;
use App\Models\Desa;
use App\Models\PengirimanSikd;
use App\Models\TahunAnggaran;
use App\Services\Sikd\PenyusunPayloadSikd;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Queue;

/*
 * Fondasi M4 (schema-agnostic) — payload SIKD tetap BLOCKED sampai skema
 * resmi Kemenkeu/DJPK masuk repo. Lihat skill sikd-teman-desa-integration.
 */

beforeEach(function () {
    $this->desa = Desa::factory()->create();
    $this->ta = TahunAnggaran::factory()->for($this->desa)->create();
});

it('menolak antrian saat integrasi belum diaktifkan (default)', function () {
    expect(config('sikd.enabled'))->toBeFalse();

    expect(fn () => app(AntrikanPengirimanSikd::class)->handle($this->ta, KategoriDataSikd::Apbdes))
        ->toThrow(RuntimeException::class, 'belum diaktifkan');

    expect(PengirimanSikd::count())->toBe(0);
});

it('mengantrekan pengiriman dengan tenant context eksplisit saat diaktifkan', function () {
    config(['sikd.enabled' => true]);
    Queue::fake();

    $pengiriman = app(AntrikanPengirimanSikd::class)->handle($this->ta, KategoriDataSikd::Apbdes);

    expect($pengiriman->status)->toBe(StatusPengirimanSikd::Antri)
        ->and($pengiriman->desa_id)->toBe($this->desa->id)
        ->and($pengiriman->kategori)->toBe(KategoriDataSikd::Apbdes);

    // pola T-9: job membawa id + desa_id eksplisit, bukan mengandalkan scope
    Queue::assertPushed(KirimKeSikd::class, fn (KirimKeSikd $job) => $job->pengirimanId === $pengiriman->id
        && $job->desaId === $this->desa->id);
});

it('job gagal PERMANEN dengan pesan skema belum tersedia — tanpa retry sia-sia', function () {
    $pengiriman = PengirimanSikd::create([
        'desa_id' => $this->desa->id,
        'tahun_anggaran_id' => $this->ta->id,
        'kategori' => KategoriDataSikd::Lra,
        'status' => StatusPengirimanSikd::Antri,
    ]);

    // jalankan job secara sinkron melalui dispatcher agar fail() -> failed() terpanggil
    dispatch_sync(new KirimKeSikd($pengiriman->id, $this->desa->id));

    $pengiriman->refresh();

    expect($pengiriman->status)->toBe(StatusPengirimanSikd::Gagal)
        ->and($pengiriman->percobaan)->toBe(1)
        ->and($pengiriman->pesan_gagal)->toContain('belum tersedia');
});

it('job menolak pasangan id/desa yang tidak cocok (isolasi tenant di queue)', function () {
    $desaLain = Desa::factory()->create();

    $pengiriman = PengirimanSikd::create([
        'desa_id' => $this->desa->id,
        'tahun_anggaran_id' => $this->ta->id,
        'kategori' => KategoriDataSikd::Apbdes,
        'status' => StatusPengirimanSikd::Antri,
    ]);

    expect(fn () => (new KirimKeSikd($pengiriman->id, $desaLain->id))->handle(
        app(PenyusunPayloadSikd::class)
    ))->toThrow(ModelNotFoundException::class);

    expect($pengiriman->refresh()->status)->toBe(StatusPengirimanSikd::Antri);
});

it('kebijakan retry mengikuti config (5 percobaan, backoff bertingkat)', function () {
    $job = new KirimKeSikd(1, 1);

    expect($job->tries)->toBe(5)
        ->and($job->backoff)->toBe([60, 300, 900, 3600]);
});

it('pengiriman ter-scope per desa untuk user login', function () {
    $pengirimanA = PengirimanSikd::create([
        'desa_id' => $this->desa->id,
        'tahun_anggaran_id' => $this->ta->id,
        'kategori' => KategoriDataSikd::Apbdes,
    ]);

    $desaB = Desa::factory()->create();
    PengirimanSikd::create([
        'desa_id' => $desaB->id,
        'tahun_anggaran_id' => TahunAnggaran::factory()->for($desaB)->create()->id,
        'kategori' => KategoriDataSikd::Lra,
    ]);

    $this->actingAs(userDenganPeran(PeranDesa::KaurKeuangan, $this->desa));

    expect(PengirimanSikd::count())->toBe(1)
        ->and(PengirimanSikd::first()->id)->toBe($pengirimanA->id);
});

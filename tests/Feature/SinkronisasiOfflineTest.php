<?php

use App\Actions\Sync\SinkronkanDraftOffline;
use App\Actions\Workflow\AjukanSpp;
use App\Enums\HasilSinkronisasi;
use App\Enums\PeranDesa;
use App\Enums\StatusTransaksi;
use App\Models\Akun;
use App\Models\Desa;
use App\Models\SinkronisasiLog;
use App\Models\TahunAnggaran;
use App\Models\Transaksi;
use Database\Seeders\CoaSeeder;
use Database\Seeders\PeranSeeder;
use Illuminate\Support\Str;

/*
 * M5 offline-first — sinkronisasi antrian draft.
 * Aturan resolusi konflik: locking berbasis state approval (CLAUDE.md #1).
 */

beforeEach(function () {
    $this->seed(PeranSeeder::class);
    $this->seed(CoaSeeder::class);

    $this->desa = Desa::factory()->create();
    $this->ta = TahunAnggaran::factory()->for($this->desa)->create();
    $this->kaur = userDenganPeran(PeranDesa::KaurKeuangan, $this->desa);
    $this->akunBelanja = Akun::where('kode', '5')->firstOrFail();
});

function itemDraft(array $override = []): array
{
    $t = test();

    return array_merge([
        'uuid' => (string) Str::uuid(),
        'tahun_anggaran_id' => $t->ta->id,
        'akun_id' => $t->akunBelanja->id,
        'tanggal' => '2026-07-15',
        'uraian' => 'Belanja ATK offline',
        'jumlah' => 1_500_000,
        'client_updated_at' => '2026-07-15T10:00:00+00:00',
    ], $override);
}

function sync(array $items): array
{
    return app(SinkronkanDraftOffline::class)->handle(test()->kaur, $items);
}

// -------------------------------------------------------------- buat baru

it('membuat draft baru dari item offline', function () {
    $this->actingAs($this->kaur);
    $item = itemDraft();

    $hasil = sync([$item]);

    expect($hasil[0]['hasil'])->toBe(HasilSinkronisasi::Dibuat->value);

    $transaksi = Transaksi::firstOrFail();
    expect($transaksi->uuid)->toBe($item['uuid'])
        ->and($transaksi->status)->toBe(StatusTransaksi::Draft)
        ->and($transaksi->desa_id)->toBe($this->desa->id)
        ->and((float) $transaksi->jumlah)->toBe(1_500_000.0);

    expect(SinkronisasiLog::where('uuid', $item['uuid'])->where('hasil', 'dibuat')->exists())->toBeTrue();
});

// ------------------------------------------------------------ idempotensi

it('idempoten — mengirim ulang item identik tidak menggandakan', function () {
    $this->actingAs($this->kaur);
    $item = itemDraft();

    sync([$item]);
    $hasil = sync([$item]); // kirim ulang persis

    expect($hasil[0]['hasil'])->toBe(HasilSinkronisasi::SudahTersinkron->value)
        ->and(Transaksi::count())->toBe(1);
});

// ------------------------------------------------ konflik: versi terbaru menang

it('memperbarui draft ketika versi klien lebih baru', function () {
    $this->actingAs($this->kaur);
    $item = itemDraft(['client_updated_at' => '2026-07-15T10:00:00+00:00']);
    sync([$item]);

    $lebihBaru = itemDraft([
        'uuid' => $item['uuid'],
        'uraian' => 'Uraian diperbarui',
        'jumlah' => 2_000_000,
        'client_updated_at' => '2026-07-15T11:00:00+00:00',
    ]);
    $hasil = sync([$lebihBaru]);

    expect($hasil[0]['hasil'])->toBe(HasilSinkronisasi::Diperbarui->value);

    $transaksi = Transaksi::firstOrFail();
    expect($transaksi->uraian)->toBe('Uraian diperbarui')
        ->and((float) $transaksi->jumlah)->toBe(2_000_000.0)
        ->and(Transaksi::count())->toBe(1);
});

it('menolak versi klien yang lebih lama — versi server dipertahankan dan dicatat', function () {
    $this->actingAs($this->kaur);
    $item = itemDraft(['uraian' => 'Versi server', 'client_updated_at' => '2026-07-15T12:00:00+00:00']);
    sync([$item]);

    $lebihLama = itemDraft([
        'uuid' => $item['uuid'],
        'uraian' => 'Versi lama basi',
        'client_updated_at' => '2026-07-15T09:00:00+00:00',
    ]);
    $hasil = sync([$lebihLama]);

    expect($hasil[0]['hasil'])->toBe(HasilSinkronisasi::KonflikDitolak->value);

    expect(Transaksi::firstOrFail()->uraian)->toBe('Versi server');

    // Jejak audit memuat ISI versi yang KALAH, bukan hanya timestamp.
    $log = SinkronisasiLog::where('uuid', $item['uuid'])->where('hasil', 'konflik_ditolak')->firstOrFail();
    expect($log->keterangan)->toContain('Versi lama basi');
});

// ---------------------------------------------------------- locking approval

it('mengunci draft yang sudah masuk alur SPP — edit offline diabaikan', function () {
    $this->actingAs($this->kaur);
    $item = itemDraft();
    sync([$item]);

    // draft diajukan jadi SPP (keluar dari status Draft)
    $transaksi = Transaksi::firstOrFail();
    app(AjukanSpp::class)->handle($transaksi, $this->kaur, ['nomor_spp' => 'SPP-001']);

    // klien yang belum tahu mencoba mengedit draft yang sama, versi lebih baru
    $editOffline = itemDraft([
        'uuid' => $item['uuid'],
        'uraian' => 'Edit setelah terkunci',
        'client_updated_at' => '2026-07-15T23:00:00+00:00',
    ]);
    $hasil = sync([$editOffline]);

    expect($hasil[0]['hasil'])->toBe(HasilSinkronisasi::Terkunci->value);

    $transaksi->refresh();
    expect($transaksi->status)->toBe(StatusTransaksi::SppDiajukan)
        ->and($transaksi->uraian)->toBe('Belanja ATK offline'); // tidak berubah
});

// ------------------------------------------------------------- validasi

it('menolak item yang tidak valid tanpa membuat transaksi', function () {
    $this->actingAs($this->kaur);

    $hasil = sync([itemDraft(['uraian' => '', 'jumlah' => 0])]);

    expect($hasil[0]['hasil'])->toBe(HasilSinkronisasi::Ditolak->value)
        ->and(Transaksi::count())->toBe(0);
});

it('mencatat upaya sync yang ditolak walau item tidak membawa uuid', function () {
    $this->actingAs($this->kaur);

    $tanpaUuid = itemDraft();
    unset($tanpaUuid['uuid']);

    $hasil = sync([$tanpaUuid]);

    expect($hasil[0]['hasil'])->toBe(HasilSinkronisasi::Ditolak->value)
        ->and(SinkronisasiLog::whereNull('uuid')->where('hasil', 'ditolak')->count())->toBe(1);
});

// -------------------------------------------------------- isolasi tenant

it('menolak tahun anggaran milik desa lain', function () {
    // dibuat sebelum actingAs — trait MilikDesa menolak membuat data desa lain
    // dalam konteks user yang login.
    $taDesaLain = TahunAnggaran::factory()->create();

    $this->actingAs($this->kaur);

    $hasil = sync([itemDraft(['tahun_anggaran_id' => $taDesaLain->id])]);

    expect($hasil[0]['hasil'])->toBe(HasilSinkronisasi::Ditolak->value)
        ->and(Transaksi::count())->toBe(0);
});

it('menolak uuid yang sudah dipakai tenant lain tanpa menyentuh datanya', function () {
    // desa B punya transaksi dengan uuid tertentu
    $desaB = Desa::factory()->create();
    $taB = TahunAnggaran::factory()->for($desaB)->create();
    $uuidB = (string) Str::uuid();
    $transaksiB = Transaksi::factory()->for($desaB)->for($taB)->create([
        'uuid' => $uuidB,
        'uraian' => 'Milik desa B',
    ]);

    // kaur desa A mencoba sync dengan uuid milik desa B
    $this->actingAs($this->kaur);
    $hasil = sync([itemDraft(['uuid' => $uuidB])]);

    expect($hasil[0]['hasil'])->toBe(HasilSinkronisasi::Ditolak->value)
        ->and($transaksiB->refresh()->uraian)->toBe('Milik desa B');
});

// --------------------------------------------------------------- batch

it('memproses batch campuran secara independen', function () {
    $this->actingAs($this->kaur);

    $valid = itemDraft(['uraian' => 'Valid']);
    $invalid = itemDraft(['jumlah' => 0]);

    $hasil = sync([$valid, $invalid]);

    expect($hasil)->toHaveCount(2)
        ->and($hasil[0]['hasil'])->toBe(HasilSinkronisasi::Dibuat->value)
        ->and($hasil[1]['hasil'])->toBe(HasilSinkronisasi::Ditolak->value)
        ->and(Transaksi::count())->toBe(1);
});

// --------------------------------------------------------------- peran

it('menolak sinkronisasi oleh peran selain Kaur Keuangan', function () {
    $sekdes = userDenganPeran(PeranDesa::SekretarisDesa, $this->desa);
    $this->actingAs($sekdes);

    // panggil action langsung dengan pelaku non-Kaur (helper sync() memakai kaur)
    expect(fn () => app(SinkronkanDraftOffline::class)->handle($sekdes, [itemDraft()]))
        ->toThrow(RuntimeException::class, 'Kaur Keuangan');
});

// --------------------------------------------------------- log append-only

it('log sinkronisasi append-only', function () {
    $this->actingAs($this->kaur);
    sync([itemDraft()]);

    $log = SinkronisasiLog::firstOrFail();

    expect(fn () => $log->update(['hasil' => 'diperbarui']))
        ->toThrow(LogicException::class, 'append-only')
        ->and(fn () => $log->delete())
        ->toThrow(LogicException::class, 'append-only');
});

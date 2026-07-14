<?php

use App\Actions\Workflow\AjukanSpp;
use App\Actions\Workflow\CairkanDana;
use App\Actions\Workflow\SelesaikanTransaksi;
use App\Actions\Workflow\TerbitkanSpm;
use App\Actions\Workflow\TransisiWorkflow;
use App\Actions\Workflow\VerifikasiSpp;
use App\Enums\PeranDesa;
use App\Enums\StatusTransaksi;
use App\Exceptions\TransisiDitolakException;
use App\Models\Desa;
use App\Models\TahunAnggaran;
use App\Models\Transaksi;
use Database\Seeders\CoaSeeder;
use Database\Seeders\PeranSeeder;

/*
 * State machine SPP → SPM → pencairan —
 * lihat .claude/skills/spp-spm-workflow/SKILL.md dan PLAN.md M1.
 */

beforeEach(function () {
    config(['audit.console' => true]); // test berjalan via console — auditing tetap harus merekam

    $this->seed(PeranSeeder::class);
    $this->seed(CoaSeeder::class);

    $this->desa = Desa::factory()->create();
    $this->kades = userDenganPeran(PeranDesa::KepalaDesa, $this->desa);
    $this->sekdes = userDenganPeran(PeranDesa::SekretarisDesa, $this->desa);
    $this->kaur = userDenganPeran(PeranDesa::KaurKeuangan, $this->desa);
    $this->bpd = userDenganPeran(PeranDesa::Bpd, $this->desa);

    $tahunAnggaran = TahunAnggaran::factory()->for($this->desa)->create();

    $this->transaksi = Transaksi::factory()
        ->for($this->desa)
        ->for($tahunAnggaran)
        ->create();
});

function jalankanSampai(StatusTransaksi $target): void
{
    $t = test();

    $langkah = [
        [StatusTransaksi::SppDiajukan, fn () => app(AjukanSpp::class)->handle($t->transaksi, $t->kaur, ['nomor_spp' => 'SPP-001'])],
        [StatusTransaksi::Diverifikasi, fn () => app(VerifikasiSpp::class)->handle($t->transaksi, $t->sekdes)],
        [StatusTransaksi::SpmDiterbitkan, fn () => app(TerbitkanSpm::class)->handle($t->transaksi, $t->sekdes, ['nomor_spm' => 'SPM-001', 'penandatangan' => $t->kades])],
        [StatusTransaksi::Dicairkan, fn () => app(CairkanDana::class)->handle($t->transaksi, $t->kaur, ['nomor_rekomendasi_camat' => 'REK-001'])],
        [StatusTransaksi::Selesai, fn () => app(SelesaikanTransaksi::class)->handle($t->transaksi, $t->kaur)],
    ];

    foreach ($langkah as [$status, $aksi]) {
        $aksi();
        $t->transaksi->refresh();

        if ($status === $target) {
            return;
        }
    }
}

it('menjalankan alur lengkap Draft → Selesai dengan peran yang benar', function () {
    jalankanSampai(StatusTransaksi::Selesai);

    expect($this->transaksi->status)->toBe(StatusTransaksi::Selesai)
        ->and($this->transaksi->nomor_spp)->toBe('SPP-001')
        ->and($this->transaksi->nomor_spm)->toBe('SPM-001')
        ->and($this->transaksi->spm_ditandatangani_oleh)->toBe($this->kades->id)
        ->and($this->transaksi->nomor_rekomendasi_camat)->toBe('REK-001');

    // Timeline lengkap bisa direkonstruksi dari log (kriteria selesai M1)
    expect($this->transaksi->logs()->where('berhasil', true)->pluck('ke_status')->all())->toBe([
        'spp_diajukan', 'diverifikasi', 'spm_diterbitkan', 'dicairkan', 'selesai',
    ]);
});

dataset('transisi dengan peran salah', [
    'ajukan SPP oleh Sekdes' => [AjukanSpp::class, StatusTransaksi::Draft, 'sekdes', ['nomor_spp' => 'X']],
    'ajukan SPP oleh Kades' => [AjukanSpp::class, StatusTransaksi::Draft, 'kades', ['nomor_spp' => 'X']],
    'ajukan SPP oleh BPD' => [AjukanSpp::class, StatusTransaksi::Draft, 'bpd', ['nomor_spp' => 'X']],
    'verifikasi oleh Kaur' => [VerifikasiSpp::class, StatusTransaksi::SppDiajukan, 'kaur', []],
    'verifikasi oleh Kades' => [VerifikasiSpp::class, StatusTransaksi::SppDiajukan, 'kades', []],
    'verifikasi oleh BPD' => [VerifikasiSpp::class, StatusTransaksi::SppDiajukan, 'bpd', []],
    'terbitkan SPM oleh Kaur' => [TerbitkanSpm::class, StatusTransaksi::Diverifikasi, 'kaur', ['nomor_spm' => 'X']],
    'terbitkan SPM oleh Kades' => [TerbitkanSpm::class, StatusTransaksi::Diverifikasi, 'kades', ['nomor_spm' => 'X']],
    'terbitkan SPM oleh BPD' => [TerbitkanSpm::class, StatusTransaksi::Diverifikasi, 'bpd', ['nomor_spm' => 'X']],
    'cairkan oleh Sekdes' => [CairkanDana::class, StatusTransaksi::SpmDiterbitkan, 'sekdes', ['nomor_rekomendasi_camat' => 'X']],
    'cairkan oleh Kades' => [CairkanDana::class, StatusTransaksi::SpmDiterbitkan, 'kades', ['nomor_rekomendasi_camat' => 'X']],
    'cairkan oleh BPD' => [CairkanDana::class, StatusTransaksi::SpmDiterbitkan, 'bpd', ['nomor_rekomendasi_camat' => 'X']],
    'selesaikan oleh Sekdes' => [SelesaikanTransaksi::class, StatusTransaksi::Dicairkan, 'sekdes', []],
    'selesaikan oleh Kades' => [SelesaikanTransaksi::class, StatusTransaksi::Dicairkan, 'kades', []],
    'selesaikan oleh BPD' => [SelesaikanTransaksi::class, StatusTransaksi::Dicairkan, 'bpd', []],
]);

it('menolak dan mencatat transisi oleh peran yang salah', function (string $action, StatusTransaksi $dariStatus, string $pelakuKey, array $atribut) {
    if ($dariStatus !== StatusTransaksi::Draft) {
        jalankanSampai($dariStatus);
    }

    $pelaku = $this->{$pelakuKey};
    $jumlahLogSebelum = $this->transaksi->logs()->count();

    /** @var TransisiWorkflow $aksi */
    $aksi = app($action);

    expect(fn () => $aksi->handle($this->transaksi, $pelaku, $atribut))
        ->toThrow(TransisiDitolakException::class, 'tidak berwenang');

    // status tidak berubah + percobaan tercatat sebagai gagal
    expect($this->transaksi->refresh()->status)->toBe($dariStatus);

    $log = $this->transaksi->logs()->latest('id')->first();
    expect($this->transaksi->logs()->count())->toBe($jumlahLogSebelum + 1)
        ->and($log->berhasil)->toBeFalse()
        ->and($log->user_id)->toBe($pelaku->id)
        ->and($log->alasan)->toContain('tidak berwenang');
})->with('transisi dengan peran salah');

dataset('transisi yang melompati state', [
    'verifikasi langsung dari draft' => [VerifikasiSpp::class, 'sekdes', []],
    'terbitkan SPM langsung dari draft' => [TerbitkanSpm::class, 'sekdes', ['nomor_spm' => 'X']],
    'cairkan langsung dari draft' => [CairkanDana::class, 'kaur', ['nomor_rekomendasi_camat' => 'X']],
    'selesaikan langsung dari draft' => [SelesaikanTransaksi::class, 'kaur', []],
]);

it('menolak transisi yang melompati state', function (string $action, string $pelakuKey, array $atribut) {
    /** @var TransisiWorkflow $aksi */
    $aksi = app($action);

    expect(fn () => $aksi->handle($this->transaksi, $this->{$pelakuKey}, $atribut))
        ->toThrow(TransisiDitolakException::class, 'dilompati');

    expect($this->transaksi->refresh()->status)->toBe(StatusTransaksi::Draft)
        ->and($this->transaksi->logs()->where('berhasil', false)->count())->toBe(1);
})->with('transisi yang melompati state');

it('menolak pengajuan SPP tanpa nomor SPP', function () {
    expect(fn () => app(AjukanSpp::class)->handle($this->transaksi, $this->kaur))
        ->toThrow(TransisiDitolakException::class, 'nomor SPP');
});

it('menolak penerbitan SPM tanpa tanda tangan Kepala Desa', function () {
    jalankanSampai(StatusTransaksi::Diverifikasi);

    expect(fn () => app(TerbitkanSpm::class)->handle($this->transaksi, $this->sekdes, [
        'nomor_spm' => 'SPM-001',
        'penandatangan' => $this->sekdes, // bukan Kades
    ]))->toThrow(TransisiDitolakException::class, 'Kepala Desa');

    expect($this->transaksi->refresh()->status)->toBe(StatusTransaksi::Diverifikasi);
});

it('menolak pencairan tanpa rekomendasi Camat', function () {
    jalankanSampai(StatusTransaksi::SpmDiterbitkan);

    expect(fn () => app(CairkanDana::class)->handle($this->transaksi, $this->kaur))
        ->toThrow(TransisiDitolakException::class, 'rekomendasi Camat');
});

it('menolak perubahan status langsung di luar Action workflow', function () {
    expect(fn () => $this->transaksi->forceFill(['status' => StatusTransaksi::Dicairkan])->save())
        ->toThrow(LogicException::class, 'TransisiWorkflow');

    // mass assignment juga tidak mempan — status bukan atribut fillable
    $this->transaksi->refresh()->update(['status' => StatusTransaksi::Dicairkan]);

    expect($this->transaksi->refresh()->status)->toBe(StatusTransaksi::Draft);
});

it('log transisi append-only — tidak bisa diubah atau dihapus', function () {
    jalankanSampai(StatusTransaksi::SppDiajukan);

    $log = $this->transaksi->logs()->firstOrFail();

    expect(fn () => $log->update(['berhasil' => false]))
        ->toThrow(LogicException::class, 'append-only')
        ->and(fn () => $log->delete())
        ->toThrow(LogicException::class, 'append-only');
});

it('merekam perubahan status di audit trail owen-it', function () {
    jalankanSampai(StatusTransaksi::SppDiajukan);

    expect($this->transaksi->audits()->count())->toBeGreaterThan(0);

    $audit = $this->transaksi->audits()->latest('id')->first();
    expect($audit->getModified())->toHaveKey('status');
});

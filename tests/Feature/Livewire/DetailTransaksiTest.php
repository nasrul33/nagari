<?php

use App\Actions\Workflow\AjukanSpp;
use App\Actions\Workflow\CairkanDana;
use App\Actions\Workflow\SelesaikanTransaksi;
use App\Actions\Workflow\TerbitkanSpm;
use App\Actions\Workflow\VerifikasiSpp;
use App\Enums\PeranDesa;
use App\Enums\StatusTransaksi;
use App\Livewire\Transaksi\DetailTransaksi;
use App\Models\Desa;
use App\Models\TahunAnggaran;
use App\Models\Transaksi;
use Database\Seeders\CoaSeeder;
use Database\Seeders\PeranSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(PeranSeeder::class);
    $this->seed(CoaSeeder::class);

    $this->desa = Desa::factory()->create();
    $this->kades = userDenganPeran(PeranDesa::KepalaDesa, $this->desa);
    $this->sekdes = userDenganPeran(PeranDesa::SekretarisDesa, $this->desa);
    $this->kaur = userDenganPeran(PeranDesa::KaurKeuangan, $this->desa);
    $this->bpd = userDenganPeran(PeranDesa::Bpd, $this->desa);

    $this->transaksi = Transaksi::factory()
        ->for($this->desa)
        ->for(TahunAnggaran::factory()->for($this->desa)->create())
        ->create();
});

/** Majukan transaksi ke state tertentu langsung via Action (bukan UI). */
function majukanKe(StatusTransaksi $target): void
{
    $t = test();

    $langkah = [
        StatusTransaksi::SppDiajukan->value => fn () => app(AjukanSpp::class)->handle($t->transaksi, $t->kaur, ['nomor_spp' => 'SPP-001']),
        StatusTransaksi::Diverifikasi->value => fn () => app(VerifikasiSpp::class)->handle($t->transaksi, $t->sekdes),
        StatusTransaksi::SpmDiterbitkan->value => fn () => app(TerbitkanSpm::class)->handle($t->transaksi, $t->sekdes, ['nomor_spm' => 'SPM-001', 'penandatangan' => $t->kades]),
        StatusTransaksi::Dicairkan->value => fn () => app(CairkanDana::class)->handle($t->transaksi, $t->kaur, ['nomor_rekomendasi_camat' => 'REK-001']),
        StatusTransaksi::Selesai->value => fn () => app(SelesaikanTransaksi::class)->handle($t->transaksi, $t->kaur),
    ];

    foreach ($langkah as $status => $aksi) {
        $aksi();
        $t->transaksi->refresh();

        if ($status === $target->value) {
            return;
        }
    }
}

// ---------------------------------------------------------------- akses

it('menolak akses detail transaksi desa lain', function () {
    $orangLuar = userDenganPeran(PeranDesa::KaurKeuangan, Desa::factory()->create());

    $this->actingAs($orangLuar)
        ->get(route('transaksi.detail', $this->transaksi))
        ->assertForbidden();
});

it('BPD bisa melihat detail (read-only) tanpa tombol aksi', function () {
    $this->actingAs($this->bpd)
        ->get(route('transaksi.detail', $this->transaksi))
        ->assertOk()
        ->assertSee('Rincian')
        ->assertDontSee('Ajukan SPP');
});

// ---------------------------------------------------- alur lengkap via UI

it('menjalankan seluruh alur Draft → Selesai melalui komponen UI', function () {
    // 1. Kaur ajukan SPP
    Livewire::actingAs($this->kaur)
        ->test(DetailTransaksi::class, ['transaksi' => $this->transaksi])
        ->set('nomor_spp', 'SPP/001/2026')
        ->call('ajukanSpp')
        ->assertHasNoErrors();
    expect($this->transaksi->refresh()->status)->toBe(StatusTransaksi::SppDiajukan);

    // 2. Sekdes verifikasi
    Livewire::actingAs($this->sekdes)
        ->test(DetailTransaksi::class, ['transaksi' => $this->transaksi])
        ->call('verifikasi')
        ->assertHasNoErrors();
    expect($this->transaksi->refresh()->status)->toBe(StatusTransaksi::Diverifikasi);

    // 3. Sekdes terbitkan SPM, ditandatangani Kades
    Livewire::actingAs($this->sekdes)
        ->test(DetailTransaksi::class, ['transaksi' => $this->transaksi])
        ->set('nomor_spm', 'SPM/001/2026')
        ->set('penandatangan_id', $this->kades->id)
        ->call('terbitkanSpm')
        ->assertHasNoErrors();
    expect($this->transaksi->refresh()->status)->toBe(StatusTransaksi::SpmDiterbitkan)
        ->and($this->transaksi->spm_ditandatangani_oleh)->toBe($this->kades->id);

    // 4. Kaur cairkan dengan rekomendasi Camat
    Livewire::actingAs($this->kaur)
        ->test(DetailTransaksi::class, ['transaksi' => $this->transaksi])
        ->set('nomor_rekomendasi_camat', 'REK/001/2026')
        ->call('cairkan')
        ->assertHasNoErrors();
    expect($this->transaksi->refresh()->status)->toBe(StatusTransaksi::Dicairkan);

    // 5. Kaur selesaikan
    Livewire::actingAs($this->kaur)
        ->test(DetailTransaksi::class, ['transaksi' => $this->transaksi])
        ->call('selesaikan')
        ->assertHasNoErrors();
    expect($this->transaksi->refresh()->status)->toBe(StatusTransaksi::Selesai);
});

// ------------------------------------------- peran salah untuk tiap transisi

dataset('aksi ui oleh peran salah', [
    // [method, state sebelum aksi, pelaku salah, properti yang di-set]
    'ajukanSpp oleh Sekdes' => ['ajukanSpp', StatusTransaksi::Draft, 'sekdes', ['nomor_spp' => 'X']],
    'ajukanSpp oleh Kades' => ['ajukanSpp', StatusTransaksi::Draft, 'kades', ['nomor_spp' => 'X']],
    'ajukanSpp oleh BPD' => ['ajukanSpp', StatusTransaksi::Draft, 'bpd', ['nomor_spp' => 'X']],
    'verifikasi oleh Kaur' => ['verifikasi', StatusTransaksi::SppDiajukan, 'kaur', []],
    'verifikasi oleh Kades' => ['verifikasi', StatusTransaksi::SppDiajukan, 'kades', []],
    'verifikasi oleh BPD' => ['verifikasi', StatusTransaksi::SppDiajukan, 'bpd', []],
    'terbitkanSpm oleh Kaur' => ['terbitkanSpm', StatusTransaksi::Diverifikasi, 'kaur', ['nomor_spm' => 'X', 'penandatangan_id' => '@kades']],
    'terbitkanSpm oleh Kades' => ['terbitkanSpm', StatusTransaksi::Diverifikasi, 'kades', ['nomor_spm' => 'X', 'penandatangan_id' => '@kades']],
    'terbitkanSpm oleh BPD' => ['terbitkanSpm', StatusTransaksi::Diverifikasi, 'bpd', ['nomor_spm' => 'X', 'penandatangan_id' => '@kades']],
    'cairkan oleh Sekdes' => ['cairkan', StatusTransaksi::SpmDiterbitkan, 'sekdes', ['nomor_rekomendasi_camat' => 'X']],
    'cairkan oleh Kades' => ['cairkan', StatusTransaksi::SpmDiterbitkan, 'kades', ['nomor_rekomendasi_camat' => 'X']],
    'cairkan oleh BPD' => ['cairkan', StatusTransaksi::SpmDiterbitkan, 'bpd', ['nomor_rekomendasi_camat' => 'X']],
    'selesaikan oleh Sekdes' => ['selesaikan', StatusTransaksi::Dicairkan, 'sekdes', []],
    'selesaikan oleh Kades' => ['selesaikan', StatusTransaksi::Dicairkan, 'kades', []],
    'selesaikan oleh BPD' => ['selesaikan', StatusTransaksi::Dicairkan, 'bpd', []],
]);

it('menolak aksi UI oleh peran yang salah dan mencatatnya', function (string $method, StatusTransaksi $dariStatus, string $pelakuKey, array $props) {
    if ($dariStatus !== StatusTransaksi::Draft) {
        majukanKe($dariStatus);
    }

    $komponen = Livewire::actingAs($this->{$pelakuKey})
        ->test(DetailTransaksi::class, ['transaksi' => $this->transaksi]);

    foreach ($props as $prop => $nilai) {
        $komponen->set($prop, $nilai === '@kades' ? $this->kades->id : $nilai);
    }

    $komponen->call($method)->assertHasErrors('transisi');

    expect($this->transaksi->refresh()->status)->toBe($dariStatus)
        ->and($this->transaksi->logs()->where('berhasil', false)->count())->toBe(1);
})->with('aksi ui oleh peran salah');

// -------------------------------------- peran benar tapi state dilompati

dataset('aksi ui melompati state', [
    // [method, state saat aksi dipanggil, pelaku (peran benar utk transisi), properti]
    'ajukanSpp saat sudah diajukan' => ['ajukanSpp', StatusTransaksi::SppDiajukan, 'kaur', ['nomor_spp' => 'X']],
    'verifikasi saat masih draft' => ['verifikasi', StatusTransaksi::Draft, 'sekdes', []],
    'terbitkanSpm saat masih draft' => ['terbitkanSpm', StatusTransaksi::Draft, 'sekdes', ['nomor_spm' => 'X', 'penandatangan_id' => '@kades']],
    'cairkan saat masih draft' => ['cairkan', StatusTransaksi::Draft, 'kaur', ['nomor_rekomendasi_camat' => 'X']],
    'selesaikan saat masih draft' => ['selesaikan', StatusTransaksi::Draft, 'kaur', []],
    'ajukanSpp saat sudah selesai' => ['ajukanSpp', StatusTransaksi::Selesai, 'kaur', ['nomor_spp' => 'X']],
]);

it('menolak aksi UI yang melompati state meski perannya benar', function (string $method, StatusTransaksi $stateSaatAksi, string $pelakuKey, array $props) {
    if ($stateSaatAksi !== StatusTransaksi::Draft) {
        majukanKe($stateSaatAksi);
    }

    $komponen = Livewire::actingAs($this->{$pelakuKey})
        ->test(DetailTransaksi::class, ['transaksi' => $this->transaksi]);

    foreach ($props as $prop => $nilai) {
        $komponen->set($prop, $nilai === '@kades' ? $this->kades->id : $nilai);
    }

    $komponen->call($method)->assertHasErrors('transisi');

    expect($this->transaksi->refresh()->status)->toBe($stateSaatAksi)
        ->and($this->transaksi->logs()->where('berhasil', false)->count())->toBe(1);
})->with('aksi ui melompati state');

it('menolak dan mencatat penandatangan SPM dari desa lain via UI', function () {
    majukanKe(StatusTransaksi::Diverifikasi);

    $kadesDesaLain = userDenganPeran(PeranDesa::KepalaDesa, Desa::factory()->create());

    Livewire::actingAs($this->sekdes)
        ->test(DetailTransaksi::class, ['transaksi' => $this->transaksi])
        ->set('nomor_spm', 'SPM/001/2026')
        ->set('penandatangan_id', $kadesDesaLain->id)
        ->call('terbitkanSpm')
        ->assertHasErrors('transisi');

    expect($this->transaksi->refresh()->status)->toBe(StatusTransaksi::Diverifikasi)
        ->and($this->transaksi->logs()->where('berhasil', false)->count())->toBe(1);
});

// --------------------------------------------------------- validasi input

it('menolak pengajuan SPP dari UI tanpa nomor SPP', function () {
    Livewire::actingAs($this->kaur)
        ->test(DetailTransaksi::class, ['transaksi' => $this->transaksi])
        ->call('ajukanSpp')
        ->assertHasErrors('nomor_spp');

    expect($this->transaksi->refresh()->status)->toBe(StatusTransaksi::Draft);
});

it('menolak penerbitan SPM dari UI tanpa penandatangan', function () {
    majukanKe(StatusTransaksi::Diverifikasi);

    Livewire::actingAs($this->sekdes)
        ->test(DetailTransaksi::class, ['transaksi' => $this->transaksi])
        ->set('nomor_spm', 'SPM/001/2026')
        ->call('terbitkanSpm')
        ->assertHasErrors('penandatangan_id');
});

it('menolak penandatangan SPM yang bukan Kepala Desa', function () {
    majukanKe(StatusTransaksi::Diverifikasi);

    Livewire::actingAs($this->sekdes)
        ->test(DetailTransaksi::class, ['transaksi' => $this->transaksi])
        ->set('nomor_spm', 'SPM/001/2026')
        ->set('penandatangan_id', $this->sekdes->id)
        ->call('terbitkanSpm')
        ->assertHasErrors('transisi');

    expect($this->transaksi->refresh()->status)->toBe(StatusTransaksi::Diverifikasi);
});

it('menolak pencairan dari UI tanpa nomor rekomendasi Camat', function () {
    majukanKe(StatusTransaksi::SpmDiterbitkan);

    Livewire::actingAs($this->kaur)
        ->test(DetailTransaksi::class, ['transaksi' => $this->transaksi])
        ->call('cairkan')
        ->assertHasErrors('nomor_rekomendasi_camat');
});

// ------------------------------------------------------------------ riwayat

it('menampilkan riwayat transisi termasuk percobaan yang gagal', function () {
    majukanKe(StatusTransaksi::SppDiajukan);

    // percobaan gagal: BPD mencoba verifikasi
    Livewire::actingAs($this->bpd)
        ->test(DetailTransaksi::class, ['transaksi' => $this->transaksi])
        ->call('verifikasi')
        ->assertHasErrors('transisi');

    $this->actingAs($this->kaur)
        ->get(route('transaksi.detail', $this->transaksi))
        ->assertOk()
        ->assertSee($this->kaur->name)     // transisi sukses
        ->assertSee('GAGAL mengubah');     // percobaan BPD tercatat
});

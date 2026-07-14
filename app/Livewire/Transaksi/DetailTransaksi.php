<?php

namespace App\Livewire\Transaksi;

use App\Actions\Workflow\AjukanSpp;
use App\Actions\Workflow\CairkanDana;
use App\Actions\Workflow\SelesaikanTransaksi;
use App\Actions\Workflow\TerbitkanSpm;
use App\Actions\Workflow\VerifikasiSpp;
use App\Enums\PeranDesa;
use App\Exceptions\TransisiDitolakException;
use App\Models\Transaksi;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Halaman detail + panel aksi alur SPP → SPM → pencairan.
 *
 * UI hanya MENAMPILKAN aksi sesuai peran & state; penegakan invariant tetap
 * di Action TransisiWorkflow — komponen ini tidak boleh jadi satu-satunya pagar.
 */
#[Layout('components.layouts.app')]
class DetailTransaksi extends Component
{
    public Transaksi $transaksi;

    public string $nomor_spp = '';

    public string $nomor_spm = '';

    public ?int $penandatangan_id = null;

    public string $nomor_rekomendasi_camat = '';

    public function mount(Transaksi $transaksi): void
    {
        abort_unless(auth()->user()->desa_id === $transaksi->desa_id, 403);

        $this->transaksi = $transaksi;
    }

    public function ajukanSpp(AjukanSpp $aksi)
    {
        $this->validate(['nomor_spp' => ['required', 'string', 'max:100']]);

        $this->jalankan(fn () => $aksi->handle($this->transaksi, auth()->user(), [
            'nomor_spp' => $this->nomor_spp,
        ]), 'SPP berhasil diajukan.');
    }

    public function verifikasi(VerifikasiSpp $aksi)
    {
        $this->jalankan(
            fn () => $aksi->handle($this->transaksi, auth()->user()),
            'SPP terverifikasi.',
        );
    }

    public function terbitkanSpm(TerbitkanSpm $aksi)
    {
        $this->validate([
            'nomor_spm' => ['required', 'string', 'max:100'],
            'penandatangan_id' => ['required', 'exists:users,id'],
        ]);

        // Validasi desa & peran penandatangan dilakukan di Action TerbitkanSpm,
        // supaya percobaan manipulasi ikut tercatat di transaksi_logs.
        $penandatangan = User::findOrFail($this->penandatangan_id);

        $this->jalankan(fn () => $aksi->handle($this->transaksi, auth()->user(), [
            'nomor_spm' => $this->nomor_spm,
            'penandatangan' => $penandatangan,
        ]), 'SPM diterbitkan.');
    }

    public function cairkan(CairkanDana $aksi)
    {
        $this->validate(['nomor_rekomendasi_camat' => ['required', 'string', 'max:100']]);

        $this->jalankan(fn () => $aksi->handle($this->transaksi, auth()->user(), [
            'nomor_rekomendasi_camat' => $this->nomor_rekomendasi_camat,
        ]), 'Dana dicairkan.');
    }

    public function selesaikan(SelesaikanTransaksi $aksi)
    {
        $this->jalankan(
            fn () => $aksi->handle($this->transaksi, auth()->user()),
            'Transaksi selesai.',
        );
    }

    private function jalankan(callable $aksi, string $pesanSukses): void
    {
        try {
            $aksi();
            session()->flash('sukses', $pesanSukses);
        } catch (TransisiDitolakException $e) {
            $this->addError('transisi', $e->getMessage());
        }

        $this->transaksi->refresh();
    }

    public function render()
    {
        $user = auth()->user();

        return view('livewire.transaksi.detail-transaksi', [
            'logs' => $this->transaksi->logs()->with('user')->get(),
            'kandidatPenandatangan' => User::role(PeranDesa::KepalaDesa->value)
                ->where('desa_id', $this->transaksi->desa_id)
                ->get(),
            'adalahKaur' => $user->hasRole(PeranDesa::KaurKeuangan->value),
            'adalahSekdes' => $user->hasRole(PeranDesa::SekretarisDesa->value),
        ]);
    }
}

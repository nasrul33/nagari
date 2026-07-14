<?php

namespace App\Livewire\Transaksi;

use App\Enums\PeranDesa;
use App\Models\Akun;
use App\Models\TahunAnggaran;
use App\Models\Transaksi;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class BuatTransaksi extends Component
{
    public ?int $tahun_anggaran_id = null;

    public ?int $akun_id = null;

    public string $tanggal = '';

    public string $uraian = '';

    public string $jumlah = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->hasRole(PeranDesa::KaurKeuangan->value), 403);

        $this->tanggal = now()->format('Y-m-d');
    }

    public function simpan()
    {
        $data = $this->validate([
            'tahun_anggaran_id' => ['required', 'exists:tahun_anggarans,id'],
            'akun_id' => ['required', 'exists:akuns,id'],
            'tanggal' => ['required', 'date'],
            'uraian' => ['required', 'string', 'max:255'],
            'jumlah' => ['required', 'numeric', 'min:1'],
        ]);

        // tahun anggaran wajib milik desa user — jangan percaya input klien
        TahunAnggaran::where('desa_id', auth()->user()->desa_id)
            ->findOrFail($data['tahun_anggaran_id']);

        $transaksi = Transaksi::create([
            ...$data,
            'desa_id' => auth()->user()->desa_id,
        ]);

        session()->flash('sukses', 'Transaksi draft berhasil dibuat.');

        return $this->redirectRoute('transaksi.detail', $transaksi);
    }

    public function render()
    {
        return view('livewire.transaksi.buat-transaksi', [
            'tahunAnggarans' => TahunAnggaran::where('desa_id', auth()->user()->desa_id)
                ->orderByDesc('tahun')->get(),
            'akuns' => Akun::orderBy('kode')->get(),
        ]);
    }
}

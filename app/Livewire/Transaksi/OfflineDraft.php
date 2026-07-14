<?php

namespace App\Livewire\Transaksi;

use App\Enums\PeranDesa;
use App\Models\Akun;
use App\Models\TahunAnggaran;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Halaman entri draft transaksi offline-first (M5).
 *
 * Komponen ini hanya me-render SHELL server-side (daftar akun & tahun
 * anggaran). Entri, antrian, dan sinkronisasi dilakukan sepenuhnya di klien
 * (Alpine + IndexedDB, lihat resources/js/offline.js) supaya tetap berfungsi
 * tanpa koneksi — Livewire tidak jalan offline.
 */
#[Layout('components.layouts.app')]
class OfflineDraft extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->user()->hasRole(PeranDesa::KaurKeuangan->value), 403);
    }

    public function render()
    {
        return view('livewire.transaksi.offline-draft', [
            'tahunAnggarans' => TahunAnggaran::where('desa_id', auth()->user()->desa_id)
                ->orderByDesc('tahun')
                ->get(['id', 'tahun', 'status']),
            'akuns' => Akun::orderBy('kode')->get(['id', 'kode', 'nama']),
        ]);
    }
}

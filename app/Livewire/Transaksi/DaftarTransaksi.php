<?php

namespace App\Livewire\Transaksi;

use App\Enums\PeranDesa;
use App\Enums\StatusTransaksi;
use App\Models\Transaksi;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class DaftarTransaksi extends Component
{
    use WithPagination;

    #[Url]
    public string $status = '';

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $transaksis = Transaksi::query()
            ->where('desa_id', auth()->user()->desa_id)
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->with('akun')
            ->latest('id')
            ->paginate(10);

        return view('livewire.transaksi.daftar-transaksi', [
            'transaksis' => $transaksis,
            'semuaStatus' => StatusTransaksi::cases(),
            'bolehBuat' => auth()->user()->hasRole(PeranDesa::KaurKeuangan->value),
        ]);
    }
}

<?php

namespace App\Livewire;

use App\Enums\StatusTransaksi;
use App\Models\Apbdes;
use App\Models\TahunAnggaran;
use App\Models\Transaksi;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Dashboard realisasi anggaran per tahun anggaran (M3).
 *
 * Definisi domain yang dipakai:
 * - Kategori mengikuti akar COA Permendagri: kode berawalan "4" = Pendapatan,
 *   "5" = Belanja (bukan kolom terpisah — sumber kebenaran tetap bagan akun).
 * - "Realisasi" = transaksi berstatus dicairkan/selesai (basis kas pencairan);
 *   status sebelum itu dihitung sebagai "dalam proses".
 *
 * Semua query otomatis ter-scope desa user (trait MilikDesa di model).
 */
#[Layout('components.layouts.app')]
class Dashboard extends Component
{
    private const REALISASI = [StatusTransaksi::Dicairkan, StatusTransaksi::Selesai];

    #[Url]
    public ?int $tahun_anggaran_id = null;

    public function mount(): void
    {
        $this->tahun_anggaran_id ??= TahunAnggaran::where('status', 'aktif')
            ->orderByDesc('tahun')
            ->value('id');

        // Jangan percaya nilai dari URL: pastikan TA milik desa user (scoped).
        if ($this->tahun_anggaran_id !== null) {
            $this->tahun_anggaran_id = TahunAnggaran::find($this->tahun_anggaran_id)?->id;
        }
    }

    public function updatedTahunAnggaranId(): void
    {
        $this->tahun_anggaran_id = TahunAnggaran::find($this->tahun_anggaran_id)?->id;

        $this->dispatch('dashboard-diperbarui', tren: $this->tren());
    }

    /** @return Collection<int, Transaksi> transaksi TA terpilih + akunnya (scoped desa) */
    private function transaksi(): Collection
    {
        if ($this->tahun_anggaran_id === null) {
            return collect();
        }

        return Transaksi::with('akun')
            ->where('tahun_anggaran_id', $this->tahun_anggaran_id)
            ->get();
    }

    private function akarKode(Transaksi|Apbdes $baris): string
    {
        return explode('.', $baris->akun->kode)[0];
    }

    private function adalahRealisasi(Transaksi $transaksi): bool
    {
        return in_array($transaksi->status, self::REALISASI, true);
    }

    /**
     * Tren bulanan realisasi pendapatan vs belanja untuk grafik.
     *
     * @return array{labels: list<string>, pendapatan: list<float>, belanja: list<float>}
     */
    public function tren(): array
    {
        $bulan = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        $pendapatan = array_fill(0, 12, 0.0);
        $belanja = array_fill(0, 12, 0.0);

        foreach ($this->transaksi() as $transaksi) {
            if (! $this->adalahRealisasi($transaksi)) {
                continue;
            }

            $indeks = $transaksi->tanggal->month - 1;

            match ($this->akarKode($transaksi)) {
                '4' => $pendapatan[$indeks] += (float) $transaksi->jumlah,
                '5' => $belanja[$indeks] += (float) $transaksi->jumlah,
                default => null,
            };
        }

        return ['labels' => $bulan, 'pendapatan' => $pendapatan, 'belanja' => $belanja];
    }

    public function render()
    {
        $transaksi = $this->transaksi();
        $realisasi = $transaksi->filter(fn (Transaksi $t) => $this->adalahRealisasi($t));

        $anggaranPerAkar = $this->tahun_anggaran_id === null
            ? collect()
            : Apbdes::with('akun')
                ->where('tahun_anggaran_id', $this->tahun_anggaran_id)
                ->get()
                ->groupBy(fn (Apbdes $a) => $this->akarKode($a))
                ->map(fn ($grup) => (float) $grup->sum('jumlah_anggaran'));

        $realisasiPerAkar = $realisasi
            ->groupBy(fn (Transaksi $t) => $this->akarKode($t))
            ->map(fn ($grup) => (float) $grup->sum('jumlah'));

        return view('livewire.dashboard', [
            'tahunAnggarans' => TahunAnggaran::orderByDesc('tahun')->get(),
            'totalPendapatan' => $realisasiPerAkar->get('4', 0.0),
            'totalBelanja' => $realisasiPerAkar->get('5', 0.0),
            // Realisasi di luar akar 4/5 TIDAK dibuang diam-diam (temuan T1
            // review M3) — ditampilkan agar keputusan kategorisasi dibuat
            // eksplisit saat COA level 2-5 resmi masuk.
            'totalLainnya' => $realisasiPerAkar->except(['4', '5'])->sum(),
            'anggaranBelanja' => $anggaranPerAkar->get('5', 0.0),
            'anggaranPendapatan' => $anggaranPerAkar->get('4', 0.0),
            'perStatus' => $transaksi->countBy(fn (Transaksi $t) => $t->status->value),
            'tren' => $this->tren(),
        ]);
    }
}

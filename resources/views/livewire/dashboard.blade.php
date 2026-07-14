<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <h1 class="text-xl font-semibold">Dashboard — {{ auth()->user()->desa?->nama }}</h1>
        <select wire:model.live="tahun_anggaran_id"
                class="rounded-md border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
            @forelse ($tahunAnggarans as $ta)
                <option value="{{ $ta->id }}">TA {{ $ta->tahun }} ({{ $ta->status }})</option>
            @empty
                <option value="">Belum ada tahun anggaran</option>
            @endforelse
        </select>
    </div>

    {{-- Kartu ringkasan realisasi (basis kas: dicairkan/selesai) --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Realisasi Pendapatan</div>
            <div class="mt-2 text-2xl font-semibold tabular-nums text-emerald-700">
                Rp {{ number_format($totalPendapatan, 0, ',', '.') }}
            </div>
            <div class="mt-1 text-xs text-slate-500">
                dari anggaran Rp {{ number_format($anggaranPendapatan, 0, ',', '.') }}
            </div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Realisasi Belanja</div>
            <div class="mt-2 text-2xl font-semibold tabular-nums text-rose-700">
                Rp {{ number_format($totalBelanja, 0, ',', '.') }}
            </div>
            <div class="mt-1 text-xs text-slate-500">
                dari anggaran Rp {{ number_format($anggaranBelanja, 0, ',', '.') }}
            </div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Serapan Belanja</div>
            @php $serapan = $anggaranBelanja > 0 ? min(100, round($totalBelanja / $anggaranBelanja * 100)) : 0; @endphp
            <div class="mt-2 text-2xl font-semibold tabular-nums">{{ $serapan }}%</div>
            <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-slate-100">
                <div class="h-full rounded-full bg-emerald-500" style="width: {{ $serapan }}%"></div>
            </div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Menunggu Tindakan</div>
            <div class="mt-2 text-2xl font-semibold tabular-nums">
                {{ ($perStatus['spp_diajukan'] ?? 0) + ($perStatus['diverifikasi'] ?? 0) + ($perStatus['spm_diterbitkan'] ?? 0) }}
            </div>
            <div class="mt-1 text-xs text-slate-500">SPP/SPM dalam proses approval</div>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Grafik tren bulanan --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
            <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-500">
                Tren Realisasi Bulanan
            </h2>
            <div wire:ignore
                 x-data="{
                    grafik: null,
                    gambar(tren) {
                        this.grafik?.destroy();
                        this.grafik = new Chart($refs.kanvas, {
                            type: 'bar',
                            data: {
                                labels: tren.labels,
                                datasets: [
                                    { label: 'Pendapatan', data: tren.pendapatan, backgroundColor: '#10b981' },
                                    { label: 'Belanja', data: tren.belanja, backgroundColor: '#f43f5e' },
                                ],
                            },
                            options: {
                                responsive: true,
                                scales: { y: { beginAtZero: true } },
                            },
                        });
                    },
                 }"
                 x-init="gambar(@js($tren))"
                 x-on:dashboard-diperbarui.window="gambar($event.detail.tren)">
                <canvas x-ref="kanvas" height="120"></canvas>
            </div>
        </div>

        {{-- Sebaran status transaksi --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-500">
                Transaksi per Status
            </h2>
            <ul class="space-y-3">
                @foreach (\App\Enums\StatusTransaksi::cases() as $status)
                    <li class="flex items-center justify-between text-sm">
                        <x-status-transaksi :status="$status" />
                        <span class="font-semibold tabular-nums">{{ $perStatus[$status->value] ?? 0 }}</span>
                    </li>
                @endforeach
            </ul>
            <a href="{{ route('transaksi.index') }}"
               class="mt-4 inline-block text-sm font-medium text-emerald-700 hover:underline">
                Lihat semua transaksi &rarr;
            </a>
        </div>
    </div>

    @if ($totalLainnya > 0)
        <div class="rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            Ada realisasi Rp {{ number_format($totalLainnya, 0, ',', '.') }} pada akun di luar
            kategori Pendapatan/Belanja (mis. Aset/Kewajiban/Pembiayaan) yang belum masuk
            ringkasan di atas — periksa kategorisasi COA transaksi terkait.
        </div>
    @endif

    <p class="text-xs text-slate-400">
        Realisasi dihitung dari transaksi berstatus Dicairkan/Selesai (basis kas pencairan).
        Kategori mengikuti akar bagan akun Permendagri (4 = Pendapatan, 5 = Belanja).
        Export laporan resmi (BKU, Buku Pembantu, LRA) menyusul setelah template resmi tersedia.
    </p>
</div>

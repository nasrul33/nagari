<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('transaksi.index') }}" class="text-sm text-slate-500 hover:text-slate-700">&larr; Kembali</a>
            <h1 class="mt-1 text-xl font-semibold">{{ $transaksi->uraian }}</h1>
        </div>
        <x-status-transaksi :status="$transaksi->status" class="text-sm" />
    </div>

    @if (session('sukses'))
        <div class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('sukses') }}
        </div>
    @endif

    @error('transisi')
        <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            {{ $message }}
        </div>
    @enderror

    {{-- Rincian transaksi --}}
    <div class="grid gap-6 md:grid-cols-2">
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-500">Rincian</h2>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-slate-500">Tanggal</dt><dd>{{ $transaksi->tanggal->format('d/m/Y') }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Akun</dt><dd>{{ $transaksi->akun->kode }} — {{ $transaksi->akun->nama }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Jumlah</dt><dd class="font-medium tabular-nums">Rp {{ number_format($transaksi->jumlah, 2, ',', '.') }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Tahun anggaran</dt><dd>{{ $transaksi->tahunAnggaran->tahun }}</dd></div>
                @if ($transaksi->nomor_spp)
                    <div class="flex justify-between"><dt class="text-slate-500">Nomor SPP</dt><dd>{{ $transaksi->nomor_spp }}</dd></div>
                @endif
                @if ($transaksi->nomor_spm)
                    <div class="flex justify-between"><dt class="text-slate-500">Nomor SPM</dt><dd>{{ $transaksi->nomor_spm }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Ditandatangani</dt><dd>{{ $transaksi->penandatanganSpm?->name }}</dd></div>
                @endif
                @if ($transaksi->nomor_rekomendasi_camat)
                    <div class="flex justify-between"><dt class="text-slate-500">Rekomendasi Camat</dt><dd>{{ $transaksi->nomor_rekomendasi_camat }}</dd></div>
                @endif
            </dl>
        </div>

        {{-- Panel aksi sesuai state + peran --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-500">Aksi</h2>

            @if ($transaksi->status === \App\Enums\StatusTransaksi::Draft && $adalahKaur)
                <form wire:submit="ajukanSpp" class="space-y-3">
                    <div>
                        <label for="nomor_spp" class="mb-1 block text-sm font-medium">Nomor SPP</label>
                        <input id="nomor_spp" type="text" wire:model="nomor_spp" placeholder="mis. SPP/001/2026"
                               class="w-full rounded-md border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                        @error('nomor_spp') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <button type="submit" class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                        Ajukan SPP
                    </button>
                </form>

            @elseif ($transaksi->status === \App\Enums\StatusTransaksi::SppDiajukan && $adalahSekdes)
                <p class="mb-3 text-sm text-slate-600">Periksa kelengkapan berkas SPP sesuai rencana kerja & ketersediaan anggaran.</p>
                <button wire:click="verifikasi" wire:confirm="Yakin berkas SPP sudah lengkap dan sesuai?"
                        class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                    Verifikasi SPP
                </button>

            @elseif ($transaksi->status === \App\Enums\StatusTransaksi::Diverifikasi && $adalahSekdes)
                <form wire:submit="terbitkanSpm" class="space-y-3">
                    <div>
                        <label for="nomor_spm" class="mb-1 block text-sm font-medium">Nomor SPM</label>
                        <input id="nomor_spm" type="text" wire:model="nomor_spm" placeholder="mis. SPM/001/2026"
                               class="w-full rounded-md border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                        @error('nomor_spm') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="penandatangan_id" class="mb-1 block text-sm font-medium">Penandatangan (Kepala Desa)</label>
                        <select id="penandatangan_id" wire:model="penandatangan_id"
                                class="w-full rounded-md border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                            <option value="">— pilih —</option>
                            @foreach ($kandidatPenandatangan as $kandidat)
                                <option value="{{ $kandidat->id }}">{{ $kandidat->name }}</option>
                            @endforeach
                        </select>
                        @error('penandatangan_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <button type="submit" class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                        Terbitkan SPM
                    </button>
                </form>

            @elseif ($transaksi->status === \App\Enums\StatusTransaksi::SpmDiterbitkan && $adalahKaur)
                <form wire:submit="cairkan" class="space-y-3">
                    <div>
                        <label for="nomor_rekomendasi_camat" class="mb-1 block text-sm font-medium">Nomor rekomendasi Camat</label>
                        <input id="nomor_rekomendasi_camat" type="text" wire:model="nomor_rekomendasi_camat" placeholder="mis. REK/001/2026"
                               class="w-full rounded-md border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                        @error('nomor_rekomendasi_camat') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <button type="submit" class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                        Cairkan Dana
                    </button>
                </form>

            @elseif ($transaksi->status === \App\Enums\StatusTransaksi::Dicairkan && $adalahKaur)
                <p class="mb-3 text-sm text-slate-600">Pastikan seluruh dokumen pendukung sudah lengkap sebelum menutup transaksi.</p>
                <button wire:click="selesaikan" wire:confirm="Tutup transaksi ini? Setelah selesai tidak ada transisi lagi."
                        class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                    Selesaikan
                </button>

            @elseif ($transaksi->status === \App\Enums\StatusTransaksi::Selesai)
                <p class="text-sm text-slate-500">Transaksi telah selesai — tidak ada aksi tersisa.</p>

            @else
                <p class="text-sm text-slate-500">
                    Menunggu tindakan {{ $transaksi->status->berikutnya()?->label() ?? '' }} oleh peran yang berwenang.
                </p>
            @endif
        </div>
    </div>

    {{-- Timeline log transisi --}}
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-500">Riwayat</h2>
        @if ($logs->isEmpty())
            <p class="text-sm text-slate-500">Belum ada transisi.</p>
        @else
            <ol class="space-y-3">
                @foreach ($logs as $log)
                    <li class="flex items-start gap-3 text-sm">
                        <span class="mt-1 h-2 w-2 shrink-0 rounded-full {{ $log->berhasil ? 'bg-emerald-500' : 'bg-red-500' }}"></span>
                        <div>
                            <div>
                                <span class="font-medium">{{ $log->user->name }}</span>
                                <span class="text-slate-500">{{ $log->berhasil ? 'mengubah' : 'GAGAL mengubah' }}
                                    {{ $log->dari_status }} → {{ $log->ke_status }}</span>
                            </div>
                            @if ($log->alasan)
                                <div class="text-xs text-red-600">{{ $log->alasan }}</div>
                            @endif
                            <div class="text-xs text-slate-400">{{ $log->created_at->format('d/m/Y H:i:s') }}</div>
                        </div>
                    </li>
                @endforeach
            </ol>
        @endif
    </div>
</div>

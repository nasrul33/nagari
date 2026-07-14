<div>
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-xl font-semibold">Transaksi</h1>
        @if ($bolehBuat)
            <a href="{{ route('transaksi.buat') }}"
               class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                + Transaksi Baru
            </a>
        @endif
    </div>

    <div class="mb-4">
        <select wire:model.live="status"
                class="rounded-md border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
            <option value="">Semua status</option>
            @foreach ($semuaStatus as $s)
                <option value="{{ $s->value }}">{{ $s->label() }}</option>
            @endforeach
        </select>
    </div>

    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
                    <th class="px-4 py-3">Tanggal</th>
                    <th class="px-4 py-3">Uraian</th>
                    <th class="px-4 py-3">Akun</th>
                    <th class="px-4 py-3 text-right">Jumlah (Rp)</th>
                    <th class="px-4 py-3">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($transaksis as $transaksi)
                    <tr class="border-b border-slate-100 last:border-0 hover:bg-slate-50">
                        <td class="px-4 py-3 whitespace-nowrap">{{ $transaksi->tanggal->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('transaksi.detail', $transaksi) }}"
                               class="font-medium text-emerald-700 hover:underline">
                                {{ $transaksi->uraian }}
                            </a>
                        </td>
                        <td class="px-4 py-3 text-slate-500">{{ $transaksi->akun->kode }} — {{ $transaksi->akun->nama }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ number_format($transaksi->jumlah, 2, ',', '.') }}</td>
                        <td class="px-4 py-3">
                            <x-status-transaksi :status="$transaksi->status" />
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-slate-500">
                            Belum ada transaksi.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $transaksis->links() }}
    </div>
</div>

<div class="mx-auto max-w-xl">
    <h1 class="mb-6 text-xl font-semibold">Transaksi Baru (Draft)</h1>

    <form wire:submit="simpan"
          class="space-y-4 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <div>
            <label for="tahun_anggaran_id" class="mb-1 block text-sm font-medium">Tahun anggaran</label>
            <select id="tahun_anggaran_id" wire:model="tahun_anggaran_id"
                    class="w-full rounded-md border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                <option value="">— pilih —</option>
                @foreach ($tahunAnggarans as $ta)
                    <option value="{{ $ta->id }}">{{ $ta->tahun }} ({{ $ta->status }})</option>
                @endforeach
            </select>
            @error('tahun_anggaran_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="akun_id" class="mb-1 block text-sm font-medium">Akun (COA)</label>
            <select id="akun_id" wire:model="akun_id"
                    class="w-full rounded-md border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                <option value="">— pilih —</option>
                @foreach ($akuns as $akun)
                    <option value="{{ $akun->id }}">{{ $akun->kode }} — {{ $akun->nama }}</option>
                @endforeach
            </select>
            @error('akun_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="tanggal" class="mb-1 block text-sm font-medium">Tanggal</label>
            <input id="tanggal" type="date" wire:model="tanggal"
                   class="w-full rounded-md border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
            @error('tanggal') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="uraian" class="mb-1 block text-sm font-medium">Uraian</label>
            <input id="uraian" type="text" wire:model="uraian" placeholder="mis. Belanja ATK kantor desa"
                   class="w-full rounded-md border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
            @error('uraian') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="jumlah" class="mb-1 block text-sm font-medium">Jumlah (Rp)</label>
            <input id="jumlah" type="number" step="0.01" min="0" wire:model="jumlah"
                   class="w-full rounded-md border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
            @error('jumlah') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('transaksi.index') }}" class="text-sm text-slate-500 hover:text-slate-700">Batal</a>
            <button type="submit"
                    class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700"
                    wire:loading.attr="disabled">
                Simpan Draft
            </button>
        </div>
    </form>
</div>

<div>
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-xl font-semibold">Draft Transaksi Offline</h1>
        <a href="{{ route('transaksi.index') }}" class="text-sm text-slate-500 hover:text-slate-700">
            &larr; Daftar transaksi
        </a>
    </div>

    <p class="mb-6 rounded-md border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800">
        Draft yang dibuat di sini disimpan di perangkat Anda dan otomatis dikirim ke server saat
        koneksi tersedia. Aman dipakai tanpa internet.
    </p>

    {{-- Data referensi untuk form offline (dibaca Alpine, tersedia walau offline via cache) --}}
    <div
        x-data="offlineDraft({
            tahunAnggarans: @js($tahunAnggarans),
            akuns: @js($akuns),
            syncUrl: @js(route('sync.transaksi')),
            csrf: @js(csrf_token()),
        })"
        class="grid gap-6 lg:grid-cols-2"
    >
        {{-- Form entri --}}
        <form @submit.prevent="tambah" class="space-y-4 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div>
                <label class="mb-1 block text-sm font-medium">Tahun anggaran</label>
                <select x-model.number="form.tahun_anggaran_id"
                        class="w-full rounded-md border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                    <option value="">— pilih —</option>
                    <template x-for="ta in tahunAnggarans" :key="ta.id">
                        <option :value="ta.id" x-text="`${ta.tahun} (${ta.status})`"></option>
                    </template>
                </select>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium">Akun (COA)</label>
                <select x-model.number="form.akun_id"
                        class="w-full rounded-md border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                    <option value="">— pilih —</option>
                    <template x-for="akun in akuns" :key="akun.id">
                        <option :value="akun.id" x-text="`${akun.kode} — ${akun.nama}`"></option>
                    </template>
                </select>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium">Tanggal</label>
                <input type="date" x-model="form.tanggal"
                       class="w-full rounded-md border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium">Uraian</label>
                <input type="text" x-model="form.uraian" placeholder="mis. Belanja ATK kantor desa"
                       class="w-full rounded-md border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium">Jumlah (Rp)</label>
                <input type="number" step="0.01" min="0" x-model.number="form.jumlah"
                       class="w-full rounded-md border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
            </div>

            <p x-show="error" x-text="error" class="text-sm text-red-600"></p>

            <button type="submit"
                    class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                Simpan ke antrian
            </button>
        </form>

        {{-- Antrian belum tersinkron --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">
                    Antrian (<span x-text="antrian.length"></span>)
                </h2>
                <button @click="sinkronkan" :disabled="!online || antrian.length === 0 || menyinkron"
                        class="rounded-md bg-sky-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-sky-700 disabled:opacity-50">
                    <span x-show="!menyinkron">Sinkronkan sekarang</span>
                    <span x-show="menyinkron">Menyinkron…</span>
                </button>
            </div>

            <template x-if="antrian.length === 0">
                <p class="text-sm text-slate-500">Tidak ada draft menunggu sinkronisasi.</p>
            </template>

            <ul class="space-y-2">
                <template x-for="item in antrian" :key="item.uuid">
                    <li class="rounded-md border border-slate-100 px-3 py-2 text-sm">
                        <div class="font-medium" x-text="item.uraian"></div>
                        <div class="text-xs text-slate-500">
                            Rp <span x-text="Number(item.jumlah).toLocaleString('id-ID')"></span>
                            · <span x-text="item.tanggal"></span>
                        </div>
                    </li>
                </template>
            </ul>

            <p x-show="pesanSync" x-text="pesanSync" class="mt-4 text-sm text-emerald-700"></p>
        </div>
    </div>
</div>

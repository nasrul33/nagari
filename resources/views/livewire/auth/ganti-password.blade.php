<div class="w-full max-w-sm rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
    <h1 class="mb-1 text-xl font-semibold text-emerald-700">Ganti Kata Sandi</h1>
    <p class="mb-6 text-sm text-slate-500">
        @if (auth()->user()->must_change_password)
            Kata sandi Anda bersifat sementara — ganti dulu sebelum melanjutkan.
        @else
            Perbarui kata sandi akun Anda.
        @endif
    </p>

    <form wire:submit="simpan" class="space-y-4">
        <div>
            <label for="password_lama" class="mb-1 block text-sm font-medium">Kata sandi saat ini</label>
            <input id="password_lama" type="password" wire:model="password_lama" autocomplete="current-password"
                   class="w-full rounded-md border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
            @error('password_lama') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="mb-1 block text-sm font-medium">Kata sandi baru</label>
            <input id="password" type="password" wire:model="password" autocomplete="new-password"
                   class="w-full rounded-md border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
            <p class="mt-1 text-xs text-slate-400">Minimal 12 karakter dengan huruf besar, kecil, dan angka.</p>
            @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password_confirmation" class="mb-1 block text-sm font-medium">Ulangi kata sandi baru</label>
            <input id="password_confirmation" type="password" wire:model="password_confirmation" autocomplete="new-password"
                   class="w-full rounded-md border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
        </div>

        <button type="submit"
                class="w-full rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700"
                wire:loading.attr="disabled">
            Simpan
        </button>
    </form>
</div>

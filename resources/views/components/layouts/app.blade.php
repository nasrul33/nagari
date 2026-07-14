<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Keuangan Desa' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-800 antialiased">
    <nav class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-3">
            <div class="flex items-center gap-6">
                <a href="{{ route('transaksi.index') }}" class="text-lg font-semibold text-emerald-700">
                    Keuangan Desa
                </a>
                <a href="{{ route('transaksi.index') }}"
                   class="text-sm text-slate-600 hover:text-emerald-700">Transaksi</a>
            </div>
            <div class="flex items-center gap-4">
                <div class="text-right text-sm">
                    <div class="font-medium">{{ auth()->user()->name }}</div>
                    <div class="text-xs text-slate-500">
                        {{ auth()->user()->roles->map(fn ($r) => \App\Enums\PeranDesa::from($r->name)->label())->join(', ') }}
                        — {{ auth()->user()->desa?->nama }}
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="rounded-md border border-slate-300 px-3 py-1.5 text-sm text-slate-600 hover:bg-slate-50">
                        Keluar
                    </button>
                </form>
            </div>
        </div>
    </nav>

    <main class="mx-auto max-w-5xl px-4 py-8">
        {{ $slot }}
    </main>
</body>
</html>

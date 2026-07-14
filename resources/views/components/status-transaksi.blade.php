@props(['status'])

@php
    $warna = match ($status) {
        \App\Enums\StatusTransaksi::Draft => 'bg-slate-100 text-slate-700',
        \App\Enums\StatusTransaksi::SppDiajukan => 'bg-amber-100 text-amber-800',
        \App\Enums\StatusTransaksi::Diverifikasi => 'bg-sky-100 text-sky-800',
        \App\Enums\StatusTransaksi::SpmDiterbitkan => 'bg-indigo-100 text-indigo-800',
        \App\Enums\StatusTransaksi::Dicairkan => 'bg-emerald-100 text-emerald-800',
        \App\Enums\StatusTransaksi::Selesai => 'bg-emerald-600 text-white',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-block rounded-full px-2.5 py-0.5 text-xs font-medium {$warna}"]) }}>
    {{ $status->label() }}
</span>

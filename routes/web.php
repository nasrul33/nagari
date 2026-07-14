<?php

use App\Livewire\Auth\Login;
use App\Livewire\Transaksi\BuatTransaksi;
use App\Livewire\Transaksi\DaftarTransaksi;
use App\Livewire\Transaksi\DetailTransaksi;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('transaksi.index'));

Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
});

Route::middleware('auth')->group(function () {
    Route::get('/transaksi', DaftarTransaksi::class)->name('transaksi.index');
    Route::get('/transaksi/baru', BuatTransaksi::class)->name('transaksi.buat');
    Route::get('/transaksi/{transaksi}', DetailTransaksi::class)->name('transaksi.detail');

    Route::post('/logout', function () {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login');
    })->name('logout');
});

<?php

use App\Enums\PeranDesa;
use App\Livewire\Auth\Login;
use Livewire\Livewire;

it('menampilkan halaman login untuk tamu', function () {
    $this->get(route('login'))->assertOk()->assertSeeLivewire(Login::class);
});

it('mengalihkan tamu yang membuka halaman transaksi ke login', function () {
    $this->get(route('transaksi.index'))->assertRedirect(route('login'));
});

it('login dengan kredensial benar diarahkan ke dashboard', function () {
    $user = userDenganPeran(PeranDesa::KaurKeuangan);

    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'password')
        ->call('masuk')
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard'));

    expect(auth()->id())->toBe($user->id);
});

it('login dengan kata sandi salah ditolak', function () {
    $user = userDenganPeran(PeranDesa::KaurKeuangan);

    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'salah-total')
        ->call('masuk')
        ->assertHasErrors('email');

    expect(auth()->check())->toBeFalse();
});

it('logout mengakhiri sesi', function () {
    $user = userDenganPeran(PeranDesa::KaurKeuangan);

    $this->actingAs($user)->post(route('logout'))->assertRedirect(route('login'));

    expect(auth()->check())->toBeFalse();
});

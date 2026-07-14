<?php

use App\Enums\PeranDesa;
use App\Livewire\Auth\GantiPassword;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = userDenganPeran(PeranDesa::KaurKeuangan);
    $this->user->forceFill(['must_change_password' => true])->save();
});

it('memaksa user berpassword sementara ke halaman ganti password', function () {
    $this->actingAs($this->user)
        ->get(route('dashboard'))
        ->assertRedirect(route('password.ganti'));

    $this->actingAs($this->user)
        ->get(route('transaksi.index'))
        ->assertRedirect(route('password.ganti'));

    $this->actingAs($this->user)
        ->get(route('password.ganti'))
        ->assertOk();
});

it('mengganti password menghapus paksaan dan memakai hash baru', function () {
    Livewire::actingAs($this->user)
        ->test(GantiPassword::class)
        ->set('password_lama', 'password')
        ->set('password', 'SandiBaru2026Kuat')
        ->set('password_confirmation', 'SandiBaru2026Kuat')
        ->call('simpan')
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard'));

    $this->user->refresh();

    expect($this->user->must_change_password)->toBeFalse()
        ->and(Hash::check('SandiBaru2026Kuat', $this->user->password))->toBeTrue();

    $this->actingAs($this->user)->get(route('dashboard'))->assertOk();
});

it('menolak password lemah', function (string $lemah) {
    Livewire::actingAs($this->user)
        ->test(GantiPassword::class)
        ->set('password_lama', 'password')
        ->set('password', $lemah)
        ->set('password_confirmation', $lemah)
        ->call('simpan')
        ->assertHasErrors('password');

    expect($this->user->refresh()->must_change_password)->toBeTrue();
})->with([
    'terlalu pendek' => 'Pendek1a',
    'tanpa angka' => 'TanpaAngkaSamaSekali',
    'tanpa huruf besar' => 'semua kecil angka 123',
]);

it('menolak jika password lama salah', function () {
    Livewire::actingAs($this->user)
        ->test(GantiPassword::class)
        ->set('password_lama', 'bukan-password-lama')
        ->set('password', 'SandiBaru2026Kuat')
        ->set('password_confirmation', 'SandiBaru2026Kuat')
        ->call('simpan')
        ->assertHasErrors('password_lama');
});

it('user tanpa paksaan tetap bisa mengakses halaman lain', function () {
    $this->user->forceFill(['must_change_password' => false])->save();

    $this->actingAs($this->user)->get(route('dashboard'))->assertOk();
});

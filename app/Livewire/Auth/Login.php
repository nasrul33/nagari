<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.guest')]
class Login extends Component
{
    /** Batas percobaan gagal per email+IP sebelum lockout sementara (temuan T-6 audit M2). */
    private const MAKS_PERCOBAAN = 5;

    private const LOCKOUT_DETIK = 60;

    public string $email = '';

    public string $password = '';

    public function masuk()
    {
        $kredensial = $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $kunci = $this->kunciThrottle();

        if (RateLimiter::tooManyAttempts($kunci, self::MAKS_PERCOBAAN)) {
            $detik = RateLimiter::availableIn($kunci);
            $this->addError('email', "Terlalu banyak percobaan masuk. Coba lagi dalam {$detik} detik.");

            return;
        }

        if (! Auth::attempt($kredensial)) {
            RateLimiter::hit($kunci, self::LOCKOUT_DETIK);
            $this->addError('email', 'Email atau kata sandi salah.');

            return;
        }

        RateLimiter::clear($kunci);
        session()->regenerate();

        return $this->redirectIntended(route('dashboard'));
    }

    private function kunciThrottle(): string
    {
        return 'login:'.Str::lower($this->email).'|'.request()->ip();
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}

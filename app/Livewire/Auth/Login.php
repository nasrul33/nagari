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

    /**
     * Batas kedua per-IP lintas email — email perangkat desa dapat dienumerasi
     * dari kode desa publik, jadi password spraying satu IP harus terbendung
     * (temuan B-2 audit).
     */
    private const MAKS_PERCOBAAN_PER_IP = 20;

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
        $kunciIp = 'login-ip:'.request()->ip();

        if (
            RateLimiter::tooManyAttempts($kunci, self::MAKS_PERCOBAAN)
            || RateLimiter::tooManyAttempts($kunciIp, self::MAKS_PERCOBAAN_PER_IP)
        ) {
            $detik = max(RateLimiter::availableIn($kunci), RateLimiter::availableIn($kunciIp));
            $this->addError('email', "Terlalu banyak percobaan masuk. Coba lagi dalam {$detik} detik.");

            return;
        }

        if (! Auth::attempt($kredensial)) {
            RateLimiter::hit($kunci, self::LOCKOUT_DETIK);
            RateLimiter::hit($kunciIp, self::LOCKOUT_DETIK);
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

<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.guest')]
class Login extends Component
{
    public string $email = '';

    public string $password = '';

    public function masuk()
    {
        $kredensial = $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($kredensial)) {
            $this->addError('email', 'Email atau kata sandi salah.');

            return;
        }

        session()->regenerate();

        return $this->redirectIntended(route('transaksi.index'));
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}

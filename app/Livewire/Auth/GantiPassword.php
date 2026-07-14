<?php

namespace App\Livewire\Auth;

use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.guest')]
class GantiPassword extends Component
{
    public string $password_lama = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function simpan()
    {
        $this->validate([
            'password_lama' => ['required', 'current_password'],
            'password' => [
                'required',
                'confirmed',
                'different:password_lama',
                Password::min(12)->letters()->mixedCase()->numbers(),
            ],
        ]);

        auth()->user()->forceFill([
            'password' => $this->password,
            'must_change_password' => false,
        ])->save();

        session()->flash('sukses', 'Kata sandi berhasil diganti.');

        return $this->redirectRoute('dashboard');
    }

    public function render()
    {
        return view('livewire.auth.ganti-password');
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Memaksa user ber-flag must_change_password mengganti password sebelum
 * mengakses halaman lain (temuan T-8 audit M2 — kredensial onboarding
 * bersifat sementara dan penggantiannya ditegakkan sistem, bukan himbauan).
 */
class WajibGantiPassword
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            $user?->must_change_password
            && ! $request->routeIs('password.ganti')
            && ! $request->routeIs('logout')
            && ! $request->routeIs('livewire.*')
        ) {
            return redirect()->route('password.ganti');
        }

        return $next($request);
    }
}

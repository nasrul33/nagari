<?php

use App\Http\Middleware\WajibGantiPassword;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\AuthenticateSession;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Diperlukan agar logoutOtherDevices() benar-benar memutus sesi lain
        // (temuan B-1 audit — jendela akses operator onboarding pasca serah terima).
        $middleware->web(append: [
            AuthenticateSession::class,
        ]);

        $middleware->alias([
            'wajib.ganti.password' => WajibGantiPassword::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Endpoint sinkronisasi offline dipanggil via fetch dan menuntut respons
        // JSON (401/422), bukan redirect HTML — sertakan sync/* selain api/*.
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->is('sync/*'),
        );
    })->create();

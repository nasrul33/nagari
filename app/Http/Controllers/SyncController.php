<?php

namespace App\Http\Controllers;

use App\Actions\Sync\SinkronkanDraftOffline;
use App\Enums\PeranDesa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SyncController extends Controller
{
    /**
     * Endpoint sinkronisasi antrian draft offline (M5).
     * Session auth (same-origin PWA) + CSRF. Hanya Kaur Keuangan.
     */
    public function transaksi(Request $request, SinkronkanDraftOffline $sync): JsonResponse
    {
        abort_unless($request->user()->hasRole(PeranDesa::KaurKeuangan->value), 403);

        $validated = $request->validate([
            'items' => ['present', 'array', 'max:200'],
        ]);

        $hasil = $sync->handle($request->user(), $validated['items']);

        return response()->json([
            'server_time' => now()->toIso8601String(),
            'results' => $hasil,
        ]);
    }
}

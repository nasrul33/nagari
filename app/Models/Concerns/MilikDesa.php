<?php

namespace App\Models\Concerns;

use App\Models\Desa;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Scoping multi-tenant: tenant = desa (single-DB dengan kolom desa_id,
 * lihat keputusan arsitektur di CLAUDE.md).
 *
 * - Global scope: semua query otomatis terbatas ke desa milik user yang login.
 * - Auto-fill: desa_id terisi otomatis saat create oleh user yang login.
 * - Konteks tanpa auth (seeder, queue console, test factory) TIDAK di-scope —
 *   kode yang berjalan di konteks itu wajib menetapkan desa_id eksplisit.
 */
trait MilikDesa
{
    protected static function bootMilikDesa(): void
    {
        static::addGlobalScope('desa', function (Builder $query) {
            if (! auth()->hasUser()) {
                return;
            }

            $desaId = auth()->user()->desa_id;

            if ($desaId === null) {
                // Fail-closed: user login tanpa desa TIDAK melihat data tenant
                // mana pun. Akses supervisori lintas tenant harus jadi fitur
                // eksplisit (role platform + audit), bukan efek samping null.
                $query->whereRaw('1 = 0');

                return;
            }

            $query->where($query->getModel()->getTable().'.desa_id', $desaId);
        });

        static::creating(function (Model $model) {
            if (! auth()->hasUser()) {
                return;
            }

            $desaUser = auth()->user()->desa_id;

            if ($model->getAttribute('desa_id') === null) {
                $model->setAttribute('desa_id', $desaUser);

                return;
            }

            // Tolak keras nilai suntikan — jangan ditimpa diam-diam, supaya
            // percobaan manipulasi lintas tenant kelihatan, bukan "dibetulkan".
            if ($desaUser === null || (int) $model->getAttribute('desa_id') !== (int) $desaUser) {
                throw new LogicException(
                    'Isolasi desa dilanggar: desa_id pada data baru tidak sesuai desa user yang login.'
                );
            }
        });
    }

    public function desa(): BelongsTo
    {
        return $this->belongsTo(Desa::class);
    }
}

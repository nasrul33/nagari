<?php

namespace App\Models\Concerns;

use App\Models\Desa;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
            if (auth()->hasUser() && auth()->user()->desa_id !== null) {
                $query->where(
                    $query->getModel()->getTable().'.desa_id',
                    auth()->user()->desa_id,
                );
            }
        });

        static::creating(function (Model $model) {
            if ($model->getAttribute('desa_id') === null && auth()->hasUser()) {
                $model->setAttribute('desa_id', auth()->user()->desa_id);
            }
        });
    }

    public function desa(): BelongsTo
    {
        return $this->belongsTo(Desa::class);
    }
}

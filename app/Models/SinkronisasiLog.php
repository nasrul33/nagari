<?php

namespace App\Models;

use App\Enums\HasilSinkronisasi;
use App\Models\Concerns\MilikDesa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Jejak audit sinkronisasi offline. Append-only (tidak boleh diubah/dihapus).
 */
class SinkronisasiLog extends Model
{
    use MilikDesa;

    public const UPDATED_AT = null;

    protected $fillable = ['desa_id', 'user_id', 'transaksi_id', 'uuid', 'hasil', 'keterangan', 'created_at'];

    protected function casts(): array
    {
        return [
            'hasil' => HasilSinkronisasi::class,
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function () {
            throw new \LogicException('Log sinkronisasi bersifat append-only dan tidak boleh diubah.');
        });

        static::deleting(function () {
            throw new \LogicException('Log sinkronisasi bersifat append-only dan tidak boleh dihapus.');
        });
    }

    public function transaksi(): BelongsTo
    {
        return $this->belongsTo(Transaksi::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

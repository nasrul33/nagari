<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only: baris log transisi tidak boleh diubah atau dihapus —
 * ini bagian dari audit trail kepatuhan.
 */
class TransaksiLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['transaksi_id', 'user_id', 'dari_status', 'ke_status', 'berhasil', 'alasan', 'created_at'];

    protected static function booted(): void
    {
        static::updating(function () {
            throw new \LogicException('Log transisi bersifat append-only dan tidak boleh diubah.');
        });

        static::deleting(function () {
            throw new \LogicException('Log transisi bersifat append-only dan tidak boleh dihapus.');
        });
    }

    protected function casts(): array
    {
        return [
            'berhasil' => 'boolean',
            'created_at' => 'datetime',
        ];
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

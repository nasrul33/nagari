<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransaksiLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['transaksi_id', 'user_id', 'dari_status', 'ke_status', 'berhasil', 'alasan', 'created_at'];

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

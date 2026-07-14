<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TahunAnggaran extends Model
{
    use HasFactory;

    protected $fillable = ['desa_id', 'tahun', 'status'];

    public function desa(): BelongsTo
    {
        return $this->belongsTo(Desa::class);
    }

    public function apbdes(): HasMany
    {
        return $this->hasMany(Apbdes::class);
    }

    public function transaksis(): HasMany
    {
        return $this->hasMany(Transaksi::class);
    }
}

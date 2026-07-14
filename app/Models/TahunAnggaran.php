<?php

namespace App\Models;

use App\Models\Concerns\MilikDesa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TahunAnggaran extends Model
{
    use HasFactory, MilikDesa;

    protected $fillable = ['desa_id', 'tahun', 'status'];

    public function apbdes(): HasMany
    {
        return $this->hasMany(Apbdes::class);
    }

    public function transaksis(): HasMany
    {
        return $this->hasMany(Transaksi::class);
    }
}

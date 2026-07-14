<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Desa extends Model
{
    use HasFactory;

    protected $fillable = ['kode_desa', 'nama', 'kecamatan', 'kabupaten', 'provinsi'];

    public function tahunAnggarans(): HasMany
    {
        return $this->hasMany(TahunAnggaran::class);
    }

    public function transaksis(): HasMany
    {
        return $this->hasMany(Transaksi::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}

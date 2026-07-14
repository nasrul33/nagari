<?php

namespace App\Models;

use App\Enums\StatusTransaksi;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Transaksi extends Model implements AuditableContract
{
    use Auditable, HasFactory;

    protected $fillable = [
        'desa_id', 'tahun_anggaran_id', 'akun_id', 'apbdes_id',
        'tanggal', 'uraian', 'jumlah', 'status',
        'nomor_spp', 'nomor_spm', 'spm_ditandatangani_oleh', 'nomor_rekomendasi_camat',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'jumlah' => 'decimal:2',
            'status' => StatusTransaksi::class,
        ];
    }

    public function desa(): BelongsTo
    {
        return $this->belongsTo(Desa::class);
    }

    public function tahunAnggaran(): BelongsTo
    {
        return $this->belongsTo(TahunAnggaran::class);
    }

    public function akun(): BelongsTo
    {
        return $this->belongsTo(Akun::class);
    }

    public function apbdes(): BelongsTo
    {
        return $this->belongsTo(Apbdes::class);
    }

    public function penandatanganSpm(): BelongsTo
    {
        return $this->belongsTo(User::class, 'spm_ditandatangani_oleh');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(TransaksiLog::class)->orderBy('created_at');
    }
}

<?php

namespace App\Models;

use App\Enums\JenisNota;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'no_nota',
    'jenis',
    'pelanggan_id',
    'nama_pelanggan',
    'jenis_layanan_id',
    'kiloan_id',
    'berat',
    'harga_per_kg',
    'biaya_layanan',
    'waktu',
    'total_harga',
    'diskon',
    'jumlah',
])]
class Nota extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'jenis' => JenisNota::class,
            'berat' => 'decimal:2',
            'harga_per_kg' => 'integer',
            'biaya_layanan' => 'integer',
            'waktu' => 'datetime',
            'total_harga' => 'integer',
            'diskon' => 'integer',
            'jumlah' => 'integer',
        ];
    }

    public function barangs(): HasMany
    {
        return $this->hasMany(NotaBarang::class);
    }

    public function pelanggan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pelanggan_id');
    }

    public function jenisLayanan(): BelongsTo
    {
        return $this->belongsTo(JenisLayanan::class);
    }

    public function kiloan(): BelongsTo
    {
        return $this->belongsTo(Kiloan::class);
    }
}

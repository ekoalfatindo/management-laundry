<?php

namespace App\Models;

use App\Enums\KategoriPelanggan;
use App\Enums\TipeHadiah;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

#[Fillable([
    'nama',
    'deskripsi',
    'syarat',
    'target_kategori',
    'target_transaksi',
    'tipe_hadiah',
    'nilai_hadiah',
    'mulai',
    'selesai',
    'is_active',
])]
class Promotion extends Model
{
    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'target_kategori' => KategoriPelanggan::class,
            'target_transaksi' => 'integer',
            'tipe_hadiah' => TipeHadiah::class,
            'nilai_hadiah' => 'integer',
            'mulai' => 'date',
            'selesai' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function rewards(): HasMany
    {
        return $this->hasMany(CustomerReward::class);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeAktif(Builder $query): void
    {
        $today = now()->toDateString();

        $query->where('is_active', true)
            ->where(fn (Builder $query) => $query->whereNull('mulai')->orWhereDate('mulai', '<=', $today))
            ->where(fn (Builder $query) => $query->whereNull('selesai')->orWhereDate('selesai', '>=', $today));
    }

    public function berlakuUntuk(KategoriPelanggan $kategori): bool
    {
        return $this->target_kategori === null || $this->target_kategori === $kategori;
    }

    public function isAktif(?Carbon $at = null): bool
    {
        $at ??= Carbon::now();

        if (! $this->is_active) {
            return false;
        }

        if ($this->mulai !== null && $this->mulai->startOfDay()->greaterThan($at)) {
            return false;
        }

        return $this->selesai === null || $this->selesai->endOfDay()->greaterThanOrEqualTo($at);
    }
}

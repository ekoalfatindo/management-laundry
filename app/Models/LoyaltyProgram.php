<?php

namespace App\Models;

use App\Enums\TipeHadiah;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'nama',
    'deskripsi',
    'target_transaksi',
    'tipe_hadiah',
    'nilai_hadiah',
    'masa_berlaku_hari',
    'is_active',
])]
class LoyaltyProgram extends Model
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
            'target_transaksi' => 'integer',
            'tipe_hadiah' => TipeHadiah::class,
            'nilai_hadiah' => 'integer',
            'masa_berlaku_hari' => 'integer',
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
        $query->where('is_active', true);
    }
}

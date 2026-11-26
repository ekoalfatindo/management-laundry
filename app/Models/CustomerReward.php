<?php

namespace App\Models;

use App\Enums\StatusHadiah;
use App\Enums\SumberHadiah;
use App\Enums\TipeHadiah;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

#[Fillable([
    'reward_key',
    'user_id',
    'sumber',
    'loyalty_program_id',
    'promotion_id',
    'siklus',
    'label',
    'tipe_hadiah',
    'nilai_hadiah',
    'status',
    'nilai_terpakai',
    'diperoleh_at',
    'kedaluwarsa_at',
    'digunakan_at',
    'nota_id',
])]
class CustomerReward extends Model
{
    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => StatusHadiah::Tersedia->value,
        'nilai_terpakai' => 0,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sumber' => SumberHadiah::class,
            'tipe_hadiah' => TipeHadiah::class,
            'status' => StatusHadiah::class,
            'siklus' => 'integer',
            'nilai_hadiah' => 'integer',
            'nilai_terpakai' => 'integer',
            'diperoleh_at' => 'datetime',
            'kedaluwarsa_at' => 'datetime',
            'digunakan_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function loyaltyProgram(): BelongsTo
    {
        return $this->belongsTo(LoyaltyProgram::class);
    }

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

    public function nota(): BelongsTo
    {
        return $this->belongsTo(Nota::class);
    }

    public function kedaluwarsa(?Carbon $at = null): bool
    {
        $at ??= Carbon::now();

        return $this->kedaluwarsa_at !== null && $this->kedaluwarsa_at->lessThan($at);
    }

    public function bisaDigunakan(?Carbon $at = null): bool
    {
        return $this->status === StatusHadiah::Tersedia && ! $this->kedaluwarsa($at);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeTersedia(Builder $query): void
    {
        $query->where('status', StatusHadiah::Tersedia->value)
            ->where(fn (Builder $query) => $query
                ->whereNull('kedaluwarsa_at')
                ->orWhere('kedaluwarsa_at', '>', now()));
    }
}

<?php

namespace App\Models;

use App\Enums\JenisNota;
use App\Enums\StatusNota;
use App\Enums\StatusPembayaran;
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
    'diskon_nominal',
    'reward_label',
    'customer_reward_id',
    'jumlah',
    'status',
    'status_pembayaran',
    'dibatalkan_at',
])]
class Nota extends Model
{
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => StatusNota::Diproses->value,
        'status_pembayaran' => StatusPembayaran::BelumLunas->value,
        'diskon_nominal' => 0,
    ];

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
            'diskon_nominal' => 'integer',
            'jumlah' => 'integer',
            'status' => StatusNota::class,
            'status_pembayaran' => StatusPembayaran::class,
            'dibatalkan_at' => 'datetime',
        ];
    }

    public function isSelesai(): bool
    {
        return $this->status === StatusNota::Selesai;
    }

    public function isDibatalkan(): bool
    {
        return $this->status === StatusNota::Dibatalkan;
    }

    public function isLunas(): bool
    {
        return $this->status_pembayaran === StatusPembayaran::Lunas;
    }

    public function reward(): BelongsTo
    {
        return $this->belongsTo(CustomerReward::class, 'customer_reward_id');
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

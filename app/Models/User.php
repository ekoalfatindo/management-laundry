<?php

namespace App\Models;

use App\Enums\KategoriPelanggan;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'jenis_kelamin', 'alamat', 'kategori_pelanggan', 'member_sejak'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'kategori_pelanggan' => KategoriPelanggan::Reguler->value,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'kategori_pelanggan' => KategoriPelanggan::class,
            'member_sejak' => 'date',
        ];
    }

    public function notas(): HasMany
    {
        return $this->hasMany(Nota::class, 'pelanggan_id');
    }

    public function rewards(): HasMany
    {
        return $this->hasMany(CustomerReward::class);
    }

    public function isMember(): bool
    {
        return $this->kategori_pelanggan === KategoriPelanggan::Member;
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(UserRole::Admin->value);
    }

    public function isStaff(): bool
    {
        return $this->hasAnyRole([UserRole::Admin->value, UserRole::Karyawan->value]);
    }
}

<?php

namespace App\Services;

use App\Enums\KategoriPelanggan;
use App\Enums\UserRole;
use App\Exceptions\DomainException;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, UserRole $role): User
    {
        $data = $this->normalizeKategori(null, $data);

        return DB::transaction(function () use ($data, $role): User {
            $user = User::query()->create($data);
            $user->syncRoles([$role->value]);

            return $user;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data, ?UserRole $role = null): User
    {
        if (array_key_exists('password', $data) && blank($data['password'])) {
            unset($data['password']);
        }

        if (
            $role !== null
            && $role !== UserRole::Admin
            && $user->hasRole(UserRole::Admin->value)
            && User::role(UserRole::Admin->value)->count() <= 1
        ) {
            throw new DomainException('Admin terakhir tidak dapat diubah perannya.');
        }

        $data = $this->normalizeKategori($user, $data);

        return DB::transaction(function () use ($user, $data, $role): User {
            $user->update($data);

            if ($role !== null) {
                $user->syncRoles([$role->value]);
            }

            return $user->refresh();
        });
    }

    public function delete(User $user): void
    {
        if (auth()->id() === $user->id) {
            throw new DomainException('Anda tidak dapat menghapus akun sendiri.');
        }

        if ($user->hasRole(UserRole::Admin->value) && User::role(UserRole::Admin->value)->count() <= 1) {
            throw new DomainException('Admin terakhir tidak dapat dihapus.');
        }

        DB::transaction(fn () => $user->delete());
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeKategori(?User $user, array $data): array
    {
        if (! array_key_exists('kategori_pelanggan', $data)) {
            return $data;
        }

        if ($data['kategori_pelanggan'] === KategoriPelanggan::Member->value) {
            $data['member_sejak'] ??= $user?->member_sejak?->toDateString() ?? now()->toDateString();

            return $data;
        }

        $data['member_sejak'] = null;

        return $data;
    }
}

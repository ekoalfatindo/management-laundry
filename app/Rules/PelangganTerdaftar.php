<?php

namespace App\Rules;

use App\Enums\UserRole;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class PelangganTerdaftar implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $terdaftar = User::query()
            ->role(UserRole::Pelanggan->value)
            ->whereKey($value)
            ->exists();

        if (! $terdaftar) {
            $fail('Akun yang dipilih bukan pelanggan terdaftar.');
        }
    }
}

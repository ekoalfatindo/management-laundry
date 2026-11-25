<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Karyawan = 'karyawan';
    case Pelanggan = 'pelanggan';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Karyawan => 'Karyawan',
            self::Pelanggan => 'Pelanggan',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

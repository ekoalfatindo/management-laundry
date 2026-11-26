<?php

namespace App\Enums;

enum KategoriPelanggan: string
{
    case Reguler = 'Reguler';
    case Member = 'Member';

    public function label(): string
    {
        return $this->value;
    }

    public function isMember(): bool
    {
        return $this === self::Member;
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

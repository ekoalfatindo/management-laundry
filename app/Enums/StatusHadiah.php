<?php

namespace App\Enums;

enum StatusHadiah: string
{
    case Tersedia = 'Tersedia';
    case Digunakan = 'Digunakan';
    case Kedaluwarsa = 'Kedaluwarsa';
    case Dibatalkan = 'Dibatalkan';

    public function label(): string
    {
        return $this->value;
    }

    public function isTersedia(): bool
    {
        return $this === self::Tersedia;
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

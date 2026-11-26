<?php

namespace App\Enums;

enum StatusPembayaran: string
{
    case BelumLunas = 'Belum Lunas';
    case Lunas = 'Lunas';

    public function label(): string
    {
        return $this->value;
    }

    public function isLunas(): bool
    {
        return $this === self::Lunas;
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

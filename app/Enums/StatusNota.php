<?php

namespace App\Enums;

enum StatusNota: string
{
    case Diproses = 'Diproses';
    case Selesai = 'Selesai';
    case Dibatalkan = 'Dibatalkan';

    public function label(): string
    {
        return $this->value;
    }

    public function isSelesai(): bool
    {
        return $this === self::Selesai;
    }

    public function isDibatalkan(): bool
    {
        return $this === self::Dibatalkan;
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

<?php

namespace App\Enums;

enum TipeHadiah: string
{
    case Persen = 'persen';
    case Nominal = 'nominal';
    case Gratis = 'gratis';

    public function label(): string
    {
        return match ($this) {
            self::Persen => 'Diskon Persen',
            self::Nominal => 'Potongan Nominal',
            self::Gratis => 'Gratis Cuci',
        };
    }

    public function isPersen(): bool
    {
        return $this === self::Persen;
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

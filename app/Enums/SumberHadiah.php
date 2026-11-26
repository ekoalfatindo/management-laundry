<?php

namespace App\Enums;

enum SumberHadiah: string
{
    case Loyalitas = 'Loyalitas';
    case Promosi = 'Promosi';

    public function label(): string
    {
        return $this->value;
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

<?php

namespace App\Enums;

enum JenisNota: string
{
    case BarangSatuan = 'Barang Satuan';
    case PaketKiloan = 'Paket Kiloan';

    public function label(): string
    {
        return $this->value;
    }

    public function isKiloan(): bool
    {
        return $this === self::PaketKiloan;
    }

    public function isSatuan(): bool
    {
        return $this === self::BarangSatuan;
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

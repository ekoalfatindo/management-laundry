<?php

namespace Tests\Unit;

use App\Support\Terbilang;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TerbilangTest extends TestCase
{
    #[DataProvider('nominalProvider')]
    public function test_nominal_is_spelled_in_indonesian(int $nominal, string $expected): void
    {
        $this->assertSame($expected, Terbilang::rupiah($nominal));
    }

    /**
     * @return array<string, array{int, string}>
     */
    public static function nominalProvider(): array
    {
        return [
            'nol' => [0, 'Nol rupiah'],
            'satuan' => [1, 'Satu rupiah'],
            'belasan' => [12, 'Dua belas rupiah'],
            'puluhan' => [25, 'Dua puluh lima rupiah'],
            'seratus' => [100, 'Seratus rupiah'],
            'seratus lebih' => [125, 'Seratus dua puluh lima rupiah'],
            'seribu' => [1000, 'Seribu rupiah'],
            'sepuluh ribu' => [10000, 'Sepuluh ribu rupiah'],
            'dua puluh lima ribu' => [25000, 'Dua puluh lima ribu rupiah'],
            'seratus dua puluh lima ribu' => [125000, 'Seratus dua puluh lima ribu rupiah'],
            'satu juta lima ratus ribu' => [1500000, 'Satu juta lima ratus ribu rupiah'],
            'dua juta' => [2000000, 'Dua juta rupiah'],
            'satu miliar' => [1000000000, 'Satu miliar rupiah'],
            'sembilan ratus sembilan puluh sembilan' => [999, 'Sembilan ratus sembilan puluh sembilan rupiah'],
            'juta dengan sisa' => [1000001, 'Satu juta satu rupiah'],
        ];
    }

    public function test_negative_value_is_normalized(): void
    {
        $this->assertSame('Lima ribu rupiah', Terbilang::rupiah(-5000));
    }
}

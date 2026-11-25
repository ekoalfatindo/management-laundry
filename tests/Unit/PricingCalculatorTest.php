<?php

namespace Tests\Unit;

use App\Services\PricingCalculator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PricingCalculatorTest extends TestCase
{
    public function test_kiloan_total_is_rounded_from_weight_and_price(): void
    {
        $result = (new PricingCalculator())->kiloan(3.5, 20000, 0);

        $this->assertSame(['total_harga' => 70000, 'jumlah' => 70000], $result);
    }

    public function test_kiloan_rounds_fractional_rupiah(): void
    {
        $result = (new PricingCalculator())->kiloan(1.333, 15000, 0);

        $this->assertSame(19995, $result['total_harga']);
    }

    public function test_kiloan_discount_is_rounded(): void
    {
        $result = (new PricingCalculator())->kiloan(2.25, 25000, 10);

        $this->assertSame(56250, $result['total_harga']);
        $this->assertSame(50625, $result['jumlah']);
    }

    public function test_satuan_adds_service_fee_and_discount(): void
    {
        $result = (new PricingCalculator())->satuan([
            ['total_harga' => 20000],
            ['total_harga' => 30000],
        ], 15000, 10);

        $this->assertSame(65000, $result['total_harga']);
        $this->assertSame(58500, $result['jumlah']);
    }

    #[DataProvider('discountBounds')]
    public function test_discount_is_clamped_between_zero_and_one_hundred(int $diskon, int $expected): void
    {
        $result = (new PricingCalculator())->kiloan(1, 100000, $diskon);

        $this->assertSame($expected, $result['jumlah']);
    }

    /**
     * @return array<string, array{int, int}>
     */
    public static function discountBounds(): array
    {
        return [
            'negative' => [-10, 100000],
            'zero' => [0, 100000],
            'full' => [100, 0],
            'over' => [150, 0],
        ];
    }
}

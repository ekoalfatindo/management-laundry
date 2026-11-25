<?php

namespace App\Services;

class PricingCalculator
{
    /**
     * @return array{total_harga: int, jumlah: int}
     */
    public function kiloan(float $berat, int $hargaPerKg, int $diskon): array
    {
        $total = (int) round($berat * $hargaPerKg);

        return $this->applyDiscount($total, $diskon);
    }

    /**
     * @param  array<int, array{total_harga: int}>  $baris
     * @return array{total_harga: int, jumlah: int}
     */
    public function satuan(array $baris, int $biayaLayanan, int $diskon): array
    {
        $subtotal = array_sum(array_map(
            fn (array $row): int => (int) $row['total_harga'],
            $baris,
        ));

        return $this->applyDiscount($subtotal + $biayaLayanan, $diskon);
    }

    /**
     * @return array{total_harga: int, jumlah: int}
     */
    private function applyDiscount(int $total, int $diskon): array
    {
        $diskon = max(0, min(100, $diskon));
        $potongan = (int) round($total * $diskon / 100);

        return [
            'total_harga' => $total,
            'jumlah' => $total - $potongan,
        ];
    }
}

<?php

namespace Database\Seeders;

use App\Models\Kiloan;
use Illuminate\Database\Seeder;

class KiloanSeeder extends Seeder
{
    public function run(): void
    {
        $kiloans = [
            ['paket' => 'SUPER DUPER 5kg', 'harga' => 50000, 'description' => 'CUCI KERING + SETRIKA + PACKING'],
            ['paket' => '4 Jam Selesai', 'harga' => 35000, 'description' => 'CUCI KERING + SETRIKA + PACKING'],
            ['paket' => '4 Jam Selesai', 'harga' => 25000, 'description' => 'CUCI KERING + PACKING'],
            ['paket' => '8 Jam Selesai', 'harga' => 30000, 'description' => 'CUCI KERING + SETRIKA + PACKING'],
            ['paket' => '24 Jam Selesai', 'harga' => 20000, 'description' => 'CUCI KERING + SETRIKA + PACKING'],
        ];

        foreach ($kiloans as $kiloan) {
            Kiloan::query()->updateOrCreate(
                ['paket' => $kiloan['paket'], 'harga' => $kiloan['harga']],
                ['description' => $kiloan['description']],
            );
        }
    }
}

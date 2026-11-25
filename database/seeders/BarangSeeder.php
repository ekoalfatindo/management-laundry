<?php

namespace Database\Seeders;

use App\Models\Barang;
use Illuminate\Database\Seeder;

class BarangSeeder extends Seeder
{
    public function run(): void
    {
        $barangs = [
            ['name' => 'Selimut', 'harga' => 15000],
            ['name' => 'Rok', 'harga' => 5000],
            ['name' => 'Celana Jeans', 'harga' => 8000],
            ['name' => 'Kemeja', 'harga' => 10000],
            ['name' => 'Bed Cover', 'harga' => 20000],
            ['name' => 'Jas', 'harga' => 25000],
            ['name' => 'Gaun', 'harga' => 15000],
            ['name' => 'Sepatu', 'harga' => 20000],
            ['name' => 'Taplak Meja', 'harga' => 7000],
            ['name' => 'Masker', 'harga' => 3000],
        ];

        foreach ($barangs as $barang) {
            Barang::query()->updateOrCreate(['name' => $barang['name']], ['harga' => $barang['harga']]);
        }
    }
}

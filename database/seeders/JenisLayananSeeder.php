<?php

namespace Database\Seeders;

use App\Models\JenisLayanan;
use Illuminate\Database\Seeder;

class JenisLayananSeeder extends Seeder
{
    public function run(): void
    {
        $layanans = [
            ['jenis_cuci' => 'Reguler', 'waktu' => '180 Menit', 'harga' => 0],
            ['jenis_cuci' => 'Cepat', 'waktu' => '60 Menit', 'harga' => 15000],
            ['jenis_cuci' => 'Kilat', 'waktu' => '30 Menit', 'harga' => 30000],
            ['jenis_cuci' => 'Super Duper', 'waktu' => '10 Menit', 'harga' => 50000],
        ];

        foreach ($layanans as $layanan) {
            JenisLayanan::query()->updateOrCreate(
                ['jenis_cuci' => $layanan['jenis_cuci']],
                ['waktu' => $layanan['waktu'], 'harga' => $layanan['harga']],
            );
        }
    }
}

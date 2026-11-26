<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            PengaturanSeeder::class,
            UserSeeder::class,
            BarangSeeder::class,
            KiloanSeeder::class,
            JenisLayananSeeder::class,
            LoyaltyProgramSeeder::class,
            PromotionSeeder::class,
            NotaSeeder::class,
        ]);
    }
}

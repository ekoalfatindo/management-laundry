<?php

namespace Database\Seeders;

use App\Models\Pengaturan;
use Illuminate\Database\Seeder;

class PengaturanSeeder extends Seeder
{
    public function run(): void
    {
        Pengaturan::query()->firstOrCreate([], [
            'nama_perusahaan' => config('app.name', 'Laundry Management'),
        ]);
    }
}

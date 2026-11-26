<?php

namespace Database\Seeders;

use App\Enums\TipeHadiah;
use App\Models\LoyaltyProgram;
use Illuminate\Database\Seeder;

class LoyaltyProgramSeeder extends Seeder
{
    public function run(): void
    {
        if (LoyaltyProgram::query()->exists()) {
            return;
        }

        LoyaltyProgram::query()->create([
            'nama' => 'Member Laundry',
            'deskripsi' => 'Kumpulkan 5 transaksi selesai untuk mendapatkan potongan 10% pada transaksi berikutnya.',
            'target_transaksi' => 5,
            'tipe_hadiah' => TipeHadiah::Persen,
            'nilai_hadiah' => 10,
            'masa_berlaku_hari' => 90,
            'is_active' => true,
        ]);
    }
}

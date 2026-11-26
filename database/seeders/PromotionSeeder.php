<?php

namespace Database\Seeders;

use App\Enums\KategoriPelanggan;
use App\Enums\TipeHadiah;
use App\Models\Promotion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class PromotionSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $promotions = [
            [
                'nama' => 'Promo Hemat Reguler',
                'deskripsi' => 'Bonus untuk pelanggan reguler yang rutin mencuci.',
                'syarat' => 'Berlaku untuk 5 transaksi selesai dalam periode promo.',
                'target_kategori' => KategoriPelanggan::Reguler,
                'target_transaksi' => 5,
                'tipe_hadiah' => TipeHadiah::Persen,
                'nilai_hadiah' => 5,
                'mulai' => $now->copy()->subMonthsNoOverflow(5)->startOfMonth()->toDateString(),
                'selesai' => $now->copy()->addMonthNoOverflow()->endOfMonth()->toDateString(),
                'is_active' => true,
            ],
            [
                'nama' => 'Bonus Member Setia',
                'deskripsi' => 'Hadiah tambahan bagi pelanggan yang sudah menjadi member.',
                'syarat' => 'Berlaku untuk 3 transaksi selesai selama menjadi member.',
                'target_kategori' => KategoriPelanggan::Member,
                'target_transaksi' => 3,
                'tipe_hadiah' => TipeHadiah::Nominal,
                'nilai_hadiah' => 10000,
                'mulai' => $now->copy()->subMonthsNoOverflow(4)->startOfMonth()->toDateString(),
                'selesai' => $now->copy()->addMonthsNoOverflow(2)->endOfMonth()->toDateString(),
                'is_active' => true,
            ],
            [
                'nama' => 'Promo Lebaran Berkah',
                'deskripsi' => 'Promo musiman yang sudah berakhir dan tidak lagi menerbitkan hadiah.',
                'syarat' => 'Berlaku untuk 4 transaksi selesai selama periode Lebaran.',
                'target_kategori' => null,
                'target_transaksi' => 4,
                'tipe_hadiah' => TipeHadiah::Gratis,
                'nilai_hadiah' => 0,
                'mulai' => $now->copy()->subMonthsNoOverflow(10)->startOfMonth()->toDateString(),
                'selesai' => $now->copy()->subMonthsNoOverflow(7)->endOfMonth()->toDateString(),
                'is_active' => false,
            ],
        ];

        foreach ($promotions as $promotion) {
            Promotion::query()->firstOrCreate(['nama' => $promotion['nama']], $promotion);
        }
    }
}

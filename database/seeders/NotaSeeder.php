<?php

namespace Database\Seeders;

use App\Enums\JenisNota;
use App\Enums\UserRole;
use App\Models\Barang;
use App\Models\JenisLayanan;
use App\Models\Kiloan;
use App\Models\Nota;
use App\Models\User;
use App\Services\PricingCalculator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class NotaSeeder extends Seeder
{
    /**
     * @var array<int, string>
     */
    private const WALK_IN_NAMES = [
        'Ibu Sari',
        'Bapak Andi',
        'Ibu Rina',
        'Bapak Darto',
        'Ibu Ningsih',
    ];

    public function run(PricingCalculator $pricing): void
    {
        /** @var Collection<int, User> $customers */
        $customers = User::query()->role(UserRole::Pelanggan->value)->orderBy('id')->get();
        /** @var Collection<int, Kiloan> $kiloans */
        $kiloans = Kiloan::query()->orderBy('id')->get();
        /** @var Collection<int, JenisLayanan> $layanans */
        $layanans = JenisLayanan::query()->orderBy('id')->get();
        /** @var Collection<int, Barang> $barangs */
        $barangs = Barang::query()->orderBy('id')->get();

        if ($customers->isEmpty() || $kiloans->isEmpty() || $layanans->isEmpty() || $barangs->isEmpty()) {
            $this->command?->warn('Seeder nota dilewati: data pelanggan, paket kiloan, layanan, atau barang belum tersedia.');

            return;
        }

        $now = Carbon::now();

        $this->seedMonth($pricing, $customers, $kiloans, $layanans, $barangs, $now->year - 1, 12, 6, 'L');

        for ($month = 1; $month <= $now->month; $month++) {
            $count = 4 + (($month * 7) % 4);

            $this->seedMonth($pricing, $customers, $kiloans, $layanans, $barangs, $now->year, $month, $count, 'D', $now);
        }
    }

    /**
     * @param  Collection<int, User>  $customers
     * @param  Collection<int, Kiloan>  $kiloans
     * @param  Collection<int, JenisLayanan>  $layanans
     * @param  Collection<int, Barang>  $barangs
     */
    private function seedMonth(
        PricingCalculator $pricing,
        Collection $customers,
        Collection $kiloans,
        Collection $layanans,
        Collection $barangs,
        int $year,
        int $month,
        int $count,
        string $prefix,
        ?Carbon $now = null,
    ): void {
        $daysInMonth = Carbon::create($year, $month, 1)->daysInMonth;

        for ($index = 1; $index <= $count; $index++) {
            $day = 2 + (($index * 5 + $month * 3) % 26);

            if ($now !== null && $year === $now->year && $month === $now->month) {
                $maxDay = $now->day - 1;

                if ($maxDay < 2) {
                    return;
                }

                $day = 2 + (($index * 5 + $month * 3) % max(1, $maxDay - 1));
            }

            $waktu = Carbon::create(
                $year,
                $month,
                min($day, $daysInMonth),
                8 + (($index * 3 + $month) % 11),
                (($index * 17 + $month * 5) % 4) * 15,
                0,
            );

            $nomor = sprintf('#%s%02d%02d-%02d', $prefix, $year % 100, $month, $index);

            $linked = $index % 5 !== 0;
            $customer = $customers[($month * 3 + $index) % $customers->count()];
            $walkIn = self::WALK_IN_NAMES[($month + $index) % count(self::WALK_IN_NAMES)];

            $attributes = [
                'pelanggan_id' => $linked ? $customer->id : null,
                'nama_pelanggan' => $linked ? $customer->name : $walkIn,
                'waktu' => $waktu,
            ];

            if (($month + $index) % 2 === 0) {
                $this->seedKiloan($pricing, $kiloans, $nomor, $attributes, $index, $month);

                continue;
            }

            $this->seedSatuan($pricing, $layanans, $barangs, $nomor, $attributes, $index, $month);
        }
    }

    /**
     * @param  Collection<int, Kiloan>  $kiloans
     * @param  array{pelanggan_id: int|null, nama_pelanggan: string, waktu: Carbon}  $attributes
     */
    private function seedKiloan(PricingCalculator $pricing, Collection $kiloans, string $nomor, array $attributes, int $index, int $month): void
    {
        $kiloan = $kiloans[($index + $month) % $kiloans->count()];
        $berat = 2 + (($index * 7 + $month * 3) % 19) / 2;
        $diskon = [0, 0, 5, 10][($index + $month) % 4];
        $total = $pricing->kiloan($berat, $kiloan->harga, $diskon);

        Nota::query()->updateOrCreate(
            ['no_nota' => $nomor],
            [
                ...$attributes,
                'jenis' => JenisNota::PaketKiloan,
                'jenis_layanan_id' => null,
                'kiloan_id' => $kiloan->id,
                'berat' => $berat,
                'harga_per_kg' => $kiloan->harga,
                'biaya_layanan' => 0,
                'total_harga' => $total['total_harga'],
                'diskon' => $diskon,
                'jumlah' => $total['jumlah'],
            ],
        );
    }

    /**
     * @param  Collection<int, JenisLayanan>  $layanans
     * @param  Collection<int, Barang>  $barangs
     * @param  array{pelanggan_id: int|null, nama_pelanggan: string, waktu: Carbon}  $attributes
     */
    private function seedSatuan(
        PricingCalculator $pricing,
        Collection $layanans,
        Collection $barangs,
        string $nomor,
        array $attributes,
        int $index,
        int $month,
    ): void {
        $layanan = $layanans[($index + $month) % $layanans->count()];
        $rowCount = 1 + (($index + $month) % 3);
        $diskon = [0, 0, 5, 10][($index + $month) % 4];

        $rows = [];

        for ($row = 0; $row < $rowCount; $row++) {
            $barang = $barangs[($index * 2 + $month + $row * 3) % $barangs->count()];
            $quantity = 1 + (($index + $row) % 3);

            $rows[] = [
                'barang_id' => $barang->id,
                'barang' => $barang->name,
                'harga' => $barang->harga,
                'jumlah_barang' => $quantity,
                'total_harga' => (int) round($barang->harga * $quantity),
            ];
        }

        $total = $pricing->satuan($rows, $layanan->harga, $diskon);

        DB::transaction(function () use ($nomor, $attributes, $layanan, $total, $diskon, $rows): void {
            $nota = Nota::query()->updateOrCreate(
                ['no_nota' => $nomor],
                [
                    ...$attributes,
                    'jenis' => JenisNota::BarangSatuan,
                    'jenis_layanan_id' => $layanan->id,
                    'kiloan_id' => null,
                    'berat' => null,
                    'harga_per_kg' => null,
                    'biaya_layanan' => $layanan->harga,
                    'total_harga' => $total['total_harga'],
                    'diskon' => $diskon,
                    'jumlah' => $total['jumlah'],
                ],
            );

            $nota->barangs()->delete();
            $nota->barangs()->createMany($rows);
        });
    }
}

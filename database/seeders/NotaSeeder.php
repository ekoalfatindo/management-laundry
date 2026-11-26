<?php

namespace Database\Seeders;

use App\Enums\JenisNota;
use App\Enums\StatusHadiah;
use App\Enums\StatusNota;
use App\Enums\StatusPembayaran;
use App\Enums\SumberHadiah;
use App\Enums\UserRole;
use App\Models\Barang;
use App\Models\CustomerReward;
use App\Models\JenisLayanan;
use App\Models\Kiloan;
use App\Models\Nota;
use App\Models\User;
use App\Services\PricingCalculator;
use App\Services\RewardService;
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
        'Bapak Slamet',
        'Ibu Wulan',
    ];

    /**
     * Bulan tambahan (offset dari bulan berjalan) untuk pelanggan dengan frekuensi tinggi.
     *
     * @var array<string, array<int, int>>
     */
    private const EXTRA_MONTHS = [
        'mega@laundry.test' => [5, 4, 3, 2],
        'ayu@laundry.test' => [5, 4, 3],
        'citra@laundry.test' => [5, 4],
    ];

    public function run(PricingCalculator $pricing, RewardService $rewards): void
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

        DB::transaction(function () use ($pricing, $rewards, $customers, $kiloans, $layanans, $barangs, $now): void {
            DB::table('notas')->delete();
            DB::table('customer_rewards')->delete();

            for ($offset = 5; $offset >= 0; $offset--) {
                $this->seedMonth($pricing, $customers, $kiloans, $layanans, $barangs, $now, $offset);
            }

            $rewards->syncAllCustomers();
            $this->backfillRewardHistory($customers);
            $this->ensureExpiredReward($customers);
            $this->applyDemoRedemptions($pricing, $customers);
        });
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
        Carbon $now,
        int $offset,
    ): void {
        $anchor = $now->copy()->subMonthsNoOverflow($offset);
        $year = (int) $anchor->year;
        $month = (int) $anchor->month;
        $counter = 0;
        $regulerRank = 0;

        foreach ($customers as $position => $customer) {
            $counter++;
            $waktu = $this->waktuFor($now, $year, $month, $counter, $position);
            $status = $this->statusFor($offset, $customer, $regulerRank);

            $this->writeNota(
                $pricing,
                $kiloans,
                $layanans,
                $barangs,
                $this->nomor($year, $month, $counter),
                [
                    'pelanggan_id' => $customer->id,
                    'nama_pelanggan' => $customer->name,
                    'waktu' => $waktu,
                ],
                $status,
                $this->paymentFor($status, $counter),
                $counter,
                $offset,
            );

            if (! $customer->isMember()) {
                $regulerRank++;
            }

            if (! in_array($offset, self::EXTRA_MONTHS[$customer->email] ?? [], true)) {
                continue;
            }

            $counter++;
            $waktu = $this->waktuFor($now, $year, $month, $counter, $position);

            $this->writeNota(
                $pricing,
                $kiloans,
                $layanans,
                $barangs,
                $this->nomor($year, $month, $counter),
                [
                    'pelanggan_id' => $customer->id,
                    'nama_pelanggan' => $customer->name,
                    'waktu' => $waktu,
                ],
                StatusNota::Selesai,
                StatusPembayaran::Lunas,
                $counter + 40,
                $offset,
            );
        }

        for ($walk = 1; $walk <= 2; $walk++) {
            $counter++;
            $waktu = $this->waktuFor($now, $year, $month, $counter, $walk + 7);
            $status = $offset === 0 ? StatusNota::Diproses : StatusNota::Selesai;

            if ($offset === 2 && $walk === 2) {
                $status = StatusNota::Dibatalkan;
            }

            $this->writeNota(
                $pricing,
                $kiloans,
                $layanans,
                $barangs,
                $this->nomor($year, $month, $counter),
                [
                    'pelanggan_id' => null,
                    'nama_pelanggan' => self::WALK_IN_NAMES[($offset + $walk) % count(self::WALK_IN_NAMES)],
                    'waktu' => $waktu,
                ],
                $status,
                $this->paymentFor($status, $counter),
                $counter + 20,
                $offset,
            );
        }
    }

    private function waktuFor(Carbon $now, int $year, int $month, int $index, int $position): Carbon
    {
        $daysInMonth = Carbon::create($year, $month, 1)->daysInMonth;
        $day = 2 + (($index * 3 + $position * 2 + $month) % max(1, $daysInMonth - 2));

        $waktu = Carbon::create(
            $year,
            $month,
            min($day, $daysInMonth),
            8 + (($index + $month) % 11),
            (($index * 7) % 4) * 15,
            0,
        );

        return $waktu->greaterThan($now)
            ? $now->copy()->subMinutes(5 + ($index % 30))
            : $waktu;
    }

    private function statusFor(int $offset, User $customer, int $regulerRank): StatusNota
    {
        if ($offset === 3 && $customer->email === 'budi@laundry.test') {
            return StatusNota::Dibatalkan;
        }

        if ($offset !== 0) {
            return StatusNota::Selesai;
        }

        if ($customer->isMember()) {
            return StatusNota::Selesai;
        }

        return $regulerRank < 3 ? StatusNota::Diproses : StatusNota::Selesai;
    }

    private function paymentFor(StatusNota $status, int $index): StatusPembayaran
    {
        if ($status !== StatusNota::Selesai) {
            return StatusPembayaran::BelumLunas;
        }

        return $index % 6 === 0 ? StatusPembayaran::BelumLunas : StatusPembayaran::Lunas;
    }

    private function nomor(int $year, int $month, int $counter): string
    {
        return sprintf('#D%02d%02d-%02d', $year % 100, $month, $counter);
    }

    /**
     * @param  Collection<int, Kiloan>  $kiloans
     * @param  Collection<int, JenisLayanan>  $layanans
     * @param  Collection<int, Barang>  $barangs
     * @param  array{pelanggan_id: int|null, nama_pelanggan: string, waktu: Carbon}  $header
     */
    private function writeNota(
        PricingCalculator $pricing,
        Collection $kiloans,
        Collection $layanans,
        Collection $barangs,
        string $nomor,
        array $header,
        StatusNota $status,
        StatusPembayaran $payment,
        int $index,
        int $offset,
    ): void {
        $flags = [
            'status' => $status,
            'status_pembayaran' => $payment,
            'dibatalkan_at' => $status === StatusNota::Dibatalkan
                ? $header['waktu']->copy()->addHour()
                : null,
            'diskon_nominal' => 0,
            'reward_label' => null,
            'customer_reward_id' => null,
        ];

        if (($index + $offset) % 2 === 0) {
            $this->seedKiloan($pricing, $kiloans, $nomor, $header, $flags, $index, $offset);

            return;
        }

        $this->seedSatuan($pricing, $layanans, $barangs, $nomor, $header, $flags, $index, $offset);
    }

    /**
     * @param  Collection<int, Kiloan>  $kiloans
     * @param  array{pelanggan_id: int|null, nama_pelanggan: string, waktu: Carbon}  $header
     * @param  array<string, mixed>  $flags
     */
    private function seedKiloan(
        PricingCalculator $pricing,
        Collection $kiloans,
        string $nomor,
        array $header,
        array $flags,
        int $index,
        int $offset,
    ): void {
        $kiloan = $kiloans[($index + $offset) % $kiloans->count()];
        $berat = 2 + (($index * 3 + $offset * 2) % 18) / 2;
        $diskon = [0, 0, 5, 10][($index + $offset) % 4];
        $total = $pricing->kiloan($berat, $kiloan->harga, $diskon);

        Nota::query()->updateOrCreate(
            ['no_nota' => $nomor],
            [
                ...$header,
                ...$flags,
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
     * @param  array{pelanggan_id: int|null, nama_pelanggan: string, waktu: Carbon}  $header
     * @param  array<string, mixed>  $flags
     */
    private function seedSatuan(
        PricingCalculator $pricing,
        Collection $layanans,
        Collection $barangs,
        string $nomor,
        array $header,
        array $flags,
        int $index,
        int $offset,
    ): void {
        $layanan = $layanans[($index + $offset) % $layanans->count()];
        $rowCount = 1 + (($index + $offset) % 3);
        $diskon = [0, 0, 5, 10][($index + $offset + 1) % 4];

        $rows = [];

        for ($row = 0; $row < $rowCount; $row++) {
            $barang = $barangs[($index * 2 + $offset + $row * 3) % $barangs->count()];
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

        DB::transaction(function () use ($nomor, $header, $flags, $layanan, $total, $diskon, $rows): void {
            $nota = Nota::query()->updateOrCreate(
                ['no_nota' => $nomor],
                [
                    ...$header,
                    ...$flags,
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

    /**
     * Sesuaikan tanggal perolehan hadiah dengan transaksi kualifikasi dan tandai yang sudah kedaluwarsa.
     *
     * @param  Collection<int, User>  $customers
     */
    private function backfillRewardHistory(Collection $customers): void
    {
        foreach ($customers as $customer) {
            /** @var Collection<int, Nota> $selesai */
            $selesai = Nota::query()
                ->where('pelanggan_id', $customer->id)
                ->where('status', StatusNota::Selesai->value)
                ->orderBy('waktu')
                ->get();

            if ($selesai->isEmpty()) {
                continue;
            }

            $rewards = $customer->rewards()
                ->with(['loyaltyProgram', 'promotion'])
                ->orderBy('siklus')
                ->get();

            foreach ($rewards as $reward) {
                if ($reward->status !== StatusHadiah::Tersedia) {
                    continue;
                }

                $program = $reward->loyaltyProgram;
                $promotion = $reward->promotion;
                $target = $program?->target_transaksi ?? $promotion?->target_transaksi;

                if ($target === null || $target < 1) {
                    continue;
                }

                $pool = $selesai;

                if ($promotion !== null) {
                    $pool = $selesai
                        ->filter(function (Nota $nota) use ($promotion): bool {
                            if ($promotion->mulai !== null && $nota->waktu->lt($promotion->mulai->copy()->startOfDay())) {
                                return false;
                            }

                            return $promotion->selesai === null || $nota->waktu->lte($promotion->selesai->copy()->endOfDay());
                        })
                        ->values();
                }

                $qualifying = $pool->get($reward->siklus * $target - 1);

                if ($qualifying === null) {
                    continue;
                }

                $expiresAt = $program?->masa_berlaku_hari !== null
                    ? $qualifying->waktu->copy()->addDays($program->masa_berlaku_hari)
                    : null;

                $reward->update([
                    'diperoleh_at' => $qualifying->waktu,
                    'kedaluwarsa_at' => $expiresAt,
                    'status' => $expiresAt !== null && $expiresAt->lt(now())
                        ? StatusHadiah::Kedaluwarsa
                        : StatusHadiah::Tersedia,
                ]);
            }
        }
    }

    /**
     * @param  Collection<int, User>  $customers
     */
    private function ensureExpiredReward(Collection $customers): void
    {
        if (CustomerReward::query()->where('status', StatusHadiah::Kedaluwarsa->value)->exists()) {
            return;
        }

        $customer = $customers->firstWhere('email', 'mega@laundry.test');

        if ($customer === null) {
            return;
        }

        $reward = $customer->rewards()
            ->where('sumber', SumberHadiah::Loyalitas->value)
            ->orderBy('siklus')
            ->first();

        $reward?->update([
            'status' => StatusHadiah::Kedaluwarsa,
            'diperoleh_at' => now()->subDays(150),
            'kedaluwarsa_at' => now()->subDays(90),
            'digunakan_at' => null,
            'nota_id' => null,
            'nilai_terpakai' => 0,
        ]);
    }

    /**
     * @param  Collection<int, User>  $customers
     */
    private function applyDemoRedemptions(PricingCalculator $pricing, Collection $customers): void
    {
        $this->redeemSource($pricing, $customers, SumberHadiah::Loyalitas);
        $this->redeemSource($pricing, $customers, SumberHadiah::Promosi);
    }

    /**
     * @param  Collection<int, User>  $customers
     */
    private function redeemSource(PricingCalculator $pricing, Collection $customers, SumberHadiah $sumber): bool
    {
        foreach ($customers as $customer) {
            $rewards = $customer->rewards()
                ->where('sumber', $sumber->value)
                ->where('status', StatusHadiah::Tersedia->value)
                ->where(fn ($query) => $query
                    ->whereNull('kedaluwarsa_at')
                    ->orWhere('kedaluwarsa_at', '>', now()))
                ->orderBy('siklus')
                ->get();

            foreach ($rewards as $reward) {
                $nota = Nota::query()
                    ->where('pelanggan_id', $customer->id)
                    ->where('status', StatusNota::Selesai->value)
                    ->whereNull('customer_reward_id')
                    ->where('waktu', '>', $reward->diperoleh_at)
                    ->orderByDesc('waktu')
                    ->orderByDesc('id')
                    ->first();

                if ($nota === null) {
                    continue;
                }

                $potongan = $pricing->potonganHadiah($nota->total_harga, $reward->tipe_hadiah, $reward->nilai_hadiah);

                $nota->update([
                    'diskon' => 0,
                    'diskon_nominal' => $potongan,
                    'reward_label' => $reward->label,
                    'customer_reward_id' => $reward->id,
                    'jumlah' => max(0, $nota->total_harga - $potongan),
                ]);

                $reward->update([
                    'status' => StatusHadiah::Digunakan,
                    'digunakan_at' => $nota->waktu,
                    'nota_id' => $nota->id,
                    'nilai_terpakai' => $potongan,
                ]);

                return true;
            }
        }

        return false;
    }
}

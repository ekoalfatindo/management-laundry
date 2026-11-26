<?php

namespace App\Services;

use App\Actions\Nota\GenerateNomorNota;
use App\DTOs\NotaData;
use App\Enums\JenisNota;
use App\Enums\StatusNota;
use App\Enums\StatusPembayaran;
use App\Exceptions\DomainException;
use App\Models\Barang;
use App\Models\JenisLayanan;
use App\Models\Kiloan;
use App\Models\Nota;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class NotaService
{
    public function __construct(
        private readonly PricingCalculator $pricing,
        private readonly GenerateNomorNota $nomor,
        private readonly RewardService $rewards,
    ) {}

    public function create(NotaData $data): Nota
    {
        return DB::transaction(function () use ($data): Nota {
            $nota = new Nota([
                'no_nota' => $this->nomor->execute(),
                'jenis' => $data->jenis,
                'pelanggan_id' => $data->pelangganId,
                'nama_pelanggan' => $data->namaPelanggan,
                'waktu' => $data->waktu,
                'diskon' => $this->effectiveDiskon($data),
            ]);

            $this->persist($nota, $data);
            $this->applyReward($nota, $data);
            $this->syncCustomer($nota->pelanggan_id);

            return $nota->fresh(['kiloan', 'jenisLayanan', 'barangs.barang', 'reward']);
        });
    }

    public function update(Nota $nota, NotaData $data): Nota
    {
        return DB::transaction(function () use ($nota, $data): Nota {
            $nota = Nota::query()->whereKey($nota->id)->lockForUpdate()->firstOrFail();

            if ($nota->isDibatalkan()) {
                throw new DomainException('Nota yang dibatalkan tidak dapat diubah.');
            }

            $previousCustomerId = $nota->pelanggan_id;

            $this->rewards->releaseFor($nota);

            $nota->fill([
                'pelanggan_id' => $data->pelangganId,
                'nama_pelanggan' => $data->namaPelanggan,
                'waktu' => $data->waktu,
                'diskon' => $this->effectiveDiskon($data),
                'diskon_nominal' => 0,
                'reward_label' => null,
                'customer_reward_id' => null,
            ]);

            if ($nota->jenis === JenisNota::BarangSatuan) {
                $nota->barangs()->delete();
            }

            $this->persist($nota, $data, $nota->jenis);
            $this->applyReward($nota, $data);

            if ($previousCustomerId !== null && $previousCustomerId !== $nota->pelanggan_id) {
                $this->syncCustomer($previousCustomerId);
            }

            $this->syncCustomer($nota->pelanggan_id);

            return $nota->fresh(['kiloan', 'jenisLayanan', 'barangs.barang', 'reward']);
        });
    }

    public function delete(Nota $nota): void
    {
        DB::transaction(function () use ($nota): void {
            $nota = Nota::query()->whereKey($nota->id)->lockForUpdate()->firstOrFail();
            $customerId = $nota->pelanggan_id;

            $this->rewards->assertCancelable($nota);
            $this->rewards->releaseFor($nota);
            $nota->delete();
            $this->syncCustomer($customerId);
        });
    }

    public function complete(Nota $nota): Nota
    {
        return DB::transaction(function () use ($nota): Nota {
            $nota = Nota::query()->whereKey($nota->id)->lockForUpdate()->firstOrFail();

            if ($nota->isDibatalkan()) {
                throw new DomainException('Nota yang dibatalkan tidak dapat diselesaikan.');
            }

            $nota->update(['status' => StatusNota::Selesai]);
            $this->syncCustomer($nota->pelanggan_id);

            return $nota->refresh();
        });
    }

    public function cancel(Nota $nota): Nota
    {
        return DB::transaction(function () use ($nota): Nota {
            $nota = Nota::query()->whereKey($nota->id)->lockForUpdate()->firstOrFail();

            if ($nota->isDibatalkan()) {
                throw new DomainException('Nota sudah dibatalkan.');
            }

            $customerId = $nota->pelanggan_id;

            $this->rewards->assertCancelable($nota);
            $this->rewards->releaseFor($nota);

            $nota->update([
                'status' => StatusNota::Dibatalkan,
                'dibatalkan_at' => Carbon::now(),
                'customer_reward_id' => null,
            ]);

            $this->syncCustomer($customerId);

            return $nota->refresh();
        });
    }

    public function setPembayaran(Nota $nota, StatusPembayaran $status): Nota
    {
        $nota->update(['status_pembayaran' => $status]);

        return $nota->refresh();
    }

    private function persist(Nota $nota, NotaData $data, ?JenisNota $jenis = null): void
    {
        $jenis = $jenis ?? $data->jenis;

        if ($jenis === JenisNota::PaketKiloan) {
            $this->persistKiloan($nota, $data);

            return;
        }

        $this->persistSatuan($nota, $data);
    }

    private function persistKiloan(Nota $nota, NotaData $data): void
    {
        $kiloan = Kiloan::query()->find($data->kiloanId);

        if (! $kiloan) {
            throw new DomainException('Paket kiloan tidak ditemukan.');
        }

        $berat = $data->berat ?? 0.0;

        if ($berat <= 0) {
            throw new DomainException('Berat cucian harus lebih dari 0 kg.');
        }

        $total = $this->pricing->kiloan($berat, $kiloan->harga, $this->effectiveDiskon($data));

        $nota->fill([
            'jenis_layanan_id' => null,
            'kiloan_id' => $kiloan->id,
            'berat' => $berat,
            'harga_per_kg' => $kiloan->harga,
            'biaya_layanan' => 0,
            'total_harga' => $total['total_harga'],
            'jumlah' => $total['jumlah'],
        ]);

        $nota->save();
    }

    private function persistSatuan(Nota $nota, NotaData $data): void
    {
        $layanan = JenisLayanan::query()->find($data->jenisLayananId);

        if (! $layanan) {
            throw new DomainException('Jenis layanan tidak ditemukan.');
        }

        $baris = $this->buildBaris($data->barangs);

        if ($baris === []) {
            throw new DomainException('Minimal satu barang harus diisi.');
        }

        $total = $this->pricing->satuan($baris, $layanan->harga, $this->effectiveDiskon($data));

        $nota->fill([
            'jenis_layanan_id' => $layanan->id,
            'kiloan_id' => null,
            'berat' => null,
            'harga_per_kg' => null,
            'biaya_layanan' => $layanan->harga,
            'total_harga' => $total['total_harga'],
            'jumlah' => $total['jumlah'],
        ]);

        $nota->save();
        $nota->barangs()->createMany($baris);
    }

    private function applyReward(Nota $nota, NotaData $data): void
    {
        $potongan = 0;
        $label = null;
        $rewardId = null;

        if ($data->customerRewardId !== null) {
            $customer = $data->pelangganId !== null ? User::query()->find($data->pelangganId) : null;

            if ($customer === null) {
                throw new DomainException('Hadiah hanya dapat digunakan untuk pelanggan terdaftar.');
            }

            $reserved = $this->rewards->reserve($customer, $data->customerRewardId, $nota->total_harga, $nota);
            $potongan = $reserved['potongan'];
            $label = $reserved['reward']->label;
            $rewardId = $reserved['reward']->id;
        }

        $nota->fill([
            'diskon_nominal' => $potongan,
            'reward_label' => $label,
            'customer_reward_id' => $rewardId,
            'jumlah' => $rewardId !== null ? max(0, $nota->total_harga - $potongan) : $nota->jumlah,
        ]);

        $nota->save();
    }

    private function effectiveDiskon(NotaData $data): int
    {
        return $data->customerRewardId !== null ? 0 : $data->diskon;
    }

    private function syncCustomer(?int $customerId): void
    {
        if ($customerId === null) {
            return;
        }

        $customer = User::query()->find($customerId);

        if ($customer !== null) {
            $this->rewards->sync($customer);
        }
    }

    /**
     * @param  array<int, array{barang_id: int, jumlah_barang: int}>  $rows
     * @return array<int, array<string, int|string>>
     */
    private function buildBaris(array $rows): array
    {
        if ($rows === []) {
            return [];
        }

        $barangs = Barang::query()
            ->whereIn('id', array_column($rows, 'barang_id'))
            ->get()
            ->keyBy('id');

        return collect($rows)
            ->map(function (array $row) use ($barangs): array {
                $barang = $barangs->get($row['barang_id']);

                if (! $barang) {
                    throw new DomainException('Barang tidak ditemukan pada master data.');
                }

                $jumlah = (int) $row['jumlah_barang'];

                if ($jumlah < 1) {
                    throw new DomainException('Jumlah barang minimal 1.');
                }

                return [
                    'barang_id' => $barang->id,
                    'barang' => $barang->name,
                    'harga' => $barang->harga,
                    'jumlah_barang' => $jumlah,
                    'total_harga' => (int) round($barang->harga * $jumlah),
                ];
            })
            ->values()
            ->all();
    }
}

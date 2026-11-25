<?php

namespace App\Services;

use App\Actions\Nota\GenerateNomorNota;
use App\DTOs\NotaData;
use App\Enums\JenisNota;
use App\Exceptions\DomainException;
use App\Models\Barang;
use App\Models\JenisLayanan;
use App\Models\Kiloan;
use App\Models\Nota;
use Illuminate\Support\Facades\DB;

class NotaService
{
    public function __construct(
        private readonly PricingCalculator $pricing,
        private readonly GenerateNomorNota $nomor,
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
                'diskon' => $data->diskon,
            ]);

            $this->persist($nota, $data);

            return $nota->fresh(['kiloan', 'jenisLayanan', 'barangs.barang']);
        });
    }

    public function update(Nota $nota, NotaData $data): Nota
    {
        return DB::transaction(function () use ($nota, $data): Nota {
            $nota->fill([
                'pelanggan_id' => $data->pelangganId,
                'nama_pelanggan' => $data->namaPelanggan,
                'waktu' => $data->waktu,
                'diskon' => $data->diskon,
            ]);

            if ($nota->jenis === JenisNota::BarangSatuan) {
                $nota->barangs()->delete();
            }

            $this->persist($nota, $data, $nota->jenis);

            return $nota->fresh(['kiloan', 'jenisLayanan', 'barangs.barang']);
        });
    }

    public function delete(Nota $nota): void
    {
        DB::transaction(fn () => $nota->delete());
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

        $total = $this->pricing->kiloan($berat, $kiloan->harga, $data->diskon);

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

        $total = $this->pricing->satuan($baris, $layanan->harga, $data->diskon);

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

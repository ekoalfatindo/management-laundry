<?php

namespace App\DTOs;

use App\Enums\JenisNota;
use Illuminate\Support\Carbon;

final readonly class NotaData
{
    /**
     * @param  array<int, array{barang_id: int, jumlah_barang: int}>  $barangs
     */
    public function __construct(
        public JenisNota $jenis,
        public string $namaPelanggan,
        public ?int $pelangganId,
        public ?int $jenisLayananId,
        public ?int $kiloanId,
        public ?float $berat,
        public Carbon $waktu,
        public int $diskon,
        public array $barangs = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data, JenisNota $jenis): self
    {
        return new self(
            jenis: $jenis,
            namaPelanggan: trim((string) $data['nama_pelanggan']),
            pelangganId: filled($data['pelanggan_id'] ?? null) ? (int) $data['pelanggan_id'] : null,
            jenisLayananId: filled($data['jenis_layanan_id'] ?? null) ? (int) $data['jenis_layanan_id'] : null,
            kiloanId: filled($data['kiloan_id'] ?? null) ? (int) $data['kiloan_id'] : null,
            berat: filled($data['berat'] ?? null) ? (float) $data['berat'] : null,
            waktu: Carbon::parse($data['waktu']),
            diskon: (int) ($data['diskon'] ?? 0),
            barangs: collect($data['barangs'] ?? [])
                ->map(fn (array $row): array => [
                    'barang_id' => (int) $row['barang_id'],
                    'jumlah_barang' => (int) $row['jumlah_barang'],
                ])
                ->values()
                ->all(),
        );
    }
}

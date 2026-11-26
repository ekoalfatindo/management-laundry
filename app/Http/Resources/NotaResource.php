<?php

namespace App\Http\Resources;

use App\Support\Terbilang;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'no_nota' => $this->no_nota,
            'jenis' => $this->jenis->value,
            'pelanggan_id' => $this->pelanggan_id,
            'nama_pelanggan' => $this->nama_pelanggan,
            'pelanggan' => $this->whenLoaded('pelanggan', fn () => $this->pelanggan ? [
                'id' => $this->pelanggan->id,
                'name' => $this->pelanggan->name,
                'email' => $this->pelanggan->email,
                'kategori_pelanggan' => $this->pelanggan->kategori_pelanggan?->value,
                'member_sejak' => $this->pelanggan->member_sejak?->toDateString(),
            ] : null),
            'berat' => $this->berat !== null ? (float) $this->berat : null,
            'harga_per_kg' => $this->harga_per_kg,
            'biaya_layanan' => $this->biaya_layanan,
            'waktu' => $this->waktu?->toIso8601String(),
            'total_harga' => $this->total_harga,
            'diskon' => $this->diskon,
            'diskon_nominal' => $this->diskon_nominal,
            'potongan' => max(0, $this->total_harga - $this->jumlah),
            'reward_label' => $this->reward_label,
            'customer_reward_id' => $this->customer_reward_id,
            'jumlah' => $this->jumlah,
            'status' => $this->status->value,
            'status_pembayaran' => $this->status_pembayaran->value,
            'dibatalkan_at' => $this->dibatalkan_at?->toIso8601String(),
            'is_selesai' => $this->isSelesai(),
            'is_dibatalkan' => $this->isDibatalkan(),
            'is_lunas' => $this->isLunas(),
            'terbilang' => Terbilang::rupiah($this->jumlah),
            'reward' => $this->whenLoaded('reward', fn () => $this->reward ? [
                'id' => $this->reward->id,
                'label' => $this->reward->label,
                'sumber' => $this->reward->sumber->value,
                'tipe_hadiah' => $this->reward->tipe_hadiah->value,
                'nilai_hadiah' => $this->reward->nilai_hadiah,
                'nilai_terpakai' => $this->reward->nilai_terpakai,
            ] : null),
            'kiloan' => $this->whenLoaded('kiloan', fn () => $this->kiloan ? [
                'id' => $this->kiloan->id,
                'paket' => $this->kiloan->paket,
                'harga' => $this->kiloan->harga,
                'description' => $this->kiloan->description,
            ] : null),
            'jenis_layanan' => $this->whenLoaded('jenisLayanan', fn () => $this->jenisLayanan ? [
                'id' => $this->jenisLayanan->id,
                'jenis_cuci' => $this->jenisLayanan->jenis_cuci,
                'waktu' => $this->jenisLayanan->waktu,
                'harga' => $this->jenisLayanan->harga,
            ] : null),
            'barangs' => $this->whenLoaded('barangs', fn () => NotaBarangResource::collection($this->barangs)->resolve()),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}

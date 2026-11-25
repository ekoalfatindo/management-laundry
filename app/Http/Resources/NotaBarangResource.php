<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotaBarangResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'barang_id' => $this->barang_id,
            'barang' => $this->barang,
            'harga' => $this->harga,
            'jumlah_barang' => $this->jumlah_barang,
            'total_harga' => $this->total_harga,
        ];
    }
}

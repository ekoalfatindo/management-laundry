<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $roles = $this->getRoleNames()->values();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'jenis_kelamin' => $this->jenis_kelamin,
            'alamat' => $this->alamat,
            'kategori_pelanggan' => $this->kategori_pelanggan?->value,
            'member_sejak' => $this->member_sejak?->toDateString(),
            'roles' => $roles,
            'role' => $roles->first(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}

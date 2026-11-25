<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

#[Fillable(['nama_perusahaan', 'logo_path'])]
class Pengaturan extends Model
{
    protected $table = 'pengaturan';

    public static function current(): self
    {
        return self::query()->firstOrCreate([], [
            'nama_perusahaan' => config('app.name', 'Laundry Management'),
        ]);
    }

    /**
     * @return array{nama_perusahaan: string, logo_url: string|null}
     */
    public function toSharedArray(): array
    {
        return [
            'nama_perusahaan' => $this->nama_perusahaan,
            'logo_url' => $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null,
        ];
    }
}

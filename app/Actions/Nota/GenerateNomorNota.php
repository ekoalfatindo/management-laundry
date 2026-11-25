<?php

namespace App\Actions\Nota;

use App\Exceptions\DomainException;
use App\Models\Nota;
use Illuminate\Support\Str;

class GenerateNomorNota
{
    public function execute(): string
    {
        for ($attempt = 0; $attempt < 20; $attempt++) {
            $nomor = '#'.Str::upper(Str::random(3)).Str::random(3);

            if (! Nota::query()->where('no_nota', $nomor)->exists()) {
                return $nomor;
            }
        }

        throw new DomainException('Gagal membuat nomor nota unik. Silakan coba lagi.');
    }
}

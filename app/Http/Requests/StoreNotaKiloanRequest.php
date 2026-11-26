<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesNotaReward;
use App\Models\Nota;
use App\Rules\PelangganTerdaftar;
use Illuminate\Foundation\Http\FormRequest;

class StoreNotaKiloanRequest extends FormRequest
{
    use ValidatesNotaReward;

    public function authorize(): bool
    {
        return $this->user()?->can('create', Nota::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nama_pelanggan' => ['required', 'string', 'max:255'],
            'pelanggan_id' => ['nullable', 'integer', 'exists:users,id', new PelangganTerdaftar],
            'kiloan_id' => ['required', 'integer', 'exists:kiloans,id'],
            'berat' => ['required', 'numeric', 'min:0.1', 'max:1000'],
            'waktu' => ['required', 'date'],
            'diskon' => ['required', 'integer', 'between:0,100'],
            ...$this->rewardRules(),
        ];
    }
}

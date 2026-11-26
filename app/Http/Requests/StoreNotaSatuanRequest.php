<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesNotaReward;
use App\Models\Nota;
use App\Rules\PelangganTerdaftar;
use Illuminate\Foundation\Http\FormRequest;

class StoreNotaSatuanRequest extends FormRequest
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
            'jenis_layanan_id' => ['required', 'integer', 'exists:jenis_layanans,id'],
            'waktu' => ['required', 'date'],
            'diskon' => ['required', 'integer', 'between:0,100'],
            'barangs' => ['required', 'array', 'min:1'],
            'barangs.*.barang_id' => ['required', 'integer', 'exists:barangs,id'],
            'barangs.*.jumlah_barang' => ['required', 'integer', 'min:1', 'max:100000'],
            ...$this->rewardRules(),
        ];
    }
}

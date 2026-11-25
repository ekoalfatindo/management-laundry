<?php

namespace App\Http\Requests;

use App\Enums\JenisNota;
use App\Models\Nota;
use Illuminate\Foundation\Http\FormRequest;

class UpdateNotaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $nota = $this->route('nota');

        return $nota instanceof Nota
            && ($this->user()?->can('update', $nota) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $nota = $this->route('nota');
        $isKiloan = $nota instanceof Nota && $nota->jenis === JenisNota::PaketKiloan;

        return [
            'nama_pelanggan' => ['required', 'string', 'max:255'],
            'pelanggan_id' => ['nullable', 'integer', 'exists:users,id'],
            'waktu' => ['required', 'date'],
            'diskon' => ['required', 'integer', 'between:0,100'],
            'kiloan_id' => $isKiloan
                ? ['required', 'integer', 'exists:kiloans,id']
                : ['nullable'],
            'berat' => $isKiloan
                ? ['required', 'numeric', 'min:0.1', 'max:1000']
                : ['nullable'],
            'jenis_layanan_id' => $isKiloan
                ? ['nullable']
                : ['required', 'integer', 'exists:jenis_layanans,id'],
            'barangs' => $isKiloan
                ? ['nullable', 'array']
                : ['required', 'array', 'min:1'],
            'barangs.*.barang_id' => ['required_with:barangs', 'integer', 'exists:barangs,id'],
            'barangs.*.jumlah_barang' => ['required_with:barangs', 'integer', 'min:1', 'max:100000'],
        ];
    }
}

<?php

namespace App\Http\Requests;

use App\Enums\KategoriPelanggan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePelangganRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('pelanggan.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'jenis_kelamin' => ['required', Rule::in(['Laki-Laki', 'Perempuan'])],
            'alamat' => ['nullable', 'string', 'max:255'],
            'kategori_pelanggan' => ['nullable', Rule::in(KategoriPelanggan::values())],
        ];
    }
}

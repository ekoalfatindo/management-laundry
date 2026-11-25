<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateJenisLayananRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('jenisLayanan')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'jenis_cuci' => [
                'required',
                'string',
                'max:255',
                Rule::unique('jenis_layanans', 'jenis_cuci')->ignore($this->route('jenisLayanan')),
            ],
            'waktu' => ['required', 'string', 'max:50'],
            'harga' => ['required', 'integer', 'min:0', 'max:1000000000'],
        ];
    }
}

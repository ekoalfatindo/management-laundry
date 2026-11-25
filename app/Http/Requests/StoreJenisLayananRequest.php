<?php

namespace App\Http\Requests;

use App\Models\JenisLayanan;
use Illuminate\Foundation\Http\FormRequest;

class StoreJenisLayananRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', JenisLayanan::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'jenis_cuci' => ['required', 'string', 'max:255', 'unique:jenis_layanans,jenis_cuci'],
            'waktu' => ['required', 'string', 'max:50'],
            'harga' => ['required', 'integer', 'min:0', 'max:1000000000'],
        ];
    }
}

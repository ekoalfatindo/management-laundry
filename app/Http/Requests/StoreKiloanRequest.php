<?php

namespace App\Http\Requests;

use App\Models\Kiloan;
use Illuminate\Foundation\Http\FormRequest;

class StoreKiloanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Kiloan::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'paket' => ['required', 'string', 'max:255'],
            'harga' => ['required', 'integer', 'min:0', 'max:1000000000'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }
}

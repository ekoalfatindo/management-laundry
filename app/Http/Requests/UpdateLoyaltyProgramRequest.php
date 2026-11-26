<?php

namespace App\Http\Requests;

use App\Enums\TipeHadiah;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLoyaltyProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('loyalitas.manage') ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('tipe_hadiah') === TipeHadiah::Gratis->value
            && in_array($this->input('nilai_hadiah'), [null, ''], true)) {
            $this->merge(['nilai_hadiah' => 0]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $nilaiRule = $this->input('tipe_hadiah') === TipeHadiah::Gratis->value
            ? 'between:0,100000000'
            : 'between:1,100000000';

        return [
            'nama' => ['required', 'string', 'max:100'],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
            'target_transaksi' => ['required', 'integer', 'between:1,1000'],
            'tipe_hadiah' => ['required', Rule::in(TipeHadiah::values())],
            'nilai_hadiah' => ['required', 'integer', $nilaiRule],
            'masa_berlaku_hari' => ['nullable', 'integer', 'between:1,3650'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->input('tipe_hadiah') === TipeHadiah::Persen->value && (int) $this->input('nilai_hadiah') > 100) {
                $validator->errors()->add('nilai_hadiah', 'Nilai diskon persen maksimal 100.');
            }
        });
    }
}

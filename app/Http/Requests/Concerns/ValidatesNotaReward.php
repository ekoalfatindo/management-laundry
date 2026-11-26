<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Contracts\Validation\Validator;

trait ValidatesNotaReward
{
    /**
     * @return array<string, mixed>
     */
    protected function rewardRules(): array
    {
        return [
            'customer_reward_id' => ['nullable', 'integer', 'exists:customer_rewards,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->filled('customer_reward_id')) {
                return;
            }

            if (blank($this->input('pelanggan_id'))) {
                $validator->errors()->add('customer_reward_id', 'Hadiah hanya dapat digunakan untuk pelanggan terdaftar.');
            }

            if ((int) $this->input('diskon', 0) !== 0) {
                $validator->errors()->add('diskon', 'Diskon persen tidak dapat digabung dengan hadiah loyalitas atau promosi.');
            }
        });
    }
}

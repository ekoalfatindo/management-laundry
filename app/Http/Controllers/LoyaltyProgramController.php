<?php

namespace App\Http\Controllers;

use App\Enums\KategoriPelanggan;
use App\Enums\StatusHadiah;
use App\Enums\SumberHadiah;
use App\Enums\UserRole;
use App\Http\Requests\UpdateLoyaltyProgramRequest;
use App\Models\CustomerReward;
use App\Models\LoyaltyProgram;
use App\Models\User;
use App\Services\RewardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class LoyaltyProgramController extends Controller
{
    public function __construct(private readonly RewardService $rewards) {}

    public function edit(): Response
    {
        $program = LoyaltyProgram::query()->latest('id')->first();

        return Inertia::render('Loyalitas/Index', [
            'program' => $program ? $this->rewards->programPayload($program) : null,
            'stats' => [
                'members' => User::query()
                    ->role(UserRole::Pelanggan->value)
                    ->where('kategori_pelanggan', KategoriPelanggan::Member->value)
                    ->count(),
                'hadiah_tersedia' => CustomerReward::query()
                    ->where('sumber', SumberHadiah::Loyalitas->value)
                    ->tersedia()
                    ->count(),
                'hadiah_digunakan' => CustomerReward::query()
                    ->where('sumber', SumberHadiah::Loyalitas->value)
                    ->where('status', StatusHadiah::Digunakan->value)
                    ->count(),
            ],
        ]);
    }

    public function update(UpdateLoyaltyProgramRequest $request): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data): void {
            $program = LoyaltyProgram::query()->latest('id')->first();

            if ($program === null) {
                LoyaltyProgram::query()->create($data);
            } else {
                LoyaltyProgram::query()
                    ->where('id', '!=', $program->id)
                    ->where('is_active', true)
                    ->update(['is_active' => false]);

                $program->update($data);
            }

            $this->rewards->syncAllCustomers();
        });

        return back()->with('success', 'Program loyalitas berhasil disimpan.');
    }
}

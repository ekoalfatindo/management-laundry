<?php

namespace App\Services;

use App\Enums\KategoriPelanggan;
use App\Enums\StatusHadiah;
use App\Enums\StatusNota;
use App\Enums\SumberHadiah;
use App\Enums\TipeHadiah;
use App\Enums\UserRole;
use App\Exceptions\DomainException;
use App\Models\CustomerReward;
use App\Models\LoyaltyProgram;
use App\Models\Nota;
use App\Models\Promotion;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class RewardService
{
    public function __construct(private readonly PricingCalculator $pricing) {}

    public function sync(User $user, ?Carbon $at = null): void
    {
        $at ??= Carbon::now();

        $this->expire($user, $at);
        $this->syncLoyalty($user, $at);
        $this->syncPromotions($user, $at);
    }

    public function activeProgram(): ?LoyaltyProgram
    {
        return LoyaltyProgram::query()->aktif()->latest('id')->first();
    }

    public function syncAllCustomers(): void
    {
        User::query()
            ->role(UserRole::Pelanggan->value)
            ->chunkById(100, function (Collection $users): void {
                foreach ($users as $user) {
                    $this->sync($user);
                }
            });
    }

    public function completedTransactions(User $user, ?Promotion $promotion = null, ?int $excludeNotaId = null): int
    {
        return Nota::query()
            ->where('pelanggan_id', $user->id)
            ->where('status', StatusNota::Selesai->value)
            ->when($excludeNotaId !== null, fn ($query) => $query->where('id', '!=', $excludeNotaId))
            ->when($promotion?->mulai, fn ($query) => $query->where('waktu', '>=', $promotion->mulai->copy()->startOfDay()))
            ->when($promotion?->selesai, fn ($query) => $query->where('waktu', '<=', $promotion->selesai->copy()->endOfDay()))
            ->count();
    }

    /**
     * @return array{reward: CustomerReward, potongan: int}
     */
    public function reserve(User $user, int $rewardId, int $gross, Nota $nota): array
    {
        $reward = CustomerReward::query()->whereKey($rewardId)->lockForUpdate()->first();

        if ($reward === null || $reward->user_id !== $user->id) {
            throw new DomainException('Hadiah tidak tersedia untuk pelanggan ini.');
        }

        if (! $reward->bisaDigunakan()) {
            throw new DomainException('Hadiah sudah digunakan atau kedaluwarsa.');
        }

        $potongan = $this->pricing->potonganHadiah($gross, $reward->tipe_hadiah, $reward->nilai_hadiah);

        $reward->update([
            'status' => StatusHadiah::Digunakan,
            'digunakan_at' => Carbon::now(),
            'nota_id' => $nota->id,
            'nilai_terpakai' => $potongan,
        ]);

        return ['reward' => $reward->refresh(), 'potongan' => $potongan];
    }

    public function releaseFor(Nota $nota): void
    {
        if ($nota->customer_reward_id === null) {
            return;
        }

        $reward = CustomerReward::query()->whereKey($nota->customer_reward_id)->lockForUpdate()->first();

        if ($reward === null) {
            return;
        }

        $reward->update([
            'status' => $reward->kedaluwarsa() ? StatusHadiah::Kedaluwarsa : StatusHadiah::Tersedia,
            'digunakan_at' => null,
            'nota_id' => null,
            'nilai_terpakai' => 0,
        ]);
    }

    public function assertCancelable(Nota $nota): void
    {
        $user = $nota->pelanggan;

        if ($user === null) {
            return;
        }

        $used = $user->rewards()
            ->where('status', StatusHadiah::Digunakan->value)
            ->get()
            ->reject(fn (CustomerReward $reward): bool => $reward->nota_id === $nota->id);

        foreach ($used as $reward) {
            $target = $reward->loyaltyProgram?->target_transaksi ?? $reward->promotion?->target_transaksi;

            if ($target === null || $target < 1) {
                continue;
            }

            $count = $this->completedTransactions($user, $reward->promotion, $nota->id);

            if (intdiv($count, $target) < $reward->siklus) {
                throw new DomainException('Nota tidak dapat dibatalkan karena hadiah dari transaksi ini sudah digunakan pada nota lain.');
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function progress(User $user): array
    {
        $program = $this->activeProgram();
        $loyalty = null;

        if ($program !== null) {
            $target = max(1, $program->target_transaksi);
            $count = $this->completedTransactions($user);
            $remainder = $count % $target;

            $loyalty = [
                'program' => $this->programPayload($program),
                'eligible' => $user->isMember(),
                'transaksi_selesai' => $count,
                'target' => $target,
                'sisa' => $remainder === 0 && $count > 0 ? 0 : $target - $remainder,
                'siklus' => intdiv($count, $target),
                'hadiah_tersedia' => $this->loyaltyRewards($user)->tersedia()->count(),
                'hadiah_digunakan' => $this->loyaltyRewards($user)->where('status', StatusHadiah::Digunakan->value)->count(),
            ];
        }

        $promotions = Promotion::query()
            ->orderByDesc('is_active')
            ->orderBy('id')
            ->get()
            ->map(function (Promotion $promotion) use ($user): array {
                $target = max(1, $promotion->target_transaksi);
                $count = $this->completedTransactions($user, $promotion);
                $remainder = $count % $target;
                $rewards = $user->rewards()->where('promotion_id', $promotion->id);

                return [
                    'promotion' => $this->promotionPayload($promotion),
                    'berlaku' => $promotion->berlakuUntuk($user->kategori_pelanggan ?? KategoriPelanggan::Reguler),
                    'aktif' => $promotion->isAktif(),
                    'transaksi_selesai' => $count,
                    'target' => $target,
                    'sisa' => $remainder === 0 && $count > 0 ? 0 : $target - $remainder,
                    'siklus' => intdiv($count, $target),
                    'hadiah_tersedia' => (clone $rewards)->tersedia()->count(),
                    'hadiah_digunakan' => (clone $rewards)->where('status', StatusHadiah::Digunakan->value)->count(),
                ];
            })
            ->all();

        $rewards = $user->rewards()
            ->with(['loyaltyProgram', 'promotion'])
            ->latest('id')
            ->get()
            ->map(fn (CustomerReward $reward): array => $this->rewardPayload($reward))
            ->all();

        return [
            'loyalty' => $loyalty,
            'promotions' => $promotions,
            'rewards' => $rewards,
        ];
    }

    /**
     * @return Collection<int, CustomerReward>
     */
    public function availableRewards(User $user): Collection
    {
        return $user->rewards()
            ->tersedia()
            ->orderByRaw('kedaluwarsa_at IS NULL')
            ->orderBy('kedaluwarsa_at')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    public function rewardPayload(CustomerReward $reward): array
    {
        $status = $reward->status === StatusHadiah::Tersedia && $reward->kedaluwarsa()
            ? StatusHadiah::Kedaluwarsa
            : $reward->status;

        return [
            'id' => $reward->id,
            'reward_key' => $reward->reward_key,
            'label' => $reward->label,
            'sumber' => $reward->sumber->value,
            'tipe_hadiah' => $reward->tipe_hadiah->value,
            'tipe_hadiah_label' => $reward->tipe_hadiah->label(),
            'nilai_hadiah' => $reward->nilai_hadiah,
            'nilai_label' => $this->valueLabel($reward->tipe_hadiah, $reward->nilai_hadiah),
            'status' => $status->value,
            'nilai_terpakai' => $reward->nilai_terpakai,
            'promotion_id' => $reward->promotion_id,
            'promotion_nama' => $reward->promotion?->nama,
            'loyalty_program_id' => $reward->loyalty_program_id,
            'loyalty_program_nama' => $reward->loyaltyProgram?->nama,
            'siklus' => $reward->siklus,
            'diperoleh_at' => $reward->diperoleh_at?->toIso8601String(),
            'kedaluwarsa_at' => $reward->kedaluwarsa_at?->toIso8601String(),
            'digunakan_at' => $reward->digunakan_at?->toIso8601String(),
            'nota_id' => $reward->nota_id,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function programPayload(LoyaltyProgram $program): array
    {
        return [
            'id' => $program->id,
            'nama' => $program->nama,
            'deskripsi' => $program->deskripsi,
            'target_transaksi' => $program->target_transaksi,
            'tipe_hadiah' => $program->tipe_hadiah->value,
            'tipe_hadiah_label' => $program->tipe_hadiah->label(),
            'nilai_hadiah' => $program->nilai_hadiah,
            'nilai_label' => $this->valueLabel($program->tipe_hadiah, $program->nilai_hadiah),
            'masa_berlaku_hari' => $program->masa_berlaku_hari,
            'is_active' => $program->is_active,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function promotionPayload(Promotion $promotion): array
    {
        return [
            'id' => $promotion->id,
            'nama' => $promotion->nama,
            'deskripsi' => $promotion->deskripsi,
            'syarat' => $promotion->syarat,
            'target_kategori' => $promotion->target_kategori?->value,
            'target_transaksi' => $promotion->target_transaksi,
            'tipe_hadiah' => $promotion->tipe_hadiah->value,
            'tipe_hadiah_label' => $promotion->tipe_hadiah->label(),
            'nilai_hadiah' => $promotion->nilai_hadiah,
            'nilai_label' => $this->valueLabel($promotion->tipe_hadiah, $promotion->nilai_hadiah),
            'mulai' => $promotion->mulai?->toDateString(),
            'selesai' => $promotion->selesai?->toDateString(),
            'is_active' => $promotion->is_active,
        ];
    }

    public function valueLabel(TipeHadiah $tipe, int $nilai): string
    {
        return match ($tipe) {
            TipeHadiah::Persen => $nilai.'%',
            TipeHadiah::Nominal => 'Rp '.number_format($nilai, 0, ',', '.'),
            TipeHadiah::Gratis => $nilai > 0
                ? 'Gratis s.d. Rp '.number_format($nilai, 0, ',', '.')
                : 'Gratis penuh',
        };
    }

    private function syncLoyalty(User $user, Carbon $at): void
    {
        $program = $this->activeProgram();

        if ($program === null) {
            return;
        }

        $target = max(1, $program->target_transaksi);
        $cycles = intdiv($this->completedTransactions($user), $target);

        if ($user->isMember()) {
            $this->ensureRewards(
                $user,
                SumberHadiah::Loyalitas,
                $program->id,
                null,
                $cycles,
                $program->nama,
                $program->tipe_hadiah,
                $program->nilai_hadiah,
                $program->masa_berlaku_hari,
                $at,
            );
        }

        $this->revokeExcess($user, SumberHadiah::Loyalitas, $program->id, null, $cycles);
    }

    private function syncPromotions(User $user, Carbon $at): void
    {
        $promotionIds = $user->rewards()
            ->where('sumber', SumberHadiah::Promosi->value)
            ->whereNotNull('promotion_id')
            ->pluck('promotion_id')
            ->all();

        $promotions = Promotion::query()
            ->where(fn ($query) => $query->whereIn('id', $promotionIds)->orWhere('is_active', true))
            ->get()
            ->filter(fn (Promotion $promotion): bool => in_array($promotion->id, $promotionIds, true) || $promotion->isAktif());

        $kategori = $user->kategori_pelanggan ?? KategoriPelanggan::Reguler;

        foreach ($promotions as $promotion) {
            $target = max(1, $promotion->target_transaksi);
            $cycles = intdiv($this->completedTransactions($user, $promotion), $target);

            if ($promotion->isAktif() && $promotion->berlakuUntuk($kategori)) {
                $this->ensureRewards(
                    $user,
                    SumberHadiah::Promosi,
                    null,
                    $promotion->id,
                    $cycles,
                    $promotion->nama,
                    $promotion->tipe_hadiah,
                    $promotion->nilai_hadiah,
                    null,
                    $at,
                );
            }

            $this->revokeExcess($user, SumberHadiah::Promosi, null, $promotion->id, $cycles);
        }
    }

    private function ensureRewards(
        User $user,
        SumberHadiah $sumber,
        ?int $programId,
        ?int $promotionId,
        int $cycles,
        string $nama,
        TipeHadiah $tipe,
        int $nilai,
        ?int $masaHari,
        Carbon $at,
    ): void {
        for ($cycle = 1; $cycle <= $cycles; $cycle++) {
            $prefix = $sumber === SumberHadiah::Loyalitas ? 'LOY' : 'PRM';
            $key = sprintf('%s-%d-%d-%d', $prefix, $programId ?? $promotionId, $user->id, $cycle);

            $reward = CustomerReward::query()->where('reward_key', $key)->lockForUpdate()->first();

            if ($reward !== null) {
                if ($reward->status === StatusHadiah::Dibatalkan && $reward->nota_id === null) {
                    $reward->update([
                        'status' => StatusHadiah::Tersedia,
                        'diperoleh_at' => $at,
                        'kedaluwarsa_at' => $masaHari !== null ? $at->copy()->addDays($masaHari) : null,
                        'digunakan_at' => null,
                        'nilai_terpakai' => 0,
                    ]);
                }

                continue;
            }

            CustomerReward::query()->create([
                'reward_key' => $key,
                'user_id' => $user->id,
                'sumber' => $sumber,
                'loyalty_program_id' => $programId,
                'promotion_id' => $promotionId,
                'siklus' => $cycle,
                'label' => $nama,
                'tipe_hadiah' => $tipe,
                'nilai_hadiah' => $nilai,
                'status' => StatusHadiah::Tersedia,
                'nilai_terpakai' => 0,
                'diperoleh_at' => $at,
                'kedaluwarsa_at' => $masaHari !== null ? $at->copy()->addDays($masaHari) : null,
            ]);
        }
    }

    private function revokeExcess(User $user, SumberHadiah $sumber, ?int $programId, ?int $promotionId, int $cycles): void
    {
        $user->rewards()
            ->where('sumber', $sumber->value)
            ->when($programId !== null, fn ($query) => $query->where('loyalty_program_id', $programId))
            ->when($promotionId !== null, fn ($query) => $query->where('promotion_id', $promotionId))
            ->where('siklus', '>', $cycles)
            ->where('status', StatusHadiah::Tersedia->value)
            ->update(['status' => StatusHadiah::Dibatalkan->value]);
    }

    private function expire(User $user, Carbon $at): void
    {
        $user->rewards()
            ->where('status', StatusHadiah::Tersedia->value)
            ->whereNotNull('kedaluwarsa_at')
            ->where('kedaluwarsa_at', '<=', $at)
            ->update(['status' => StatusHadiah::Kedaluwarsa->value]);
    }

    private function loyaltyRewards(User $user): HasMany
    {
        return $user->rewards()->where('sumber', SumberHadiah::Loyalitas->value);
    }
}

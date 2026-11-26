<?php

namespace Tests\Feature;

use App\DTOs\NotaData;
use App\Enums\JenisNota;
use App\Enums\KategoriPelanggan;
use App\Enums\StatusHadiah;
use App\Enums\SumberHadiah;
use App\Enums\TipeHadiah;
use App\Enums\UserRole;
use App\Models\Kiloan;
use App\Models\LoyaltyProgram;
use App\Models\Nota;
use App\Models\Promotion;
use App\Models\User;
use App\Services\NotaService;
use App\Services\RewardService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RewardAccuracyTest extends TestCase
{
    use RefreshDatabase;

    private NotaService $notas;

    private RewardService $rewards;

    private Kiloan $kiloan;

    private User $member;

    private User $reguler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->kiloan = Kiloan::query()->create(['paket' => 'Reguler 3 Hari', 'harga' => 20000]);

        $this->member = $this->userWithRole(UserRole::Pelanggan, [
            'kategori_pelanggan' => KategoriPelanggan::Member->value,
            'member_sejak' => '2026-01-05',
        ]);

        $this->reguler = $this->userWithRole(UserRole::Pelanggan, [
            'kategori_pelanggan' => KategoriPelanggan::Reguler->value,
        ]);

        $this->notas = app(NotaService::class);
        $this->rewards = app(RewardService::class);
    }

    public function test_loyalty_reward_count_matches_completed_cycles_and_is_member_only(): void
    {
        $program = $this->loyaltyProgram(target: 2);

        foreach (range(1, 5) as $day) {
            $this->completeNota($this->member, "2026-11-0{$day} 09:00:00");
            $this->completeNota($this->reguler, "2026-11-0{$day} 10:00:00");
        }

        $loyalty = $this->member->rewards()
            ->where('sumber', SumberHadiah::Loyalitas->value)
            ->orderBy('siklus')
            ->get();

        $this->assertCount(2, $loyalty);
        $this->assertSame([1, 2], $loyalty->pluck('siklus')->all());
        $this->assertSame([
            "LOY-{$program->id}-{$this->member->id}-1",
            "LOY-{$program->id}-{$this->member->id}-2",
        ], $loyalty->pluck('reward_key')->all());
        $this->assertTrue($loyalty->every(fn ($reward): bool => $reward->status === StatusHadiah::Tersedia));

        $this->assertSame(0, $this->reguler->rewards()->where('sumber', SumberHadiah::Loyalitas->value)->count());

        $progress = $this->rewards->progress($this->reguler);
        $this->assertFalse($progress['loyalty']['eligible']);
        $this->assertSame(5, $progress['loyalty']['transaksi_selesai']);
        $this->assertSame(0, $progress['loyalty']['hadiah_tersedia']);
    }

    public function test_shared_promotion_reaches_member_and_regular_customers(): void
    {
        $promotion = $this->promotion(target: 1, targetKategori: null);

        $this->completeNota($this->member, '2026-11-01 09:00:00');
        $this->completeNota($this->reguler, '2026-11-01 10:00:00');

        foreach ([$this->member, $this->reguler] as $customer) {
            $rewards = $customer->rewards()
                ->where('sumber', SumberHadiah::Promosi->value)
                ->get();

            $this->assertCount(1, $rewards);
            $this->assertSame($promotion->id, $rewards->first()->promotion_id);
            $this->assertSame($customer->id, $rewards->first()->user_id);
            $this->assertSame("PRM-{$promotion->id}-{$customer->id}-1", $rewards->first()->reward_key);
            $this->assertSame(StatusHadiah::Tersedia, $rewards->first()->status);
        }
    }

    public function test_loyalty_and_shared_promotion_count_cycles_independently(): void
    {
        $program = $this->loyaltyProgram(target: 2);
        $promotion = $this->promotion(target: 3, tipe: TipeHadiah::Nominal, nilai: 5000, targetKategori: null);

        foreach (range(1, 6) as $day) {
            $this->completeNota($this->member, "2026-11-0{$day} 09:00:00");
        }

        $this->assertSame(3, $this->member->rewards()
            ->where('loyalty_program_id', $program->id)
            ->count());
        $this->assertSame(2, $this->member->rewards()
            ->where('promotion_id', $promotion->id)
            ->count());
        $this->assertSame(5, $this->member->rewards()->count());

        $progress = $this->rewards->progress($this->member);
        $this->assertSame(3, $progress['loyalty']['siklus']);
        $this->assertSame(2, $progress['promotions'][0]['siklus']);
    }

    public function test_promotion_counts_only_in_period_completed_notas(): void
    {
        $promotion = $this->promotion(
            target: 2,
            targetKategori: null,
            mulai: '2026-11-15',
            selesai: '2026-11-30',
        );

        $this->completeNota($this->member, '2026-11-10 09:00:00');
        $this->completeNota($this->member, '2026-11-16 09:00:00');
        $this->notas->create($this->notaData($this->member, '2026-11-18 09:00:00'));

        $this->assertSame(0, $this->member->rewards()->where('promotion_id', $promotion->id)->count());

        $trigger = $this->completeNota($this->member, '2026-11-20 09:00:00');

        $reward = $this->member->rewards()->where('promotion_id', $promotion->id)->firstOrFail();
        $this->assertSame(1, $reward->siklus);
        $this->assertSame(StatusHadiah::Tersedia, $reward->status);

        $this->notas->cancel($trigger);
        $this->assertSame(StatusHadiah::Dibatalkan, $reward->refresh()->status);

        $this->completeNota($this->member, '2026-11-22 09:00:00');
        $this->completeNota($this->member, '2026-11-24 09:00:00');

        $reward->refresh();
        $this->assertSame(StatusHadiah::Tersedia, $reward->status);
        $this->assertSame(1, $this->member->rewards()->where('promotion_id', $promotion->id)->count());
    }

    public function test_reward_value_cannot_exceed_gross_and_repeated_sync_is_idempotent(): void
    {
        $promotion = $this->promotion(target: 1, tipe: TipeHadiah::Nominal, nilai: 100000, targetKategori: null);

        $this->completeNota($this->member, '2026-11-01 09:00:00');
        $reward = $this->member->rewards()->where('promotion_id', $promotion->id)->firstOrFail();

        $nota = $this->notas->create($this->notaData($this->member, '2026-11-02 09:00:00', $reward->id));

        $this->assertSame(60000, $nota->total_harga);
        $this->assertSame(60000, $nota->diskon_nominal);
        $this->assertSame(0, $nota->jumlah);
        $this->assertSame(60000, $reward->refresh()->nilai_terpakai);

        $this->rewards->sync($this->member);
        $this->rewards->sync($this->member);

        $this->assertSame(1, $this->member->rewards()->where('promotion_id', $promotion->id)->count());
        $this->assertSame(StatusHadiah::Digunakan, $reward->refresh()->status);
    }

    private function loyaltyProgram(int $target, TipeHadiah $tipe = TipeHadiah::Persen, int $nilai = 10): LoyaltyProgram
    {
        return LoyaltyProgram::query()->create([
            'nama' => 'Loyalty Pelanggan Setia',
            'deskripsi' => 'Hadiah untuk pelanggan member.',
            'target_transaksi' => $target,
            'tipe_hadiah' => $tipe,
            'nilai_hadiah' => $nilai,
            'masa_berlaku_hari' => 30,
            'is_active' => true,
        ]);
    }

    private function promotion(
        int $target,
        TipeHadiah $tipe = TipeHadiah::Nominal,
        int $nilai = 5000,
        ?KategoriPelanggan $targetKategori = null,
        string $mulai = '2026-01-01',
        string $selesai = '2026-12-31',
    ): Promotion {
        return Promotion::query()->create([
            'nama' => 'Promo Belanja',
            'deskripsi' => 'Promosi umum.',
            'target_kategori' => $targetKategori,
            'target_transaksi' => $target,
            'tipe_hadiah' => $tipe,
            'nilai_hadiah' => $nilai,
            'mulai' => $mulai,
            'selesai' => $selesai,
            'is_active' => true,
        ]);
    }

    private function completeNota(User $customer, string $waktu, ?int $rewardId = null): Nota
    {
        $nota = $this->notas->create($this->notaData($customer, $waktu, $rewardId));

        return $this->notas->complete($nota);
    }

    private function notaData(User $customer, string $waktu, ?int $rewardId = null): NotaData
    {
        return new NotaData(
            jenis: JenisNota::PaketKiloan,
            namaPelanggan: $customer->name,
            pelangganId: $customer->id,
            jenisLayananId: null,
            kiloanId: $this->kiloan->id,
            berat: 3.0,
            waktu: Carbon::parse($waktu),
            diskon: 0,
            customerRewardId: $rewardId,
        );
    }
}

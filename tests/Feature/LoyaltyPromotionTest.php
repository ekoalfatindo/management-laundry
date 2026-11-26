<?php

namespace Tests\Feature;

use App\DTOs\NotaData;
use App\Enums\JenisNota;
use App\Enums\KategoriPelanggan;
use App\Enums\StatusHadiah;
use App\Enums\StatusNota;
use App\Enums\SumberHadiah;
use App\Enums\TipeHadiah;
use App\Enums\UserRole;
use App\Exceptions\DomainException;
use App\Models\Kiloan;
use App\Models\LoyaltyProgram;
use App\Models\Nota;
use App\Models\Promotion;
use App\Models\User;
use App\Services\NotaService;
use App\Services\RewardService;
use App\Services\UserService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class LoyaltyPromotionTest extends TestCase
{
    use RefreshDatabase;

    private NotaService $notas;

    private RewardService $rewards;

    private Kiloan $kiloan;

    private User $member;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->kiloan = Kiloan::query()->create(['paket' => 'Reguler 3 Hari', 'harga' => 20000]);
        $this->member = $this->userWithRole(UserRole::Pelanggan, [
            'kategori_pelanggan' => KategoriPelanggan::Member->value,
            'member_sejak' => '2026-01-05',
        ]);

        $this->notas = app(NotaService::class);
        $this->rewards = app(RewardService::class);
    }

    public function test_loyalty_reward_is_issued_only_after_enough_completed_transactions(): void
    {
        $program = $this->loyaltyProgram(target: 3);

        $this->completeNota($this->member, '2026-11-01 09:00:00');
        $this->completeNota($this->member, '2026-11-02 09:00:00');

        $this->assertSame(0, $this->member->rewards()->count());

        $this->completeNota($this->member, '2026-11-03 09:00:00');

        $reward = $this->member->rewards()->firstOrFail();

        $this->assertSame(SumberHadiah::Loyalitas, $reward->sumber);
        $this->assertSame($program->id, $reward->loyalty_program_id);
        $this->assertSame(1, $reward->siklus);
        $this->assertSame(StatusHadiah::Tersedia, $reward->status);
        $this->assertSame(0, $this->member->rewards()->where('siklus', 2)->count());
    }

    public function test_processing_and_cancelled_notas_do_not_count_towards_progress(): void
    {
        $this->loyaltyProgram(target: 2);

        $this->notas->create($this->notaData($this->member, '2026-11-01 09:00:00'));
        $cancelled = $this->completeNota($this->member, '2026-11-02 09:00:00');
        $this->notas->cancel($cancelled);

        $this->assertSame(0, $this->member->rewards()->count());
        $this->assertSame(0, $this->rewards->completedTransactions($this->member));
    }

    public function test_member_reward_is_single_use_and_reduces_the_next_nota(): void
    {
        $this->loyaltyProgram(target: 1, tipe: TipeHadiah::Persen, nilai: 10);
        $this->completeNota($this->member, '2026-11-01 09:00:00');

        $reward = $this->member->rewards()->firstOrFail();

        $nota = $this->notas->create($this->notaData($this->member, '2026-11-02 09:00:00', $reward->id));

        $this->assertSame(60000, $nota->total_harga);
        $this->assertSame(6000, $nota->diskon_nominal);
        $this->assertSame(54000, $nota->jumlah);
        $this->assertSame(0, $nota->diskon);
        $this->assertSame($reward->label, $nota->reward_label);

        $reward->refresh();
        $this->assertSame(StatusHadiah::Digunakan, $reward->status);
        $this->assertSame($nota->id, $reward->nota_id);
        $this->assertSame(6000, $reward->nilai_terpakai);

        $this->expectException(DomainException::class);
        $this->notas->create($this->notaData($this->member, '2026-11-03 09:00:00', $reward->id));
    }

    public function test_free_wash_reward_with_zero_value_makes_the_next_nota_free(): void
    {
        $this->loyaltyProgram(target: 1, tipe: TipeHadiah::Gratis, nilai: 0);
        $this->completeNota($this->member, '2026-11-01 09:00:00');

        $reward = $this->member->rewards()->firstOrFail();

        $this->assertSame(TipeHadiah::Gratis, $reward->tipe_hadiah);
        $this->assertSame(0, $reward->nilai_hadiah);

        $nota = $this->notas->create($this->notaData($this->member, '2026-11-02 09:00:00', $reward->id));

        $this->assertSame(60000, $nota->total_harga);
        $this->assertSame(60000, $nota->diskon_nominal);
        $this->assertSame(0, $nota->jumlah);
        $this->assertSame(0, $nota->diskon);
        $this->assertSame($reward->label, $nota->reward_label);

        $reward->refresh();
        $this->assertSame(StatusHadiah::Digunakan, $reward->status);
        $this->assertSame($nota->id, $reward->nota_id);
        $this->assertSame(60000, $reward->nilai_terpakai);
    }

    public function test_updating_a_nota_can_release_its_applied_reward(): void
    {
        $this->loyaltyProgram(target: 1, tipe: TipeHadiah::Persen, nilai: 10);
        $this->completeNota($this->member, '2026-11-01 09:00:00');

        $reward = $this->member->rewards()->firstOrFail();
        $nota = $this->notas->create($this->notaData($this->member, '2026-11-02 09:00:00', $reward->id));

        $this->assertSame(54000, $nota->jumlah);

        $updated = $this->notas->update($nota, $this->notaData($this->member, '2026-11-02 09:00:00'));

        $this->assertNull($updated->customer_reward_id);
        $this->assertNull($updated->reward_label);
        $this->assertSame(0, $updated->diskon_nominal);
        $this->assertSame(60000, $updated->jumlah);

        $reward->refresh();
        $this->assertSame(StatusHadiah::Tersedia, $reward->status);
        $this->assertNull($reward->nota_id);
    }

    public function test_updating_a_nota_can_switch_to_another_available_reward(): void
    {
        $this->loyaltyProgram(target: 1, tipe: TipeHadiah::Persen, nilai: 10);
        $this->completeNota($this->member, '2026-11-01 09:00:00');
        $this->completeNota($this->member, '2026-11-02 09:00:00');

        [$first, $second] = $this->member->rewards()->orderBy('id')->get()->all();
        $nota = $this->notas->create($this->notaData($this->member, '2026-11-03 09:00:00', $first->id));

        $updated = $this->notas->update($nota, $this->notaData($this->member, '2026-11-03 09:00:00', $second->id));

        $this->assertSame($second->id, $updated->customer_reward_id);
        $this->assertSame(StatusHadiah::Tersedia, $first->refresh()->status);
        $this->assertSame(StatusHadiah::Digunakan, $second->refresh()->status);
        $this->assertSame($nota->id, $second->nota_id);
    }

    public function test_reward_from_another_customer_is_rejected(): void
    {
        $this->loyaltyProgram(target: 1);
        $this->completeNota($this->member, '2026-11-01 09:00:00');

        $other = $this->userWithRole(UserRole::Pelanggan, [
            'kategori_pelanggan' => KategoriPelanggan::Member->value,
        ]);

        $this->expectException(DomainException::class);
        $this->notas->create($this->notaData($other, '2026-11-02 09:00:00', $this->member->rewards()->firstOrFail()->id));
    }

    public function test_loyalty_and_promotion_rewards_coexist_without_double_counting(): void
    {
        $this->loyaltyProgram(target: 2, tipe: TipeHadiah::Persen, nilai: 10);
        $this->promotion(target: 2, tipe: TipeHadiah::Nominal, nilai: 5000);

        $this->completeNota($this->member, '2026-11-01 09:00:00');
        $this->completeNota($this->member, '2026-11-02 09:00:00');

        $this->assertSame(2, $this->member->rewards()->count());
        $this->assertSame(1, $this->member->rewards()->where('sumber', SumberHadiah::Loyalitas->value)->count());
        $this->assertSame(1, $this->member->rewards()->where('sumber', SumberHadiah::Promosi->value)->count());

        $first = $this->member->rewards()->where('sumber', SumberHadiah::Loyalitas->value)->firstOrFail();
        $second = $this->member->rewards()->where('sumber', SumberHadiah::Promosi->value)->firstOrFail();

        $notaA = $this->notas->create($this->notaData($this->member, '2026-11-03 09:00:00', $first->id));
        $notaB = $this->notas->create($this->notaData($this->member, '2026-11-04 09:00:00', $second->id));

        $this->assertSame(54000, $notaA->jumlah);
        $this->assertSame(55000, $notaB->jumlah);
        $this->assertSame(2, $this->member->rewards()->where('status', StatusHadiah::Digunakan->value)->count());
    }

    public function test_inactive_or_expired_promotions_do_not_issue_rewards(): void
    {
        $inactive = Promotion::query()->create([
            'nama' => 'Promo Nonaktif',
            'target_transaksi' => 1,
            'tipe_hadiah' => TipeHadiah::Nominal,
            'nilai_hadiah' => 5000,
            'is_active' => false,
        ]);

        $expired = Promotion::query()->create([
            'nama' => 'Promo Kedaluwarsa',
            'target_transaksi' => 1,
            'tipe_hadiah' => TipeHadiah::Nominal,
            'nilai_hadiah' => 5000,
            'mulai' => '2025-01-01',
            'selesai' => '2025-03-31',
            'is_active' => true,
        ]);

        $this->completeNota($this->member, '2025-02-01 09:00:00');

        $this->assertSame(0, $this->member->rewards()->where('promotion_id', $inactive->id)->count());
        $this->assertSame(0, $this->member->rewards()->where('promotion_id', $expired->id)->count());
    }

    public function test_promotion_targets_the_configured_customer_category(): void
    {
        $this->promotion(target: 1, targetKategori: KategoriPelanggan::Member);

        $reguler = $this->userWithRole(UserRole::Pelanggan, [
            'kategori_pelanggan' => KategoriPelanggan::Reguler->value,
        ]);

        $this->completeNota($reguler, '2026-11-01 09:00:00');
        $this->completeNota($this->member, '2026-11-01 09:00:00');

        $this->assertSame(0, $reguler->rewards()->count());
        $this->assertSame(1, $this->member->rewards()->count());
    }

    public function test_cancelling_a_completed_nota_revokes_available_rewards(): void
    {
        $this->loyaltyProgram(target: 1);
        $nota = $this->completeNota($this->member, '2026-11-01 09:00:00');

        $reward = $this->member->rewards()->firstOrFail();
        $this->assertSame(StatusHadiah::Tersedia, $reward->status);

        $this->notas->cancel($nota);

        $reward->refresh();
        $this->assertSame(StatusHadiah::Dibatalkan, $reward->status);
        $this->assertSame(StatusNota::Dibatalkan, $nota->refresh()->status);
    }

    public function test_cancelling_a_nota_restores_the_reward_it_consumed(): void
    {
        $this->loyaltyProgram(target: 1);
        $this->completeNota($this->member, '2026-11-01 09:00:00');
        $reward = $this->member->rewards()->firstOrFail();

        $nota = $this->notas->create($this->notaData($this->member, '2026-11-02 09:00:00', $reward->id));
        $this->assertSame(StatusHadiah::Digunakan, $reward->refresh()->status);

        $this->notas->cancel($nota);

        $reward->refresh();
        $this->assertSame(StatusHadiah::Tersedia, $reward->status);
        $this->assertNull($reward->nota_id);
        $this->assertNull($reward->digunakan_at);
    }

    public function test_cancellation_is_blocked_when_the_generated_reward_was_already_used(): void
    {
        $this->loyaltyProgram(target: 1);
        $source = $this->completeNota($this->member, '2026-11-01 09:00:00');
        $reward = $this->member->rewards()->firstOrFail();

        $this->notas->create($this->notaData($this->member, '2026-11-02 09:00:00', $reward->id));

        $this->expectException(DomainException::class);
        $this->notas->cancel($source);

        $this->assertSame(StatusNota::Selesai, $source->refresh()->status);
    }

    public function test_expired_rewards_cannot_be_used(): void
    {
        $this->loyaltyProgram(target: 1, masaHari: 1);
        $this->completeNota($this->member, '2026-11-01 09:00:00');

        $reward = $this->member->rewards()->firstOrFail();
        $reward->update(['kedaluwarsa_at' => Carbon::now()->subDay()]);

        $this->expectException(DomainException::class);
        $this->notas->create($this->notaData($this->member, '2026-11-02 09:00:00', $reward->id));
    }

    public function test_expired_rewards_report_expired_status_in_progress_payload(): void
    {
        $this->loyaltyProgram(target: 1, masaHari: 1);
        $this->completeNota($this->member, '2026-11-01 09:00:00');

        $reward = $this->member->rewards()->firstOrFail();
        $reward->update(['kedaluwarsa_at' => Carbon::now()->subDay()]);

        $progress = $this->rewards->progress($this->member);
        $payload = collect($progress['rewards'])->firstWhere('id', $reward->id);

        $this->assertNotNull($payload);
        $this->assertSame(StatusHadiah::Kedaluwarsa->value, $payload['status']);
        $this->assertSame(0, $progress['loyalty']['hadiah_tersedia']);
    }

    public function test_category_change_keeps_history_and_stops_loyalty_issuance(): void
    {
        $this->loyaltyProgram(target: 2);

        $this->completeNota($this->member, '2026-11-01 09:00:00');
        $this->completeNota($this->member, '2026-11-02 09:00:00');

        $this->assertSame(1, $this->member->rewards()->count());

        app(UserService::class)->update($this->member, [
            'kategori_pelanggan' => KategoriPelanggan::Reguler->value,
        ], UserRole::Pelanggan);

        $this->member->refresh();

        $this->assertFalse($this->member->isMember());
        $this->assertNull($this->member->member_sejak);
        $this->assertSame(2, $this->member->notas()->count());

        $this->completeNota($this->member, '2026-11-03 09:00:00');
        $this->completeNota($this->member, '2026-11-04 09:00:00');

        $this->assertSame(1, $this->member->rewards()->count());
    }

    public function test_progress_reports_full_remaining_target_before_first_transaction(): void
    {
        $this->loyaltyProgram(target: 3);

        $progress = $this->rewards->progress($this->member);

        $this->assertSame(0, $progress['loyalty']['transaksi_selesai']);
        $this->assertSame(3, $progress['loyalty']['sisa']);
        $this->assertSame(0, $progress['loyalty']['siklus']);
    }

    public function test_progress_payload_reports_target_and_reward_counts(): void
    {
        $this->loyaltyProgram(target: 3);
        $this->completeNota($this->member, '2026-11-01 09:00:00');
        $this->completeNota($this->member, '2026-11-02 09:00:00');

        $progress = $this->rewards->progress($this->member);

        $this->assertSame(2, $progress['loyalty']['transaksi_selesai']);
        $this->assertSame(3, $progress['loyalty']['target']);
        $this->assertSame(1, $progress['loyalty']['sisa']);
        $this->assertSame(0, $progress['loyalty']['siklus']);
        $this->assertTrue($progress['loyalty']['eligible']);
    }

    private function loyaltyProgram(int $target, TipeHadiah $tipe = TipeHadiah::Persen, int $nilai = 10, ?int $masaHari = 30): LoyaltyProgram
    {
        return LoyaltyProgram::query()->create([
            'nama' => 'Loyalty Pelanggan Setia',
            'deskripsi' => 'Hadiah untuk pelanggan member.',
            'target_transaksi' => $target,
            'tipe_hadiah' => $tipe,
            'nilai_hadiah' => $nilai,
            'masa_berlaku_hari' => $masaHari,
            'is_active' => true,
        ]);
    }

    private function promotion(
        int $target,
        TipeHadiah $tipe = TipeHadiah::Nominal,
        int $nilai = 5000,
        ?KategoriPelanggan $targetKategori = null,
    ): Promotion {
        return Promotion::query()->create([
            'nama' => 'Promo Belanja',
            'deskripsi' => 'Promosi umum.',
            'target_kategori' => $targetKategori,
            'target_transaksi' => $target,
            'tipe_hadiah' => $tipe,
            'nilai_hadiah' => $nilai,
            'mulai' => '2026-01-01',
            'selesai' => '2026-12-31',
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

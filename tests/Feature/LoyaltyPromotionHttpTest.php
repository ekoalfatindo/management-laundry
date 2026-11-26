<?php

namespace Tests\Feature;

use App\Enums\JenisNota;
use App\Enums\KategoriPelanggan;
use App\Enums\StatusHadiah;
use App\Enums\StatusNota;
use App\Enums\StatusPembayaran;
use App\Enums\TipeHadiah;
use App\Enums\UserRole;
use App\Models\CustomerReward;
use App\Models\Kiloan;
use App\Models\LoyaltyProgram;
use App\Models\Nota;
use App\Models\Promotion;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class LoyaltyPromotionHttpTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $member;

    private Kiloan $kiloan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->admin = $this->userWithRole(UserRole::Admin);
        $this->member = $this->userWithRole(UserRole::Pelanggan, [
            'kategori_pelanggan' => KategoriPelanggan::Member->value,
            'member_sejak' => '2026-01-01',
        ]);
        $this->kiloan = Kiloan::query()->create(['paket' => 'Reguler 3 Hari', 'harga' => 20000]);
    }

    public function test_admin_can_save_loyalty_program_and_existing_progress_issues_rewards(): void
    {
        $this->completedNota('2026-11-01 09:00:00');
        $this->completedNota('2026-11-02 09:00:00');

        $this->actingAs($this->admin)
            ->put('/loyalitas', $this->loyaltyPayload())
            ->assertRedirect();

        $this->assertDatabaseCount('loyalty_programs', 1);
        $this->assertSame(1, LoyaltyProgram::query()->aktif()->count());

        $reward = CustomerReward::query()->where('user_id', $this->member->id)->firstOrFail();

        $this->assertSame(StatusHadiah::Tersedia, $reward->status);
        $this->assertSame(1, $reward->siklus);
    }

    public function test_loyalty_settings_page_reports_stats(): void
    {
        LoyaltyProgram::query()->create([
            'nama' => 'Loyalty Member',
            'target_transaksi' => 3,
            'tipe_hadiah' => TipeHadiah::Persen,
            'nilai_hadiah' => 10,
            'is_active' => true,
        ]);

        $this->actingAs($this->admin)
            ->get('/loyalitas')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Loyalitas/Index')
                ->where('program.target_transaksi', 3)
                ->where('stats.members', 1));
    }

    public function test_karyawan_cannot_manage_loyalty_or_promotions(): void
    {
        $karyawan = $this->userWithRole(UserRole::Karyawan);

        $this->actingAs($karyawan)->get('/loyalitas')->assertForbidden();
        $this->actingAs($karyawan)->put('/loyalitas', $this->loyaltyPayload())->assertForbidden();
        $this->actingAs($karyawan)->get('/promosi')->assertForbidden();
        $this->actingAs($karyawan)->post('/promosi', $this->promotionPayload())->assertForbidden();
    }

    public function test_admin_can_create_update_and_delete_promotions_without_history(): void
    {
        $this->actingAs($this->admin)
            ->post('/promosi', $this->promotionPayload())
            ->assertRedirect();

        $promotion = Promotion::query()->firstOrFail();
        $this->assertTrue($promotion->is_active);

        $this->actingAs($this->admin)
            ->put("/promosi/{$promotion->id}", $this->promotionPayload([
                'nama' => 'Promo Diperbarui',
                'is_active' => false,
            ]))
            ->assertRedirect();

        $promotion->refresh();
        $this->assertSame('Promo Diperbarui', $promotion->nama);
        $this->assertFalse($promotion->is_active);

        $this->actingAs($this->admin)
            ->delete("/promosi/{$promotion->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('promotions', ['id' => $promotion->id]);
    }

    public function test_deleting_promotion_returns_to_unfiltered_list(): void
    {
        $promotion = Promotion::query()->create([
            'nama' => 'Promo Hapus',
            'target_transaksi' => 1,
            'tipe_hadiah' => TipeHadiah::Nominal,
            'nilai_hadiah' => 5000,
            'is_active' => true,
        ]);

        $this->actingAs($this->admin)
            ->from('/promosi?search=Promo&status=aktif&page=2')
            ->delete("/promosi/{$promotion->id}")
            ->assertRedirect('/promosi');

        $this->assertDatabaseMissing('promotions', ['id' => $promotion->id]);
    }

    public function test_promotion_accepts_zero_value_for_free_wash_reward(): void
    {
        $this->actingAs($this->admin)
            ->post('/promosi', $this->promotionPayload([
                'tipe_hadiah' => TipeHadiah::Gratis->value,
                'nilai_hadiah' => 0,
            ]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $promotion = Promotion::query()->firstOrFail();

        $this->assertSame(TipeHadiah::Gratis, $promotion->tipe_hadiah);
        $this->assertSame(0, $promotion->nilai_hadiah);
    }

    public function test_loyalty_accepts_zero_value_for_free_wash_reward(): void
    {
        $this->actingAs($this->admin)
            ->put('/loyalitas', $this->loyaltyPayload([
                'tipe_hadiah' => TipeHadiah::Gratis->value,
                'nilai_hadiah' => 0,
            ]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame(0, LoyaltyProgram::query()->firstOrFail()->nilai_hadiah);
    }

    public function test_nominal_reward_still_requires_positive_value(): void
    {
        $this->actingAs($this->admin)
            ->post('/promosi', $this->promotionPayload([
                'tipe_hadiah' => TipeHadiah::Nominal->value,
                'nilai_hadiah' => 0,
            ]))
            ->assertSessionHasErrors('nilai_hadiah');

        $this->assertDatabaseCount('promotions', 0);
    }

    public function test_promotion_with_reward_history_cannot_be_deleted(): void
    {
        $promotion = Promotion::query()->create([
            'nama' => 'Promo Lama',
            'target_transaksi' => 1,
            'tipe_hadiah' => TipeHadiah::Nominal,
            'nilai_hadiah' => 5000,
            'is_active' => false,
        ]);

        CustomerReward::query()->create([
            'reward_key' => 'PRM-'.$promotion->id.'-'.$this->member->id.'-1',
            'user_id' => $this->member->id,
            'sumber' => 'Promosi',
            'promotion_id' => $promotion->id,
            'siklus' => 1,
            'label' => $promotion->nama,
            'tipe_hadiah' => TipeHadiah::Nominal,
            'nilai_hadiah' => 5000,
            'status' => StatusHadiah::Tersedia,
            'diperoleh_at' => now(),
        ]);

        $this->actingAs($this->admin)
            ->delete("/promosi/{$promotion->id}")
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('promotions', ['id' => $promotion->id]);
    }

    public function test_pelanggan_list_can_be_filtered_by_category_and_detail_shows_progress(): void
    {
        LoyaltyProgram::query()->create([
            'nama' => 'Loyalty Member',
            'target_transaksi' => 5,
            'tipe_hadiah' => TipeHadiah::Persen,
            'nilai_hadiah' => 10,
            'is_active' => true,
        ]);

        $this->completedNota('2026-11-01 09:00:00');

        $this->actingAs($this->admin)
            ->get('/pelanggan?kategori=Member')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('pelanggans.data', 1)
                ->where('pelanggans.data.0.id', $this->member->id)
                ->where('pelanggans.data.0.kategori_pelanggan', 'Member'));

        $this->actingAs($this->admin)
            ->get("/pelanggan/{$this->member->id}")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Pelanggan/Show')
                ->where('pelanggan.kategori_pelanggan', 'Member')
                ->where('loyalitas.loyalty.transaksi_selesai', 1)
                ->where('loyalitas.loyalty.target', 5)
                ->has('notas.data', 1));

        $this->actingAs($this->admin)
            ->get("/pelanggan/{$this->admin->id}")
            ->assertNotFound();
    }

    public function test_pelanggan_category_change_sets_member_date_and_keeps_history(): void
    {
        $this->completedNota('2026-11-01 09:00:00');

        $payload = [
            'name' => $this->member->name,
            'email' => $this->member->email,
            'password' => '',
            'password_confirmation' => '',
            'jenis_kelamin' => 'Perempuan',
            'alamat' => 'Alamat lama',
            'kategori_pelanggan' => KategoriPelanggan::Reguler->value,
        ];

        $this->actingAs($this->admin)
            ->put("/pelanggan/{$this->member->id}", $payload)
            ->assertRedirect();

        $this->member->refresh();
        $this->assertFalse($this->member->isMember());
        $this->assertNull($this->member->member_sejak);
        $this->assertSame(1, $this->member->notas()->count());

        $this->actingAs($this->admin)
            ->put("/pelanggan/{$this->member->id}", [...$payload, 'kategori_pelanggan' => KategoriPelanggan::Member->value])
            ->assertRedirect();

        $this->member->refresh();
        $this->assertTrue($this->member->isMember());
        $this->assertNotNull($this->member->member_sejak);
        $this->assertSame(1, $this->member->notas()->count());
    }

    public function test_nota_status_payment_and_cancel_actions_work(): void
    {
        $program = LoyaltyProgram::query()->create([
            'nama' => 'Loyalty Member',
            'target_transaksi' => 1,
            'tipe_hadiah' => TipeHadiah::Persen,
            'nilai_hadiah' => 10,
            'is_active' => true,
        ]);

        $nota = $this->notaRecord();

        $this->actingAs($this->admin)
            ->post("/nota/{$nota->id}/selesai")
            ->assertRedirect();

        $this->assertSame(StatusNota::Selesai, $nota->refresh()->status);
        $this->assertSame(1, CustomerReward::query()->where('loyalty_program_id', $program->id)->count());

        $this->actingAs($this->admin)
            ->patch("/nota/{$nota->id}/pembayaran", ['status_pembayaran' => StatusPembayaran::Lunas->value])
            ->assertRedirect();

        $this->assertSame(StatusPembayaran::Lunas, $nota->refresh()->status_pembayaran);

        $this->actingAs($this->admin)
            ->post("/nota/{$nota->id}/batal")
            ->assertRedirect();

        $this->assertSame(StatusNota::Dibatalkan, $nota->refresh()->status);
        $this->assertSame(
            StatusHadiah::Dibatalkan,
            CustomerReward::query()->where('loyalty_program_id', $program->id)->firstOrFail()->status,
        );
    }

    public function test_nota_form_exposes_customer_categories_and_available_rewards(): void
    {
        CustomerReward::query()->create([
            'reward_key' => 'LOY-1-'.$this->member->id.'-1',
            'user_id' => $this->member->id,
            'sumber' => 'Loyalitas',
            'siklus' => 1,
            'label' => 'Loyalty Member',
            'tipe_hadiah' => TipeHadiah::Persen,
            'nilai_hadiah' => 10,
            'status' => StatusHadiah::Tersedia,
            'diperoleh_at' => now(),
        ]);

        $this->actingAs($this->admin)
            ->get('/nota/create')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Nota/Create')
                ->where('pelanggans.0.kategori_pelanggan', 'Member')
                ->has('hadiahs', 1)
                ->where('hadiahs.0.user_id', $this->member->id)
                ->where('hadiahs.0.nilai_label', '10%'));
    }

    public function test_reward_cannot_be_combined_with_manual_percent_discount(): void
    {
        CustomerReward::query()->create([
            'reward_key' => 'LOY-9-'.$this->member->id.'-1',
            'user_id' => $this->member->id,
            'sumber' => 'Loyalitas',
            'siklus' => 1,
            'label' => 'Loyalty Member',
            'tipe_hadiah' => TipeHadiah::Persen,
            'nilai_hadiah' => 10,
            'status' => StatusHadiah::Tersedia,
            'diperoleh_at' => now(),
        ]);

        $this->actingAs($this->admin)
            ->post('/nota/kiloan', [
                'nama_pelanggan' => $this->member->name,
                'pelanggan_id' => $this->member->id,
                'kiloan_id' => $this->kiloan->id,
                'berat' => 3,
                'waktu' => '2026-11-20 10:00:00',
                'diskon' => 10,
                'customer_reward_id' => CustomerReward::query()->firstOrFail()->id,
            ])
            ->assertSessionHasErrors(['diskon']);

        $this->assertDatabaseCount('notas', 0);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function loyaltyPayload(array $overrides = []): array
    {
        return [
            'nama' => 'Loyalty Pelanggan Setia',
            'deskripsi' => 'Hadiah untuk pelanggan member.',
            'target_transaksi' => 2,
            'tipe_hadiah' => TipeHadiah::Persen->value,
            'nilai_hadiah' => 10,
            'masa_berlaku_hari' => 30,
            'is_active' => true,
            ...$overrides,
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function promotionPayload(array $overrides = []): array
    {
        return [
            'nama' => 'Beli 2 Gratis Potongan',
            'deskripsi' => 'Promosi umum.',
            'syarat' => 'Berlaku untuk semua pelanggan.',
            'target_kategori' => null,
            'target_transaksi' => 2,
            'tipe_hadiah' => TipeHadiah::Nominal->value,
            'nilai_hadiah' => 5000,
            'mulai' => '2026-01-01',
            'selesai' => '2026-12-31',
            'is_active' => true,
            ...$overrides,
        ];
    }

    private function completedNota(string $waktu): Nota
    {
        $nota = $this->notaRecord($waktu);
        $nota->update(['status' => StatusNota::Selesai]);

        return $nota;
    }

    private function notaRecord(string $waktu = '2026-11-01 09:00:00'): Nota
    {
        return Nota::query()->create([
            'no_nota' => '#HTTP'.str()->random(4),
            'jenis' => JenisNota::PaketKiloan,
            'pelanggan_id' => $this->member->id,
            'nama_pelanggan' => $this->member->name,
            'kiloan_id' => $this->kiloan->id,
            'berat' => 3,
            'harga_per_kg' => 20000,
            'biaya_layanan' => 0,
            'waktu' => $waktu,
            'total_harga' => 60000,
            'diskon' => 0,
            'jumlah' => 60000,
        ]);
    }
}

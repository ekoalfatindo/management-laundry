<?php

namespace Tests\Feature;

use App\Enums\KategoriPelanggan;
use App\Enums\StatusHadiah;
use App\Enums\StatusNota;
use App\Enums\StatusPembayaran;
use App\Enums\UserRole;
use App\Models\CustomerReward;
use App\Models\Nota;
use App\Models\User;
use Database\Seeders\NotaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seeder_builds_six_months_of_consistent_transactions(): void
    {
        $this->seed();

        $notas = Nota::query()->get();

        $this->assertGreaterThanOrEqual(60, $notas->count());
        $this->assertCount(6, $notas->map(fn (Nota $nota): string => $nota->waktu->format('Y-m'))->unique());
        $this->assertTrue(
            Carbon::parse(Nota::query()->min('waktu'))
                ->gte(now()->subMonthsNoOverflow(6)->startOfMonth()),
        );
        $this->assertSame(0, Nota::query()->where('waktu', '>', now())->count());

        foreach ([StatusNota::Selesai, StatusNota::Diproses, StatusNota::Dibatalkan] as $status) {
            $this->assertGreaterThan(0, Nota::query()->where('status', $status->value)->count(), $status->value);
        }

        foreach ([StatusPembayaran::Lunas, StatusPembayaran::BelumLunas] as $status) {
            $this->assertGreaterThan(0, Nota::query()->where('status_pembayaran', $status->value)->count(), $status->value);
        }

        $customers = User::query()->role(UserRole::Pelanggan->value)->get();

        $this->assertSame(5, $customers->where('kategori_pelanggan', KategoriPelanggan::Member)->count());
        $this->assertSame(5, $customers->where('kategori_pelanggan', KategoriPelanggan::Reguler)->count());

        foreach ([StatusHadiah::Tersedia, StatusHadiah::Digunakan, StatusHadiah::Kedaluwarsa] as $status) {
            $this->assertGreaterThan(0, CustomerReward::query()->where('status', $status->value)->count(), $status->value);
        }

        $used = CustomerReward::query()->where('status', StatusHadiah::Digunakan->value)->with('nota')->get();

        $this->assertGreaterThanOrEqual(2, $used->count());

        foreach ($used as $reward) {
            $nota = $reward->nota;

            $this->assertNotNull($nota);
            $this->assertSame($reward->user_id, $nota->pelanggan_id);
            $this->assertSame($reward->id, $nota->customer_reward_id);
            $this->assertSame(0, $nota->diskon);
            $this->assertSame($reward->nilai_terpakai, $nota->total_harga - $nota->jumlah);
            $this->assertTrue($nota->isSelesai());
        }
    }

    public function test_demo_seeder_can_be_re_run_without_duplicating_data(): void
    {
        $this->seed();

        $notaCount = Nota::query()->count();
        $rewardCount = CustomerReward::query()->count();

        $this->seed(NotaSeeder::class);

        $this->assertSame($notaCount, Nota::query()->count());
        $this->assertSame($rewardCount, CustomerReward::query()->count());
        $this->assertSame(
            CustomerReward::query()->where('status', StatusHadiah::Digunakan->value)->count(),
            Nota::query()->whereNotNull('customer_reward_id')->count(),
        );
    }
}

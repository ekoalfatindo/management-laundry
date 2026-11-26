<?php

namespace Tests\Feature;

use App\Enums\JenisNota;
use App\Enums\StatusNota;
use App\Enums\UserRole;
use App\Models\Kiloan;
use App\Models\Nota;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class AkunTest extends TestCase
{
    use RefreshDatabase;

    private User $pelanggan;

    private User $pelangganLain;

    private Kiloan $kiloan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->pelanggan = $this->userWithRole(UserRole::Pelanggan, ['name' => 'Ayu Lestari']);
        $this->pelangganLain = $this->userWithRole(UserRole::Pelanggan, ['name' => 'Budi Santoso']);
        $this->kiloan = Kiloan::query()->create(['paket' => 'Reguler 3 Hari', 'harga' => 20000]);
    }

    public function test_pelanggan_only_sees_own_notas(): void
    {
        $notaBaru = $this->notaFor($this->pelanggan, '#OWN02', 50000, now());
        $notaLama = $this->notaFor($this->pelanggan, '#OWN01', 40000, now()->subDay());
        $this->notaFor($this->pelangganLain, '#LAIN01', 60000, now()->subDays(2));

        $this->actingAs($this->pelanggan)
            ->get('/akun')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Akun/Index')
                ->has('notas.data', 2)
                ->where('notas.data.0.id', $notaBaru->id)
                ->where('notas.data.1.id', $notaLama->id)
                ->where('notas.meta.total', 2)
                ->where('ringkasan.total_nota', 2)
                ->where('ringkasan.total_biaya', 90000));
    }

    public function test_ringkasan_excludes_cancelled_notas_from_total_cost(): void
    {
        $this->notaFor($this->pelanggan, '#OWN05', 40000, now());
        $cancelled = $this->notaFor($this->pelanggan, '#OWN06', 75000, now());
        $cancelled->update(['status' => StatusNota::Dibatalkan]);

        $this->actingAs($this->pelanggan)
            ->get('/akun')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('notas.data', 2)
                ->where('ringkasan.total_nota', 2)
                ->where('ringkasan.total_biaya', 40000));
    }

    public function test_pelanggan_can_open_own_nota_detail(): void
    {
        $nota = $this->notaFor($this->pelanggan, '#OWN03', 40000, now());

        $this->actingAs($this->pelanggan)
            ->get("/akun/nota/{$nota->id}")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Akun/Show')
                ->where('nota.id', $nota->id)
                ->where('nota.no_nota', '#OWN03'));
    }

    public function test_pelanggan_cannot_open_another_customers_nota(): void
    {
        $nota = $this->notaFor($this->pelangganLain, '#LAIN02', 60000, now());

        $this->actingAs($this->pelanggan)
            ->get("/akun/nota/{$nota->id}")
            ->assertNotFound();
    }

    public function test_pelanggan_can_download_own_nota_pdf(): void
    {
        $nota = $this->notaFor($this->pelanggan, '#OWN04', 40000, now());

        $response = $this->actingAs($this->pelanggan)
            ->get("/akun/nota/{$nota->id}/pdf")
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->assertStringStartsWith('%PDF', (string) $response->getContent());
    }

    public function test_pelanggan_cannot_download_another_customers_nota_pdf(): void
    {
        $nota = $this->notaFor($this->pelangganLain, '#LAIN03', 60000, now());

        $this->actingAs($this->pelanggan)
            ->get("/akun/nota/{$nota->id}/pdf")
            ->assertNotFound();
    }

    public function test_staff_is_redirected_away_from_customer_portal(): void
    {
        $admin = $this->userWithRole(UserRole::Admin);

        $this->actingAs($admin)
            ->get('/akun')
            ->assertRedirect('/');
    }

    private function notaFor(User $pelanggan, string $noNota, int $jumlah, Carbon $waktu): Nota
    {
        return Nota::query()->create([
            'no_nota' => $noNota,
            'jenis' => JenisNota::PaketKiloan,
            'pelanggan_id' => $pelanggan->id,
            'nama_pelanggan' => $pelanggan->name,
            'kiloan_id' => $this->kiloan->id,
            'berat' => 2,
            'harga_per_kg' => 20000,
            'biaya_layanan' => 0,
            'waktu' => $waktu,
            'total_harga' => $jumlah,
            'diskon' => 0,
            'jumlah' => $jumlah,
        ]);
    }
}

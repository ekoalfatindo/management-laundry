<?php

namespace Tests\Feature;

use App\Enums\JenisNota;
use App\Enums\UserRole;
use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Nota;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_karyawan_cannot_open_user_and_karyawan_management(): void
    {
        $karyawan = $this->userWithRole(UserRole::Karyawan);

        $this->actingAs($karyawan)->get('/users')->assertForbidden();
        $this->actingAs($karyawan)->get('/karyawan')->assertForbidden();
        $this->actingAs($karyawan)->post('/users', [])->assertForbidden();
    }

    public function test_karyawan_can_open_operational_and_katalog_pages(): void
    {
        $karyawan = $this->userWithRole(UserRole::Karyawan);

        $this->actingAs($karyawan)->get('/')->assertOk();
        $this->actingAs($karyawan)->get('/nota')->assertOk();
        $this->actingAs($karyawan)->get('/nota/create')->assertOk();
        $this->actingAs($karyawan)->get('/barang')->assertOk();
        $this->actingAs($karyawan)->get('/pelanggan')->assertOk();
    }

    public function test_pelanggan_is_redirected_from_dashboard_and_forbidden_from_staff_routes(): void
    {
        $pelanggan = $this->userWithRole(UserRole::Pelanggan);

        $this->actingAs($pelanggan)->get('/')->assertRedirect('/akun');
        $this->actingAs($pelanggan)->get('/nota')->assertForbidden();
    }

    public function test_inertia_403_is_rendered_with_error_page(): void
    {
        $karyawan = $this->userWithRole(UserRole::Karyawan);
        $version = app(HandleInertiaRequests::class)->version(request());

        $this->actingAs($karyawan)
            ->withHeaders([
                'X-Inertia' => 'true',
                'X-Inertia-Version' => (string) $version,
            ])
            ->get('/users')
            ->assertStatus(403)
            ->assertJsonPath('component', 'Error')
            ->assertJsonPath('props.status', 403);
    }

    public function test_only_admin_can_delete_nota(): void
    {
        $admin = $this->userWithRole(UserRole::Admin);
        $karyawan = $this->userWithRole(UserRole::Karyawan);

        $nota = Nota::query()->create([
            'no_nota' => '#TEST01',
            'jenis' => JenisNota::PaketKiloan,
            'nama_pelanggan' => 'Ibu Sari',
            'kiloan_id' => null,
            'harga_per_kg' => 20000,
            'berat' => 2,
            'biaya_layanan' => 0,
            'waktu' => now(),
            'total_harga' => 40000,
            'diskon' => 0,
            'jumlah' => 40000,
        ]);

        $this->actingAs($karyawan)
            ->delete("/nota/{$nota->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('notas', ['id' => $nota->id]);

        $this->actingAs($admin)
            ->delete("/nota/{$nota->id}")
            ->assertRedirect('/nota');

        $this->assertDatabaseMissing('notas', ['id' => $nota->id]);
    }

    public function test_karyawan_can_manage_pelanggan(): void
    {
        $karyawan = $this->userWithRole(UserRole::Karyawan);

        $this->actingAs($karyawan)->post('/pelanggan', [
            'name' => 'Pelanggan Baru',
            'email' => 'pelanggan.baru@laundry.test',
            'password' => 'password',
            'password_confirmation' => 'password',
            'jenis_kelamin' => 'Perempuan',
            'alamat' => 'Jl. Mawar No. 1, Sukoharjo',
        ])->assertRedirect();

        $this->assertDatabaseHas('users', ['email' => 'pelanggan.baru@laundry.test']);
    }
}

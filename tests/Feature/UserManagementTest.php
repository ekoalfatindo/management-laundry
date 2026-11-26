<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->admin = $this->userWithRole(UserRole::Admin);
    }

    public function test_admin_can_create_user_with_role(): void
    {
        $this->actingAs($this->admin)
            ->post('/users', $this->userPayload(['role' => UserRole::Karyawan->value]))
            ->assertRedirect();

        $user = User::query()->where('email', 'staf.baru@laundry.test')->firstOrFail();

        $this->assertTrue($user->hasRole(UserRole::Karyawan->value));
        $this->assertTrue(Hash::check('password', $user->password));
    }

    public function test_admin_can_change_user_role(): void
    {
        $target = $this->userWithRole(UserRole::Karyawan);

        $this->actingAs($this->admin)
            ->put("/users/{$target->id}", $this->userPayload([
                'email' => $target->email,
                'password' => '',
                'password_confirmation' => '',
                'role' => UserRole::Pelanggan->value,
            ]))
            ->assertRedirect();

        $target->refresh();

        $this->assertFalse($target->hasRole(UserRole::Karyawan->value));
        $this->assertTrue($target->hasRole(UserRole::Pelanggan->value));
        $this->assertTrue(Hash::check('password', $target->password));
    }

    public function test_last_admin_cannot_be_demoted(): void
    {
        $this->actingAs($this->admin)
            ->from('/users')
            ->put("/users/{$this->admin->id}", $this->userPayload([
                'email' => $this->admin->email,
                'password' => '',
                'password_confirmation' => '',
                'role' => UserRole::Karyawan->value,
            ]))
            ->assertRedirect('/users')
            ->assertSessionHas('error', 'Admin terakhir tidak dapat diubah perannya.');

        $this->assertTrue($this->admin->fresh()->hasRole(UserRole::Admin->value));
    }

    public function test_duplicate_email_is_rejected(): void
    {
        $existing = $this->userWithRole(UserRole::Karyawan);

        $this->actingAs($this->admin)
            ->post('/users', $this->userPayload(['email' => $existing->email]))
            ->assertSessionHasErrors('email');
    }

    public function test_password_must_be_confirmed_and_minimum_length(): void
    {
        $this->actingAs($this->admin)
            ->post('/users', $this->userPayload([
                'password' => 'short',
                'password_confirmation' => 'different',
            ]))
            ->assertSessionHasErrors(['password']);
    }

    public function test_admin_cannot_delete_own_account(): void
    {
        $this->actingAs($this->admin)
            ->from('/users')
            ->delete("/users/{$this->admin->id}")
            ->assertRedirect('/users')
            ->assertSessionHas('error', 'Anda tidak dapat menghapus akun sendiri.');

        $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
    }

    public function test_deleting_users_returns_to_unfiltered_lists(): void
    {
        $karyawan = $this->userWithRole(UserRole::Karyawan);
        $pelanggan = $this->userWithRole(UserRole::Pelanggan);
        $pengguna = $this->userWithRole(UserRole::Karyawan);

        $this->actingAs($this->admin)
            ->from('/karyawan?search=Karyawan&page=2')
            ->delete("/karyawan/{$karyawan->id}")
            ->assertRedirect('/karyawan');

        $this->actingAs($this->admin)
            ->from('/pelanggan?search=Pelanggan&kategori=Member&page=2')
            ->delete("/pelanggan/{$pelanggan->id}")
            ->assertRedirect('/pelanggan');

        $this->actingAs($this->admin)
            ->from('/users?search=Karyawan&role=karyawan&page=2')
            ->delete("/users/{$pengguna->id}")
            ->assertRedirect('/users');

        $this->assertDatabaseMissing('users', ['id' => $karyawan->id]);
        $this->assertDatabaseMissing('users', ['id' => $pelanggan->id]);
        $this->assertDatabaseMissing('users', ['id' => $pengguna->id]);
    }

    public function test_karyawan_can_manage_customers_but_not_non_customers(): void
    {
        $karyawan = $this->userWithRole(UserRole::Karyawan);
        $adminTarget = $this->userWithRole(UserRole::Admin);

        $this->actingAs($karyawan)
            ->post('/pelanggan', $this->userPayload([
                'name' => 'Pelanggan Baru',
                'email' => 'pelanggan.baru@laundry.test',
            ]))
            ->assertRedirect();

        $created = User::query()->where('email', 'pelanggan.baru@laundry.test')->firstOrFail();
        $this->assertTrue($created->hasRole(UserRole::Pelanggan->value));

        $this->actingAs($karyawan)
            ->post('/karyawan', $this->userPayload([
                'name' => 'Karyawan Baru',
                'email' => 'karyawan.baru@laundry.test',
            ]))
            ->assertForbidden();

        $this->actingAs($this->admin)
            ->put("/karyawan/{$adminTarget->id}", $this->userPayload([
                'email' => $adminTarget->email,
                'password' => '',
                'password_confirmation' => '',
            ]))
            ->assertNotFound();

        $this->actingAs($this->admin)
            ->delete("/karyawan/{$adminTarget->id}")
            ->assertNotFound();
    }

    public function test_pelanggan_endpoint_only_manages_customers(): void
    {
        $adminTarget = $this->userWithRole(UserRole::Admin);

        $this->actingAs($this->admin)
            ->post('/pelanggan', $this->userPayload([
                'name' => 'Pelanggan Baru',
                'email' => 'pelanggan.baru@laundry.test',
            ]))
            ->assertRedirect();

        $this->assertTrue(
            User::query()->where('email', 'pelanggan.baru@laundry.test')->firstOrFail()
                ->hasRole(UserRole::Pelanggan->value),
        );

        $this->actingAs($this->admin)
            ->delete("/pelanggan/{$adminTarget->id}")
            ->assertNotFound();
    }

    public function test_index_supports_search_role_filter_and_pagination(): void
    {
        $this->userWithRole(UserRole::Karyawan, ['name' => 'Karyawan Satu']);
        $this->userWithRole(UserRole::Pelanggan, ['name' => 'Pelanggan Satu']);

        $this->actingAs($this->admin)
            ->get('/users?search=Pelanggan')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Users/Index')
                ->has('users.data', 1)
                ->where('users.data.0.name', 'Pelanggan Satu'));

        $this->actingAs($this->admin)
            ->get('/users?role=karyawan')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('users.data', 1)
                ->where('users.data.0.role', UserRole::Karyawan->value));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function userPayload(array $overrides = []): array
    {
        return [
            'name' => 'Staf Baru',
            'email' => 'staf.baru@laundry.test',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => UserRole::Karyawan->value,
            'jenis_kelamin' => 'Laki-Laki',
            'alamat' => 'Jl. Contoh No. 1, Sukoharjo',
            ...$overrides,
        ];
    }
}

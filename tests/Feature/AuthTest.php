<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_login_page_is_rendered(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Auth/Login'));
    }

    public function test_admin_can_login_and_reach_dashboard(): void
    {
        $admin = $this->userWithRole(UserRole::Admin);

        $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect('/');

        $this->assertAuthenticatedAs($admin);

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Dashboard')
                ->where('auth.user.id', $admin->id));
    }

    public function test_karyawan_can_login(): void
    {
        $karyawan = $this->userWithRole(UserRole::Karyawan);

        $this->post('/login', [
            'email' => $karyawan->email,
            'password' => 'password',
        ])->assertRedirect('/');

        $this->assertAuthenticatedAs($karyawan);
    }

    public function test_pelanggan_can_login_and_reach_own_portal(): void
    {
        $pelanggan = $this->userWithRole(UserRole::Pelanggan);

        $this->post('/login', [
            'email' => $pelanggan->email,
            'password' => 'password',
        ])->assertRedirect('/akun');

        $this->assertAuthenticatedAs($pelanggan);

        $this->get('/akun')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Akun/Index')
                ->where('auth.user.id', $pelanggan->id));

        $this->get('/')->assertRedirect('/akun');
    }

    public function test_authenticated_pelanggan_visiting_login_is_redirected_to_portal(): void
    {
        $pelanggan = $this->userWithRole(UserRole::Pelanggan);

        $this->actingAs($pelanggan)
            ->get('/login')
            ->assertRedirect('/akun');
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $admin = $this->userWithRole(UserRole::Admin);

        $this->from('/login')->post('/login', [
            'email' => $admin->email,
            'password' => 'kata-sandi-salah',
        ])
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['email' => 'Email atau kata sandi yang dimasukkan salah.']);

        $this->assertGuest();
    }

    public function test_login_is_rate_limited_after_five_failures(): void
    {
        $admin = $this->userWithRole(UserRole::Admin);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/login', [
                'email' => $admin->email,
                'password' => 'salah',
            ]);
        }

        $response = $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString(
            'Terlalu banyak percobaan masuk',
            (string) session('errors')?->first('email'),
        );
        $this->assertGuest();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/nota')->assertRedirect('/login');
        $this->get('/akun')->assertRedirect('/login');
    }

    public function test_user_can_logout(): void
    {
        $admin = $this->userWithRole(UserRole::Admin);

        $this->actingAs($admin)
            ->post('/logout')
            ->assertRedirect('/login');

        $this->assertGuest();
    }
}

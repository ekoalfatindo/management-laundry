<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->user = $this->userWithRole(UserRole::Admin);
    }

    public function test_forgot_password_page_is_rendered(): void
    {
        $this->get('/forgot-password')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Auth/ForgotPassword'));
    }

    public function test_reset_link_is_sent_for_registered_email(): void
    {
        Notification::fake();

        $this->post('/forgot-password', ['email' => $this->user->email])
            ->assertSessionHas('status');

        Notification::assertSentTo($this->user, ResetPassword::class);
    }

    public function test_unregistered_email_gets_the_same_message_without_notification(): void
    {
        Notification::fake();

        $this->post('/forgot-password', ['email' => 'tidak-terdaftar@laundry.test'])
            ->assertSessionHas('status');

        Notification::assertNothingSent();
    }

    public function test_reset_password_page_is_rendered_with_token(): void
    {
        $token = Password::createToken($this->user);

        $this->get('/reset-password/'.$token.'?email='.urlencode($this->user->email))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Auth/ResetPassword')
                ->where('email', $this->user->email)
                ->where('token', $token));
    }

    public function test_password_can_be_reset_and_new_password_works(): void
    {
        $token = Password::createToken($this->user);

        $this->post('/reset-password', [
            'token' => $token,
            'email' => $this->user->email,
            'password' => 'kata-sandi-baru',
            'password_confirmation' => 'kata-sandi-baru',
        ])->assertRedirect('/login');

        $this->assertTrue(Hash::check('kata-sandi-baru', $this->user->fresh()->password));
        $this->assertFalse(Hash::check('password', $this->user->fresh()->password));

        $this->post('/login', [
            'email' => $this->user->email,
            'password' => 'kata-sandi-baru',
        ])->assertRedirect('/');

        $this->assertAuthenticatedAs($this->user);
    }

    public function test_reset_token_cannot_be_reused(): void
    {
        $token = Password::createToken($this->user);

        $this->post('/reset-password', [
            'token' => $token,
            'email' => $this->user->email,
            'password' => 'kata-sandi-baru',
            'password_confirmation' => 'kata-sandi-baru',
        ])->assertRedirect('/login');

        $this->post('/reset-password', [
            'token' => $token,
            'email' => $this->user->email,
            'password' => 'kata-sandi-lain',
            'password_confirmation' => 'kata-sandi-lain',
        ])->assertSessionHasErrors('email');
    }

    public function test_invalid_token_is_rejected(): void
    {
        $this->post('/reset-password', [
            'token' => 'token-palsu',
            'email' => $this->user->email,
            'password' => 'kata-sandi-baru',
            'password_confirmation' => 'kata-sandi-baru',
        ])->assertSessionHasErrors('email');
    }

    public function test_new_password_requires_minimum_length_and_confirmation(): void
    {
        $token = Password::createToken($this->user);

        $this->post('/reset-password', [
            'token' => $token,
            'email' => $this->user->email,
            'password' => 'pendek',
            'password_confirmation' => 'beda',
        ])->assertSessionHasErrors(['password']);
    }
}

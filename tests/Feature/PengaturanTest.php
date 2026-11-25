<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Pengaturan;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class PengaturanTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->admin = $this->userWithRole(UserRole::Admin);
    }

    public function test_setup_page_is_rendered_for_admin(): void
    {
        $this->actingAs($this->admin)
            ->get('/pengaturan')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Pengaturan/Index')
                ->where('pengaturan.nama_perusahaan', config('app.name')));
    }

    public function test_company_name_can_be_updated(): void
    {
        $this->actingAs($this->admin)
            ->post('/pengaturan', ['nama_perusahaan' => 'Laundry Bersih Sejahtera'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('pengaturan', ['nama_perusahaan' => 'Laundry Bersih Sejahtera']);
    }

    public function test_logo_can_be_uploaded_and_replaced(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin)
            ->post('/pengaturan', [
                'nama_perusahaan' => 'Laundry Bersih Sejahtera',
                'logo' => UploadedFile::fake()->image('logo.png', 240, 120),
            ])
            ->assertRedirect();

        $firstPath = Pengaturan::current()->logo_path;

        $this->assertNotNull($firstPath);
        Storage::disk('public')->assertExists($firstPath);

        $this->actingAs($this->admin)
            ->post('/pengaturan', [
                'nama_perusahaan' => 'Laundry Bersih Sejahtera',
                'logo' => UploadedFile::fake()->image('logo-baru.webp', 300, 150),
            ])
            ->assertRedirect();

        $secondPath = Pengaturan::current()->logo_path;

        $this->assertNotNull($secondPath);
        $this->assertNotSame($firstPath, $secondPath);
        Storage::disk('public')->assertExists($secondPath);
        Storage::disk('public')->assertMissing($firstPath);
    }

    public function test_logo_must_be_a_supported_image(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin)
            ->post('/pengaturan', [
                'nama_perusahaan' => 'Laundry Bersih Sejahtera',
                'logo' => UploadedFile::fake()->create('logo.pdf', 120, 'application/pdf'),
            ])
            ->assertSessionHasErrors('logo');

        $this->assertDatabaseMissing('pengaturan', ['nama_perusahaan' => 'Laundry Bersih Sejahtera']);
    }

    public function test_company_name_is_required(): void
    {
        $this->actingAs($this->admin)
            ->post('/pengaturan', ['nama_perusahaan' => ''])
            ->assertSessionHasErrors('nama_perusahaan');
    }

    public function test_karyawan_cannot_access_setup(): void
    {
        $karyawan = $this->userWithRole(UserRole::Karyawan);

        $this->actingAs($karyawan)->get('/pengaturan')->assertForbidden();
        $this->actingAs($karyawan)->post('/pengaturan', ['nama_perusahaan' => 'Tidak Boleh'])->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/pengaturan')->assertRedirect('/login');
    }

    public function test_company_profile_is_shared_with_the_frontend(): void
    {
        Pengaturan::current()->update(['nama_perusahaan' => 'Laundry Test Company']);

        $this->actingAs($this->admin)
            ->get('/')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('company.nama_perusahaan', 'Laundry Test Company'));
    }
}

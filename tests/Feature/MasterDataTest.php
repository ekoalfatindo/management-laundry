<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Barang;
use App\Models\JenisLayanan;
use App\Models\Kiloan;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class MasterDataTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->admin = $this->userWithRole(UserRole::Admin);
    }

    public function test_barang_can_be_created_updated_and_deleted(): void
    {
        $this->actingAs($this->admin)
            ->post('/barang', ['name' => 'Jas', 'harga' => 25000])
            ->assertRedirect();

        $barang = Barang::query()->where('name', 'Jas')->firstOrFail();

        $this->actingAs($this->admin)
            ->put("/barang/{$barang->id}", ['name' => 'Jas Formal', 'harga' => 30000])
            ->assertRedirect();

        $this->assertDatabaseHas('barangs', ['id' => $barang->id, 'name' => 'Jas Formal', 'harga' => 30000]);

        $this->actingAs($this->admin)
            ->delete("/barang/{$barang->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('barangs', ['id' => $barang->id]);
    }

    public function test_deleting_barang_returns_to_unfiltered_list(): void
    {
        $barang = Barang::query()->create(['name' => 'Topi', 'harga' => 5000]);
        Barang::query()->create(['name' => 'Kaos', 'harga' => 7000]);

        $this->actingAs($this->admin)
            ->from('/barang?search=Topi&page=2')
            ->delete("/barang/{$barang->id}")
            ->assertRedirect('/barang');

        $this->assertDatabaseMissing('barangs', ['id' => $barang->id]);
    }

    public function test_deleting_kiloan_and_jenis_layanan_return_to_unfiltered_lists(): void
    {
        $kiloan = Kiloan::query()->create(['paket' => 'Satuan', 'harga' => 10000]);
        $layanan = JenisLayanan::query()->create(['jenis_cuci' => 'Setrika Saja', 'waktu' => '1 Hari', 'harga' => 8000]);

        $this->actingAs($this->admin)
            ->from('/kiloan?search=Satuan&page=2')
            ->delete("/kiloan/{$kiloan->id}")
            ->assertRedirect('/kiloan');

        $this->actingAs($this->admin)
            ->from('/jenis-layanan?search=Setrika&page=2')
            ->delete("/jenis-layanan/{$layanan->id}")
            ->assertRedirect('/jenis-layanan');

        $this->assertDatabaseMissing('kiloans', ['id' => $kiloan->id]);
        $this->assertDatabaseMissing('jenis_layanans', ['id' => $layanan->id]);
    }

    public function test_flash_messages_are_shared_as_inertia_props_after_redirect(): void
    {
        $this->actingAs($this->admin)
            ->post('/barang', ['name' => 'Jas', 'harga' => 25000])
            ->assertSessionHas('success', 'Barang berhasil ditambahkan.');

        $this->actingAs($this->admin)
            ->get('/barang')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Barang/Index')
                ->where('flash.success', 'Barang berhasil ditambahkan.'));
    }

    public function test_barang_name_must_be_unique_but_may_keep_its_own_name(): void
    {
        Barang::query()->create(['name' => 'Kemeja', 'harga' => 10000]);
        $barang = Barang::query()->create(['name' => 'Rok', 'harga' => 5000]);

        $this->actingAs($this->admin)
            ->post('/barang', ['name' => 'Kemeja', 'harga' => 12000])
            ->assertSessionHasErrors('name');

        $this->actingAs($this->admin)
            ->put("/barang/{$barang->id}", ['name' => 'Rok', 'harga' => 6000])
            ->assertSessionHasNoErrors();
    }

    public function test_barang_validation_rejects_negative_price(): void
    {
        $this->actingAs($this->admin)
            ->post('/barang', ['name' => 'Selimut', 'harga' => -1])
            ->assertSessionHasErrors('harga');
    }

    public function test_kiloan_can_be_managed(): void
    {
        $this->actingAs($this->admin)
            ->post('/kiloan', ['paket' => 'Same Day', 'harga' => 25000, 'description' => 'Cuci kering + setrika'])
            ->assertRedirect();

        $kiloan = Kiloan::query()->where('paket', 'Same Day')->firstOrFail();

        $this->actingAs($this->admin)
            ->put("/kiloan/{$kiloan->id}", ['paket' => 'Same Day Express', 'harga' => 28000, 'description' => null])
            ->assertRedirect();

        $this->assertDatabaseHas('kiloans', ['id' => $kiloan->id, 'paket' => 'Same Day Express', 'harga' => 28000]);

        $this->actingAs($this->admin)->delete("/kiloan/{$kiloan->id}")->assertRedirect();

        $this->assertDatabaseMissing('kiloans', ['id' => $kiloan->id]);
    }

    public function test_kiloan_requires_paket_and_non_negative_price(): void
    {
        $this->actingAs($this->admin)
            ->post('/kiloan', ['paket' => '', 'harga' => -5])
            ->assertSessionHasErrors(['paket', 'harga']);
    }

    public function test_jenis_layanan_can_be_managed_and_is_unique(): void
    {
        $this->actingAs($this->admin)
            ->post('/jenis-layanan', ['jenis_cuci' => 'Cepat', 'waktu' => '60 Menit', 'harga' => 15000])
            ->assertRedirect();

        $layanan = JenisLayanan::query()->where('jenis_cuci', 'Cepat')->firstOrFail();

        $this->actingAs($this->admin)
            ->post('/jenis-layanan', ['jenis_cuci' => 'Cepat', 'waktu' => '30 Menit', 'harga' => 20000])
            ->assertSessionHasErrors('jenis_cuci');

        $this->actingAs($this->admin)
            ->put("/jenis-layanan/{$layanan->id}", ['jenis_cuci' => 'Cepat', 'waktu' => '45 Menit', 'harga' => 18000])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('jenis_layanans', ['id' => $layanan->id, 'waktu' => '45 Menit', 'harga' => 18000]);
    }

    public function test_view_only_user_can_read_katalog_but_cannot_mutate_it(): void
    {
        $viewer = $this->userWithRole(UserRole::Pelanggan);
        $viewer->givePermissionTo('katalog.view');

        $kiloan = Kiloan::query()->create(['paket' => 'Reguler', 'harga' => 20000]);

        $this->actingAs($viewer)->get('/barang')->assertOk();
        $this->actingAs($viewer)->get('/kiloan')->assertOk();

        $this->actingAs($viewer)
            ->post('/barang', ['name' => 'Tidak Boleh', 'harga' => 1000])
            ->assertForbidden();

        $this->actingAs($viewer)
            ->delete("/kiloan/{$kiloan->id}")
            ->assertForbidden();
    }

    public function test_index_supports_search_and_per_page(): void
    {
        Barang::query()->create(['name' => 'Selimut', 'harga' => 15000]);
        Barang::query()->create(['name' => 'Kemeja', 'harga' => 10000]);

        $this->actingAs($this->admin)
            ->get('/barang?search=Selimut&per_page=25')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Barang/Index')
                ->has('barangs.data', 1)
                ->where('barangs.data.0.name', 'Selimut')
                ->where('filters.per_page', 25));
    }

    public function test_invalid_per_page_is_rejected(): void
    {
        $this->actingAs($this->admin)
            ->get('/barang?per_page=7')
            ->assertSessionHasErrors('per_page');
    }
}

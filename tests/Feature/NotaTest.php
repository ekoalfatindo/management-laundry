<?php

namespace Tests\Feature;

use App\Enums\JenisNota;
use App\Enums\StatusNota;
use App\Enums\UserRole;
use App\Models\Barang;
use App\Models\JenisLayanan;
use App\Models\Kiloan;
use App\Models\Nota;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;
use ZipArchive;

class NotaTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Kiloan $kiloan;

    private Kiloan $kiloanPremium;

    private JenisLayanan $layanan;

    private Barang $kemeja;

    private Barang $selimut;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->admin = $this->userWithRole(UserRole::Admin);

        $this->kiloan = Kiloan::query()->create(['paket' => 'Reguler 3 Hari', 'harga' => 20000]);
        $this->kiloanPremium = Kiloan::query()->create(['paket' => 'Express 4 Jam', 'harga' => 30000]);
        $this->layanan = JenisLayanan::query()->create(['jenis_cuci' => 'Cepat', 'waktu' => '60 Menit', 'harga' => 15000]);
        $this->kemeja = Barang::query()->create(['name' => 'Kemeja', 'harga' => 10000]);
        $this->selimut = Barang::query()->create(['name' => 'Selimut', 'harga' => 15000]);
    }

    public function test_kiloan_nota_is_created_with_server_side_pricing(): void
    {
        $this->actingAs($this->admin)
            ->post('/nota/kiloan', $this->kiloanPayload())
            ->assertRedirect();

        $nota = Nota::query()->firstOrFail();

        $this->assertStringStartsWith('#', $nota->no_nota);
        $this->assertSame(JenisNota::PaketKiloan, $nota->jenis);
        $this->assertSame(20000, $nota->harga_per_kg);
        $this->assertSame('3.50', $nota->berat);
        $this->assertSame(70000, $nota->total_harga);
        $this->assertSame(10, $nota->diskon);
        $this->assertSame(63000, $nota->jumlah);
    }

    public function test_satuan_nota_sums_items_and_service_fee(): void
    {
        $this->actingAs($this->admin)
            ->post('/nota/satuan', $this->satuanPayload())
            ->assertRedirect();

        $nota = Nota::query()->with('barangs')->firstOrFail();

        $this->assertSame(JenisNota::BarangSatuan, $nota->jenis);
        $this->assertSame(15000, $nota->biaya_layanan);
        $this->assertSame(50000, $nota->total_harga);
        $this->assertSame(45000, $nota->jumlah);
        $this->assertCount(2, $nota->barangs);
        $this->assertDatabaseHas('nota_barangs', [
            'nota_id' => $nota->id,
            'barang' => 'Kemeja',
            'harga' => 10000,
            'jumlah_barang' => 2,
            'total_harga' => 20000,
        ]);
    }

    public function test_kiloan_validation_rejects_invalid_payload(): void
    {
        $this->actingAs($this->admin)
            ->post('/nota/kiloan', $this->kiloanPayload([
                'kiloan_id' => 999999,
                'berat' => 0,
                'diskon' => 101,
            ]))
            ->assertSessionHasErrors(['kiloan_id', 'berat', 'diskon']);

        $this->assertDatabaseCount('notas', 0);
    }

    public function test_kiloan_nota_rejects_staff_account_as_customer(): void
    {
        $karyawan = $this->userWithRole(UserRole::Karyawan);

        $this->actingAs($this->admin)
            ->post('/nota/kiloan', $this->kiloanPayload(['pelanggan_id' => $karyawan->id]))
            ->assertSessionHasErrors('pelanggan_id');

        $this->assertDatabaseCount('notas', 0);
    }

    public function test_satuan_validation_requires_at_least_one_item(): void
    {
        $this->actingAs($this->admin)
            ->post('/nota/satuan', $this->satuanPayload(['barangs' => []]))
            ->assertSessionHasErrors(['barangs']);
    }

    public function test_kiloan_nota_can_be_updated_and_refreshes_price_snapshot(): void
    {
        $this->actingAs($this->admin)->post('/nota/kiloan', $this->kiloanPayload());
        $nota = Nota::query()->firstOrFail();
        $originalNumber = $nota->no_nota;

        $this->actingAs($this->admin)
            ->put("/nota/{$nota->id}", $this->kiloanPayload([
                'kiloan_id' => $this->kiloanPremium->id,
                'berat' => 4,
                'diskon' => 0,
                'nama_pelanggan' => 'Ibu Sari Diperbarui',
            ]))
            ->assertRedirect("/nota/{$nota->id}");

        $nota->refresh();

        $this->assertSame($originalNumber, $nota->no_nota);
        $this->assertSame(30000, $nota->harga_per_kg);
        $this->assertSame(120000, $nota->total_harga);
        $this->assertSame(120000, $nota->jumlah);
        $this->assertSame('Ibu Sari Diperbarui', $nota->nama_pelanggan);
    }

    public function test_nota_update_rejects_staff_account_as_customer(): void
    {
        $this->actingAs($this->admin)->post('/nota/kiloan', $this->kiloanPayload());
        $nota = Nota::query()->firstOrFail();
        $karyawan = $this->userWithRole(UserRole::Karyawan);

        $this->actingAs($this->admin)
            ->from("/nota/{$nota->id}/edit")
            ->put("/nota/{$nota->id}", $this->kiloanPayload([
                'pelanggan_id' => $karyawan->id,
                'nama_pelanggan' => 'Akun Staf',
            ]))
            ->assertSessionHasErrors('pelanggan_id');

        $nota->refresh();

        $this->assertNull($nota->pelanggan_id);
        $this->assertSame('Ibu Sari', $nota->nama_pelanggan);
    }

    public function test_cancelled_nota_cannot_be_edited(): void
    {
        $this->actingAs($this->admin)->post('/nota/kiloan', $this->kiloanPayload());
        $nota = Nota::query()->firstOrFail();
        $nota->update(['status' => StatusNota::Dibatalkan]);

        $this->actingAs($this->admin)
            ->from("/nota/{$nota->id}")
            ->put("/nota/{$nota->id}", $this->kiloanPayload(['berat' => 9, 'diskon' => 0]))
            ->assertRedirect("/nota/{$nota->id}")
            ->assertSessionHas('error', 'Nota yang dibatalkan tidak dapat diubah.');

        $nota->refresh();

        $this->assertSame('3.50', $nota->berat);
        $this->assertSame(63000, $nota->jumlah);
        $this->assertSame(StatusNota::Dibatalkan, $nota->status);
    }

    public function test_cancelled_nota_edit_page_is_blocked(): void
    {
        $this->actingAs($this->admin)->post('/nota/kiloan', $this->kiloanPayload());
        $nota = Nota::query()->firstOrFail();
        $nota->update(['status' => StatusNota::Dibatalkan]);

        $this->actingAs($this->admin)
            ->from('/nota')
            ->get("/nota/{$nota->id}/edit")
            ->assertRedirect('/nota')
            ->assertSessionHas('error', 'Nota yang dibatalkan tidak dapat diubah.');
    }

    public function test_satuan_nota_update_replaces_item_rows(): void
    {
        $this->actingAs($this->admin)->post('/nota/satuan', $this->satuanPayload());
        $nota = Nota::query()->firstOrFail();

        $this->actingAs($this->admin)
            ->put("/nota/{$nota->id}", $this->satuanPayload([
                'diskon' => 0,
                'barangs' => [
                    ['barang_id' => $this->selimut->id, 'jumlah_barang' => 3],
                ],
            ]))
            ->assertRedirect("/nota/{$nota->id}");

        $nota->refresh();

        $this->assertSame(60000, $nota->total_harga);
        $this->assertSame(60000, $nota->jumlah);
        $this->assertSame(1, $nota->barangs()->count());
        $this->assertDatabaseHas('nota_barangs', ['nota_id' => $nota->id, 'barang' => 'Selimut']);
        $this->assertDatabaseMissing('nota_barangs', ['nota_id' => $nota->id, 'barang' => 'Kemeja']);
    }

    public function test_deleting_satuan_nota_removes_its_items(): void
    {
        $this->actingAs($this->admin)->post('/nota/satuan', $this->satuanPayload());
        $nota = Nota::query()->firstOrFail();

        $this->actingAs($this->admin)
            ->delete("/nota/{$nota->id}")
            ->assertRedirect('/nota');

        $this->assertDatabaseMissing('notas', ['id' => $nota->id]);
        $this->assertDatabaseMissing('nota_barangs', ['nota_id' => $nota->id]);
    }

    public function test_kiloan_deletion_keeps_nota_snapshot(): void
    {
        $this->actingAs($this->admin)->post('/nota/kiloan', $this->kiloanPayload());
        $nota = Nota::query()->firstOrFail();

        $this->kiloan->delete();
        $nota->refresh();

        $this->assertNull($nota->kiloan_id);
        $this->assertSame(20000, $nota->harga_per_kg);
        $this->assertSame(63000, $nota->jumlah);
    }

    public function test_barang_deletion_keeps_item_snapshot(): void
    {
        $this->actingAs($this->admin)->post('/nota/satuan', $this->satuanPayload());
        $nota = Nota::query()->firstOrFail();

        $this->kemeja->delete();

        $this->assertDatabaseHas('nota_barangs', [
            'nota_id' => $nota->id,
            'barang_id' => null,
            'barang' => 'Kemeja',
            'harga' => 10000,
        ]);
    }

    public function test_index_filters_by_search_type_and_date(): void
    {
        $this->createNota('#CARI01', JenisNota::PaketKiloan, '2026-06-10 09:00:00', 'Ibu Sari');
        $this->createNota('#CARI02', JenisNota::BarangSatuan, '2026-07-10 09:00:00', 'Bapak Andi');

        $this->actingAs($this->admin)
            ->get('/nota?search=CARI01')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Nota/Index')
                ->has('notas.data', 1)
                ->where('notas.data.0.no_nota', '#CARI01'));

        $this->actingAs($this->admin)
            ->get('/nota?jenis='.urlencode(JenisNota::BarangSatuan->value))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('notas.data', 1));

        $this->actingAs($this->admin)
            ->get('/nota?dari=2026-07-01&sampai=2026-07-31')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('notas.data', 1)
                ->where('notas.data.0.no_nota', '#CARI02'));
    }

    public function test_index_paginates_and_reports_meta(): void
    {
        for ($index = 1; $index <= 12; $index++) {
            $this->createNota('#PAGE'.str_pad((string) $index, 2, '0', STR_PAD_LEFT), JenisNota::PaketKiloan, '2026-05-01 08:00:00', 'Pelanggan '.$index);
        }

        $this->actingAs($this->admin)
            ->get('/nota')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('notas.data', 10)
                ->where('notas.meta.total', 12)
                ->where('notas.meta.last_page', 2));
    }

    public function test_export_downloads_tidy_xlsx_workbook(): void
    {
        $this->createNota('#EXPORT1', JenisNota::PaketKiloan, '2026-08-01 10:00:00', '=SUM(A1:A2)');

        $response = $this->actingAs($this->admin)->get('/nota/export');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->assertDownload();

        $disposition = (string) $response->headers->get('content-disposition');
        $this->assertStringContainsString('.xlsx', $disposition);

        $file = $response->baseResponse->getFile()->getPathname();
        $this->assertFileExists($file);

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($file) === true);

        $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        $styles = $zip->getFromName('xl/styles.xml');
        $workbook = $zip->getFromName('xl/workbook.xml');

        $this->assertIsString($sheet);
        $this->assertIsString($styles);
        $this->assertIsString($workbook);
        $this->assertIsString($zip->getFromName('[Content_Types].xml'));

        $this->assertStringContainsString('Laporan Nota Transaksi', $sheet);
        $this->assertStringContainsString('Nama Pelanggan', $sheet);
        $this->assertStringContainsString('#EXPORT1', $sheet);
        $this->assertStringContainsString('=SUM(A1:A2)', $sheet);
        $this->assertStringContainsString('t="inlineStr"', $sheet);
        $this->assertStringContainsString('autoFilter', $sheet);
        $this->assertStringContainsString('state="frozen"', $sheet);
        $this->assertStringContainsString('TOTAL (1 nota)', $sheet);
        $this->assertStringContainsString('&quot;Rp&quot; #,##0', $styles);
        $this->assertStringContainsString('dd/mm/yyyy hh:mm', $styles);
        $this->assertStringContainsString('Laporan Nota', $workbook);

        $zip->close();
        @unlink($file);
    }

    public function test_export_requires_permission(): void
    {
        $karyawan = $this->userWithRole(UserRole::Karyawan);
        $this->assertTrue($karyawan->can('nota.export'));

        $pelanggan = $this->userWithRole(UserRole::Pelanggan);
        $this->actingAs($pelanggan)->get('/nota/export')->assertForbidden();
    }

    public function test_show_page_returns_nota_resource(): void
    {
        $this->actingAs($this->admin)->post('/nota/satuan', $this->satuanPayload());
        $nota = Nota::query()->firstOrFail();

        $this->actingAs($this->admin)
            ->get("/nota/{$nota->id}")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Nota/Show')
                ->where('nota.id', $nota->id)
                ->where('nota.jumlah', 45000)
                ->where('nota.terbilang', 'Empat puluh lima ribu rupiah')
                ->has('nota.barangs', 2));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function kiloanPayload(array $overrides = []): array
    {
        return [
            'nama_pelanggan' => 'Ibu Sari',
            'pelanggan_id' => null,
            'kiloan_id' => $this->kiloan->id,
            'berat' => 3.5,
            'waktu' => '2026-11-20 10:00:00',
            'diskon' => 10,
            ...$overrides,
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function satuanPayload(array $overrides = []): array
    {
        return [
            'nama_pelanggan' => 'Bapak Andi',
            'pelanggan_id' => null,
            'jenis_layanan_id' => $this->layanan->id,
            'waktu' => '2026-11-21 14:30:00',
            'diskon' => 10,
            'barangs' => [
                ['barang_id' => $this->kemeja->id, 'jumlah_barang' => 2],
                ['barang_id' => $this->selimut->id, 'jumlah_barang' => 1],
            ],
            ...$overrides,
        ];
    }

    private function createNota(string $nomor, JenisNota $jenis, string $waktu, string $nama): Nota
    {
        return Nota::query()->create([
            'no_nota' => $nomor,
            'jenis' => $jenis,
            'nama_pelanggan' => $nama,
            'kiloan_id' => $jenis === JenisNota::PaketKiloan ? $this->kiloan->id : null,
            'jenis_layanan_id' => $jenis === JenisNota::BarangSatuan ? $this->layanan->id : null,
            'berat' => $jenis === JenisNota::PaketKiloan ? 2 : null,
            'harga_per_kg' => $jenis === JenisNota::PaketKiloan ? 20000 : null,
            'biaya_layanan' => $jenis === JenisNota::BarangSatuan ? 15000 : 0,
            'waktu' => $waktu,
            'total_harga' => $jenis === JenisNota::PaketKiloan ? 40000 : 15000,
            'diskon' => 0,
            'jumlah' => $jenis === JenisNota::PaketKiloan ? 40000 : 15000,
        ]);
    }
}

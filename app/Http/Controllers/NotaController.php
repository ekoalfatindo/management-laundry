<?php

namespace App\Http\Controllers;

use App\DTOs\NotaData;
use App\Enums\JenisNota;
use App\Http\Requests\StoreNotaKiloanRequest;
use App\Http\Requests\StoreNotaSatuanRequest;
use App\Http\Requests\UpdateNotaRequest;
use App\Http\Resources\NotaResource;
use App\Models\Barang;
use App\Models\JenisLayanan;
use App\Models\Kiloan;
use App\Models\Nota;
use App\Models\User;
use App\Services\NotaService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NotaController extends Controller
{
    public function __construct(private readonly NotaService $notas) {}

    public function index(Request $request): Response
    {
        $filters = $this->filters($request);
        $perPage = (int) ($filters['per_page'] ?? 10);

        $notas = $this->applyFilters(
            Nota::query()->with(['kiloan', 'jenisLayanan']),
            $filters,
        )
            ->latest('waktu')
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('Nota/Index', [
            'notas' => $this->paginated(
                $notas,
                NotaResource::collection($notas->getCollection())->resolve(),
            ),
            'filters' => [
                'search' => $filters['search'] ?? '',
                'jenis' => $filters['jenis'] ?? '',
                'dari' => $filters['dari'] ?? '',
                'sampai' => $filters['sampai'] ?? '',
                'per_page' => $perPage,
            ],
            'jenisOptions' => collect(JenisNota::cases())
                ->map(fn (JenisNota $jenis): array => ['value' => $jenis->value, 'label' => $jenis->label()])
                ->all(),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Nota::class);

        return Inertia::render('Nota/Create', $this->formOptions() + [
            'defaultWaktu' => now()->format('Y-m-d\TH:i'),
            'defaultJenis' => $request->query('jenis') === 'satuan' ? 'satuan' : 'kiloan',
        ]);
    }

    public function storeKiloan(StoreNotaKiloanRequest $request): RedirectResponse
    {
        $nota = $this->notas->create(
            NotaData::fromArray($request->validated(), JenisNota::PaketKiloan),
        );

        return redirect()
            ->route('nota.show', $nota)
            ->with('success', 'Nota kiloan berhasil dibuat.');
    }

    public function storeSatuan(StoreNotaSatuanRequest $request): RedirectResponse
    {
        $nota = $this->notas->create(
            NotaData::fromArray($request->validated(), JenisNota::BarangSatuan),
        );

        return redirect()
            ->route('nota.show', $nota)
            ->with('success', 'Nota barang satuan berhasil dibuat.');
    }

    public function show(Nota $nota): Response
    {
        $nota->load(['kiloan', 'jenisLayanan', 'barangs', 'pelanggan']);

        return Inertia::render('Nota/Show', [
            'nota' => (new NotaResource($nota))->resolve(),
        ]);
    }

    public function edit(Nota $nota): Response
    {
        $this->authorize('update', $nota);

        $nota->load(['barangs', 'kiloan', 'jenisLayanan']);

        return Inertia::render('Nota/Edit', $this->formOptions() + [
            'nota' => (new NotaResource($nota))->resolve(),
            'defaultWaktu' => $nota->waktu->format('Y-m-d\TH:i'),
        ]);
    }

    public function update(UpdateNotaRequest $request, Nota $nota): RedirectResponse
    {
        $this->notas->update(
            $nota,
            NotaData::fromArray($request->validated(), $nota->jenis),
        );

        return redirect()
            ->route('nota.show', $nota)
            ->with('success', 'Nota berhasil diperbarui.');
    }

    public function destroy(Nota $nota): RedirectResponse
    {
        $this->authorize('delete', $nota);

        $this->notas->delete($nota);

        return redirect()
            ->route('nota.index')
            ->with('success', 'Nota berhasil dihapus.');
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $this->filters($request);

        $query = $this->applyFilters(
            Nota::query()->with(['kiloan', 'jenisLayanan']),
            $filters,
        )->orderBy('waktu')->orderBy('id');

        $filename = 'laporan-nota-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($query): void {
            $handle = fopen('php://output', 'w');

            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'No Nota',
                'Jenis',
                'Nama Pelanggan',
                'Layanan',
                'Waktu',
                'Berat (kg)',
                'Harga per kg',
                'Biaya Layanan',
                'Total Harga',
                'Diskon (%)',
                'Jumlah',
            ]);

            $query->chunk(200, function ($notas) use ($handle): void {
                foreach ($notas as $nota) {
                    $layanan = $nota->jenis->isKiloan()
                        ? ($nota->kiloan?->paket ?? '-')
                        : ($nota->jenisLayanan?->jenis_cuci ?? '-');

                    fputcsv($handle, [
                        $nota->no_nota,
                        $nota->jenis->value,
                        $this->safeCsv($nota->nama_pelanggan),
                        $this->safeCsv($layanan),
                        $nota->waktu?->format('d/m/Y H:i'),
                        $nota->berat !== null ? (float) $nota->berat : '',
                        $nota->harga_per_kg ?? '',
                        $nota->biaya_layanan,
                        $nota->total_harga,
                        $nota->diskon,
                        $nota->jumlah,
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function filters(Request $request): array
    {
        return $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'jenis' => ['nullable', Rule::in(JenisNota::values())],
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date', 'after_or_equal:dari'],
            'per_page' => ['nullable', 'integer', 'in:10,25,50,100'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('no_nota', 'like', "%{$search}%")
                        ->orWhere('nama_pelanggan', 'like', "%{$search}%");
                });
            })
            ->when($filters['jenis'] ?? null, fn (Builder $query, string $jenis) => $query->where('jenis', $jenis))
            ->when($filters['dari'] ?? null, fn (Builder $query, string $dari) => $query->where('waktu', '>=', Carbon::parse($dari)->startOfDay()))
            ->when($filters['sampai'] ?? null, fn (Builder $query, string $sampai) => $query->where('waktu', '<=', Carbon::parse($sampai)->endOfDay()));
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'barangs' => Barang::query()
                ->orderBy('name')
                ->get()
                ->map(fn (Barang $barang): array => [
                    'id' => $barang->id,
                    'name' => $barang->name,
                    'harga' => $barang->harga,
                ])
                ->all(),
            'kiloans' => Kiloan::query()
                ->orderBy('paket')
                ->get()
                ->map(fn (Kiloan $kiloan): array => [
                    'id' => $kiloan->id,
                    'paket' => $kiloan->paket,
                    'harga' => $kiloan->harga,
                    'description' => $kiloan->description,
                ])
                ->all(),
            'jenisLayanans' => JenisLayanan::query()
                ->orderBy('jenis_cuci')
                ->get()
                ->map(fn (JenisLayanan $jenisLayanan): array => [
                    'id' => $jenisLayanan->id,
                    'jenis_cuci' => $jenisLayanan->jenis_cuci,
                    'waktu' => $jenisLayanan->waktu,
                    'harga' => $jenisLayanan->harga,
                ])
                ->all(),
            'pelanggans' => User::query()
                ->role('pelanggan')
                ->orderBy('name')
                ->get()
                ->map(fn (User $pelanggan): array => [
                    'id' => $pelanggan->id,
                    'name' => $pelanggan->name,
                ])
                ->all(),
        ];
    }

    private function safeCsv(?string $value): string
    {
        $value = (string) $value;

        return preg_match('/^[=+\-@]/', $value) === 1 ? "'".$value : $value;
    }
}

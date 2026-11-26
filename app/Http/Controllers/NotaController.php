<?php

namespace App\Http\Controllers;

use App\DTOs\NotaData;
use App\Enums\JenisNota;
use App\Enums\StatusNota;
use App\Enums\StatusPembayaran;
use App\Exceptions\DomainException;
use App\Http\Requests\StoreNotaKiloanRequest;
use App\Http\Requests\StoreNotaSatuanRequest;
use App\Http\Requests\UpdateNotaRequest;
use App\Http\Resources\NotaResource;
use App\Models\Barang;
use App\Models\CustomerReward;
use App\Models\JenisLayanan;
use App\Models\Kiloan;
use App\Models\Nota;
use App\Models\Pengaturan;
use App\Models\User;
use App\Services\NotaService;
use App\Services\RewardService;
use App\Support\NotaExcelExport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class NotaController extends Controller
{
    public function __construct(
        private readonly NotaService $notas,
        private readonly RewardService $rewards,
    ) {}

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
                'status' => $filters['status'] ?? '',
                'status_pembayaran' => $filters['status_pembayaran'] ?? '',
                'dari' => $filters['dari'] ?? '',
                'sampai' => $filters['sampai'] ?? '',
                'per_page' => $perPage,
            ],
            'jenisOptions' => collect(JenisNota::cases())
                ->map(fn (JenisNota $jenis): array => ['value' => $jenis->value, 'label' => $jenis->label()])
                ->all(),
            'statusOptions' => collect(StatusNota::cases())
                ->map(fn (StatusNota $status): array => ['value' => $status->value, 'label' => $status->label()])
                ->all(),
            'statusPembayaranOptions' => collect(StatusPembayaran::cases())
                ->map(fn (StatusPembayaran $status): array => ['value' => $status->value, 'label' => $status->label()])
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
        $nota->load(['kiloan', 'jenisLayanan', 'barangs', 'pelanggan', 'reward']);

        return Inertia::render('Nota/Show', [
            'nota' => (new NotaResource($nota))->resolve(),
        ]);
    }

    public function edit(Nota $nota): Response
    {
        $this->authorize('update', $nota);

        if ($nota->isDibatalkan()) {
            throw new DomainException('Nota yang dibatalkan tidak dapat diubah.');
        }

        $nota->load(['barangs', 'kiloan', 'jenisLayanan', 'reward']);

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

    public function complete(Nota $nota): RedirectResponse
    {
        $this->authorize('update', $nota);

        $this->notas->complete($nota);

        return back()->with('success', 'Nota ditandai selesai. Hadiah otomatis dihitung ulang.');
    }

    public function cancel(Nota $nota): RedirectResponse
    {
        $this->authorize('update', $nota);

        $this->notas->cancel($nota);

        return back()->with('success', 'Nota berhasil dibatalkan.');
    }

    public function setPembayaran(Request $request, Nota $nota): RedirectResponse
    {
        $this->authorize('update', $nota);

        $data = $request->validate([
            'status_pembayaran' => ['required', Rule::in(StatusPembayaran::values())],
        ]);

        $this->notas->setPembayaran($nota, StatusPembayaran::from($data['status_pembayaran']));

        return back()->with('success', 'Status pembayaran berhasil diperbarui.');
    }

    public function export(Request $request): BinaryFileResponse
    {
        $filters = $this->filters($request);

        $query = $this->applyFilters(
            Nota::query()->with(['kiloan', 'jenisLayanan']),
            $filters,
        )->orderBy('waktu')->orderBy('id');

        $generatedAt = now();

        $path = (new NotaExcelExport(
            $query,
            $generatedAt,
            $this->filterSummary($filters),
            Pengaturan::current()->nama_perusahaan,
        ))->create();

        return response()->download(
            $path,
            'laporan-nota-'.$generatedAt->format('Ymd-His').'.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        )->deleteFileAfterSend(true);
    }

    /**
     * @return array<string, mixed>
     */
    private function filters(Request $request): array
    {
        return $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'jenis' => ['nullable', Rule::in(JenisNota::values())],
            'status' => ['nullable', Rule::in(StatusNota::values())],
            'status_pembayaran' => ['nullable', Rule::in(StatusPembayaran::values())],
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
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['status_pembayaran'] ?? null, fn (Builder $query, string $status) => $query->where('status_pembayaran', $status))
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
                    'kategori_pelanggan' => $pelanggan->kategori_pelanggan?->value,
                ])
                ->all(),
            'hadiahs' => CustomerReward::query()
                ->tersedia()
                ->with('user:id,name')
                ->orderByRaw('kedaluwarsa_at IS NULL')
                ->orderBy('kedaluwarsa_at')
                ->orderBy('id')
                ->get()
                ->map(fn (CustomerReward $reward): array => [
                    'id' => $reward->id,
                    'user_id' => $reward->user_id,
                    'user_name' => $reward->user?->name,
                    'label' => $reward->label,
                    'tipe_hadiah' => $reward->tipe_hadiah->value,
                    'nilai_hadiah' => $reward->nilai_hadiah,
                    'nilai_label' => $this->rewards->valueLabel($reward->tipe_hadiah, $reward->nilai_hadiah),
                    'kedaluwarsa_at' => $reward->kedaluwarsa_at?->toIso8601String(),
                ])
                ->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function filterSummary(array $filters): string
    {
        $parts = [];

        if (! empty($filters['search'])) {
            $parts[] = 'Pencarian: "'.$filters['search'].'"';
        }

        if (! empty($filters['jenis'])) {
            $parts[] = 'Jenis: '.$filters['jenis'];
        }

        if (! empty($filters['status'])) {
            $parts[] = 'Status: '.$filters['status'];
        }

        if (! empty($filters['status_pembayaran'])) {
            $parts[] = 'Pembayaran: '.$filters['status_pembayaran'];
        }

        if (! empty($filters['dari']) || ! empty($filters['sampai'])) {
            $dari = ! empty($filters['dari'])
                ? Carbon::parse($filters['dari'])->format('d/m/Y')
                : 'awal';
            $sampai = ! empty($filters['sampai'])
                ? Carbon::parse($filters['sampai'])->format('d/m/Y')
                : 'akhir';

            $parts[] = "Periode: {$dari} – {$sampai}";
        }

        return $parts === [] ? 'Semua data' : implode(' · ', $parts);
    }
}

<?php

namespace App\Http\Controllers;

use App\Enums\KategoriPelanggan;
use App\Enums\StatusHadiah;
use App\Enums\TipeHadiah;
use App\Exceptions\DomainException;
use App\Http\Requests\StorePromotionRequest;
use App\Http\Requests\UpdatePromotionRequest;
use App\Models\Promotion;
use App\Services\RewardService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PromotionController extends Controller
{
    public function __construct(private readonly RewardService $rewards) {}

    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['aktif', 'nonaktif'])],
            'per_page' => ['nullable', 'integer', 'in:10,25,50,100'],
        ]);

        $perPage = (int) ($filters['per_page'] ?? 10);

        $promotions = Promotion::query()
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(fn (Builder $query) => $query
                    ->where('nama', 'like', "%{$search}%")
                    ->orWhere('deskripsi', 'like', "%{$search}%"));
            })
            ->when(($filters['status'] ?? null) === 'aktif', fn (Builder $query) => $query->aktif())
            ->when(($filters['status'] ?? null) === 'nonaktif', fn (Builder $query) => $query->where('is_active', false))
            ->withCount([
                'rewards as total_hadiah_count',
                'rewards as hadiah_tersedia_count' => fn (Builder $query) => $query
                    ->where('status', StatusHadiah::Tersedia->value)
                    ->where(fn (Builder $query) => $query
                        ->whereNull('kedaluwarsa_at')
                        ->orWhere('kedaluwarsa_at', '>', now())),
                'rewards as hadiah_digunakan_count' => fn (Builder $query) => $query
                    ->where('status', StatusHadiah::Digunakan->value),
            ])
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        $rows = $promotions->getCollection()
            ->map(fn (Promotion $promotion): array => $this->rewards->promotionPayload($promotion) + [
                'aktif_sekarang' => $promotion->isAktif(),
                'hadiah_tersedia' => (int) $promotion->hadiah_tersedia_count,
                'hadiah_digunakan' => (int) $promotion->hadiah_digunakan_count,
                'total_hadiah' => (int) $promotion->total_hadiah_count,
            ])
            ->all();

        return Inertia::render('Promosi/Index', [
            'promotions' => $this->paginated($promotions, $rows),
            'filters' => [
                'search' => $filters['search'] ?? '',
                'status' => $filters['status'] ?? '',
                'per_page' => $perPage,
            ],
            'kategoriOptions' => [
                ['value' => '', 'label' => 'Semua pelanggan'],
                ['value' => KategoriPelanggan::Reguler->value, 'label' => 'Reguler'],
                ['value' => KategoriPelanggan::Member->value, 'label' => 'Member'],
            ],
            'tipeOptions' => collect(TipeHadiah::cases())
                ->map(fn (TipeHadiah $tipe): array => ['value' => $tipe->value, 'label' => $tipe->label()])
                ->all(),
        ]);
    }

    public function store(StorePromotionRequest $request): RedirectResponse
    {
        Promotion::query()->create($request->validated());
        $this->rewards->syncAllCustomers();

        return back()->with('success', 'Promosi berhasil ditambahkan.');
    }

    public function update(UpdatePromotionRequest $request, Promotion $promotion): RedirectResponse
    {
        $promotion->update($request->validated());
        $this->rewards->syncAllCustomers();

        return back()->with('success', 'Promosi berhasil diperbarui.');
    }

    public function destroy(Promotion $promotion): RedirectResponse
    {
        if ($promotion->rewards()->exists()) {
            throw new DomainException('Promosi yang sudah memiliki riwayat hadiah tidak dapat dihapus. Nonaktifkan promosi tersebut.');
        }

        $promotion->delete();

        return redirect()
            ->route('promosi.index')
            ->with('success', 'Promosi berhasil dihapus.');
    }
}

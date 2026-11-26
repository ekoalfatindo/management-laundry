<?php

namespace App\Http\Controllers;

use App\Enums\KategoriPelanggan;
use App\Enums\StatusNota;
use App\Enums\UserRole;
use App\Http\Requests\StorePelangganRequest;
use App\Http\Requests\UpdatePelangganRequest;
use App\Http\Resources\NotaResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\RewardService;
use App\Services\UserService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PelangganController extends Controller
{
    public function __construct(
        private readonly UserService $users,
        private readonly RewardService $rewards,
    ) {}

    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'kategori' => ['nullable', Rule::in(KategoriPelanggan::values())],
            'per_page' => ['nullable', 'integer', 'in:10,25,50,100'],
        ]);

        $perPage = (int) ($filters['per_page'] ?? 10);

        $pelanggans = User::query()
            ->role(UserRole::Pelanggan->value)
            ->with('roles')
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($filters['kategori'] ?? null, fn (Builder $query, string $kategori) => $query->where('kategori_pelanggan', $kategori))
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('Pelanggan/Index', [
            'pelanggans' => $this->paginated(
                $pelanggans,
                UserResource::collection($pelanggans->getCollection())->resolve(),
            ),
            'filters' => [
                'search' => $filters['search'] ?? '',
                'kategori' => $filters['kategori'] ?? '',
                'per_page' => $perPage,
            ],
            'kategoriOptions' => collect(KategoriPelanggan::cases())
                ->map(fn (KategoriPelanggan $kategori): array => ['value' => $kategori->value, 'label' => $kategori->label()])
                ->all(),
        ]);
    }

    public function show(User $user): Response
    {
        $this->ensureRole($user);

        $user->load('roles');

        $notas = $user->notas()
            ->with(['kiloan', 'jenisLayanan'])
            ->latest('waktu')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Pelanggan/Show', [
            'pelanggan' => (new UserResource($user))->resolve(),
            'notas' => $this->paginated(
                $notas,
                NotaResource::collection($notas->getCollection())->resolve(),
            ),
            'ringkasan' => [
                'total_nota' => $user->notas()->count(),
                'transaksi_selesai' => $user->notas()->where('status', StatusNota::Selesai->value)->count(),
                'total_belanja' => (int) $user->notas()->where('status', '!=', StatusNota::Dibatalkan->value)->sum('jumlah'),
            ],
            'loyalitas' => $this->rewards->progress($user),
        ]);
    }

    public function store(StorePelangganRequest $request): RedirectResponse
    {
        $this->users->create($request->validated(), UserRole::Pelanggan);

        return back()->with('success', 'Pelanggan berhasil ditambahkan.');
    }

    public function update(UpdatePelangganRequest $request, User $user): RedirectResponse
    {
        $this->ensureRole($user);

        $this->users->update($user, $request->validated(), UserRole::Pelanggan);

        return back()->with('success', 'Pelanggan berhasil diperbarui.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->ensureRole($user);

        $this->users->delete($user);

        return redirect()
            ->route('pelanggan.index')
            ->with('success', 'Pelanggan berhasil dihapus.');
    }

    private function ensureRole(User $user): void
    {
        abort_unless($user->hasRole(UserRole::Pelanggan->value), 404);
    }
}

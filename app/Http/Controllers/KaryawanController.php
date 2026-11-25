<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\StoreKaryawanRequest;
use App\Http\Requests\UpdateKaryawanRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class KaryawanController extends Controller
{
    public function __construct(private readonly UserService $users) {}

    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'in:10,25,50,100'],
        ]);

        $perPage = (int) ($filters['per_page'] ?? 10);

        $karyawans = User::query()
            ->role(UserRole::Karyawan->value)
            ->with('roles')
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('Karyawan/Index', [
            'karyawans' => $this->paginated(
                $karyawans,
                UserResource::collection($karyawans->getCollection())->resolve(),
            ),
            'filters' => [
                'search' => $filters['search'] ?? '',
                'per_page' => $perPage,
            ],
        ]);
    }

    public function store(StoreKaryawanRequest $request): RedirectResponse
    {
        $this->users->create($request->validated(), UserRole::Karyawan);

        return back()->with('success', 'Karyawan berhasil ditambahkan.');
    }

    public function update(UpdateKaryawanRequest $request, User $user): RedirectResponse
    {
        $this->ensureRole($user);

        $this->users->update($user, $request->validated(), UserRole::Karyawan);

        return back()->with('success', 'Karyawan berhasil diperbarui.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->ensureRole($user);

        $this->users->delete($user);

        return back()->with('success', 'Karyawan berhasil dihapus.');
    }

    private function ensureRole(User $user): void
    {
        abort_unless($user->hasRole(UserRole::Karyawan->value), 404);
    }
}

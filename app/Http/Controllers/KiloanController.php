<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreKiloanRequest;
use App\Http\Requests\UpdateKiloanRequest;
use App\Models\Kiloan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class KiloanController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'in:10,25,50,100'],
        ]);

        $perPage = (int) ($filters['per_page'] ?? 10);

        $kiloans = Kiloan::query()
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $query->where('paket', 'like', "%{$search}%"))
            ->orderBy('paket')
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('Kiloan/Index', [
            'kiloans' => $this->paginated(
                $kiloans,
                $kiloans->getCollection()
                    ->map(fn (Kiloan $kiloan): array => [
                        'id' => $kiloan->id,
                        'paket' => $kiloan->paket,
                        'harga' => $kiloan->harga,
                        'description' => $kiloan->description,
                    ])
                    ->all(),
            ),
            'filters' => [
                'search' => $filters['search'] ?? '',
                'per_page' => $perPage,
            ],
        ]);
    }

    public function store(StoreKiloanRequest $request): RedirectResponse
    {
        Kiloan::query()->create($request->validated());

        return back()->with('success', 'Paket kiloan berhasil ditambahkan.');
    }

    public function update(UpdateKiloanRequest $request, Kiloan $kiloan): RedirectResponse
    {
        $kiloan->update($request->validated());

        return back()->with('success', 'Paket kiloan berhasil diperbarui.');
    }

    public function destroy(Kiloan $kiloan): RedirectResponse
    {
        $this->authorize('delete', $kiloan);

        $kiloan->delete();

        return redirect()
            ->route('kiloan.index')
            ->with('success', 'Paket kiloan berhasil dihapus.');
    }
}

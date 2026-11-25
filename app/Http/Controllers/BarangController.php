<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBarangRequest;
use App\Http\Requests\UpdateBarangRequest;
use App\Models\Barang;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BarangController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'in:10,25,50,100'],
        ]);

        $perPage = (int) ($filters['per_page'] ?? 10);

        $barangs = Barang::query()
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('Barang/Index', [
            'barangs' => $this->paginated(
                $barangs,
                $barangs->getCollection()
                    ->map(fn (Barang $barang): array => [
                        'id' => $barang->id,
                        'name' => $barang->name,
                        'harga' => $barang->harga,
                    ])
                    ->all(),
            ),
            'filters' => [
                'search' => $filters['search'] ?? '',
                'per_page' => $perPage,
            ],
        ]);
    }

    public function store(StoreBarangRequest $request): RedirectResponse
    {
        Barang::query()->create($request->validated());

        return back()->with('success', 'Barang berhasil ditambahkan.');
    }

    public function update(UpdateBarangRequest $request, Barang $barang): RedirectResponse
    {
        $barang->update($request->validated());

        return back()->with('success', 'Barang berhasil diperbarui.');
    }

    public function destroy(Barang $barang): RedirectResponse
    {
        $this->authorize('delete', $barang);

        $barang->delete();

        return back()->with('success', 'Barang berhasil dihapus.');
    }
}

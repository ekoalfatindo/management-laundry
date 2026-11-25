<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreJenisLayananRequest;
use App\Http\Requests\UpdateJenisLayananRequest;
use App\Models\JenisLayanan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class JenisLayananController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'in:10,25,50,100'],
        ]);

        $perPage = (int) ($filters['per_page'] ?? 10);

        $jenisLayanans = JenisLayanan::query()
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $query->where('jenis_cuci', 'like', "%{$search}%"))
            ->orderBy('jenis_cuci')
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('JenisLayanan/Index', [
            'jenisLayanans' => $this->paginated(
                $jenisLayanans,
                $jenisLayanans->getCollection()
                    ->map(fn (JenisLayanan $jenisLayanan): array => [
                        'id' => $jenisLayanan->id,
                        'jenis_cuci' => $jenisLayanan->jenis_cuci,
                        'waktu' => $jenisLayanan->waktu,
                        'harga' => $jenisLayanan->harga,
                    ])
                    ->all(),
            ),
            'filters' => [
                'search' => $filters['search'] ?? '',
                'per_page' => $perPage,
            ],
        ]);
    }

    public function store(StoreJenisLayananRequest $request): RedirectResponse
    {
        JenisLayanan::query()->create($request->validated());

        return back()->with('success', 'Jenis layanan berhasil ditambahkan.');
    }

    public function update(UpdateJenisLayananRequest $request, JenisLayanan $jenisLayanan): RedirectResponse
    {
        $jenisLayanan->update($request->validated());

        return back()->with('success', 'Jenis layanan berhasil diperbarui.');
    }

    public function destroy(JenisLayanan $jenisLayanan): RedirectResponse
    {
        $this->authorize('delete', $jenisLayanan);

        $jenisLayanan->delete();

        return back()->with('success', 'Jenis layanan berhasil dihapus.');
    }
}

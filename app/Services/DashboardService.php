<?php

namespace App\Services;

use App\Enums\StatusNota;
use App\Models\Kiloan;
use App\Models\Nota;
use App\Models\User;
use Illuminate\Support\Collection;

class DashboardService
{
    /**
     * @return array<string, mixed>
     */
    public function summary(): array
    {
        $tahun = (int) now()->year;
        $aktif = fn ($query) => $query->where('status', '!=', StatusNota::Dibatalkan->value);

        /** @var array<int, int> $perBulan */
        $perBulan = $aktif(Nota::query())
            ->whereYear('waktu', $tahun)
            ->selectRaw('MONTH(waktu) as bulan, SUM(jumlah) as total')
            ->groupBy('bulan')
            ->pluck('total', 'bulan')
            ->toArray();

        $bulanan = collect(range(1, 12))
            ->map(fn (int $bulan): int => (int) ($perBulan[$bulan] ?? 0))
            ->all();

        return [
            'totalPendapatan' => (int) $aktif(Nota::query())->sum('jumlah'),
            'totalPenjualan' => $aktif(Nota::query())->count(),
            'totalUsers' => User::query()->count(),
            'totalPaket' => Kiloan::query()->count(),
            'tahun' => $tahun,
            'bulanan' => $bulanan,
            'pendapatanBulanIni' => (int) ($perBulan[(int) now()->month] ?? 0),
        ];
    }

    public function recent(int $limit = 6): Collection
    {
        return Nota::query()
            ->with(['kiloan', 'jenisLayanan'])
            ->latest('waktu')
            ->latest('id')
            ->limit($limit)
            ->get();
    }
}

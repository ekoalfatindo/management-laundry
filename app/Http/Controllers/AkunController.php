<?php

namespace App\Http\Controllers;

use App\Enums\StatusNota;
use App\Http\Resources\NotaResource;
use App\Models\Nota;
use App\Models\Pengaturan;
use App\Support\Terbilang;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;

class AkunController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $query = $request->user()->notas();

        $ringkasan = [
            'total_nota' => (clone $query)->count(),
            'total_biaya' => (int) (clone $query)
                ->where('status', '!=', StatusNota::Dibatalkan->value)
                ->sum('jumlah'),
        ];

        $notas = $query
            ->with(['kiloan', 'jenisLayanan'])
            ->latest('waktu')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Akun/Index', [
            'notas' => $this->paginated(
                $notas,
                NotaResource::collection($notas->getCollection())->resolve(),
            ),
            'ringkasan' => $ringkasan,
        ]);
    }

    public function show(Request $request, Nota $nota): InertiaResponse
    {
        $nota = $this->ownedNota($request, $nota);
        $nota->load(['kiloan', 'jenisLayanan', 'barangs', 'pelanggan']);

        return Inertia::render('Akun/Show', [
            'nota' => (new NotaResource($nota))->resolve(),
        ]);
    }

    public function pdf(Request $request, Nota $nota): Response
    {
        $nota = $this->ownedNota($request, $nota);
        $nota->load(['kiloan', 'jenisLayanan', 'barangs', 'pelanggan']);

        $html = view('pdf.nota', [
            'nota' => $nota,
            'company' => Pengaturan::current(),
            'terbilang' => Terbilang::rupiah($nota->jumlah),
            'subtotal' => $nota->total_harga,
            'potongan' => $nota->total_harga - $nota->jumlah,
        ])->render();

        $options = new Options;
        $options->setIsRemoteEnabled(false);
        $options->setDefaultFont('Helvetica');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4');
        $dompdf->render();

        $filename = trim(preg_replace('/[^A-Za-z0-9]+/', '-', $nota->no_nota) ?? '', '-');

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="nota-'.$filename.'.pdf"',
            'Cache-Control' => 'private, max-age=0, must-revalidate',
        ]);
    }

    private function ownedNota(Request $request, Nota $nota): Nota
    {
        abort_unless($nota->pelanggan_id !== null && $nota->pelanggan_id === $request->user()->id, 404);

        return $nota;
    }
}

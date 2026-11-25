<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePengaturanRequest;
use App\Models\Pengaturan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PengaturanController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('Pengaturan/Index', [
            'pengaturan' => Pengaturan::current()->toSharedArray(),
        ]);
    }

    public function update(UpdatePengaturanRequest $request): RedirectResponse
    {
        $pengaturan = Pengaturan::current();
        $data = $request->validated();

        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('logos', 'public');

            if ($pengaturan->logo_path) {
                Storage::disk('public')->delete($pengaturan->logo_path);
            }

            $data['logo_path'] = $logoPath;
        }

        unset($data['logo']);

        $pengaturan->update($data);

        return back()->with('success', 'Informasi perusahaan berhasil disimpan.');
    }
}

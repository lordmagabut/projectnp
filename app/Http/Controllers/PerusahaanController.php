<?php

namespace App\Http\Controllers;

use App\Models\Perusahaan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PerusahaanController extends Controller
{
    public function index()
    {
        $perusahaan = Perusahaan::first();
    
        $jumlahPerusahaan = $perusahaan ? 1 : 0;

        return view('perusahaan.index', compact('perusahaan', 'jumlahPerusahaan'));
    }

    public function create()
    {
        return view('perusahaan.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_perusahaan' => 'required',
            'alamat' => 'required',
            'email' => 'nullable|email',
            'no_telp' => 'nullable',
            'npwp' => 'nullable',
            'tipe_perusahaan' => 'required|in:UMKM,Kontraktor,Perorangan',
        ]);

        $data = $request->only([
            'nama_perusahaan',
            'alamat',
            'email',
            'no_telp',
            'npwp',
            'tipe_perusahaan'
        ]);


        Perusahaan::create($data);

        return redirect()->route('perusahaan.index')->with('success', 'Data perusahaan berhasil disimpan.');
    }

    public function edit($id)
    {
        $perusahaan = Perusahaan::findOrFail($id);
        return view('perusahaan.edit', compact('perusahaan'));
    }

    public function update(Request $request, $id)
    {
        if (auth()->user()->edit_perusahaan != 1) {
            abort(403, 'Anda tidak memiliki izin untuk mengedit perusahaan.');
        }

        $request->validate([
            'nama_perusahaan' => 'required',
            'alamat' => 'required',
            'email' => 'nullable|email',
            'no_telp' => 'nullable',
            'npwp' => 'nullable',
            'tipe_perusahaan' => 'required|in:UMKM,Kontraktor,Perorangan',
        ]);

        $perusahaan = Perusahaan::findOrFail($id);

        $data = $request->only([
            'nama_perusahaan',
            'alamat',
            'email',
            'no_telp',
            'npwp',
            'tipe_perusahaan',
        ]);


        $perusahaan->update($data);

        return redirect()->route('perusahaan.index')->with('success', 'Data perusahaan berhasil diupdate.');
    }

    public function destroy($id)
    {
        $perusahaan = Perusahaan::findOrFail($id);

        if ($perusahaan->template_po && Storage::disk('public')->exists($perusahaan->template_po)) {
            Storage::disk('public')->delete($perusahaan->template_po);
        }

        $perusahaan->delete();

        return redirect()->route('perusahaan.index')->with('success', 'Data perusahaan berhasil dihapus.');
    }
}

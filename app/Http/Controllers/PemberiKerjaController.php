<?php

namespace App\Http\Controllers;

use App\Models\PemberiKerja;
use Illuminate\Http\Request;

class PemberiKerjaController extends Controller
{
    public function index()
    {
        $pemberiKerjas = PemberiKerja::all();
        return view('pemberiKerja.index', compact('pemberiKerjas'));
    }

    public function create()
    {
        if (auth()->user()->buat_pemberikerja != 1) {
            abort(403, 'Anda tidak memiliki izin untuk menambah pemberi kerja.');
        }

        return view('pemberiKerja.create');
    }

    public function store(Request $request)
    {
        if (auth()->user()->buat_pemberikerja != 1) {
            abort(403, 'Anda tidak memiliki izin untuk menambah pemberi kerja.');
        }

        $request->validate([
            'nama_pemberi_kerja' => 'required',
            'pic' => 'nullable',
            'no_kontak' => 'nullable',
            'alamat' => 'nullable',
        ]);

        PemberiKerja::create([
            'nama_pemberi_kerja' => $request->nama_pemberi_kerja,
            'pic' => $request->pic,
            'no_kontak' => $request->no_kontak,
            'alamat' => $request->alamat,
        ]);

        return redirect()->route('pemberiKerja.index')->with('success', 'Data pemberi kerja berhasil disimpan.');
    }

    public function edit($id)
    {
        if (auth()->user()->edit_pemberikerja != 1) {
            abort(403, 'Anda tidak memiliki izin untuk mengedit pemberi kerja.');
        }

        $pemberiKerja = PemberiKerja::findOrFail($id);
        return view('pemberiKerja.edit', compact('pemberiKerja'));
    }

    public function update(Request $request, $id)
    {
        if (auth()->user()->edit_pemberikerja != 1) {
            abort(403, 'Anda tidak memiliki izin untuk mengedit pemberi kerja.');
        }

        $request->validate([
            'nama_pemberi_kerja' => 'required',
            'pic' => 'nullable',
            'no_kontak' => 'nullable',
            'alamat' => 'nullable',
        ]);

        $pemberiKerja = PemberiKerja::findOrFail($id);

        $pemberiKerja->update([
            'nama_pemberi_kerja' => $request->nama_pemberi_kerja,
            'pic' => $request->pic,
            'no_kontak' => $request->no_kontak,
            'alamat' => $request->alamat,
        ]);

        return redirect()->route('pemberiKerja.index')->with('success', 'Data pemberi kerja berhasil diupdate.');
    }

    public function destroy($id)
    {
        if (auth()->user()->hapus_pemberikerja != 1) {
            abort(403, 'Anda tidak memiliki izin untuk menghapus pemberi kerja.');
        }

        $pemberiKerja = PemberiKerja::findOrFail($id);
        $pemberiKerja->delete();

        return redirect()->route('pemberiKerja.index')->with('success', 'Data pemberi kerja berhasil dihapus.');
    }
}

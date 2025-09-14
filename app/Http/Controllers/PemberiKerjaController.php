<?php

namespace App\Http\Controllers;

use App\Models\PemberiKerja;
use Illuminate\Http\Request;

class PemberiKerjaController extends Controller
{
    public function index()
    {
        $pemberiKerjas = PemberiKerja::orderBy('nama_pemberi_kerja')->get();
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

        $validated = $request->validate([
            'nama_pemberi_kerja' => 'required|string|max:255',
            'pic'                => 'nullable|string|max:100',
            'jabatan_pic'        => 'nullable|string|max:100',
            'nama_direktur'      => 'nullable|string|max:255',
            'jabatan_direktur'   => 'nullable|string|max:100',
            'no_kontak'          => 'nullable|string|max:30',
            'alamat'             => 'nullable|string',
        ]);

        PemberiKerja::create($request->only([
            'nama_pemberi_kerja',
            'pic',
            'jabatan_pic',
            'nama_direktur',
            'jabatan_direktur',
            'no_kontak',
            'alamat',
        ]));

        return redirect()->route('pemberiKerja.index')->with('success', 'Data pemberi kerja berhasil disimpan.');
    }

    // ---- pakai implicit binding ----
    public function edit(PemberiKerja $pemberiKerja)
    {
        if (auth()->user()->edit_pemberikerja != 1) {
            abort(403, 'Anda tidak memiliki izin untuk mengedit pemberi kerja.');
        }

        return view('pemberiKerja.edit', compact('pemberiKerja'));
    }

    public function update(Request $request, PemberiKerja $pemberiKerja)
    {
        if (auth()->user()->edit_pemberikerja != 1) {
            abort(403, 'Anda tidak memiliki izin untuk mengedit pemberi kerja.');
        }

        $validated = $request->validate([
            'nama_pemberi_kerja' => 'required|string|max:255',
            'pic'                => 'nullable|string|max:100',
            'jabatan_pic'        => 'nullable|string|max:100',
            'nama_direktur'      => 'nullable|string|max:255',
            'jabatan_direktur'   => 'nullable|string|max:100',
            'no_kontak'          => 'nullable|string|max:30',
            'alamat'             => 'nullable|string',
        ]);

        $pemberiKerja->update($request->only([
            'nama_pemberi_kerja',
            'pic',
            'jabatan_pic',
            'nama_direktur',
            'jabatan_direktur',
            'no_kontak',
            'alamat',
        ]));

        return redirect()->route('pemberiKerja.index')->with('success', 'Data pemberi kerja berhasil diupdate.');
    }

    public function destroy(PemberiKerja $pemberiKerja)
    {
        if (auth()->user()->hapus_pemberikerja != 1) {
            abort(403, 'Anda tidak memiliki izin untuk menghapus pemberi kerja.');
        }

        $pemberiKerja->delete();

        return redirect()->route('pemberiKerja.index')->with('success', 'Data pemberi kerja berhasil dihapus.');
    }
}

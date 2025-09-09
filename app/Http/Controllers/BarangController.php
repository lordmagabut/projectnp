<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\TipeBarangJasa;
use App\Models\Coa;
use Illuminate\Http\Request;

class BarangController extends Controller
{
    public function index()
    {
        $barangs = Barang::with('tipe')->get();
        return view('barang.index', compact('barangs'));
    }

    public function create()
    {
        if (auth()->user()->buat_barang != 1) {
            abort(403, 'Anda tidak memiliki izin');
        }

        $tipeBarangJasa = TipeBarangJasa::all();
        $coa = Coa::all();

        return view('barang.create', compact('tipeBarangJasa', 'coa'));
    }

    public function store(Request $request)
    {
        if (auth()->user()->buat_barang != 1) {
            abort(403, 'Anda tidak memiliki izin');
        }

        $request->validate([
            'kode_barang' => 'required|unique:barang,kode_barang',
            'nama_barang' => 'required',
            'tipe_id' => 'required|exists:tipe_barang_jasa,id',
            'coa_persediaan_id' => 'nullable|exists:coa,id',
            'coa_beban_id' => 'nullable|exists:coa,id',
            'coa_hpp_id' => 'nullable|exists:coa,id',
        ]);

        Barang::create($request->all());

        return redirect()->route('barang.index')->with('success', 'Data berhasil disimpan.');
    }

    public function edit($id)
    {
        if (auth()->user()->edit_barang != 1) {
            abort(403, 'Anda tidak memiliki izin');
        }

        $barang = Barang::findOrFail($id);
        $tipeBarangJasa = TipeBarangJasa::all();
        $coa = Coa::all();

        return view('barang.edit', compact('barang', 'tipeBarangJasa', 'coa'));
    }

    public function update(Request $request, $id)
    {
        if (auth()->user()->edit_barang != 1) {
            abort(403, 'Anda tidak memiliki izin');
        }

        $request->validate([
            'kode_barang' => 'required|unique:barang,kode_barang,' . $id,
            'nama_barang' => 'required',
            'tipe_id' => 'required|exists:tipe_barang_jasa,id',
            'coa_persediaan_id' => 'nullable|exists:coa,id',
            'coa_beban_id' => 'nullable|exists:coa,id',
            'coa_hpp_id' => 'nullable|exists:coa,id',
        ]);

        $barang = Barang::findOrFail($id);
        $barang->update($request->all());

        return redirect()->route('barang.index')->with('success', 'Data berhasil diupdate.');
    }

    public function destroy($id)
    {
        if (auth()->user()->hapus_barang != 1) {
            abort(403, 'Anda tidak memiliki izin');
        }

        $barang = Barang::findOrFail($id);
        $barang->delete();

        return redirect()->route('barang.index')->with('success', 'Data berhasil dihapus.');
    }
}

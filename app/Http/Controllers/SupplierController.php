<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index()
    {
        $suppliers = Supplier::all();
        return view('supplier.index', compact('suppliers'));
    }

    public function create()
    {
        if (auth()->user()->buat_supplier != 1) {
            abort(403, 'Anda tidak memiliki izin untuk menambah perusahaan.');
        }
        return view('supplier.create');
    }

    public function store(Request $request)
    {
        if (auth()->user()->buat_supplier != 1) {
            abort(403, 'Anda tidak memiliki izin untuk menambah perusahaan.');
        }
        $request->validate([
            'nama_supplier' => 'required',
            'pic' => 'required',
            'no_kontak' => 'nullable|numeric',
            'keterangan' => 'nullable',
        ]);

        Supplier::create($request->all());

        return redirect()->route('supplier.index')->with('success', 'Data supplier berhasil disimpan.');
    }

    public function edit($id)
    {
        if (auth()->user()->edit_supplier != 1) {
            abort(403, 'Anda tidak memiliki izin untuk menambah perusahaan.');
        }
        $supplier = Supplier::findOrFail($id);
        return view('supplier.edit', compact('supplier'));
    }

    public function update(Request $request, $id)
    {
        if (auth()->user()->edit_supplier != 1) {
            abort(403, 'Anda tidak memiliki izin untuk menambah perusahaan.');
        }
        $request->validate([
            'nama_supplier' => 'required',
            'pic' => 'required',
            'no_kontak' => 'nullable|numeric',
            'keterangan' => 'nullable',
        ]);

        $supplier = Supplier::findOrFail($id);
        $supplier->update($request->all());

        return redirect()->route('supplier.index')->with('success', 'Data supplier berhasil diupdate.');
    }

    public function destroy($id)
    {
        if (auth()->user()->hapus_supplier != 1) {
            abort(403, 'Anda tidak memiliki izin untuk menambah perusahaan.');
        }
        $supplier = Supplier::findOrFail($id);
        $supplier->delete();

        return redirect()->route('supplier.index')->with('success', 'Data supplier berhasil dihapus.');
    }
}

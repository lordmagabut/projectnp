<?php

namespace App\Http\Controllers;

use App\Models\Coa;
use Illuminate\Http\Request;

class CoaController extends Controller
{
    public function index()
    {
        $coas = Coa::with(['children' => function ($query) {
                        $query->orderBy('_lft');
                    }])
                    ->whereIsRoot()
                    ->defaultOrder()
                    ->get();
    
        return view('coa.index', compact('coas'));
    }

    public function create()
    {
        $parentAkun = Coa::defaultOrder()->get()->toFlatTree(); // ambil semua akun dalam struktur hirarkis
        return view('coa.create', compact('parentAkun'));
    }

    public function store(Request $request)
    {
        if (auth()->user()->buat_coa != 1) {
            abort(403, 'Anda tidak memiliki izin.');
        }
        $request->validate([
            'no_akun' => 'required',
            'nama_akun' => 'required',
            'tipe' => 'required',
        ]);

        Coa::create([
            'no_akun' => $request->no_akun,
            'nama_akun' => $request->nama_akun,
            'parent_id' => $request->parent_id,
            'tipe' => $request->tipe
        ]);

        return redirect()->route('coa.index')->with('success', 'Akun berhasil disimpan.');
    }

    public function edit($id)
    {
        if (auth()->user()->edit_coa != 1) {
            abort(403, 'Anda tidak memiliki izin.');
        }
        $coa = Coa::findOrFail($id);
        $parentAkun = Coa::where('id', '!=', $id)->get();
        return view('coa.edit', compact('coa', 'parentAkun'));
    }

    public function update(Request $request, $id)
    {
        if (auth()->user()->edit_coa != 1) {
            abort(403, 'Anda tidak memiliki izin.');
        }
        $request->validate([
            'no_akun' => 'required',
            'nama_akun' => 'required',
            'tipe' => 'required',
        ]);

        $coa = Coa::findOrFail($id);

        $coa->update([
            'no_akun' => $request->no_akun,
            'nama_akun' => $request->nama_akun,
            'parent_id' => $request->parent_id,
            'tipe' => $request->tipe
        ]);

        return redirect()->route('coa.index')->with('success', 'Akun berhasil diupdate.');
    }

    public function destroy($id)
    {
        if (auth()->user()->hapus_coa != 1) {
            abort(403, 'Anda tidak memiliki izin.');
        }
        $coa = Coa::findOrFail($id);
        $coa->delete();

        return redirect()->route('coa.index')->with('success', 'Akun berhasil dihapus.');
    }
}

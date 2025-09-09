<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HsdMaterial;
use App\Models\HsdUpah;
use App\Models\Users;

class HsdUpahController extends Controller
{
    public function create()
    {
        return view('hsd_upah.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'kode' => 'required|string|max:50|unique:hsd_upah,kode',
            'jenis_pekerja' => 'required|string|max:255',
            'satuan' => 'required|string|max:50',
            'harga_satuan' => 'required|numeric|min:0',
            'keterangan' => 'nullable|string',
        ]);

        HsdUpah::create($request->all());

        return redirect()->route('ahsp.index', ['tab' => 'upah'])->with('success', 'Upah berhasil ditambah.');
    }

    public function edit($id)
    {
        $upah = HsdUpah::findOrFail($id);
        return view('hsd_upah.edit', compact('upah'));
    }

    public function update(Request $request, $id)
    {
        $upah = HsdUpah::findOrFail($id);
    
        $request->validate([
            'kode'          => 'required|string|max:50|unique:hsd_upah,kode,' . $upah->id,
            'jenis_pekerja' => 'required|string|max:255',
            'satuan'        => 'required|string|max:50',
            'harga_satuan'  => 'required|numeric|min:0',
            'keterangan'    => 'nullable|string',
            'sumber'        => 'nullable|string|max:255',
        ]);
    
        if ($request->harga_satuan != $upah->harga_satuan) {
            $upah->histories()->create([
                'harga_satuan'    => $upah->harga_satuan,
                'harga_baru'      => $request->harga_satuan,
                'tanggal_berlaku' => now(),
                'sumber'          => $request->sumber ?? 'update manual',
                'updated_by'      => auth()->id(),
            ]);
        }
    
        $upah->update([
            'kode'         => $request->kode,
            'jenis_pekerja'=> $request->jenis_pekerja,
            'satuan'       => $request->satuan,
            'harga_satuan' => $request->harga_satuan,
            'keterangan'   => $request->keterangan,
        ]);
    
        return redirect()->route('ahsp.index', ['tab' => 'upah'])->with('success', 'Upah berhasil diperbarui.');
    }
    

    public function destroy($id)
    {
        $upah = HsdUpah::findOrFail($id);
        $upah->delete();

        return redirect()->route('ahsp.index', ['tab' => 'upah'])->with('success', 'Upah berhasil dihapus.');
    }
}

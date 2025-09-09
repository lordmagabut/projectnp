<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HsdMaterial;
use App\Models\Users;

class HsdMaterialController extends Controller
{

    public function create()
    {
        return view('hsd_material.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'kode' => 'required|string|max:50|unique:hsd_material,kode',
            'nama' => 'required|string|max:255',
            'satuan' => 'required|string|max:50',
            'harga_satuan' => 'required|numeric|min:0',
            'keterangan' => 'nullable|string',
        ]);

        HsdMaterial::create($request->all());

        return redirect()->route('ahsp.index', ['tab' => 'material'])->with('success', 'Material berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $material = HsdMaterial::findOrFail($id);
        return view('hsd_material.edit', compact('material'));
    }

    public function update(Request $request, $id)
    {
        $material = HsdMaterial::findOrFail($id);
    
        $request->validate([
            'kode' => 'required|string|max:50|unique:hsd_material,kode,' . $material->id,
            'nama' => 'required|string|max:255',
            'satuan' => 'required|string|max:50',
            'harga_satuan' => 'required|numeric|min:0',
            'keterangan' => 'nullable|string',
            'sumber'        => 'nullable|string|max:255',
        ]);
    
        // Jika harga berubah, simpan histori harga
        if ($request->harga_satuan != $material->harga_satuan) {
            $material->histories()->create([
                'harga_satuan'    => $material->harga_satuan,           // harga lama
                'harga_baru'      => $request->harga_satuan,            // harga baru
                'tanggal_berlaku' => now(),
                'sumber'          => $request->sumber ?? 'update manual',
                'updated_by'      => auth()->id(),
            ]);
        }
        
    
        // Update data utama
        $material->update([
            'kode'          => $request->kode,
            'nama'          => $request->nama,
            'satuan'        => $request->satuan,
            'harga_satuan'  => $request->harga_satuan,
            'keterangan'    => $request->keterangan,
        ]);
    
        return redirect()->route('ahsp.index', ['tab' => 'material'])->with('success', 'Material berhasil diperbarui.');
    }
    
    public function destroy($id)
    {
        $material = HsdMaterial::findOrFail($id);
        $material->delete();

        return redirect()->route('ahsp.index', ['tab' => 'material'])->with('success', 'Material berhasil dihapus.');
    }
}

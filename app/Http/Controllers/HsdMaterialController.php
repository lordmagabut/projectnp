<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HsdMaterial;
use App\Models\ActivityLog; // <-- tambahkan

class HsdMaterialController extends Controller
{
    public function create()
    {
        return view('hsd_material.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'kode'         => 'required|string|max:50|unique:hsd_material,kode',
            'nama'         => 'required|string|max:255',
            'satuan'       => 'required|string|max:50',
            'harga_satuan' => 'required|numeric|min:0',
            'keterangan'   => 'nullable|string',
        ]);

        $material = HsdMaterial::create($request->only('kode','nama','satuan','harga_satuan','keterangan'));

        // === LOG: create ===
        ActivityLog::create([
            'user_id'     => auth()->id(),
            'event'       => 'hsd_material_create',
            'description' => sprintf(
                'Tambah Material %s - %s; satuan: %s; harga: %s',
                $material->kode,
                $material->nama,
                $material->satuan,
                number_format($material->harga_satuan, 0, ',', '.')
            ),
            'ip_address'  => $request->ip(),
            'user_agent'  => $request->userAgent(),
        ]);

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
            'kode'         => 'required|string|max:50|unique:hsd_material,kode,' . $material->id,
            'nama'         => 'required|string|max:255',
            'satuan'       => 'required|string|max:50',
            'harga_satuan' => 'required|numeric|min:0',
            'keterangan'   => 'nullable|string',
            'sumber'       => 'nullable|string|max:255',
        ]);

        // nilai sebelum
        $before = $material->only(['kode','nama','satuan','harga_satuan','keterangan']);

        // histori harga bila berubah
        if ($request->harga_satuan != $material->harga_satuan) {
            $material->histories()->create([
                'harga_satuan'    => $material->harga_satuan,
                'harga_baru'      => $request->harga_satuan,
                'tanggal_berlaku' => now(),
                'sumber'          => $request->sumber ?? 'update manual',
                'updated_by'      => auth()->id(),
            ]);
        }

        // update
        $material->update([
            'kode'         => $request->kode,
            'nama'         => $request->nama,
            'satuan'       => $request->satuan,
            'harga_satuan' => $request->harga_satuan,
            'keterangan'   => $request->keterangan,
        ]);

        // bangun ringkasan perubahan
        $after   = $material->only(['kode','nama','satuan','harga_satuan','keterangan']);
        $changed = [];
        foreach ($after as $k => $v) {
            if (($before[$k] ?? null) != $v) {
                $changed[] = $k === 'harga_satuan'
                    ? "harga_satuan: ".number_format($before[$k],0,',','.')." → ".number_format($v,0,',','.')
                    : "$k: ".($before[$k] ?? '—')." → ".$v;
            }
        }

        // === LOG: update ===
        ActivityLog::create([
            'user_id'     => auth()->id(),
            'event'       => 'hsd_material_update',
            'description' => sprintf(
                'Update Material %s - %s%s',
                $material->kode,
                $material->nama,
                $changed ? '; perubahan: '.implode(', ', $changed) : ''
            ),
            'ip_address'  => $request->ip(),
            'user_agent'  => $request->userAgent(),
        ]);

        return redirect()->route('ahsp.index', ['tab' => 'material'])->with('success', 'Material berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $material = HsdMaterial::findOrFail($id);
        $kode = $material->kode;
        $nama = $material->nama;
        $material->delete();

        // === LOG: delete ===
        ActivityLog::create([
            'user_id'     => auth()->id(),
            'event'       => 'hsd_material_delete',
            'description' => sprintf('Hapus Material %s - %s', $kode, $nama),
            'ip_address'  => request()->ip(),
            'user_agent'  => request()->userAgent(),
        ]);

        return redirect()->route('ahsp.index', ['tab' => 'material'])->with('success', 'Material berhasil dihapus.');
    }
}

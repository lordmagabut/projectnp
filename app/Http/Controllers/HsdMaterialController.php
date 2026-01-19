<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\HsdMaterial;
use App\Models\ActivityLog;

class HsdMaterialController extends Controller
{
    public function create()
    {
        $nextKode = HsdMaterial::generateKode();
        return view('hsd_material.create', compact('nextKode'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama'         => 'required|string|max:255',
            'satuan'       => 'required|string|max:50',
            'harga_satuan' => 'required|numeric|min:0',
            'keterangan'   => 'nullable|string',
        ]);

        DB::transaction(function () use ($request) {
            // Auto-generate kode
            $kode = HsdMaterial::generateKode();
            
            $material = HsdMaterial::create([
                'kode'         => $kode,
                'nama'         => $request->nama,
                'satuan'       => $request->satuan,
                'harga_satuan' => $request->harga_satuan,
                'keterangan'   => $request->keterangan,
            ]);

            // LOG: create
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
        });

        return redirect()->route('ahsp.index', ['tab' => 'material'])
            ->with('success', 'Material berhasil ditambahkan.');
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
            'nama'         => 'required|string|max:255',
            'satuan'       => 'required|string|max:50',
            'harga_satuan' => 'required|numeric|min:0',
            'keterangan'   => 'nullable|string',
            'sumber'       => 'nullable|string|max:255',
        ]);

        if ($request->filled('kode') && $request->kode !== $material->kode) {
            return back()->withErrors(['kode' => 'Kode material tidak boleh diubah.'])->withInput();
        }

        DB::transaction(function () use ($request, $material) {
            // nilai sebelum
            $before = $material->only(['kode','nama','satuan','harga_satuan','keterangan']);

            // histori harga bila berubah
            if ((float)$request->harga_satuan !== (float)$material->harga_satuan) {
                // gunakan relasi histories() pada model
                $material->histories()->create([
                    'harga_satuan'    => $material->harga_satuan,   // old price
                    'harga_baru'      => $request->harga_satuan,     // new price
                    'tanggal_berlaku' => now(),
                    'sumber'          => $request->sumber ?? 'update manual',
                    'updated_by'      => auth()->id(),
                ]);
            }

            $material->update([
                'nama'         => $request->nama,
                'satuan'       => $request->satuan,
                'harga_satuan' => $request->harga_satuan,
                'keterangan'   => $request->keterangan,
            ]);

            // ringkasan perubahan
            $after   = $material->only(['kode','nama','satuan','harga_satuan','keterangan']);
            $changed = [];
            foreach ($after as $k => $v) {
                if (($before[$k] ?? null) != $v) {
                    $changed[] = $k === 'harga_satuan'
                        ? "harga_satuan: ".number_format($before[$k],0,',','.')." → ".number_format($v,0,',','.')
                        : "$k: ".($before[$k] ?? '—')." → ".$v;
                }
            }

            // LOG: update
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
        });

        return redirect()->route('ahsp.index', ['tab' => 'material'])
            ->with('success', 'Material berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $material = HsdMaterial::findOrFail($id);

        DB::transaction(function () use ($material) {
            $kode = $material->kode;
            $nama = $material->nama;

            $material->delete();

            // LOG: delete
            ActivityLog::create([
                'user_id'     => auth()->id(),
                'event'       => 'hsd_material_delete',
                'description' => sprintf('Hapus Material %s - %s', $kode, $nama),
                'ip_address'  => request()->ip(),
                'user_agent'  => request()->userAgent(),
            ]);
        });

        return redirect()->route('ahsp.index', ['tab' => 'material'])
            ->with('success', 'Material berhasil dihapus.');
    }

    /**
     * Endpoint untuk modal history Material (JSON).
     * Mengembalikan maksimal 50 baris terbaru.
     *
     * Output contoh tiap baris:
     * [
     *   'tanggal'    => '2025-10-01T10:00:00',
     *   'harga_lama' => 12000,
     *   'harga_baru' => 12500,
     *   'satuan'     => 'kg',
     *   'keterangan' => 'update manual',
     *   'user_name'  => 'Admin'
     * ]
     */
    public function history($id)
    {
        $material = HsdMaterial::findOrFail($id);

        // Ambil history via relasi; jika relasi punya kolom sesuai:
        $rows = $material->histories()
            ->orderByDesc('tanggal_berlaku')
            ->limit(50)
            ->get(['id','harga_satuan','harga_baru','tanggal_berlaku','sumber','updated_by','created_at']);

        // Resolve user name (jika ada)
        $userMap = collect();
        if ($rows->pluck('updated_by')->filter()->isNotEmpty()) {
            $userMap = User::whereIn('id', $rows->pluck('updated_by')->filter()->unique())
                ->pluck('name', 'id');
        }

        $payload = $rows->map(function ($r) use ($material, $userMap) {
            return [
                'tanggal'    => $r->tanggal_berlaku ?? $r->created_at,
                'harga_lama' => $r->harga_satuan, // old price
                'harga_baru' => $r->harga_baru,   // new price
                'satuan'     => $material->satuan,
                'keterangan' => $r->sumber,
                'user_name'  => $userMap[$r->updated_by] ?? null,
            ];
        });

        return response()->json($payload, 200);
    }
}

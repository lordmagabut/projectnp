<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HsdUpah;
use App\Models\ActivityLog; // <-- tambahkan

class HsdUpahController extends Controller
{
    public function create()
    {
        return view('hsd_upah.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'kode'          => 'required|string|max:50|unique:hsd_upah,kode',
            'jenis_pekerja' => 'required|string|max:255',
            'satuan'        => 'required|string|max:50',
            'harga_satuan'  => 'required|numeric|min:0',
            'keterangan'    => 'nullable|string',
        ]);

        $upah = HsdUpah::create($request->only('kode','jenis_pekerja','satuan','harga_satuan','keterangan'));

        // === LOG: create ===
        ActivityLog::create([
            'user_id'     => auth()->id(),
            'event'       => 'hsd_upah_create',
            'description' => sprintf(
                'Tambah Upah %s - %s; satuan: %s; harga: %s',
                $upah->kode,
                $upah->jenis_pekerja,
                $upah->satuan,
                number_format($upah->harga_satuan, 0, ',', '.')
            ),
            'ip_address'  => $request->ip(),
            'user_agent'  => $request->userAgent(),
        ]);

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

        $before = $upah->only(['kode','jenis_pekerja','satuan','harga_satuan','keterangan']);

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
            'kode'          => $request->kode,
            'jenis_pekerja' => $request->jenis_pekerja,
            'satuan'        => $request->satuan,
            'harga_satuan'  => $request->harga_satuan,
            'keterangan'    => $request->keterangan,
        ]);

        $after   = $upah->only(['kode','jenis_pekerja','satuan','harga_satuan','keterangan']);
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
            'event'       => 'hsd_upah_update',
            'description' => sprintf(
                'Update Upah %s - %s%s',
                $upah->kode,
                $upah->jenis_pekerja,
                $changed ? '; perubahan: '.implode(', ', $changed) : ''
            ),
            'ip_address'  => $request->ip(),
            'user_agent'  => $request->userAgent(),
        ]);

        return redirect()->route('ahsp.index', ['tab' => 'upah'])->with('success', 'Upah berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $upah = HsdUpah::findOrFail($id);
        $kode = $upah->kode;
        $jenis= $upah->jenis_pekerja;

        $upah->delete();

        // === LOG: delete ===
        ActivityLog::create([
            'user_id'     => auth()->id(),
            'event'       => 'hsd_upah_delete',
            'description' => sprintf('Hapus Upah %s - %s', $kode, $jenis),
            'ip_address'  => request()->ip(),
            'user_agent'  => request()->userAgent(),
        ]);

        return redirect()->route('ahsp.index', ['tab' => 'upah'])->with('success', 'Upah berhasil dihapus.');
    }
}

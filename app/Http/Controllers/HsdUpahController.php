<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\HsdUpah;
use App\Models\ActivityLog;

class HsdUpahController extends Controller
{
    public function create()
    {
        $nextKode = HsdUpah::generateKode();
        return view('hsd_upah.create', compact('nextKode'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'jenis_pekerja' => 'required|string|max:255',
            'satuan'        => 'required|string|max:50',
            'harga_satuan'  => 'required|numeric|min:0',
            'keterangan'    => 'nullable|string',
        ]);

        DB::transaction(function () use ($request) {
            // Auto-generate kode
            $kode = HsdUpah::generateKode();
            
            $upah = HsdUpah::create([
                'kode'          => $kode,
                'jenis_pekerja' => $request->jenis_pekerja,
                'satuan'        => $request->satuan,
                'harga_satuan'  => $request->harga_satuan,
                'keterangan'    => $request->keterangan,
            ]);

            // LOG: create
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
        });

        return redirect()->route('ahsp.index', ['tab' => 'upah'])
            ->with('success', 'Upah berhasil ditambah.');
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
            'jenis_pekerja' => 'required|string|max:255',
            'satuan'        => 'required|string|max:50',
            'harga_satuan'  => 'required|numeric|min:0',
            'keterangan'    => 'nullable|string',
            'sumber'        => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($request, $upah) {
            $before = $upah->only(['kode','jenis_pekerja','satuan','harga_satuan','keterangan']);

            if ((float)$request->harga_satuan !== (float)$upah->harga_satuan) {
                $upah->histories()->create([
                    'harga_satuan'    => $upah->harga_satuan,   // old price
                    'harga_baru'      => $request->harga_satuan, // new price
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

            // LOG: update
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
        });

        return redirect()->route('ahsp.index', ['tab' => 'upah'])
            ->with('success', 'Upah berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $upah = HsdUpah::findOrFail($id);

        DB::transaction(function () use ($upah) {
            $kode  = $upah->kode;
            $jenis = $upah->jenis_pekerja;

            $upah->delete();

            // LOG: delete
            ActivityLog::create([
                'user_id'     => auth()->id(),
                'event'       => 'hsd_upah_delete',
                'description' => sprintf('Hapus Upah %s - %s', $kode, $jenis),
                'ip_address'  => request()->ip(),
                'user_agent'  => request()->userAgent(),
            ]);
        });

        return redirect()->route('ahsp.index', ['tab' => 'upah'])
            ->with('success', 'Upah berhasil dihapus.');
    }

    /**
     * Endpoint untuk modal history Upah (JSON).
     * Mengembalikan maksimal 50 baris terbaru.
     *
     * Output contoh tiap baris:
     * [
     *   'tanggal'    => '2025-10-01T10:00:00',
     *   'harga_lama' => 100000,
     *   'harga_baru' => 110000,
     *   'satuan'     => 'hari',
     *   'keterangan' => 'survey lapangan',
     *   'user_name'  => 'Admin'
     * ]
     */
    public function history($id)
    {
        $upah = HsdUpah::findOrFail($id);

        $rows = $upah->histories()
            ->orderByDesc('tanggal_berlaku')
            ->limit(50)
            ->get(['id','harga_satuan','harga_baru','tanggal_berlaku','sumber','updated_by','created_at']);

        $userMap = collect();
        if ($rows->pluck('updated_by')->filter()->isNotEmpty()) {
            $userMap = User::whereIn('id', $rows->pluck('updated_by')->filter()->unique())
                ->pluck('name', 'id');
        }

        $payload = $rows->map(function ($r) use ($upah, $userMap) {
            return [
                'tanggal'    => $r->tanggal_berlaku ?? $r->created_at,
                'harga_lama' => $r->harga_satuan, // old price
                'harga_baru' => $r->harga_baru,   // new price
                'satuan'     => $upah->satuan,
                'keterangan' => $r->sumber,
                'user_name'  => $userMap[$r->updated_by] ?? null,
            ];
        });

        return response()->json($payload, 200);
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AhspHeader;
use App\Models\AhspDetail;
use App\Models\AhspKategori;
use App\Models\HsdMaterial;
use App\Models\HsdUpah;
use App\Models\ActivityLog;           // <-- tambahkan
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AhspController extends Controller
{
    public function index()
    {
        // Tidak load data lagi, akan di-load via AJAX untuk performa lebih baik
        return view('ahsp.index');
    }

    /**
     * Server-side DataTables untuk Material
     */
    public function getMaterialData(Request $request)
    {
        $query = HsdMaterial::query();

        // Search
        if ($search = $request->input('search.value')) {
            $query->where(function($q) use ($search) {
                $q->where('kode', 'like', "%{$search}%")
                  ->orWhere('nama', 'like', "%{$search}%")
                  ->orWhere('satuan', 'like', "%{$search}%")
                  ->orWhere('keterangan', 'like', "%{$search}%");
            });
        }

        // Total records
        $totalFiltered = $query->count();
        $totalData = HsdMaterial::count();

        // Order
        $orderColumn = $request->input('order.0.column', 0);
        $orderDir = $request->input('order.0.dir', 'asc');
        $columns = ['kode', 'nama', 'satuan', 'harga_satuan', 'keterangan'];
        if (isset($columns[$orderColumn])) {
            $query->orderBy($columns[$orderColumn], $orderDir);
        }

        // Pagination
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);
        $materials = $query->skip($start)->take($length)->get();

        // Format data
        $data = $materials->map(function($material) {
            return [
                'id' => $material->id,
                'kode' => $material->kode,
                'nama' => $material->nama,
                'satuan' => $material->satuan,
                'harga_satuan' => $material->harga_satuan,
                'harga_satuan_formatted' => 'Rp ' . number_format($material->harga_satuan, 0, ',', '.'),
                'keterangan' => $material->keterangan ?? '',
            ];
        });

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $totalData,
            'recordsFiltered' => $totalFiltered,
            'data' => $data
        ]);
    }

    /**
     * Server-side DataTables untuk Upah
     */
    public function getUpahData(Request $request)
    {
        $query = HsdUpah::query();

        // Search
        if ($search = $request->input('search.value')) {
            $query->where(function($q) use ($search) {
                $q->where('kode', 'like', "%{$search}%")
                  ->orWhere('jenis_pekerja', 'like', "%{$search}%")
                  ->orWhere('satuan', 'like', "%{$search}%")
                  ->orWhere('keterangan', 'like', "%{$search}%");
            });
        }

        // Total records
        $totalFiltered = $query->count();
        $totalData = HsdUpah::count();

        // Order
        $orderColumn = $request->input('order.0.column', 0);
        $orderDir = $request->input('order.0.dir', 'asc');
        $columns = ['kode', 'jenis_pekerja', 'satuan', 'harga_satuan', 'keterangan'];
        if (isset($columns[$orderColumn])) {
            $query->orderBy($columns[$orderColumn], $orderDir);
        }

        // Pagination
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);
        $upahs = $query->skip($start)->take($length)->get();

        // Format data
        $data = $upahs->map(function($upah) {
            return [
                'id' => $upah->id,
                'kode' => $upah->kode,
                'jenis_pekerja' => $upah->jenis_pekerja,
                'satuan' => $upah->satuan,
                'harga_satuan' => $upah->harga_satuan,
                'harga_satuan_formatted' => 'Rp ' . number_format($upah->harga_satuan, 0, ',', '.'),
                'keterangan' => $upah->keterangan ?? '',
            ];
        });

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $totalData,
            'recordsFiltered' => $totalFiltered,
            'data' => $data
        ]);
    }

    /**
     * Server-side DataTables untuk AHSP
     */
    public function getAhspData(Request $request)
    {
        $query = AhspHeader::with('kategori');

        // Search
        if ($search = $request->input('search.value')) {
            $query->where(function($q) use ($search) {
                $q->where('kode_pekerjaan', 'like', "%{$search}%")
                  ->orWhere('nama_pekerjaan', 'like', "%{$search}%")
                  ->orWhere('satuan', 'like', "%{$search}%")
                  ->orWhereHas('kategori', function($qq) use ($search) {
                      $qq->where('nama', 'like', "%{$search}%");
                  });
            });
        }

        // Total records
        $totalFiltered = $query->count();
        $totalData = AhspHeader::count();

        // Order
        $orderColumn = $request->input('order.0.column', 0);
        $orderDir = $request->input('order.0.dir', 'asc');
        $columns = ['id', 'kode_pekerjaan', 'nama_pekerjaan', 'kategori_id', 'satuan', 'total_harga', 'total_harga_pembulatan', 'is_locked'];
        if (isset($columns[$orderColumn])) {
            $query->orderBy($columns[$orderColumn], $orderDir);
        } else {
            $query->orderBy('kode_pekerjaan', 'asc');
        }

        // Pagination
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);
        $ahsps = $query->skip($start)->take($length)->get();

        // Format data
        $data = $ahsps->map(function($ahsp) {
            return [
                'id' => $ahsp->id,
                'kode_pekerjaan' => $ahsp->kode_pekerjaan,
                'nama_pekerjaan' => $ahsp->nama_pekerjaan,
                'kategori' => $ahsp->kategori->nama ?? '-',
                'satuan' => $ahsp->satuan,
                'total_harga' => $ahsp->total_harga,
                'total_harga_formatted' => 'Rp ' . number_format($ahsp->total_harga, 0, ',', '.'),
                'total_harga_pembulatan' => $ahsp->total_harga_pembulatan ?? 0,
                'total_pembulatan_formatted' => 'Rp ' . number_format($ahsp->total_harga_pembulatan ?? 0, 0, ',', '.'),
                'is_locked' => $ahsp->is_locked,
            ];
        });

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $totalData,
            'recordsFiltered' => $totalFiltered,
            'data' => $data
        ]);
    }

    public function create()
    {
        $nextKode = AhspHeader::generateKode();
        $kategoris = AhspKategori::all();
        $materials = HsdMaterial::orderBy('nama')->get();
        $upahs = HsdUpah::orderBy('jenis_pekerja')->get();
        return view('ahsp.create', compact('nextKode', 'kategoris', 'materials', 'upahs'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_pekerjaan' => 'required|string|max:255',
            'satuan'         => 'required|string|max:20',
            'kategori_id'    => 'nullable|exists:ahsp_kategori,id',
            'items'          => 'required|array|min:1',
            'items.*.tipe'   => 'required|in:material,upah',
            'items.*.referensi_id' => 'required|numeric',
            'items.*.koefisien' => 'required|numeric|min:0',
            'items.*.diskon_persen' => 'nullable|numeric|min:0|max:100',
            'items.*.ppn_persen' => 'nullable|numeric|min:0|max:100',
            'total_harga_pembulatan' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            // Auto-generate kode
            $kode = AhspHeader::generateKode();
            
            $total_harga_sebenarnya = 0;

            foreach ($request->items as $item) {
                $harga_satuan = $item['tipe'] === 'material'
                    ? HsdMaterial::findOrFail($item['referensi_id'])->harga_satuan
                    : HsdUpah::findOrFail($item['referensi_id'])->harga_satuan;

                $subtotal = $harga_satuan * $item['koefisien'];
                $diskonPersen = (float)($item['diskon_persen'] ?? 0);
                $ppnPersen = (float)($item['ppn_persen'] ?? 0);
                
                // Normalize percentage: if input is 0-1 range (decimal), multiply by 100
                if ($diskonPersen > 0 && $diskonPersen < 1) {
                    $diskonPersen = $diskonPersen * 100;
                }
                if ($ppnPersen > 0 && $ppnPersen < 1) {
                    $ppnPersen = $ppnPersen * 100;
                }
                
                // Hitung subtotal final dengan diskon & ppn
                $diskonNominal = $subtotal * ($diskonPersen / 100);
                $subtotalSetelahDiskon = $subtotal - $diskonNominal;
                $ppnNominal = $subtotalSetelahDiskon * ($ppnPersen / 100);
                $subtotalFinal = $subtotalSetelahDiskon + $ppnNominal;

                $total_harga_sebenarnya += $subtotalFinal;
            }

            $rounded = (int) ceil($total_harga_sebenarnya / 1000) * 1000;

            $header = AhspHeader::create([
                'kode_pekerjaan' => $kode,
                'nama_pekerjaan' => $request->nama_pekerjaan,
                'satuan'         => $request->satuan,
                'kategori_id'    => $request->kategori_id,
                'total_harga'    => $total_harga_sebenarnya,
                'total_harga_pembulatan' => $rounded,
                'is_locked'      => false,
            ]);

            foreach ($request->items as $item) {
                $harga_satuan = $item['tipe'] === 'material'
                    ? HsdMaterial::findOrFail($item['referensi_id'])->harga_satuan
                    : HsdUpah::findOrFail($item['referensi_id'])->harga_satuan;

                $diskonPersen = (float)($item['diskon_persen'] ?? 0);
                $ppnPersen = (float)($item['ppn_persen'] ?? 0);
                
                // Normalize percentage: if input is 0-1 range (decimal), multiply by 100
                if ($diskonPersen > 0 && $diskonPersen < 1) {
                    $diskonPersen = $diskonPersen * 100;
                }
                if ($ppnPersen > 0 && $ppnPersen < 1) {
                    $ppnPersen = $ppnPersen * 100;
                }

                AhspDetail::create([
                    'ahsp_id'      => $header->id,
                    'tipe'         => $item['tipe'],
                    'referensi_id' => $item['referensi_id'],
                    'koefisien'    => $item['koefisien'],
                    'harga_satuan' => $harga_satuan,
                    'subtotal'     => $harga_satuan * $item['koefisien'],
                    'diskon_persen' => $diskonPersen,
                    'ppn_persen' => $ppnPersen,
                    // Model boot akan auto-calculate diskon_nominal, ppn_nominal, subtotal_final
                ]);
            }

            DB::commit();

            // === LOG: create ===
            ActivityLog::create([
                'user_id'     => auth()->id(),
                'event'       => 'ahsp_create',
                'description' => sprintf(
                    'Buat AHSP %s - %s; satuan: %s; kategori_id: %s; items: %d; total: %s; pembulatan: %s',
                    $header->kode_pekerjaan,
                    $header->nama_pekerjaan,
                    $header->satuan,
                    $header->kategori_id ?? '—',
                    count($request->items),
                    number_format($header->total_harga, 0, ',', '.'),
                    number_format($header->total_harga_pembulatan, 0, ',', '.')
                ),
                'ip_address'  => $request->ip(),
                'user_agent'  => $request->userAgent(),
            ]);

            return redirect()->route('ahsp.index', ['tab' => 'ahsp'])->with('success', 'Data AHSP berhasil disimpan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Gagal menyimpan AHSP: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $ahsp = AhspHeader::with(['kategori', 'details'])->findOrFail($id);
        return view('ahsp.show', compact('ahsp'));
    }

    public function edit($id)
    {
        $ahsp = AhspHeader::with('details')->findOrFail($id);

        if ($ahsp->is_locked) {
            return redirect()->route('ahsp.index')->with('error', 'AHSP ini sudah terkunci dan tidak dapat diedit.');
        }

        $kategoris = AhspKategori::all();
        $materials = HsdMaterial::orderBy('nama')->get();
        $upahs = HsdUpah::orderBy('jenis_pekerja')->get();

        return view('ahsp.edit', compact('ahsp', 'kategoris', 'materials', 'upahs'));
    }

    public function destroy($id)
    {
        $ahsp = AhspHeader::findOrFail($id);

        if ($ahsp->is_locked) {
            return redirect()->route('ahsp.index')->with('error', 'Data sudah digunakan dan tidak dapat dihapus.');
        }

        $kode = $ahsp->kode_pekerjaan;
        $nama = $ahsp->nama_pekerjaan;

        $ahsp->delete();

        // === LOG: delete ===
        ActivityLog::create([
            'user_id'     => auth()->id(),
            'event'       => 'ahsp_delete',
            'description' => sprintf('Hapus AHSP %s - %s', $kode, $nama),
            'ip_address'  => request()->ip(),
            'user_agent'  => request()->userAgent(),
        ]);

        return redirect()->route('ahsp.index', ['tab' => 'ahsp'])->with('success', 'Data AHSP berhasil dihapus.');
    }

    public function update(Request $request, $id)
    {
        $ahsp = AhspHeader::with('details')->findOrFail($id);

        if ($ahsp->is_locked) {
            return redirect()->route('ahsp.index', ['tab' => 'ahsp'])->with('error', 'Data AHSP ini sudah terkunci dan tidak dapat diedit.');
        }

        $request->validate([
            'nama_pekerjaan' => 'required|string|max:255',
            'satuan'         => 'required|string|max:20',
            'kategori_id'    => 'nullable|exists:ahsp_kategori,id',
            'items'          => 'required|array|min:1',
            'items.*.tipe'   => 'required|in:material,upah',
            'items.*.referensi_id' => 'required|numeric',
            'items.*.koefisien' => 'required|numeric|min:0',
            'items.*.diskon_persen' => 'nullable|numeric|min:0|max:100',
            'items.*.ppn_persen' => 'nullable|numeric|min:0|max:100',
            'total_harga_pembulatan' => 'required|numeric|min:0',
        ]);

        // simpan nilai sebelum update untuk log
        $beforeTotal   = $ahsp->total_harga;
        $beforeRounded = $ahsp->total_harga_pembulatan;
        $beforeItems   = $ahsp->details->count();

        DB::beginTransaction();
        try {
            $total_harga_sebenarnya = 0;

            foreach ($request->items as $item) {
                $harga_satuan = $item['tipe'] === 'material'
                    ? HsdMaterial::findOrFail($item['referensi_id'])->harga_satuan
                    : HsdUpah::findOrFail($item['referensi_id'])->harga_satuan;

                $subtotal = $harga_satuan * $item['koefisien'];
                
                // Calculate diskon & ppn
                $diskon_persen = isset($item['diskon_persen']) ? (float)$item['diskon_persen'] : 0;
                $ppn_persen = isset($item['ppn_persen']) ? (float)$item['ppn_persen'] : 0;
                
                // Normalize percentage: if input is 0-1 range (decimal), multiply by 100
                if ($diskon_persen > 0 && $diskon_persen < 1) {
                    $diskon_persen = $diskon_persen * 100;
                }
                if ($ppn_persen > 0 && $ppn_persen < 1) {
                    $ppn_persen = $ppn_persen * 100;
                }
                
                $diskon_nominal = $subtotal * ($diskon_persen / 100);
                $subtotal_setelah_diskon = $subtotal - $diskon_nominal;
                $ppn_nominal = $subtotal_setelah_diskon * ($ppn_persen / 100);
                $subtotal_final = $subtotal_setelah_diskon + $ppn_nominal;
                
                $total_harga_sebenarnya += $subtotal_final;
            }

            $rounded = (int) ceil($total_harga_sebenarnya / 1000) * 1000;

            $ahsp->update([
                'nama_pekerjaan' => $request->nama_pekerjaan,
                'satuan'         => $request->satuan,
                'kategori_id'    => $request->kategori_id,
                'total_harga'    => $total_harga_sebenarnya,
                'total_harga_pembulatan' => $rounded,
            ]);

            // reset detail
            $ahsp->details()->delete();

            foreach ($request->items as $item) {
                $harga_satuan = $item['tipe'] === 'material'
                    ? HsdMaterial::findOrFail($item['referensi_id'])->harga_satuan
                    : HsdUpah::findOrFail($item['referensi_id'])->harga_satuan;

                $diskon_persen = isset($item['diskon_persen']) ? (float)$item['diskon_persen'] : 0;
                $ppn_persen = isset($item['ppn_persen']) ? (float)$item['ppn_persen'] : 0;
                
                // Normalize percentage: if input is 0-1 range (decimal), multiply by 100
                if ($diskon_persen > 0 && $diskon_persen < 1) {
                    $diskon_persen = $diskon_persen * 100;
                }
                if ($ppn_persen > 0 && $ppn_persen < 1) {
                    $ppn_persen = $ppn_persen * 100;
                }

                AhspDetail::create([
                    'ahsp_id'      => $ahsp->id,
                    'tipe'         => $item['tipe'],
                    'referensi_id' => $item['referensi_id'],
                    'koefisien'    => $item['koefisien'],
                    'harga_satuan' => $harga_satuan,
                    'subtotal'     => $harga_satuan * $item['koefisien'],
                    'diskon_persen' => $diskon_persen,
                    'ppn_persen' => $ppn_persen,
                ]);
            }

            DB::commit();

            // refresh & logging
            $ahsp->refresh();
            $afterItems = $ahsp->details()->count();

            // === LOG: update ===
            ActivityLog::create([
                'user_id'     => auth()->id(),
                'event'       => 'ahsp_update',
                'description' => sprintf(
                    'Update AHSP %s - %s; total: %s → %s; pembulatan: %s → %s; items: %d → %d',
                    $ahsp->kode_pekerjaan,
                    $ahsp->nama_pekerjaan,
                    number_format($beforeTotal, 0, ',', '.'),
                    number_format($ahsp->total_harga, 0, ',', '.'),
                    number_format($beforeRounded, 0, ',', '.'),
                    number_format($ahsp->total_harga_pembulatan, 0, ',', '.'),
                    $beforeItems,
                    $afterItems
                ),
                'ip_address'  => $request->ip(),
                'user_agent'  => $request->userAgent(),
            ]);

            return redirect()->route('ahsp.index', ['tab' => 'ahsp'])->with('success', 'Data AHSP berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Gagal memperbarui AHSP: ' . $e->getMessage());
        }
    }

    public function duplicate($id)
    {
        $originalAhsp = AhspHeader::with('details')->find($id);

        if (!$originalAhsp) {
            return redirect()->back()->with('error', 'AHSP yang ingin diduplikasi tidak ditemukan.');
        }

        DB::beginTransaction();
        try {
            $newAhsp = $originalAhsp->replicate();
            $newAhsp->kode_pekerjaan = $originalAhsp->kode_pekerjaan . '_copy_' . Str::random(4);
            $newAhsp->nama_pekerjaan = $originalAhsp->nama_pekerjaan . ' (Salinan)';
            $newAhsp->is_locked = false;
            $newAhsp->save();

            foreach ($originalAhsp->details as $detail) {
                $newDetail = $detail->replicate();
                $newDetail->ahsp_id = $newAhsp->id;
                $newDetail->save();
            }

            DB::commit();

            // === LOG: duplicate ===
            ActivityLog::create([
                'user_id'     => auth()->id(),
                'event'       => 'ahsp_duplicate',
                'description' => sprintf(
                    'Duplikasi AHSP %s → %s (%s)',
                    $originalAhsp->kode_pekerjaan,
                    $newAhsp->kode_pekerjaan,
                    $newAhsp->nama_pekerjaan
                ),
                'ip_address'  => request()->ip(),
                'user_agent'  => request()->userAgent(),
            ]);

            return redirect()->route('ahsp.show', $newAhsp->id)->with('success', 'AHSP berhasil diduplikasi menjadi: ' . $newAhsp->nama_pekerjaan);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal menduplikasi AHSP: ' . $e->getMessage());
        }
    }

    // AJAX Select2
    public function search(Request $request)
    {
        $q = AhspHeader::query();

        if ($request->filled('search')) {
            $term = '%'.$request->search.'%';
            $q->where(function ($w) use ($term) {
                $w->where('kode_pekerjaan', 'like', $term)
                  ->orWhere('nama_pekerjaan', 'like', $term);
            });
        }

        if ($request->filled('id'))          $q->where('id', $request->id);
        if ($request->filled('kategori_id')) $q->where('kategori_id', $request->kategori_id);

        $rows = $q->orderBy('kode_pekerjaan')->limit(50)
          ->get(['id','kode_pekerjaan','nama_pekerjaan','satuan','total_harga','total_harga_pembulatan']);

        return response()->json([
            'results' => $rows->map(function ($a) {
                $rounded = (int) ($a->total_harga_pembulatan ?? ceil(($a->total_harga ?? 0)/1000)*1000);
                return [
                    'id'   => $a->id,
                    'text' => $a->kode_pekerjaan.' - '.$a->nama_pekerjaan,
                    'satuan' => $a->satuan,
                    'harga_satuan_pembulatan' => $rounded,
                ];
            }),
        ]);
    }
}

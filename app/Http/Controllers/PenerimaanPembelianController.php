<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PenerimaanPembelian;
use App\Models\PenerimaanPembelianDetail;
use App\Models\Po;
use App\Models\PoDetail;
use DB;

class PenerimaanPembelianController extends Controller
{
    public function index()
    {
        $penerimaans = PenerimaanPembelian::with(['po', 'supplier', 'proyek'])
                        ->orderByDesc('tanggal')
                        ->get();
        
        return view('penerimaan.index', compact('penerimaans'));
    }

    public function create($po_id)
    {
        $po = Po::with(['poDetails.barang', 'supplier', 'proyek'])->findOrFail($po_id);
        
        // Validasi: cek apakah masih ada item yang belum diterima sepenuhnya
        $adaSisaPenerimaan = $po->poDetails->some(function ($detail) {
            return $detail->qty_diterima < $detail->qty;
        });

        if (!$adaSisaPenerimaan) {
            return redirect()->route('po.index')
                ->with('warning', 'Semua item dalam PO ini sudah diterima sepenuhnya.');
        }

        return view('penerimaan.create', compact('po'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'po_id'            => 'required|exists:po,id',
            'tanggal'          => 'required|date',
            'no_penerimaan'    => 'required|unique:penerimaan_pembelian,no_penerimaan',
            'items'            => 'required|array|min:1',
            'file_surat_jalan' => 'nullable|file|mimes:pdf|max:2048', // 2MB max
        ]);

        DB::beginTransaction();
        try {
            $po = Po::with('poDetails')->findOrFail($request->po_id);

            // Handle file upload
            $filePath = null;
            if ($request->hasFile('file_surat_jalan')) {
                $file = $request->file('file_surat_jalan');
                $fileName = 'surat_jalan_' . time() . '_' . $file->getClientOriginalName();
                $filePath = $file->storeAs('surat_jalan', $fileName, 'public');
            }

            // Buat Header Penerimaan
            $penerimaan = PenerimaanPembelian::create([
                'no_penerimaan'    => $request->no_penerimaan,
                'tanggal'          => $request->tanggal,
                'po_id'            => $po->id,
                'id_supplier'      => $po->id_supplier,
                'nama_supplier'    => $po->nama_supplier,
                'id_proyek'        => $po->id_proyek,
                'id_perusahaan'    => $po->id_perusahaan,
                'dibuat_oleh'      => auth()->id(),
                'dibuat_at'        => now(),
                'keterangan'       => $request->keterangan,
                'no_surat_jalan'   => $request->no_surat_jalan,
                'file_surat_jalan' => $filePath,
                'status'           => 'draft',
            ]);

            // Simpan Detail Penerimaan
            foreach ($request->items as $item) {
                $qtyDiterima = floatval($item['qty_diterima'] ?? 0);
                if ($qtyDiterima <= 0) continue;

                $poDetail = $po->poDetails->firstWhere('id', $item['po_detail_id']);
                if (!$poDetail) continue;

                // Validasi: qty_diterima tidak boleh melebihi sisa
                $sisaPenerimaan = $poDetail->qty - $poDetail->qty_diterima;
                if ($qtyDiterima > $sisaPenerimaan) {
                    throw new \Exception("Qty diterima untuk item {$poDetail->kode_item} melebihi sisa PO.");
                }

                // Simpan detail penerimaan
                PenerimaanPembelianDetail::create([
                    'penerimaan_id'  => $penerimaan->id,
                    'po_detail_id'   => $poDetail->id,
                    'kode_item'      => $poDetail->kode_item,
                    'uraian'         => $poDetail->uraian,
                    'qty_po'         => $poDetail->qty,
                    'qty_diterima'   => $qtyDiterima,
                    'uom'            => $poDetail->uom,
                    'keterangan'     => $item['keterangan'] ?? null,
                ]);

                // Update qty_diterima di po_detail
                $poDetail->qty_diterima += $qtyDiterima;
                $poDetail->save();
            }

            DB::commit();
            return redirect()->route('penerimaan.index')
                ->with('success', 'Penerimaan pembelian berhasil dicatat.');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->withInput()
                ->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $penerimaan = PenerimaanPembelian::with(['details', 'po', 'supplier', 'proyek'])
                        ->findOrFail($id);
        
        return view('penerimaan.show', compact('penerimaan'));
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $penerimaan = PenerimaanPembelian::with('details')->findOrFail($id);

            // Cek apakah sudah ada retur
            if ($penerimaan->returs()->count() > 0) {
                return back()->with('error', 'Tidak bisa menghapus penerimaan yang sudah ada retur.');
            }

            // Cek apakah sudah ada alokasi ke faktur
            $adaTerfaktur = $penerimaan->details->sum('qty_terfaktur') > 0;
            if ($adaTerfaktur) {
                return back()->with('error', 'Tidak bisa menghapus penerimaan yang sudah dialokasikan ke faktur.');
            }

            // Hapus file surat jalan jika ada
            if ($penerimaan->file_surat_jalan && \Storage::disk('public')->exists($penerimaan->file_surat_jalan)) {
                \Storage::disk('public')->delete($penerimaan->file_surat_jalan);
            }

            // Kembalikan qty_diterima di po_detail
            foreach ($penerimaan->details as $detail) {
                $poDetail = PoDetail::find($detail->po_detail_id);
                if ($poDetail) {
                    $poDetail->qty_diterima -= $detail->qty_diterima;
                    if ($poDetail->qty_diterima < 0) $poDetail->qty_diterima = 0;
                    $poDetail->save();
                }
            }

            // Hapus penerimaan
            $penerimaan->details()->delete();
            $penerimaan->delete();

            DB::commit();
            return redirect()->route('penerimaan.index')
                ->with('success', 'Penerimaan berhasil dihapus.');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    public function approve($id)
    {
        $penerimaan = PenerimaanPembelian::with('details')->findOrFail($id);

        if ($penerimaan->status === 'approved') {
            return redirect()->route('penerimaan.show', $penerimaan->id)
                ->with('info', 'Penerimaan sudah disetujui.');
        }

        // Minimal guard: tidak boleh approve jika qty_diterima keseluruhan 0
        $totalDiterima = $penerimaan->details->sum('qty_diterima');
        if ($totalDiterima <= 0) {
            return redirect()->route('penerimaan.show', $penerimaan->id)
                ->with('error', 'Tidak dapat menyetujui penerimaan tanpa qty diterima.');
        }

        $penerimaan->status = 'approved';
        $penerimaan->disetujui_oleh = auth()->id();
        $penerimaan->disetujui_at = now();
        // Set status penagihan awal berdasarkan qty_terfaktur saat ini
        $sumTerfaktur = $penerimaan->details->sum('qty_terfaktur');
        if ($sumTerfaktur >= $totalDiterima) {
            $penerimaan->status_penagihan = 'lunas';
        } elseif ($sumTerfaktur > 0) {
            $penerimaan->status_penagihan = 'sebagian';
        } else {
            $penerimaan->status_penagihan = 'belum';
        }
        $penerimaan->save();

        return redirect()->route('penerimaan.show', $penerimaan->id)
            ->with('success', 'Penerimaan berhasil disetujui.');
    }

    public function revisi($id)
    {
        $penerimaan = PenerimaanPembelian::with(['details', 'returs'])->findOrFail($id);

        if ($penerimaan->status !== 'approved') {
            return redirect()->route('penerimaan.show', $penerimaan->id)
                ->with('warning', 'Hanya penerimaan yang sudah di-approve yang bisa direvisi.');
        }

        // Tidak boleh revisi jika sudah ada alokasi faktur
        $adaTerfaktur = $penerimaan->details->sum('qty_terfaktur') > 0;
        if ($adaTerfaktur) {
            return redirect()->route('penerimaan.show', $penerimaan->id)
                ->with('error', 'Tidak dapat merevisi penerimaan yang sudah dialokasikan ke faktur. Hapus faktur terlebih dahulu.');
        }

        // Tidak boleh revisi jika ada retur terkait
        if (method_exists($penerimaan, 'returs') && $penerimaan->returs()->exists()) {
            return redirect()->route('penerimaan.show', $penerimaan->id)
                ->with('error', 'Tidak dapat merevisi penerimaan yang sudah memiliki retur.');
        }

        $penerimaan->status = 'draft';
        $penerimaan->status_penagihan = 'belum';
        $penerimaan->disetujui_oleh = null;
        $penerimaan->disetujui_at = null;
        $penerimaan->save();

        return redirect()->route('penerimaan.show', $penerimaan->id)
            ->with('success', 'Penerimaan dikembalikan ke status draft untuk direvisi.');
    }

    public function viewSuratJalan($id)
    {
        $penerimaan = PenerimaanPembelian::findOrFail($id);
        
        if (!$penerimaan->file_surat_jalan || !\Storage::disk('public')->exists($penerimaan->file_surat_jalan)) {
            return back()->with('error', 'File surat jalan tidak ditemukan.');
        }

        $path = storage_path('app/public/' . $penerimaan->file_surat_jalan);
        return response()->file($path);
    }
}


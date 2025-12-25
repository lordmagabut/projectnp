<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Faktur;
use App\Models\FakturDetail;
use App\Models\Po;
use App\Models\Supplier;
use App\Models\Perusahaan;
use App\Models\Proyek;
use App\Models\Jurnal;
use App\Models\JurnalDetail;
use App\Models\PenerimaanPembelian;
use App\Models\PenerimaanPembelianDetail;
use App\Models\ReturPembelianDetail;
use App\Services\AccountService;
use App\Models\Coa;
use App\Models\AccountMapping;

class FakturController extends Controller
{
    public function index()
    {
        $fakturs = Faktur::with(['proyek', 'perusahaan'])->orderByDesc('tanggal')->get();
        return view('faktur.index', compact('fakturs'));
    }

    public function create()
    {
        $suppliers = Supplier::all();
        $perusahaans = Perusahaan::all();
        $proyeks = Proyek::all();
        return view('faktur.create', compact('suppliers', 'perusahaans', 'proyeks'));
    }

    public function createFromPo($id)
    {
        $po = Po::with(['poDetails.barang'])->findOrFail($id);

        // Periksa penerimaan approved dikurangi retur approved
        $adaApprovedQtyBelumDifaktur = $po->poDetails->some(function ($detail) {
            $qtyApproved = PenerimaanPembelianDetail::where('po_detail_id', $detail->id)
                ->whereHas('penerimaan', function ($q) { $q->where('status', 'approved'); })
                ->sum('qty_diterima');
            $qtyReturApproved = ReturPembelianDetail::whereHas('retur', function($q){ $q->where('status','approved'); })
                ->whereHas('penerimaanDetail', function($q) use ($detail){ $q->where('po_detail_id', $detail->id); })
                ->sum('qty_retur');
            $netApproved = max(0, $qtyApproved - $qtyReturApproved);
            return ($netApproved - $detail->qty_terfaktur) > 0;
        });

        if (!$adaApprovedQtyBelumDifaktur) {
            return redirect()->route('po.index')->with('warning', 'Belum ada penerimaan approved atau semua qty approved sudah difakturkan.');
        }

        return view('faktur.create-from-po', compact('po'));
    }

    public function createFromPenerimaan($id)
    {
        $penerimaan = PenerimaanPembelian::with(['details.poDetail.barang', 'po'])->findOrFail($id);

        // Cek apakah penerimaan sudah approved
        if ($penerimaan->status !== 'approved') {
            return redirect()->route('penerimaan.index')
                           ->with('warning', 'Penerimaan harus sudah di-approve untuk membuat faktur.');
        }

        // Cek apakah ada qty yang belum difaktur
        $adaBelumDifaktur = $penerimaan->details->some(function ($detail) {
            $qtyReturApproved = ReturPembelianDetail::where('penerimaan_detail_id', $detail->id)
                ->whereHas('retur', function($q){ $q->where('status','approved'); })
                ->sum('qty_retur');
            $netQty = $detail->qty_diterima - $qtyReturApproved;
            return ($netQty - $detail->qty_terfaktur) > 0;
        });

        if (!$adaBelumDifaktur) {
            return redirect()->route('penerimaan.index')
                           ->with('warning', 'Semua qty pada penerimaan ini sudah difakturkan.');
        }

        return view('faktur.create-from-penerimaan', compact('penerimaan'));
    }

    public function store(Request $request)
{
    $request->validate([
        'no_faktur'   => 'required',
        'tanggal'     => 'required|date',
        'file_path'   => 'nullable|file|mimes:pdf|max:30000',
    ]);

    $filePath = null;
    if ($request->hasFile('file_path')) {
        $filePath = $request->file('file_path')->store('faktur', 'public');
    }

    // === FAKTUR DARI PO ===
    if ($request->has('po_id')) {
        $po = \App\Models\Po::with(['poDetails.barang'])->findOrFail($request->po_id);

        $faktur = new \App\Models\Faktur();
        $faktur->no_faktur      = $request->no_faktur;
        $faktur->tanggal        = $request->tanggal;
        $faktur->id_po          = $po->id;
        $faktur->id_supplier    = $po->id_supplier;
        $faktur->nama_supplier  = $po->nama_supplier;
        $faktur->id_perusahaan  = $po->id_perusahaan;
        $faktur->id_proyek      = $po->id_proyek;
        $faktur->file_path      = $filePath;
        $faktur->status         = 'draft';
        $faktur->subtotal       = 0;
        $faktur->total_diskon   = 0;
        $faktur->total_ppn      = 0;
        $faktur->total          = 0;
        $faktur->save();

        $diskonPersenGlobal = floatval($request->diskon_persen ?? 0);
        $ppnPersenGlobal    = floatval($request->ppn_persen ?? 0);

        foreach ($request->items as $item) {
            logger($item);

            $qtyFaktur = floatval($item['qty'] ?? 0);
            if ($qtyFaktur <= 0) continue;

            $poDetail = $po->poDetails->firstWhere('id', $item['po_detail_id'] ?? null);
            if (!$poDetail) continue;

            $barang = $poDetail->barang; // Ambil relasi barang dari kode_item

            // VALIDASI OPSI A (approved only): Faktur tidak boleh melebihi qty yang sudah diterima dan disetujui
            $qtyDiterima = PenerimaanPembelianDetail::where('po_detail_id', $poDetail->id)
                ->whereHas('penerimaan', function ($q) { $q->where('status', 'approved'); })
                ->sum('qty_diterima');
            $qtyReturApproved = ReturPembelianDetail::whereHas('retur', function($q){ $q->where('status','approved'); })
                ->whereHas('penerimaanDetail', function($q) use ($poDetail){ $q->where('po_detail_id', $poDetail->id); })
                ->sum('qty_retur');
            $qtyDiterima -= $qtyReturApproved;
            $qtyTerfaktur = $poDetail->qty_terfaktur;
            $sisaBisaDifaktur = $qtyDiterima - $qtyTerfaktur;

            if ($sisaBisaDifaktur <= 0) {
                continue; // Skip item yang sudah difaktur semua
            }

            if ($qtyFaktur > $sisaBisaDifaktur) {
                throw new \Exception("Item {$poDetail->kode_item}: Qty faktur ({$qtyFaktur}) melebihi qty yang sudah diterima dan belum difaktur ({$sisaBisaDifaktur}). Qty diterima: {$qtyDiterima}, Sudah terfaktur: {$qtyTerfaktur}");
            }

            $qtyDipakai = $qtyFaktur;
            if ($qtyDipakai <= 0) continue;

            $harga         = floatval($item['harga']);
            $subtotalBaris = $qtyDipakai * $harga;
            $diskonRupiah  = $subtotalBaris * $diskonPersenGlobal / 100;
            $afterDiskon   = $subtotalBaris - $diskonRupiah;
            $ppnRupiah     = $afterDiskon * $ppnPersenGlobal / 100;
            $totalBaris    = $afterDiskon + $ppnRupiah;

            // Validasi & fallback COA IDs agar tidak melanggar FK
            $coaBebanId = $barang?->coa_beban_id;
            if ($coaBebanId && !Coa::whereKey($coaBebanId)->exists()) { $coaBebanId = null; }

            $coaPersediaanId = $barang?->coa_persediaan_id;
            if ($coaPersediaanId && !Coa::whereKey($coaPersediaanId)->exists()) { $coaPersediaanId = null; }

            $coaHppId = $barang?->coa_hpp_id;
            if ($coaHppId && !Coa::whereKey($coaHppId)->exists()) { $coaHppId = null; }

            // Fallback ke global mapping bila tidak ada di barang
            $coaBebanId = $coaBebanId ?? AccountMapping::getCoaId('beban_bahan_baku');
            $coaPersediaanId = $coaPersediaanId ?? AccountMapping::getCoaId('persediaan_bahan_baku');
            // Jika tidak ada akun HPP spesifik, gunakan beban bahan baku sebagai default
            $coaHppId = $coaHppId ?? AccountMapping::getCoaId('beban_bahan_baku');

            $faktur->details()->create([
                'po_detail_id'       => $poDetail->id,
                'kode_item'          => $item['kode_item'] ?? '',
                'uraian'             => $item['uraian'] ?? '',
                'qty'                => $qtyDipakai,
                'uom'                => $item['uom'] ?? '',
                'harga'              => $harga,
                'diskon_persen'      => $diskonPersenGlobal,
                'diskon_rupiah'      => $diskonRupiah,
                'ppn_persen'         => $ppnPersenGlobal,
                'ppn_rupiah'         => $ppnRupiah,
                'total'              => $totalBaris,
                'coa_beban_id'       => $coaBebanId,
                'coa_persediaan_id'  => $coaPersediaanId,
                'coa_hpp_id'         => $coaHppId,
            ]);

            $poDetail->qty_terfaktur += $qtyDipakai;
            $poDetail->save();

            // Alokasikan qty faktur ke detail penerimaan (FIFO)
            $remaining = $qtyDipakai;
            $receiptDetails = PenerimaanPembelianDetail::where('po_detail_id', $poDetail->id)
                ->whereHas('penerimaan', function ($q) { $q->where('status', 'approved'); })
                ->orderBy('id', 'asc')
                ->get();
            foreach ($receiptDetails as $rd) {
                if ($remaining <= 0) break;
                $returOnDetail = ReturPembelianDetail::where('penerimaan_detail_id', $rd->id)
                    ->whereHas('retur', function($q){ $q->where('status','approved'); })
                    ->sum('qty_retur');
                $available = max(0, ($rd->qty_diterima - $returOnDetail - $rd->qty_terfaktur));
                if ($available <= 0) continue;
                $use = min($available, $remaining);
                $rd->qty_terfaktur += $use;
                $rd->save();
                $remaining -= $use;
            }

            $faktur->subtotal     += $subtotalBaris;
            $faktur->total_diskon += $diskonRupiah;
            $faktur->total_ppn    += $ppnRupiah;
        }

        $faktur->total = $faktur->subtotal - $faktur->total_diskon + $faktur->total_ppn;
        $faktur->save();

        // Recalculate status_penagihan for related receipts in this PO
        $receiptIds = PenerimaanPembelianDetail::whereIn('po_detail_id', $po->poDetails->pluck('id'))
            ->pluck('penerimaan_id')
            ->unique()
            ->values();
        $receipts = PenerimaanPembelian::whereIn('id', $receiptIds)->get();
        foreach ($receipts as $rec) {
            $sumDiterima = $rec->details()->sum('qty_diterima');
            $sumTerfaktur = $rec->details()->sum('qty_terfaktur');
            $statusPenagihan = 'belum';
            if ($sumTerfaktur >= $sumDiterima && $sumDiterima > 0) {
                $statusPenagihan = 'lunas';
            } elseif ($sumTerfaktur > 0) {
                $statusPenagihan = 'sebagian';
            }
            $rec->status_penagihan = $statusPenagihan;
            $rec->save();
        }

        // Ubah status PO jika semua selesai
        if ($po->poDetails->every(fn($d) => $d->qty_terfaktur >= $d->qty)) {
            $po->update(['status' => 'selesai']);
        }

        return redirect()->route('faktur.index')->with('success', 'Faktur berhasil dibuat dari PO.');
    }

    // === FAKTUR DARI PENERIMAAN ===
    if ($request->has('penerimaan_id')) {
        $penerimaan = PenerimaanPembelian::with(['details.poDetail.barang', 'po'])->findOrFail($request->penerimaan_id);
        $po = $penerimaan->po;

        $faktur = new \App\Models\Faktur();
        $faktur->no_faktur      = $request->no_faktur;
        $faktur->tanggal        = $request->tanggal;
        $faktur->id_po          = $po->id;
        $faktur->id_supplier    = $po->id_supplier;
        $faktur->nama_supplier  = $po->nama_supplier;
        $faktur->id_perusahaan  = $po->id_perusahaan;
        $faktur->id_proyek      = $po->id_proyek;
        $faktur->file_path      = $filePath;
        $faktur->status         = 'draft';
        $faktur->subtotal       = 0;
        $faktur->total_diskon   = 0;
        $faktur->total_ppn      = 0;
        $faktur->total          = 0;
        $faktur->save();

        $diskonPersenGlobal = floatval($request->diskon_persen ?? 0);
        $ppnPersenGlobal    = floatval($request->ppn_persen ?? 0);

        foreach ($request->items as $item) {
            $qtyFaktur = floatval($item['qty'] ?? 0);
            if ($qtyFaktur <= 0) continue;

            $penerimaanDetail = PenerimaanPembelianDetail::findOrFail($item['penerimaan_detail_id']);
            $poDetail = $penerimaanDetail->poDetail;
            $barang = $poDetail->barang;

            // VALIDASI: Faktur tidak boleh melebihi qty diterima - retur - sudah terfaktur
            $qtyReturApproved = ReturPembelianDetail::where('penerimaan_detail_id', $penerimaanDetail->id)
                ->whereHas('retur', function($q){ $q->where('status','approved'); })
                ->sum('qty_retur');
            $netQty = $penerimaanDetail->qty_diterima - $qtyReturApproved;
            $sisaBisaDifaktur = $netQty - $penerimaanDetail->qty_terfaktur;

            if ($sisaBisaDifaktur <= 0) {
                continue; // Skip item yang sudah difaktur semua
            }

            if ($qtyFaktur > $sisaBisaDifaktur) {
                throw new \Exception("Item {$poDetail->kode_item}: Qty faktur ({$qtyFaktur}) melebihi qty yang bisa difaktur ({$sisaBisaDifaktur}).");
            }

            $qtyDipakai = $qtyFaktur;
            $harga = floatval($item['harga'] ?? $poDetail->harga ?? 0);
            if ($harga == 0) {
                $harga = floatval($poDetail->harga ?? 0);
            }
            $subtotalBaris = $qtyDipakai * $harga;
            $diskonRupiah  = $subtotalBaris * $diskonPersenGlobal / 100;
            $afterDiskon   = $subtotalBaris - $diskonRupiah;
            $ppnRupiah     = $afterDiskon * $ppnPersenGlobal / 100;
            $totalBaris    = $afterDiskon + $ppnRupiah;

            // Validasi & fallback COA IDs
            $coaBebanId = $barang?->coa_beban_id;
            if ($coaBebanId && !Coa::whereKey($coaBebanId)->exists()) { $coaBebanId = null; }

            $coaPersediaanId = $barang?->coa_persediaan_id;
            if ($coaPersediaanId && !Coa::whereKey($coaPersediaanId)->exists()) { $coaPersediaanId = null; }

            $coaHppId = $barang?->coa_hpp_id;
            if ($coaHppId && !Coa::whereKey($coaHppId)->exists()) { $coaHppId = null; }

            $coaBebanId = $coaBebanId ?? AccountMapping::getCoaId('beban_bahan_baku');
            $coaPersediaanId = $coaPersediaanId ?? AccountMapping::getCoaId('persediaan_bahan_baku');
            $coaHppId = $coaHppId ?? AccountMapping::getCoaId('beban_bahan_baku');

            $faktur->details()->create([
                'po_detail_id'       => $poDetail->id,
                'kode_item'          => $item['kode_item'] ?? '',
                'uraian'             => $item['uraian'] ?? '',
                'qty'                => $qtyDipakai,
                'uom'                => $item['uom'] ?? '',
                'harga'              => $harga,
                'diskon_persen'      => $diskonPersenGlobal,
                'diskon_rupiah'      => $diskonRupiah,
                'ppn_persen'         => $ppnPersenGlobal,
                'ppn_rupiah'         => $ppnRupiah,
                'total'              => $totalBaris,
                'coa_beban_id'       => $coaBebanId,
                'coa_persediaan_id'  => $coaPersediaanId,
                'coa_hpp_id'         => $coaHppId,
            ]);

            // Update qty_terfaktur di penerimaan detail
            $penerimaanDetail->qty_terfaktur += $qtyDipakai;
            $penerimaanDetail->save();

            // Update qty_terfaktur di PO detail
            $poDetail->qty_terfaktur += $qtyDipakai;
            $poDetail->save();

            $faktur->subtotal     += $subtotalBaris;
            $faktur->total_diskon += $diskonRupiah;
            $faktur->total_ppn    += $ppnRupiah;
        }

        $faktur->total = $faktur->subtotal - $faktur->total_diskon + $faktur->total_ppn;
        $faktur->save();

        // Update status_penagihan penerimaan
        $sumDiterima = $penerimaan->details()->sum('qty_diterima');
        $sumTerfaktur = $penerimaan->details()->sum('qty_terfaktur');
        $statusPenagihan = 'belum';
        if ($sumTerfaktur >= $sumDiterima && $sumDiterima > 0) {
            $statusPenagihan = 'lunas';
        } elseif ($sumTerfaktur > 0) {
            $statusPenagihan = 'sebagian';
        }
        $penerimaan->status_penagihan = $statusPenagihan;
        $penerimaan->save();

        // Update status PO jika semua selesai
        if ($po->poDetails->every(fn($d) => $d->qty_terfaktur >= $d->qty)) {
            $po->update(['status' => 'selesai']);
        }

        return redirect()->route('faktur.index')->with('success', 'Faktur berhasil dibuat dari Penerimaan Barang.');
    }

    return back()->with('error', 'Faktur tidak dapat diproses.');
}

    
    public function show($id)
    {
        $faktur = Faktur::with('details')->findOrFail($id);
        return view('faktur.show', compact('faktur'));
    }

public function destroy($id)
{
    $faktur = Faktur::with('details')->findOrFail($id);

    foreach ($faktur->details as $detail) {
        if ($detail->po_detail_id) {
            $poDetail = \App\Models\PoDetail::find($detail->po_detail_id);
            if ($poDetail) {
                $poDetail->qty_terfaktur -= $detail->qty;
                if ($poDetail->qty_terfaktur < 0) $poDetail->qty_terfaktur = 0;
                $poDetail->save();
            }
        }

        // Reverse allocation from receipt details (LIFO)
        $remaining = $detail->qty;
        $receiptDetails = PenerimaanPembelianDetail::where('po_detail_id', $detail->po_detail_id)
            ->orderBy('id', 'desc')
            ->get();
        foreach ($receiptDetails as $rd) {
            if ($remaining <= 0) break;
            $allocated = max(0, $rd->qty_terfaktur);
            if ($allocated <= 0) continue;
            $reduce = min($allocated, $remaining);
            $rd->qty_terfaktur -= $reduce;
            if ($rd->qty_terfaktur < 0) $rd->qty_terfaktur = 0;
            $rd->save();
            $remaining -= $reduce;
        }

        $detail->delete();
    }

    $faktur->delete();

    // Tambahan status PO saat faktur dihapus:
    // - Jika tidak ada penerimaan sama sekali dan semua qty_terfaktur 0 -> draft
    // - Jika masih ada penerimaan dan belum semua terfaktur -> sedang diproses
    // - Jika masih ada penerimaan dan semua terfaktur = qty -> selesai (tetap)
    if ($faktur->id_po) {
        $po = Po::with(['poDetails', 'penerimaans'])->find($faktur->id_po);
        if ($po) {
            $hasReceipts = $po->penerimaans()->exists();
            $allInvoiced = $po->poDetails->every(fn($d) => $d->qty_terfaktur >= $d->qty);
            $allUninvoiced = $po->poDetails->every(fn($d) => $d->qty_terfaktur <= 0);

            if (!$hasReceipts && $allUninvoiced) {
                $po->status = 'draft';
                $po->save();
            } elseif ($hasReceipts && !$allInvoiced) {
                // Pastikan status kembali ke sedang diproses jika sebelumnya selesai
                if ($po->status !== 'sedang diproses') {
                    $po->status = 'sedang diproses';
                    $po->save();
                }
            }
            // Jika hasReceipts && allInvoiced -> biarkan status selesai
        }
    }

    // Recalculate receipt billing status for this PO
    if ($faktur->id_po) {
        $po = Po::with('poDetails')->find($faktur->id_po);
        if ($po) {
            $receiptIds = PenerimaanPembelianDetail::whereIn('po_detail_id', $po->poDetails->pluck('id'))
                ->pluck('penerimaan_id')
                ->unique()
                ->values();
            $receipts = PenerimaanPembelian::whereIn('id', $receiptIds)->get();
            foreach ($receipts as $rec) {
                $sumDiterima = $rec->details()->sum('qty_diterima');
                $sumTerfaktur = $rec->details()->sum('qty_terfaktur');
                $statusPenagihan = 'belum';
                if ($sumTerfaktur >= $sumDiterima && $sumDiterima > 0) {
                    $statusPenagihan = 'lunas';
                } elseif ($sumTerfaktur > 0) {
                    $statusPenagihan = 'sebagian';
                }
                $rec->status_penagihan = $statusPenagihan;
                $rec->save();
            }
        }
    }

    return redirect()->route('faktur.index')->with('success', 'Faktur dan referensi qty berhasil dihapus.');
}

    public function approve($id)
    {
        $faktur = Faktur::with('details')->findOrFail($id);

        if ($faktur->status !== 'draft') {
            return redirect()->back()->with('warning', 'Faktur sudah diproses.');
        }

        $faktur->status = 'sedang diproses';
        $faktur->save();

        $jurnal = new Jurnal();
        $jurnal->no_jurnal = 'JV-' . now()->format('ymd') . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
        $jurnal->tanggal = $faktur->tanggal;
        $jurnal->id_perusahaan = $faktur->id_perusahaan;
        $jurnal->keterangan = 'Faktur: ' . $faktur->no_faktur;
        $jurnal->save();

        $totalFaktur = 0;

        foreach ($faktur->details as $detail) {
            $coaId = $detail->coa_beban_id ?? $detail->coa_persediaan_id ?? $detail->coa_hpp_id;
            if (!$coaId) continue;

            $jurnal->details()->create([
                'coa_id' => $coaId,
                'debit' => $detail->total,
                'kredit' => 0,
            ]);

            $totalFaktur += $detail->total;
        }

        $jurnal->details()->create([
            'coa_id' => AccountService::getHutangUsaha($faktur->id_perusahaan),
            'debit' => 0,
            'kredit' => $totalFaktur,
        ]);

        $faktur->jurnal_id = $jurnal->id;
        $faktur->save();

        return redirect()->route('faktur.index')->with('success', 'Faktur disetujui & jurnal berhasil dibuat.');
    }

    public function revisi($id)
    {
        $faktur = Faktur::findOrFail($id);

        if ($faktur->jurnal_id) {
            JurnalDetail::where('jurnal_id', $faktur->jurnal_id)->delete();
            Jurnal::where('id', $faktur->jurnal_id)->delete();
            $faktur->jurnal_id = null;
        }

        $faktur->status = 'draft';
        $faktur->save();

        return redirect()->route('faktur.index')->with('success', 'Faktur berhasil direvisi. Jurnal telah dihapus.');
    }

}

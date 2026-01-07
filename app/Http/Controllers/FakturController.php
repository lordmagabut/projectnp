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
use App\Models\UangMukaPembelian;
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
        $nomorFaktur = Faktur::generateNomorFaktur();
        return view('faktur.create', compact('suppliers', 'perusahaans', 'proyeks', 'nomorFaktur'));
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

        $nomorFaktur = Faktur::generateNomorFaktur();
        return view('faktur.create-from-po', compact('po', 'nomorFaktur'));
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

        $nomorFaktur = Faktur::generateNomorFaktur();
        return view('faktur.create-from-penerimaan', compact('penerimaan', 'nomorFaktur'));
    }

    public function store(Request $request)
{
    $request->validate([
        'no_faktur'           => 'nullable|string',
        'tanggal'             => 'required|date',
        'file_path'           => 'nullable|file|mimes:pdf|max:30000',
        'uang_muka_dipakai'   => 'nullable|numeric|min:0',  // NEW: UM amount to use
    ]);

    // Generate nomor faktur otomatis jika tidak diisi
    $noFaktur = $request->no_faktur ?: Faktur::generateNomorFaktur();

    $filePath = null;
    if ($request->hasFile('file_path')) {
        $filePath = $request->file('file_path')->store('faktur', 'public');
    }

    // NEW: Get UM if provided
    $uangMukaDisepakati = floatval($request->uang_muka_dipakai ?? 0);
    $uangMukaId = null;
    $uangMukaModel = null;
    
    if ($uangMukaDisepakati > 0) {
        // Find approved UM for this PO
        if ($request->has('uang_muka_id')) {
            $uangMukaModel = \App\Models\UangMukaPembelian::findOrFail($request->uang_muka_id);
            if ($uangMukaModel->status !== 'approved') {
                throw new \Exception('Uang Muka harus berstatus Approved');
            }
            if ($uangMukaDisepakati > $uangMukaModel->sisa_uang_muka) {
                throw new \Exception('Nominal UM melebihi sisa UM tersedia');
            }
            $uangMukaId = $uangMukaModel->id;
        }
    }

    // === FAKTUR DARI PO ===
    if ($request->has('po_id')) {
        $po = \App\Models\Po::with(['poDetails.barang'])->findOrFail($request->po_id);

        $faktur = new \App\Models\Faktur();
        $faktur->no_faktur      = $noFaktur;
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
        $faktur->uang_muka_dipakai = $uangMukaDisepakati;  // NEW
        $faktur->uang_muka_id       = $uangMukaId;         // NEW
        $faktur->dibuat_oleh    = auth()->id();
        $faktur->dibuat_at      = now();
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

        // Validasi: faktur harus memiliki minimal 1 item
        if ($faktur->details()->count() === 0) {
            $faktur->delete();
            return back()->with('error', 'Faktur harus memiliki minimal 1 item dengan qty > 0. Silakan cek kembali.');
        }

        $faktur->total = $faktur->subtotal - $faktur->total_diskon + $faktur->total_ppn;

        // Validate UM does not exceed total after discount+PPN
        if ($faktur->uang_muka_dipakai > 0 && $faktur->uang_muka_dipakai > $faktur->total) {
            throw new \Exception('Uang Muka yang dipakai melebihi total faktur setelah diskon dan PPN');
        }

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
        $faktur->no_faktur      = $noFaktur;
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
        $faktur->uang_muka_dipakai = $uangMukaDisepakati;  // NEW
        $faktur->uang_muka_id       = $uangMukaId;         // NEW
        $faktur->dibuat_oleh    = auth()->id();
        $faktur->dibuat_at      = now();
        $faktur->save();

        $diskonPersenGlobal = floatval($request->diskon_persen ?? 0);
        $ppnPersenGlobal    = floatval($request->ppn_persen ?? 0);

        $items = $request->items ?? [];
        if (!is_array($items)) {
            $items = [];
        }

        foreach ($items as $item) {
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

        // Validasi: faktur harus memiliki minimal 1 item
        if ($faktur->details()->count() === 0) {
            $faktur->delete();
            return back()->with('error', 'Faktur harus memiliki minimal 1 item dengan qty > 0. Silakan cek kembali.');
        }

        $faktur->total = $faktur->subtotal - $faktur->total_diskon + $faktur->total_ppn;

        // Validate UM does not exceed total after discount+PPN
        if ($faktur->uang_muka_dipakai > 0 && $faktur->uang_muka_dipakai > $faktur->total) {
            throw new \Exception('Uang Muka yang dipakai melebihi total faktur setelah diskon dan PPN');
        }

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

    // === FAKTUR DARI MULTIPLE PENERIMAAN ===
    if ($request->has('penerimaan_ids')) {
        $penerimaanIdsInput = $request->input('penerimaan_ids');
        
        // Convert string atau array ke array
        $penerimaanIds = [];
        if (is_array($penerimaanIdsInput)) {
            $penerimaanIds = $penerimaanIdsInput;
        } elseif (is_string($penerimaanIdsInput) && !empty($penerimaanIdsInput)) {
            $penerimaanIds = array_filter(array_map('trim', explode(',', $penerimaanIdsInput)));
        }

        if (!empty($penerimaanIds)) {
            $penerimaans = PenerimaanPembelian::whereIn('id', $penerimaanIds)
                ->with(['details.poDetail.barang', 'po'])
            ->get();

        if ($penerimaans->isEmpty()) {
            return back()->with('error', 'Penerimaan tidak ditemukan.');
        }

        // Validasi semua penerimaan dari supplier dan PO yang sama
        $firstPenerimaan = $penerimaans->first();
        $po = $firstPenerimaan->po;

        $allFromSameSupplier = $penerimaans->every(fn($p) => $p->po->id_supplier === $po->id_supplier);
        $allFromSamePo = $penerimaans->every(fn($p) => $p->po_id === $po->id);

        if (!$allFromSameSupplier || !$allFromSamePo) {
            return back()->with('error', 'Semua penerimaan harus dari supplier dan PO yang sama.');
        }

        $faktur = new \App\Models\Faktur();
        $faktur->no_faktur      = $noFaktur;
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
        $faktur->uang_muka_dipakai = $uangMukaDisepakati;
        $faktur->uang_muka_id       = $uangMukaId;
        $faktur->dibuat_oleh    = auth()->id();
        $faktur->dibuat_at      = now();
        $faktur->save();

        $diskonPersenGlobal = floatval($request->diskon_persen ?? 0);
        $ppnPersenGlobal    = floatval($request->ppn_persen ?? 0);

        // Collect items dari semua penerimaan yang dipilih
        $items = $request->items ?? [];
        if (!is_array($items)) {
            $items = [];
        }

        foreach ($items as $item) {
            $qtyFaktur = floatval($item['qty'] ?? 0);
            if ($qtyFaktur <= 0) continue;

            $poDetail = $po->poDetails->firstWhere('id', $item['po_detail_id'] ?? null);
            if (!$poDetail) continue;

            $barang = $poDetail->barang;

            // Validasi total qty yang bisa difaktur dari semua penerimaan
            $totalQtyAvailable = 0;
            $penerimaanDetailsForItem = [];

            foreach ($penerimaans as $penerimaan) {
                $details = $penerimaan->details->where('po_detail_id', $poDetail->id);
                foreach ($details as $detail) {
                    $qtyReturApproved = ReturPembelianDetail::where('penerimaan_detail_id', $detail->id)
                        ->whereHas('retur', function($q) { $q->where('status', 'approved'); })
                        ->sum('qty_retur');
                    $qtyAvailable = max(0, ($detail->qty_diterima - $qtyReturApproved - $detail->qty_terfaktur));
                    if ($qtyAvailable > 0) {
                        $totalQtyAvailable += $qtyAvailable;
                        $penerimaanDetailsForItem[] = [
                            'detail' => $detail,
                            'available' => $qtyAvailable
                        ];
                    }
                }
            }

            if ($totalQtyAvailable <= 0) {
                continue;
            }

            if ($qtyFaktur > $totalQtyAvailable) {
                throw new \Exception("Item {$poDetail->kode_item}: Qty faktur ({$qtyFaktur}) melebihi qty tersedia ({$totalQtyAvailable}).");
            }

            $harga = floatval($item['harga']);
            $subtotalBaris = $qtyFaktur * $harga;
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
                'qty'                => $qtyFaktur,
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

            // Alokasikan qty faktur ke penerimaan yang tersedia (FIFO)
            $remaining = $qtyFaktur;
            foreach ($penerimaanDetailsForItem as $item) {
                if ($remaining <= 0) break;
                $detail = $item['detail'];
                $available = $item['available'];
                $use = min($available, $remaining);
                $detail->qty_terfaktur += $use;
                $detail->save();
                $remaining -= $use;
            }

            $poDetail->qty_terfaktur += $qtyFaktur;
            $poDetail->save();

            $faktur->subtotal     += $subtotalBaris;
            $faktur->total_diskon += $diskonRupiah;
            $faktur->total_ppn    += $ppnRupiah;
        }

        // Validasi: faktur harus memiliki minimal 1 item
        if ($faktur->details()->count() === 0) {
            $faktur->delete();
            return back()->with('error', 'Faktur harus memiliki minimal 1 item dengan qty > 0. Silakan cek kembali.');
        }

        $faktur->total = $faktur->subtotal - $faktur->total_diskon + $faktur->total_ppn;

        // Validate UM does not exceed total after discount+PPN
        if ($faktur->uang_muka_dipakai > 0 && $faktur->uang_muka_dipakai > $faktur->total) {
            throw new \Exception('Uang Muka yang dipakai melebihi total faktur setelah diskon dan PPN');
        }

        $faktur->save();

        // Update status_penagihan untuk semua penerimaan yang digunakan
        foreach ($penerimaans as $penerimaan) {
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
        }

        // Update status PO jika semua selesai
        if ($po->poDetails->every(fn($d) => $d->qty_terfaktur >= $d->qty)) {
            $po->update(['status' => 'selesai']);
        }

        return redirect()->route('faktur.index')->with('success', 'Faktur berhasil dibuat dari ' . count($penerimaans) . ' penerimaan.');
        }  // Closing brace untuk if (!empty($penerimaanIds))
    }  // Closing brace untuk if ($request->has('penerimaan_ids'))

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

    // Return UM if faktur used it
    if ($faktur->uang_muka_dipakai > 0 && $faktur->uang_muka_id) {
        $uangMuka = \App\Models\UangMukaPembelian::find($faktur->uang_muka_id);
        if ($uangMuka) {
            $uangMuka->nominal_digunakan = max(0, $uangMuka->nominal_digunakan - $faktur->uang_muka_dipakai);
            $uangMuka->save();
        }
    }

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

        // NEW: Track UM usage if this faktur uses UM
        if ($faktur->uang_muka_dipakai > 0 && $faktur->uang_muka_id) {
            $uangMuka = \App\Models\UangMukaPembelian::findOrFail($faktur->uang_muka_id);
            $uangMuka->updateNominalDigunakan($faktur->uang_muka_dipakai);
        }

        $faktur->status = 'sedang diproses';
        $faktur->disetujui_oleh = auth()->id();
        $faktur->disetujui_at = now();
        $faktur->save();

        $jurnal = new Jurnal();
        $jurnal->no_jurnal = 'JV-' . now()->format('ymd') . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
        $jurnal->tanggal = $faktur->tanggal;
        $jurnal->id_perusahaan = $faktur->id_perusahaan;
        $jurnal->keterangan = 'Faktur: ' . $faktur->no_faktur;
        $jurnal->save();

        $totalDpp = 0.0;
        $totalPpn = 0.0;

        foreach ($faktur->details as $detail) {
            $coaId = $detail->coa_beban_id ?? $detail->coa_persediaan_id ?? $detail->coa_hpp_id;
            if (!$coaId) continue;

            $ppnBaris = floatval($detail->ppn_rupiah ?? 0);
            $dppBaris = floatval($detail->total) - $ppnBaris;
            if ($dppBaris < 0) { $dppBaris = 0; }

            $jurnal->details()->create([
                'coa_id' => $coaId,
                'debit' => $dppBaris,
                'kredit' => 0,
            ]);

            $totalDpp += $dppBaris;
            $totalPpn += $ppnBaris;
        }

        $totalGross = $totalDpp + $totalPpn;
        $umGrossUsed = min(floatval($faktur->uang_muka_dipakai ?? 0), $totalGross);
        $umPpnUsed = $totalGross > 0 ? round($umGrossUsed * ($totalPpn / $totalGross), 2) : 0.0;
        $umDppUsed = max(0, round($umGrossUsed - $umPpnUsed, 2));

        // Debit PPN Masukan hanya untuk sisa yang belum diakui saat UM
        $ppnToDebitNow = max(0, round($totalPpn - $umPpnUsed, 2));
        if ($ppnToDebitNow > 0) {
            $jurnal->details()->create([
                'coa_id' => AccountService::getPpnMasukan($faktur->id_perusahaan),
                'debit'  => $ppnToDebitNow,
                'kredit' => 0,
            ]);
        }

        // Credits
        if ($umDppUsed > 0) {
            $coaUangMukaId = AccountMapping::getCoaId('uang_muka_vendor') 
                             ?? \App\Models\Coa::where('no_akun', '1-150')->first()?->id;
            if ($coaUangMukaId) {
                $jurnal->details()->create([
                    'coa_id' => $coaUangMukaId,
                    'debit'  => 0,
                    'kredit' => $umDppUsed,
                ]);
            }
        }

        // Sisanya ke Hutang Usaha: totalGross - umGrossUsed
        $sisaHutang = max(0, round($totalGross - $umGrossUsed, 2));
        if ($sisaHutang > 0) {
            $jurnal->details()->create([
                'coa_id' => AccountService::getHutangUsaha($faktur->id_perusahaan),
                'debit'  => 0,
                'kredit' => $sisaHutang,
            ]);
        }

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

    /**
     * API: Get PO by Supplier (for faktur create dropdown)
     */
    public function getPoBySupplier($supplier_id)
    {
        $pos = Po::where('id_supplier', $supplier_id)
            ->whereIn('status', ['sedang diproses', 'selesai'])
            ->whereHas('penerimaans', function($q) {
                $q->where('status', 'approved');
            })
            ->with(['details'])
            ->orderByDesc('created_at')
            ->get()
            ->map(function($po) {
                return [
                    'id' => $po->id,
                    'no_po' => $po->no_po,
                    'tanggal' => $po->tanggal,
                    'total' => $po->total,
                    'ppn_persen' => $po->ppn_persen ?? 0,
                    'diskon_persen' => $po->diskon_persen ?? 0,
                ];
            });

        return response()->json($pos);
    }

    /**
     * API: Get PO Details with barang info
     */
    public function getPoDetail($po_id)
    {
        $po = Po::with(['poDetails.barang', 'supplier'])->findOrFail($po_id);
        
        $details = $po->poDetails->map(function($detail) {
            // Calculate qty available for faktur (approved penerimaan - retur - already faktured)
            $qtyApproved = PenerimaanPembelianDetail::where('po_detail_id', $detail->id)
                ->whereHas('penerimaan', function ($q) { 
                    $q->where('status', 'approved'); 
                })
                ->sum('qty_diterima');
                
            $qtyReturApproved = ReturPembelianDetail::whereHas('retur', function($q){ 
                    $q->where('status','approved'); 
                })
                ->whereHas('penerimaanDetail', function($q) use ($detail){ 
                    $q->where('po_detail_id', $detail->id); 
                })
                ->sum('qty_retur');
                
            $netApproved = max(0, $qtyApproved - $qtyReturApproved);
            $qtyAvailable = max(0, $netApproved - $detail->qty_terfaktur);

            return [
                'id' => $detail->id,
                'barang_id' => $detail->id_barang,
                'barang_nama' => $detail->barang->nama_barang ?? '',
                'satuan' => $detail->barang->satuan ?? '',
                'qty_po' => $detail->qty,
                'qty_available' => $qtyAvailable,
                'harga' => $detail->harga,
                'diskon_persen' => $detail->diskon_persen ?? 0,
                'ppn_persen' => $detail->ppn_persen ?? 0,
            ];
        });

        return response()->json([
            'po' => [
                'id' => $po->id,
                'no_po' => $po->no_po,
                'supplier' => $po->supplier->nama ?? '',
            ],
            'details' => $details,
        ]);
    }

    /**
     * API: Get available Uang Muka by Supplier
     */
    public function getUangMukaBySupplier($supplier_id)
    {
        $uangMukas = UangMukaPembelian::where('id_supplier', $supplier_id)
            ->where('status', 'approved')
            ->where('nominal_digunakan', '<', \DB::raw('nominal'))
            ->with(['po'])
            ->orderByDesc('tanggal')
            ->get();

        $result = $uangMukas->map(function($um) {
            $nominal = floatval($um->nominal);
            $digunakan = floatval($um->nominal_digunakan);
            $sisa = $nominal - $digunakan;
            
            return [
                'id' => $um->id,
                'no_um' => $um->no_uang_muka,
                'tanggal' => $um->tanggal,
                'po_no' => $um->po->no_po ?? '-',
                'nominal' => $nominal,
                'nominal_digunakan' => $digunakan,
                'sisa' => $sisa,
            ];
        })->values()->toArray();

        return response()->json($result);
    }

    /**
     * API: Get approved penerimaan for a specific PO
     */
    public function getPenerimaanByPo($po_id)
    {
        $penerimaans = PenerimaanPembelian::where('po_id', $po_id)
            ->where('status', 'approved')
            ->orderBy('tanggal', 'desc')
            ->get(['id', 'no_penerimaan', 'tanggal']);

        return response()->json($penerimaans);
    }

    /**
     * API: Get details from multiple penerimaan with items not yet invoiced
     */
    public function getPenerimaanDetail(Request $request)
    {
        $penerimaanIds = $request->input('penerimaan_ids', []);

        if (empty($penerimaanIds)) {
            return response()->json(['details' => []]);
        }

        $details = PenerimaanPembelianDetail::whereIn('penerimaan_id', $penerimaanIds)
            ->with(['poDetail.barang', 'penerimaan'])
            ->get();

        $result = $details->filter(function($detail) {
            // Hanya items yang belum difaktur semua
            $qtyReturApproved = ReturPembelianDetail::where('penerimaan_detail_id', $detail->id)
                ->whereHas('retur', function($q) { $q->where('status', 'approved'); })
                ->sum('qty_retur');
            $netQty = $detail->qty_diterima - $qtyReturApproved;
            return ($netQty - $detail->qty_terfaktur) > 0;
        })->map(function($detail) {
            $qtyReturApproved = ReturPembelianDetail::where('penerimaan_detail_id', $detail->id)
                ->whereHas('retur', function($q) { $q->where('status', 'approved'); })
                ->sum('qty_retur');
            $qtyAvailable = max(0, ($detail->qty_diterima - $qtyReturApproved - $detail->qty_terfaktur));

            return [
                'id' => $detail->id,
                'penerimaan_id' => $detail->penerimaan_id,
                'po_detail_id' => $detail->po_detail_id,
                'no_penerimaan' => $detail->penerimaan->no_penerimaan,
                'barang_id' => $detail->poDetail->barang_id,
                'barang_nama' => $detail->poDetail->barang->nama ?? $detail->poDetail->kode_item,
                'satuan' => $detail->poDetail->barang->satuan ?? 'PCS',
                'harga' => $detail->poDetail->harga,
                'qty_diterima' => $detail->qty_diterima,
                'qty_terfaktur' => $detail->qty_terfaktur,
                'qty_available' => $qtyAvailable,
            ];
        })->values()->toArray();

        return response()->json(['details' => $result]);
    }

}


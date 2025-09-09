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
    
        // Cek jika semua qty sudah difakturkan
        $semuaSudahDifakturkan = $po->poDetails->every(function ($detail) {
            return $detail->qty_terfaktur >= $detail->qty;
        });
    
        if ($semuaSudahDifakturkan) {
            return redirect()->route('faktur.index')->with('warning', 'Semua item dalam PO ini sudah difakturkan.');
        }
    
        return view('faktur.create-from-po', compact('po'));
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

            $qtySisa = $poDetail->qty - $poDetail->qty_terfaktur;
            $qtyDipakai = min($qtyFaktur, $qtySisa);
            if ($qtyDipakai <= 0) continue;

            $harga         = floatval($item['harga']);
            $subtotalBaris = $qtyDipakai * $harga;
            $diskonRupiah  = $subtotalBaris * $diskonPersenGlobal / 100;
            $afterDiskon   = $subtotalBaris - $diskonRupiah;
            $ppnRupiah     = $afterDiskon * $ppnPersenGlobal / 100;
            $totalBaris    = $afterDiskon + $ppnRupiah;

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
                'coa_beban_id'       => $barang?->coa_beban_id,
                'coa_persediaan_id'  => $barang?->coa_persediaan_id,
                'coa_hpp_id'         => $barang?->coa_hpp_id,
            ]);

            $poDetail->qty_terfaktur += $qtyDipakai;
            $poDetail->save();

            $faktur->subtotal     += $subtotalBaris;
            $faktur->total_diskon += $diskonRupiah;
            $faktur->total_ppn    += $ppnRupiah;

            if ($qtyFaktur > $qtySisa) {
                return back()->with('error', 'Qty faktur melebihi sisa PO untuk item: ' . $item['kode_item']);
            }
        }

        $faktur->total = $faktur->subtotal - $faktur->total_diskon + $faktur->total_ppn;
        $faktur->save();

        // Ubah status PO jika semua selesai
        if ($po->poDetails->every(fn($d) => $d->qty_terfaktur >= $d->qty)) {
            $po->update(['status' => 'selesai']);
        }

        return redirect()->route('faktur.index')->with('success', 'Faktur berhasil dibuat dari PO.');
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

        $detail->delete();
    }

    $faktur->delete();

    // Tambahan: ubah status PO menjadi draft jika semua qty_terfaktur kembali 0
    if ($faktur->id_po) {
        $po = Po::with('poDetails')->find($faktur->id_po);
        if ($po && $po->poDetails->every(fn($d) => $d->qty_terfaktur <= 0)) {
            $po->status = 'draft';
            $po->save();
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
            'coa_id' => 77, // ID akun Hutang Usaha, ubah sesuai sistemmu
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

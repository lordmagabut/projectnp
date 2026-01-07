<?php

namespace App\Http\Controllers;

use App\Models\Po;
use App\Models\PoDetail;
use App\Models\Supplier;
use App\Models\Perusahaan;
use App\Models\Proyek;
use App\Models\Barang;
use App\Models\PenerimaanPembelian;
use Illuminate\Http\Request;
use Carbon\Carbon;
// Removed PDF generation dependencies

class PoController extends Controller
{
    public function index(Request $request)
    {
        $tahun = $request->get('tahun', now()->format('Y'));
        $tahunList = Po::selectRaw('YEAR(tanggal) as tahun')
            ->distinct()
            ->orderByDesc('tahun')
            ->pluck('tahun')
            ->toArray();
        if (empty($tahunList)) {
            $tahunList = [now()->format('Y')];
        }

        $po = Po::with(['proyek', 'supplier'])
            ->whereYear('tanggal', $tahun)
            ->orderByDesc('tanggal')
            ->get();

        return view('po.index', compact('po', 'tahunList', 'tahun'));
    }

    public function create()
    {
        $suppliers = Supplier::all();
        $proyek = Proyek::all();
        $barang = Barang::all();
        $company = auth()->user()->perusahaans->first();
        $previewNoPo = $this->generateNomorPo($company?->id, now()->toDateString());
        return view('po.create', compact('suppliers', 'proyek', 'barang', 'previewNoPo'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'tanggal' => 'required',
            'id_supplier' => 'required',
            'id_proyek' => 'required',
            'items' => 'required|array|min:1'
        ]);

        $supplier = Supplier::findOrFail($request->id_supplier);
        $company = auth()->user()->perusahaans->first();
        $generatedNo = $this->generateNomorPo($company?->id, $request->tanggal);

        $po = Po::create([
            'no_po' => $generatedNo,
            'tanggal' => $request->tanggal,
            'id_supplier' => $request->id_supplier,
            'nama_supplier' => $supplier->nama_supplier,
            'id_proyek' => $request->id_proyek,
            'id_perusahaan' => $company?->id,
            'keterangan' => $request->keterangan,
            'status' => 'draft',
            'dibuat_oleh' => auth()->id(),
            'dibuat_at' => now(),
        ]);

        $diskonGlobal = $request->diskon_persen ?? 0;
        $ppnGlobal = $request->ppn_persen ?? 0;
        $grandSubtotal = 0;

        foreach ($request->items as $item) {
            $qty = floatval($item['qty']);
            $harga = floatval($item['harga']);
            $subtotal = $qty * $harga;
            $grandSubtotal += $subtotal;
        }

        $diskonRupiah = ($diskonGlobal / 100) * $grandSubtotal;
        $ppnRupiah = (($grandSubtotal - $diskonRupiah) * $ppnGlobal / 100);
        $grandTotal = $grandSubtotal - $diskonRupiah + $ppnRupiah;

        foreach ($request->items as $item) {
            $qty = floatval($item['qty']);
            $harga = floatval($item['harga']);
            $subtotal = $qty * $harga;

            PoDetail::create([
                'po_id' => $po->id,
                'kode_item' => $item['kode_item'],
                'uraian' => $item['uraian'],
                'qty' => $qty,
                'uom' => $item['uom'],
                'harga' => $harga,
                'diskon_persen' => $diskonGlobal,
                'diskon_rupiah' => $diskonRupiah,
                'ppn_persen' => $ppnGlobal,
                'ppn_rupiah' => $ppnRupiah,
                'total' => $subtotal
            ]);
        }

        $po->update(['total' => $grandTotal]);

        if ($request->submit == 'simpan_lanjut') {
            return redirect()->route('po.create')->with('success', 'PO berhasil disimpan, silakan input PO baru');
        }
        return redirect()->route('po.index')->with('success', 'PO berhasil disimpan');
    }

    public function edit($id)
    {
        $po = Po::with('details')->findOrFail($id);
        
        if (in_array($po->status, ['reviewed','sedang diproses'])) {
            return redirect()->route('po.index')->with('error', 'PO ini sudah diproses dan tidak dapat diedit.');
        }

        // Blok edit jika sudah ada penerimaan (walaupun sebagian)
        $sudahAdaPenerimaan = \App\Models\PenerimaanPembelian::where('po_id', $po->id)->exists();
        if ($sudahAdaPenerimaan) {
            return redirect()->route('po.index')->with('error', 'PO tidak dapat diedit karena sudah ada penerimaan barang.');
        }

        $suppliers = Supplier::all();
        $proyek = Proyek::all();
        $barang = Barang::all();
        $company = auth()->user()->perusahaans->first();
        $previewNoPo = $this->generateNomorPo($company?->id, $po->tanggal);

        return view('po.edit', compact('po', 'suppliers', 'proyek', 'barang', 'previewNoPo'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'tanggal' => 'required',
            'id_supplier' => 'required',
            'id_proyek' => 'required',
            'items' => 'required|array|min:1'
        ]);

        $diskonGlobal = $request->diskon_persen ?? 0;
        $ppnGlobal = $request->ppn_persen ?? 0;

        $po = Po::findOrFail($id);

        // Blok update jika sudah ada penerimaan (walaupun sebagian)
        $sudahAdaPenerimaan = \App\Models\PenerimaanPembelian::where('po_id', $po->id)->exists();
        if ($sudahAdaPenerimaan) {
            return redirect()->route('po.index')->with('error', 'PO tidak dapat diupdate karena sudah ada penerimaan barang.');
        }
        $supplier = Supplier::findOrFail($request->id_supplier);

        // Regenerate nomor PO berdasarkan tanggal & alias perusahaan saat ini
        $company = auth()->user()->perusahaans->first();
        $generatedNo = $this->generateNomorPo($company?->id, $request->tanggal);

        $po->update([
            'no_po' => $generatedNo,
            'tanggal' => $request->tanggal,
            'id_supplier' => $request->id_supplier,
            'nama_supplier' => $supplier->nama_supplier,
            'id_proyek' => $request->id_proyek,
            'keterangan' => $request->keterangan,
        ]);

        $po->details()->delete();

        $diskonGlobal = $request->diskon_persen ?? 0;
        $ppnGlobal = $request->ppn_persen ?? 0;
        
        $grandSubtotal = 0;

        // Hitung total subtotal terlebih dahulu
        foreach ($request->items as $item) {
            $qty = floatval($item['qty']);
            $harga = floatval($item['harga']);
            $subtotal = $qty * $harga;
            $grandSubtotal += $subtotal;
        }

        // Hitung diskon dan PPN dari grand subtotal
        $diskonRupiah = ($diskonGlobal / 100) * $grandSubtotal;
        $ppnRupiah = (($grandSubtotal - $diskonRupiah) * $ppnGlobal / 100);
        $grandTotal = $grandSubtotal - $diskonRupiah + $ppnRupiah;

        foreach ($request->items as $item) {
            $qty = floatval($item['qty']);
            $harga = floatval($item['harga']);
            $subtotal = $qty * $harga;

            PoDetail::create([
                'po_id' => $po->id,
                'kode_item' => $item['kode_item'],
                'uraian' => $item['uraian'],
                'qty' => $qty,
                'uom' => $item['uom'],
                'harga' => $harga,
                'diskon_persen' => $diskonGlobal,
                'diskon_rupiah' => $diskonRupiah,
                'ppn_persen' => $ppnGlobal,
                'ppn_rupiah' => $ppnRupiah,
                'total' => $subtotal
            ]);
        }

        $po->update(['total' => $grandTotal]);

        return redirect()->route('po.index')->with('success', 'PO berhasil diupdate.');
    }

    public function show($id)
    {
        $po = Po::with(['details', 'perusahaan', 'proyek', 'supplier'])->findOrFail($id);

        $subtotal = $po->details->sum('total');
        $diskonPersen = $po->details->first()->diskon_persen ?? 0;
        $ppnPersen = $po->details->first()->ppn_persen ?? 0;

        $diskonRupiah = ($diskonPersen / 100) * $subtotal;
        $ppnRupiah = (($subtotal - $diskonRupiah) * $ppnPersen / 100);
        $grandTotal = ($subtotal - $diskonRupiah) + $ppnRupiah;

        return view('po.show', compact(
            'po',
            'subtotal',
            'diskonPersen',
            'diskonRupiah',
            'ppnPersen',
            'ppnRupiah',
            'grandTotal'
        ));
    }
        
    public function destroy($id)
    {
        $po = Po::findOrFail($id);

        // Blok hapus jika sudah ada penerimaan (walaupun sebagian)
        $sudahAdaPenerimaan = \App\Models\PenerimaanPembelian::where('po_id', $po->id)->exists();
        if ($sudahAdaPenerimaan) {
            return redirect()->route('po.index')->with('error', 'PO tidak dapat dihapus karena sudah ada penerimaan barang.');
        }
        $po->details()->delete();
        $po->delete();

        return redirect()->route('po.index')->with('success', 'PO berhasil dihapus.');
    }
    public function print($id)
    {
        $po = Po::with(['details', 'perusahaan', 'proyek', 'supplier'])->findOrFail($id);
        if ($po->status !== 'reviewed') {
            return redirect()->route('po.index')->with('info', 'PO harus direview terlebih dahulu sebelum disetujui dan dicetak.');
        }
        $updates = [
            'status' => 'sedang diproses',
            'printed_at' => now(),
            'file_path' => null,
            'disetujui_oleh' => auth()->id(),
            'disetujui_at' => now(),
        ];
        // Jika belum ada proses review terpisah, isi otomatis
        if (!$po->direview_oleh) {
            $updates['direview_oleh'] = auth()->id();
            $updates['direview_at'] = now();
        }
        $po->update($updates);
        return redirect()->route('po.show', $id)->with('success', 'PO disetujui. Gunakan tombol "Cetak Dokumen" untuk mencetak.');
    }

    public function review($id)
    {
        $po = Po::with(['details', 'perusahaan', 'proyek', 'supplier'])->findOrFail($id);
        if ($po->status !== 'draft') {
            return redirect()->route('po.index')->with('info', 'Hanya PO draft yang dapat direview.');
        }
        $po->update([
            'status' => 'reviewed',
            'direview_oleh' => auth()->id(),
            'direview_at' => now(),
        ]);
        return redirect()->route('po.index')->with('success', 'PO berhasil direview. Silakan lanjutkan ke persetujuan.');
    }
    
public function revisi($id)
{
    $po = Po::findOrFail($id);

    if ($po->status != 'sedang diproses') {
        return redirect()->route('po.index')->with('error', 'Hanya PO yang sedang diproses yang bisa direvisi.');
    }

    // Blok revisi jika sudah ada penerimaan barang untuk PO ini
    $sudahAdaPenerimaan = PenerimaanPembelian::where('po_id', $po->id)->exists();
    if ($sudahAdaPenerimaan) {
        return redirect()->route('po.index')->with('error', 'PO tidak dapat direvisi karena sudah memiliki penerimaan barang.');
    }

    // Hapus file dari storage jika ada
    if ($po->file_path && \Storage::exists('public/' . $po->file_path)) {
        \Storage::delete('public/' . $po->file_path);
    }

    // Update status PO menjadi draft, kosongkan file_path, dan hapus audit trail
    $po->update([
        'status' => 'draft',
        'file_path' => null,
        'direview_oleh' => null,
        'direview_at' => null,
        'disetujui_oleh' => null,
        'disetujui_at' => null,
    ]);

    return redirect()->route('po.index')->with('success', 'PO berhasil direvisi dan status dikembalikan menjadi draft.');
}

    /**
     * GET preview next PO number based on given tanggal.
     */
    public function previewNoPo(Request $request)
    {
        $tanggal = $request->get('tanggal') ?: now()->toDateString();
        $company = auth()->user()->perusahaans->first();
        $no = $this->generateNomorPo($company?->id, $tanggal);
        return response()->json(['no_po' => $no]);
    }

    /**
     * Generate nomor PO dengan format: PO/{alias}/{YYYY}/{NNNN}
     * Sequence berjalan per-perusahaan per-tahun.
     */
    protected function generateNomorPo(?int $perusahaanId, $tanggal): string
    {
        $date = Carbon::parse($tanggal);
        $year = $date->format('Y');
        $perusahaan = $perusahaanId ? Perusahaan::find($perusahaanId) : null;
        $alias = $perusahaan?->alias ?: 'NA';
        // Sanitasi alias: huruf/angka saja, uppercase
        $alias = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $alias));
        if ($alias === '') { $alias = 'NA'; }

        $prefix = "PO/{$alias}/{$year}/";

        // Cari nomor terakhir untuk prefix ini
        $lastPo = Po::query()
            ->where('id_perusahaan', $perusahaanId)
            ->whereYear('tanggal', $year)
            ->where('no_po', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->first();

        $lastSeq = 0;
        if ($lastPo && preg_match('/^'.preg_quote($prefix, '/').'([0-9]+)$/', $lastPo->no_po, $m)) {
            $lastSeq = intval($m[1]);
        }

        $nextSeq = $lastSeq + 1;
        $no = $prefix . str_pad((string)$nextSeq, 4, '0', STR_PAD_LEFT);

        // Pastikan unik (jaga-jaga)
        while (Po::where('no_po', $no)->exists()) {
            $nextSeq++;
            $no = $prefix . str_pad((string)$nextSeq, 4, '0', STR_PAD_LEFT);
        }

        return $no;
    }

    // QR validation feature removed for security; no validation key generation or API
    
}

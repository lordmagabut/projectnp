<?php

namespace App\Http\Controllers;

use App\Models\Po;
use App\Models\PoDetail;
use App\Models\Supplier;
use App\Models\Perusahaan;
use App\Models\Proyek;
use App\Models\Barang;
use Illuminate\Http\Request;
use Carbon\Carbon;
use NcJoes\OfficeConverter\OfficeConverter;
use PhpOffice\PhpWord\TemplateProcessor;

class PoController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = Po::query()->with(['supplier', 'proyek']);
    
        // Ambil tahun dari request atau default tahun saat ini
        $tahun = $request->tahun ?? now()->format('Y');
    
        // Filter berdasarkan tahun saja
        $query->whereYear('tanggal', $tahun);
    
        // Ambil semua PO yang sudah difilter
        $po = $query->orderByDesc('tanggal')->get();
    
        // Ambil daftar tahun unik dari tabel po untuk dropdown filter
        $tahunList = Po::selectRaw('YEAR(tanggal) as tahun')
        ->distinct()
        ->orderByDesc('tahun')
        ->pluck('tahun')
        ->toArray();
    
        return view('po.index', compact('po', 'tahun', 'tahunList')); //
    }
    
    
    public function create()
    {   
        if (auth()->user()->buat_po != 1) {
            abort(403, 'Anda tidak memiliki izin.');
        }
        $suppliers = Supplier::all();
        $proyek = Proyek::all();
        $barang = Barang::all();

        return view('po.create', compact('suppliers', 'proyek', 'barang'));
    }

    public function store(Request $request)
    {
        if (auth()->user()->buat_po != 1) {
            abort(403, 'Anda tidak memiliki izin.');
        }
        $request->validate([
            'no_po' => 'required',
            'tanggal' => 'required',
            'id_supplier' => 'required',
            'id_proyek' => 'required',
            'items' => 'required|array|min:1'
        ]);

        $diskonGlobal = $request->diskon_persen ?? 0;
        $ppnGlobal = $request->ppn_persen ?? 0;

        $grandSubtotal = 0;

        foreach ($request->items as $item) {
            $qty = floatval($item['qty']);
            $harga = floatval($item['harga']);
            $grandSubtotal += $qty * $harga;
        }

        $diskonRupiah = ($diskonGlobal / 100) * $grandSubtotal;
        $ppnRupiah = (($grandSubtotal - $diskonRupiah) * $ppnGlobal / 100);
        $grandTotal = $grandSubtotal - $diskonRupiah + $ppnRupiah;

        $supplier = Supplier::find($request->id_supplier);

        $po = Po::create([
            'no_po' => $request->no_po,
            'tanggal' => $request->tanggal,
            'id_supplier' => $request->id_supplier,
            'nama_supplier' => $supplier->nama_supplier,
            'id_proyek' => $request->id_proyek,
            'keterangan' => $request->keterangan,
            'total' => $grandTotal,
            'id_perusahaan' => auth()->user()->perusahaans->first()->id
        ]);

        foreach ($request->items as $item) {
            $qty = floatval($item['qty']);
            $harga = floatval($item['harga']);
            $subtotal = $qty * $harga;
            $diskonItem = ($diskonGlobal / 100) * $subtotal;
            $ppnItem = (($subtotal - $diskonItem) * $ppnGlobal / 100);
            $totalItem = ($subtotal - $diskonItem) + $ppnItem;

            PoDetail::create([
                'po_id' => $po->id,
                'kode_item' => $item['kode_item'],
                'uraian' => $item['uraian'],
                'qty' => $qty,
                'uom' => $item['uom'],
                'harga' => $harga,
                'diskon_persen' => $diskonGlobal,
                'diskon_rupiah' => $diskonItem,
                'ppn_persen' => $ppnGlobal,
                'ppn_rupiah' => $ppnItem,
                'total' => $totalItem
            ]);
        }

    // Cek tombol yang ditekan
    if ($request->submit == 'simpan') {
        return redirect()->route('po.index')->with('success', 'PO berhasil disimpan');
    } elseif ($request->submit == 'simpan_lanjut') {
        return redirect()->route('po.create')->with('success', 'PO berhasil disimpan, silakan input PO baru');
    }
    }

    public function edit($id)
    {
        if (auth()->user()->edit_po != 1) {
            abort(403, 'Anda tidak memiliki izin.');
        }
        $po = Po::with('details')->findOrFail($id);
        
        if ($po->status == 'sedang diproses') {
            return redirect()->route('po.index')->with('error', 'PO ini sudah diproses dan tidak dapat diedit.');
        }

        $suppliers = Supplier::all();
        $proyek = Proyek::all();
        $barang = Barang::all();

        return view('po.edit', compact('po', 'suppliers', 'proyek', 'barang'));
    }

    public function update(Request $request, $id)
    {
        if (auth()->user()->edit_po != 1) {
            abort(403, 'Anda tidak memiliki izin.');
        }
        $request->validate([
            'no_po' => 'required',
            'tanggal' => 'required',
            'id_supplier' => 'required',
            'id_proyek' => 'required',
            'items' => 'required|array|min:1'
        ]);

        $diskonGlobal = $request->diskon_persen ?? 0;
        $ppnGlobal = $request->ppn_persen ?? 0;

        $po = Po::findOrFail($id);
        $supplier = Supplier::findOrFail($request->id_supplier);

        $po->update([
            'no_po' => $request->no_po,
            'tanggal' => $request->tanggal,
            'id_supplier' => $request->id_supplier,
            'nama_supplier' => $supplier->nama_supplier,
            'id_proyek' => $request->id_proyek,
            'keterangan' => $request->keterangan,
        ]);

        $po->details()->delete();

        $grandTotal = 0;

        foreach ($request->items as $item) {
            $qty = floatval($item['qty']);
            $harga = floatval($item['harga']);
            $subtotal = $qty * $harga;
            $diskonItem = ($diskonGlobal / 100) * $subtotal;
            $ppnItem = (($subtotal - $diskonItem) * $ppnGlobal / 100);
            $totalItem = ($subtotal - $diskonItem) + $ppnItem;

            PoDetail::create([
                'po_id' => $po->id,
                'kode_item' => $item['kode_item'],
                'uraian' => $item['uraian'],
                'qty' => $qty,
                'uom' => $item['uom'],
                'harga' => $harga,
                'diskon_persen' => $diskonGlobal,
                'diskon_rupiah' => $diskonItem,
                'ppn_persen' => $ppnGlobal,
                'ppn_rupiah' => $ppnItem,
                'total' => $totalItem
            ]);

            $grandTotal += $totalItem;
        }

        $po->update(['total' => $grandTotal]);

        return redirect()->route('po.index')->with('success', 'PO berhasil diupdate.');
    }
        
    public function destroy($id)
    {
        if (auth()->user()->hapus_po != 1) {
            abort(403, 'Anda tidak memiliki izin.');
        }
        $po = Po::findOrFail($id);
        $po->details()->delete();
        $po->delete();

        return redirect()->route('po.index')->with('success', 'PO berhasil dihapus.');
    }
    public function print($id)
    {
        if (auth()->user()->print_po != 1) {
            abort(403, 'Anda tidak memiliki izin.');
        }
    
        $po = Po::with(['details', 'perusahaan', 'proyek', 'supplier'])->findOrFail($id);
    
        if ($po->status !== 'draft') {
            return redirect()->route('po.index')->with('info', 'PO sudah diproses atau dicetak sebelumnya.');
        }
    
        if (empty($po->perusahaan?->template_po)) {
            return back()->with('error', 'Template PO untuk perusahaan belum tersedia.');

            $templatePath = storage_path('app/public/' . $po->perusahaan->template_po);

            if (!file_exists($templatePath)) {
                return back()->with('error', 'File template tidak ditemukan di storage.');
            }

        }
    
        $templatePath = storage_path('app/public/' . $po->perusahaan->template_po);
        $outputDir = storage_path("app/public/po_files/{$po->id}");
        $poFileName = 'PO_' . str_replace('/', '_', $po->no_po);
        $outputDocx = "{$outputDir}/{$poFileName}.docx";
        $outputPdf = "{$outputDir}/{$poFileName}.pdf";
    
        if (!file_exists($outputDir)) {
            mkdir($outputDir, 0777, true);
        }
    
        $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($templatePath);
        $templateProcessor->setValue('no_po', $po->no_po);
        $templateProcessor->setValue('tanggal', Carbon::parse($po->tanggal)->translatedFormat('d F Y'));
        $templateProcessor->setValue('supplier', $po->nama_supplier);
        $templateProcessor->setValue('keterangan', $po->keterangan ?? '-');
        $templateProcessor->setValue('proyek', $po->proyek->nama_proyek ?? '-');
        $templateProcessor->setValue('pic', $po->supplier->pic ?? '-');
        $templateProcessor->setValue('no_kontak', $po->supplier->no_kontak ?? '-');
    
        $templateProcessor->cloneRow('item_no', $po->details->count());
        $grandTotal = 0;
    
        foreach ($po->details as $i => $detail) {
            $row = $i + 1;
            $templateProcessor->setValue("item_no#{$row}", $row);
            $templateProcessor->setValue("uraian#{$row}", $detail->uraian);
            $templateProcessor->setValue("qty#{$row}", number_format($detail->qty, 0, ',', '.'));
            $templateProcessor->setValue("uom#{$row}", $detail->uom);
            $templateProcessor->setValue("harga#{$row}", number_format($detail->harga, 0, ',', '.'));
            $templateProcessor->setValue("total#{$row}", number_format($detail->total, 0, ',', '.'));
            $grandTotal += $detail->total;
        }
    
        $diskonPersen = $po->details->first()->diskon_persen ?? 0;
        $diskonRupiah = ($diskonPersen / 100) * $grandTotal;
        $ppnPersen = $po->details->first()->ppn_persen ?? 0;
        $ppnRupiah = (($grandTotal - $diskonRupiah) * $ppnPersen / 100);
        $finalTotal = ($grandTotal - $diskonRupiah) + $ppnRupiah;
    
        $templateProcessor->setValue('subtotal', number_format($grandTotal, 0, ',', '.'));
        $templateProcessor->setValue('diskon_persen', $diskonPersen ? number_format($diskonPersen, 0, ',', '.') : '');
        $templateProcessor->setValue('diskon_rupiah', $diskonRupiah ? number_format($diskonRupiah, 0, ',', '.') : '');
        $templateProcessor->setValue('ppn_persen', $ppnPersen ? number_format($ppnPersen, 0, ',', '.') : '');
        $templateProcessor->setValue('ppn_rupiah', $ppnRupiah ? number_format($ppnRupiah, 0, ',', '.') : '');
        $templateProcessor->setValue('grand_total', number_format($finalTotal, 0, ',', '.'));
    
        $templateProcessor->saveAs($outputDocx);
    
        $libreOfficePath = '"C:\\Program Files\\LibreOffice\\program\\soffice.exe"';
        $command = "{$libreOfficePath} --headless --convert-to pdf --outdir " . escapeshellarg($outputDir) . ' ' . escapeshellarg($outputDocx);
        exec($command, $output, $resultCode);
    
        if ($resultCode !== 0) {
            return back()->with('error', 'Konversi gagal. Hubungi administrator. Kode: ' . $resultCode);
        }
    
        $pdfPath = "po_files/{$po->id}/{$poFileName}.pdf";
    
        $po->update([
            'status' => 'sedang diproses',
            'printed_at' => now(),
            'file_path' => $pdfPath,
        ]);
    
        // 👇 Preview otomatis hasil cetak (PDF)
        if (!file_exists(storage_path('app/public/' . $pdfPath))) {
            return back()->with('error', 'PDF gagal dibuat atau tidak ditemukan.');
        }
        
        return redirect()->route('po.index')->with([
            'success' => 'PO berhasil dicetak.',
            'preview_pdf' => asset('storage/' . $pdfPath)
        ]);
        
    }
    
public function revisi($id)
{
    if (auth()->user()->revisi_po != 1) {
        abort(403, 'Anda tidak memiliki izin.');
    }

    $po = Po::findOrFail($id);

    if ($po->status != 'sedang diproses') {
        return redirect()->route('po.index')->with('error', 'Hanya PO yang sedang diproses yang bisa direvisi.');
    }

    // Hapus file dari storage jika ada
    if ($po->file_path && \Storage::exists('public/' . $po->file_path)) {
        \Storage::delete('public/' . $po->file_path);
    }

    // Update status PO menjadi draft dan kosongkan file_path
    $po->update([
        'status' => 'draft',
        'file_path' => null
    ]);

    return redirect()->route('po.index')->with('success', 'PO berhasil direvisi dan status dikembalikan menjadi draft.');
}

    
}

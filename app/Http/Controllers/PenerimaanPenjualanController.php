<?php

namespace App\Http\Controllers;

use App\Models\PenerimaanPenjualan;
use App\Models\PenerimaanPenjualanDetail;
use App\Models\FakturPenjualan;
use Illuminate\Http\Request;

class PenerimaanPenjualanController extends Controller
{
    public function index()
    {
        $penerimaanPenjualan = PenerimaanPenjualan::with(['fakturPenjualan', 'details.faktur', 'pembuatnya', 'penyetujunya'])
            ->orderBy('tanggal', 'desc')
            ->paginate(20);

        return view('penerimaan-penjualan.index', compact('penerimaanPenjualan'));
    }

    public function create(Request $request)
    {
        $fakturPenjualan = FakturPenjualan::where('status_pembayaran', '!=', 'lunas')
            ->with(['sertifikatPembayaran', 'perusahaan'])
            ->orderBy('tanggal', 'desc')
            ->get();
        
        // Pre-select faktur if passed from query parameter
        $selectedFakturId = $request->query('faktur_penjualan_id');

        return view('penerimaan-penjualan.create', compact('fakturPenjualan', 'selectedFakturId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tanggal' => 'required|date',
            'metode_pembayaran' => 'required|string|max:50',
            'keterangan' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.faktur_penjualan_id' => 'required|exists:faktur_penjualan,id',
            'items.*.nominal' => 'required|numeric|min:0.01',
            'items.*.pph_dipotong' => 'nullable|numeric|min:0',
            'items.*.keterangan_pph' => 'nullable|string|max:100',
        ]);

        // Pastikan semua faktur dari pemberi kerja (perusahaan) yang sama
        $fakturIds = collect($validated['items'])->pluck('faktur_penjualan_id')->filter()->unique();
        $fakturs = FakturPenjualan::whereIn('id', $fakturIds)->get();
        $perusahaanIds = $fakturs->pluck('id_perusahaan')->filter()->unique();
        if ($perusahaanIds->count() > 1) {
            return back()->withInput()->withErrors(['items' => 'Semua faktur harus dari pemberi kerja (perusahaan) yang sama.']);
        }

        $totalNominal = collect($validated['items'])->sum(fn($row) => floatval($row['nominal']));
        $totalPph = collect($validated['items'])->sum(fn($row) => floatval($row['pph_dipotong'] ?? 0));
        $firstFakturId = $fakturIds->first();

        $penerimaan = PenerimaanPenjualan::create([
            'no_bukti' => PenerimaanPenjualan::generateNomorBukti(),
            'tanggal' => $validated['tanggal'],
            'faktur_penjualan_id' => $firstFakturId, // legacy anchor
            'nominal' => $totalNominal,
            'pph_dipotong' => $totalPph,
            'keterangan_pph' => null,
            'metode_pembayaran' => $validated['metode_pembayaran'],
            'keterangan' => $validated['keterangan'] ?? null,
            'status' => 'draft',
            'dibuat_oleh_id' => auth()->id(),
        ]);

        foreach ($validated['items'] as $row) {
            PenerimaanPenjualanDetail::create([
                'penerimaan_penjualan_id' => $penerimaan->id,
                'faktur_penjualan_id' => $row['faktur_penjualan_id'],
                'nominal' => $row['nominal'],
                'pph_dipotong' => $row['pph_dipotong'] ?? 0,
                'keterangan_pph' => $row['keterangan_pph'] ?? null,
            ]);
        }

        // Update status pembayaran faktur yang terlibat
        foreach ($fakturIds as $fid) {
            $this->updateFakturPembayaranStatus($fid);
        }

        return redirect()
            ->route('penerimaan-penjualan.show', $penerimaan->id)
            ->with('success', 'Penerimaan pembayaran berhasil dibuat dengan nomor: ' . $penerimaan->no_bukti);
    }

    public function edit(PenerimaanPenjualan $penerimaanPenjualan)
    {
        if ($penerimaanPenjualan->status !== 'draft') {
            return redirect()
                ->route('penerimaan-penjualan.show', $penerimaanPenjualan->id)
                ->with('error', 'Hanya draft yang dapat diedit');
        }

        $fakturPenjualan = FakturPenjualan::where('status_pembayaran', '!=', 'lunas')
            ->orWhere('id', $penerimaanPenjualan->faktur_penjualan_id)
            ->with(['sertifikatPembayaran', 'perusahaan'])
            ->orderBy('tanggal', 'desc')
            ->get();

        return view('penerimaan-penjualan.edit', compact('penerimaanPenjualan', 'fakturPenjualan'));
    }

    public function update(Request $request, PenerimaanPenjualan $penerimaanPenjualan)
    {
        if ($penerimaanPenjualan->status !== 'draft') {
            return redirect()
                ->route('penerimaan-penjualan.show', $penerimaanPenjualan->id)
                ->with('error', 'Hanya draft yang dapat diubah');
        }

        $validated = $request->validate([
            'tanggal' => 'required|date',
            'metode_pembayaran' => 'required|string|max:50',
            'keterangan' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.faktur_penjualan_id' => 'required|exists:faktur_penjualan,id',
            'items.*.nominal' => 'required|numeric|min:0.01',
            'items.*.pph_dipotong' => 'nullable|numeric|min:0',
            'items.*.keterangan_pph' => 'nullable|string|max:100',
        ]);

        $oldFakturIds = $penerimaanPenjualan->details()->pluck('faktur_penjualan_id')->unique();
        if ($oldFakturIds->isEmpty() && $penerimaanPenjualan->faktur_penjualan_id) {
            $oldFakturIds = collect([$penerimaanPenjualan->faktur_penjualan_id]);
        }

        $fakturIds = collect($validated['items'])->pluck('faktur_penjualan_id')->filter()->unique();
        $fakturs = FakturPenjualan::whereIn('id', $fakturIds)->get();
        $perusahaanIds = $fakturs->pluck('id_perusahaan')->filter()->unique();
        if ($perusahaanIds->count() > 1) {
            return back()->withInput()->withErrors(['items' => 'Semua faktur harus dari pemberi kerja (perusahaan) yang sama.']);
        }

        $totalNominal = collect($validated['items'])->sum(fn($row) => floatval($row['nominal']));
        $totalPph = collect($validated['items'])->sum(fn($row) => floatval($row['pph_dipotong'] ?? 0));
        $firstFakturId = $fakturIds->first();

        $penerimaanPenjualan->update([
            'tanggal' => $validated['tanggal'],
            'faktur_penjualan_id' => $firstFakturId,
            'nominal' => $totalNominal,
            'pph_dipotong' => $totalPph,
            'keterangan_pph' => null,
            'metode_pembayaran' => $validated['metode_pembayaran'],
            'keterangan' => $validated['keterangan'] ?? null,
        ]);

        $penerimaanPenjualan->details()->delete();
        foreach ($validated['items'] as $row) {
            $penerimaanPenjualan->details()->create([
                'faktur_penjualan_id' => $row['faktur_penjualan_id'],
                'nominal' => $row['nominal'],
                'pph_dipotong' => $row['pph_dipotong'] ?? 0,
                'keterangan_pph' => $row['keterangan_pph'] ?? null,
            ]);
        }

        $affected = $oldFakturIds->merge($fakturIds)->unique();
        foreach ($affected as $fid) {
            $this->updateFakturPembayaranStatus($fid);
        }

        return redirect()
            ->route('penerimaan-penjualan.show', $penerimaanPenjualan->id)
            ->with('success', 'Penerimaan pembayaran berhasil diperbarui');
    }

    public function revisi(PenerimaanPenjualan $penerimaanPenjualan)
    {
        if ($penerimaanPenjualan->status !== 'approved') {
            return redirect()
                ->route('penerimaan-penjualan.show', $penerimaanPenjualan->id)
                ->with('error', 'Hanya approved yang dapat direvisi');
        }

        $penerimaanPenjualan->update([
            'status' => 'draft',
            'disetujui_oleh_id' => null,
            'tanggal_disetujui' => null,
        ]);

        $fakturIds = $penerimaanPenjualan->details()->pluck('faktur_penjualan_id');
        if ($fakturIds->isEmpty() && $penerimaanPenjualan->faktur_penjualan_id) {
            $fakturIds = collect([$penerimaanPenjualan->faktur_penjualan_id]);
        }
        foreach ($fakturIds as $fid) {
            $this->updateFakturPembayaranStatus($fid);
        }

        return redirect()
            ->route('penerimaan-penjualan.show', $penerimaanPenjualan->id)
            ->with('success', 'Penerimaan pembayaran berhasil direvisi ke draft');
    }

    public function show(PenerimaanPenjualan $penerimaanPenjualan)
    {
        $penerimaanPenjualan->load(['details.faktur.sertifikatPembayaran', 'pembuatnya', 'penyetujunya']);

        return view('penerimaan-penjualan.show', compact('penerimaanPenjualan'));
    }

    public function approve(PenerimaanPenjualan $penerimaanPenjualan)
    {
        if ($penerimaanPenjualan->status !== 'draft') {
            return redirect()
                ->route('penerimaan-penjualan.show', $penerimaanPenjualan->id)
                ->with('error', 'Hanya draft yang dapat disetujui');
        }

        $penerimaanPenjualan->update([
            'status' => 'approved',
            'disetujui_oleh_id' => auth()->id(),
            'tanggal_disetujui' => now(),
        ]);

        // Update status pembayaran semua faktur terkait
        $fakturIds = $penerimaanPenjualan->details()->pluck('faktur_penjualan_id');
        if ($fakturIds->isEmpty() && $penerimaanPenjualan->faktur_penjualan_id) {
            $fakturIds = collect([$penerimaanPenjualan->faktur_penjualan_id]);
        }
        foreach ($fakturIds as $fid) {
            $this->updateFakturPembayaranStatus($fid);
        }

        return redirect()
            ->route('penerimaan-penjualan.show', $penerimaanPenjualan->id)
            ->with('success', 'Penerimaan pembayaran telah disetujui');
    }

    public function destroy(PenerimaanPenjualan $penerimaanPenjualan)
    {
        if ($penerimaanPenjualan->status !== 'draft') {
            return redirect()
                ->route('penerimaan-penjualan.show', $penerimaanPenjualan->id)
                ->with('error', 'Hanya draft yang dapat dihapus');
        }

        $fakturIds = $penerimaanPenjualan->details()->pluck('faktur_penjualan_id');
        if ($fakturIds->isEmpty() && $penerimaanPenjualan->faktur_penjualan_id) {
            $fakturIds = collect([$penerimaanPenjualan->faktur_penjualan_id]);
        }

        $penerimaanPenjualan->delete();

        foreach ($fakturIds as $fid) {
            $this->updateFakturPembayaranStatus($fid);
        }

        return redirect()
            ->route('penerimaan-penjualan.index')
            ->with('success', 'Penerimaan pembayaran telah dihapus');
    }

    private function updateFakturPembayaranStatus($fakturPenjualanId)
    {
        $faktur = FakturPenjualan::findOrFail($fakturPenjualanId);

        $sumDetail = PenerimaanPenjualanDetail::where('faktur_penjualan_id', $fakturPenjualanId)
            ->whereHas('penerimaan', function ($q) {
                $q->whereIn('status', ['draft', 'approved']);
            })
            ->sum('nominal');

        $legacySum = PenerimaanPenjualan::where('faktur_penjualan_id', $fakturPenjualanId)
            ->whereDoesntHave('details')
            ->whereIn('status', ['draft', 'approved'])
            ->sum('nominal');

        $totalDiterima = $sumDetail + $legacySum;
        $total = $faktur->total;

        if ($totalDiterima >= $total) {
            $faktur->update(['status_pembayaran' => 'lunas']);
        } elseif ($totalDiterima > 0) {
            $faktur->update(['status_pembayaran' => 'sebagian']);
        } else {
            $faktur->update(['status_pembayaran' => 'belum_dibayar']);
        }
    }
}

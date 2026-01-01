<?php

namespace App\Http\Controllers;

use App\Models\PenerimaanPenjualan;
use App\Models\FakturPenjualan;
use Illuminate\Http\Request;

class PenerimaanPenjualanController extends Controller
{
    public function index()
    {
        $penerimaanPenjualan = PenerimaanPenjualan::with(['fakturPenjualan', 'pembuatnya', 'penyetujunya'])
            ->orderBy('tanggal', 'desc')
            ->paginate(20);

        return view('penerimaan-penjualan.index', compact('penerimaanPenjualan'));
    }

    public function create(Request $request)
    {
        $fakturPenjualan = FakturPenjualan::where('status_pembayaran', '!=', 'lunas')
            ->with('sertifikatPembayaran')
            ->get();
        
        // Pre-select faktur if passed from query parameter
        $selectedFakturId = $request->query('faktur_penjualan_id');

        return view('penerimaan-penjualan.create', compact('fakturPenjualan', 'selectedFakturId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tanggal' => 'required|date',
            'faktur_penjualan_id' => 'required|exists:faktur_penjualan,id',
            'nominal' => 'required|numeric|min:0.01',
            'pph_dipotong' => 'nullable|numeric|min:0',
            'keterangan_pph' => 'nullable|string|max:100',
            'metode_pembayaran' => 'required|string|max:50',
            'keterangan' => 'nullable|string',
        ]);

        $validated['no_bukti'] = PenerimaanPenjualan::generateNomorBukti();
        $validated['dibuat_oleh_id'] = auth()->id();
        $validated['status'] = 'draft';
        $validated['pph_dipotong'] = $validated['pph_dipotong'] ?? 0;

        $penerimaan = PenerimaanPenjualan::create($validated);

        // Update status pembayaran faktur
        $this->updateFakturPembayaranStatus($penerimaan->faktur_penjualan_id);

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
            ->with('sertifikatPembayaran')
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
            'faktur_penjualan_id' => 'required|exists:faktur_penjualan,id',
            'nominal' => 'required|numeric|min:0.01',
            'pph_dipotong' => 'nullable|numeric|min:0',
            'keterangan_pph' => 'nullable|string|max:100',
            'metode_pembayaran' => 'required|string|max:50',
            'keterangan' => 'nullable|string',
        ]);

        $oldFakturId = $penerimaanPenjualan->faktur_penjualan_id;
        $validated['pph_dipotong'] = $validated['pph_dipotong'] ?? 0;

        $penerimaanPenjualan->update($validated);

        // Update status pembayaran untuk faktur lama dan baru jika berbeda
        if ($oldFakturId != $penerimaanPenjualan->faktur_penjualan_id) {
            $this->updateFakturPembayaranStatus($oldFakturId);
        }
        $this->updateFakturPembayaranStatus($penerimaanPenjualan->faktur_penjualan_id);

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

        // Update status pembayaran faktur
        $this->updateFakturPembayaranStatus($penerimaanPenjualan->faktur_penjualan_id);

        return redirect()
            ->route('penerimaan-penjualan.show', $penerimaanPenjualan->id)
            ->with('success', 'Penerimaan pembayaran berhasil direvisi ke draft');
    }

    public function show(PenerimaanPenjualan $penerimaanPenjualan)
    {
        $penerimaanPenjualan->load(['fakturPenjualan.sertifikatPembayaran', 'pembuatnya', 'penyetujunya']);

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

        $fakturId = $penerimaanPenjualan->faktur_penjualan_id;
        $penerimaanPenjualan->delete();

        // Update status pembayaran faktur setelah penghapusan
        $this->updateFakturPembayaranStatus($fakturId);

        return redirect()
            ->route('penerimaan-penjualan.index')
            ->with('success', 'Penerimaan pembayaran telah dihapus');
    }

    private function updateFakturPembayaranStatus($fakturPenjualanId)
    {
        $faktur = FakturPenjualan::findOrFail($fakturPenjualanId);
        $totalDiterima = PenerimaanPenjualan::where('faktur_penjualan_id', $fakturPenjualanId)
            ->whereIn('status', ['draft', 'approved'])
            ->sum('nominal');

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

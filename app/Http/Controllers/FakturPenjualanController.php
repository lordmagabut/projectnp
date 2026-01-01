<?php

namespace App\Http\Controllers;

use App\Models\FakturPenjualan;
use Illuminate\Http\Request;

class FakturPenjualanController extends Controller
{
    public function index()
    {
        // Ambil faktur penjualan dari tabel terpisah
        $fakturs = FakturPenjualan::with('proyek')
            ->orderByDesc('tanggal')
            ->paginate(20);

        return view('faktur-penjualan.index', compact('fakturs'));
    }

    public function show($id)
    {
        $faktur = FakturPenjualan::with('proyek', 'sertifikatPembayaran')->findOrFail($id);
        return view('faktur-penjualan.show', compact('faktur'));
    }

    public function edit($id)
    {
        $faktur = FakturPenjualan::findOrFail($id);
        if ($faktur->status !== 'draft') {
            return redirect()->route('faktur-penjualan.show', $faktur->id)
                ->with('warning', 'Hanya faktur draft yang dapat diedit.');
        }
        return view('faktur-penjualan.edit', compact('faktur'));
    }

    public function update(Request $request, $id)
    {
        $faktur = FakturPenjualan::findOrFail($id);
        if ($faktur->status !== 'draft') {
            return redirect()->route('faktur-penjualan.show', $faktur->id)
                ->with('warning', 'Hanya faktur draft yang dapat diedit.');
        }

        $validated = $request->validate([
            'tanggal' => 'required|date',
            'subtotal' => 'required|numeric|min:0',
            'total_diskon' => 'nullable|numeric|min:0',
            'total_ppn' => 'required|numeric|min:0',
            'retensi_persen' => 'nullable|numeric|min:0|max:100',
            'retensi_nilai' => 'nullable|numeric|min:0',
            'ppn_persen' => 'nullable|numeric|min:0|max:100',
            'ppn_nilai' => 'nullable|numeric|min:0',
            'pph_persen' => 'nullable|numeric|min:0|max:100',
            'pph_nilai' => 'nullable|numeric|min:0',
            'uang_muka_dipakai' => 'nullable|numeric|min:0',
        ]);

        $validated['total_diskon'] = $validated['total_diskon'] ?? 0;
        $validated['retensi_persen'] = $validated['retensi_persen'] ?? 0;
        $validated['retensi_nilai'] = $validated['retensi_nilai'] ?? 0;
        $validated['ppn_persen'] = $validated['ppn_persen'] ?? 0;
        $validated['ppn_nilai'] = $validated['ppn_nilai'] ?? 0;
        $validated['pph_persen'] = $validated['pph_persen'] ?? 0;
        $validated['pph_nilai'] = $validated['pph_nilai'] ?? 0;
        $validated['uang_muka_dipakai'] = $validated['uang_muka_dipakai'] ?? 0;

        // Recalculate total
        $validated['total'] = $validated['subtotal'] - $validated['total_diskon'] 
            + $validated['total_ppn'];

        $faktur->update($validated);

        return redirect()->route('faktur-penjualan.show', $faktur->id)
            ->with('success', 'Faktur berhasil diperbarui.');
    }

    public function revisi($id)
    {
        $faktur = FakturPenjualan::findOrFail($id);
        if ($faktur->status !== 'approved') {
            return redirect()->route('faktur-penjualan.show', $faktur->id)
                ->with('warning', 'Hanya faktur yang sudah disetujui yang dapat direvisi.');
        }

        // Revert ke draft untuk perbaikan
        $faktur->status = 'draft';
        $faktur->save();

        return redirect()->route('faktur-penjualan.show', $faktur->id)
            ->with('success', 'Faktur dikembalikan ke draft untuk revisi.');
    }

    public function destroy($id)
    {
        $faktur = FakturPenjualan::findOrFail($id);
        
        // Hanya draft dan belum ada penerimaan yang bisa dihapus
        $penerimaanCount = $faktur->penerimaanPenjualan()->count();
        if ($penerimaanCount > 0) {
            return redirect()->route('faktur-penjualan.show', $faktur->id)
                ->with('error', 'Tidak dapat menghapus faktur yang sudah menerima pembayaran.');
        }

        $noFaktur = $faktur->no_faktur;
        $faktur->delete();

        return redirect()->route('faktur-penjualan.index')
            ->with('success', "Faktur {$noFaktur} berhasil dihapus.");
    }
}


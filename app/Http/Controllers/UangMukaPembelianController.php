<?php

namespace App\Http\Controllers;

use App\Models\Po;
use App\Models\Supplier;
use App\Models\UangMukaPembelian;
use App\Models\Perusahaan;
use App\Models\Coa;
use App\Models\Jurnal;
use App\Models\AccountMapping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UangMukaPembelianController extends Controller
{
    public function index(Request $request)
    {
        $query = UangMukaPembelian::with(['po', 'supplier', 'perusahaan']);
        
        if ($request->status) {
            $query->where('status', $request->status);
        }
        
        if ($request->id_perusahaan) {
            $query->where('id_perusahaan', $request->id_perusahaan);
        }

        // Add payment status filter
        if ($request->payment_status === 'paid') {
            $query->where('status', 'approved')
                  ->whereExists(function($q){
                      $q->select('id')
                        ->from('jurnal')
                        ->whereColumn('jurnal.ref_id', 'uang_muka_pembelian.id')
                        ->where('jurnal.ref_table', 'uang_muka_pembelian');
                  });
        } elseif ($request->payment_status === 'unpaid') {
            $query->where('status', 'approved')
                  ->whereNotExists(function($q){
                      $q->select('id')
                        ->from('jurnal')
                        ->whereColumn('jurnal.ref_id', 'uang_muka_pembelian.id')
                        ->where('jurnal.ref_table', 'uang_muka_pembelian');
                  });
        }

        $uangMukas = $query->orderBy('tanggal', 'desc')->paginate(15);
        $perusahaans = Perusahaan::all();

        return view('uang-muka-pembelian.index', compact('uangMukas', 'perusahaans'));
    }

    public function create(Request $request)
    {
        $po_id = $request->po_id;
        $po = null;

        if ($po_id) {
            $po = Po::with(['supplier', 'perusahaan', 'proyek', 'details'])->findOrFail($po_id);
            // Cegah UM untuk PO yang sudah selesai
            if ($po->status === 'selesai') {
                return redirect()->route('po.show', $po->id)->with('error', 'PO sudah selesai, tidak bisa membuat UM baru');
            }
        }

        // Ambil daftar PO yang eligible untuk Uang Muka: sudah disetujui (sedang diproses atau selesai)
        // dan belum ada UM yang dibuat untuk PO tersebut
        $existingUmPoIds = UangMukaPembelian::pluck('po_id')->toArray();
        $pos = Po::with(['supplier', 'details'])
            ->whereIn('status', ['sedang diproses', 'selesai'])
            ->whereNotIn('id', $existingUmPoIds)
            ->orderBy('tanggal', 'desc')
            ->get();

        $perusahaans = Perusahaan::all();
        $suppliers = Supplier::all();

        return view('uang-muka-pembelian.create', compact('po', 'perusahaans', 'suppliers', 'pos'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'po_id' => 'required|exists:po,id',
            'tanggal' => 'required|date',
            'nominal' => 'required|numeric|min:0.01',
            'keterangan' => 'nullable|string',
            'file_path' => 'nullable|file|mimes:pdf|max:30000',
        ]);

        $po = Po::findOrFail($validated['po_id']);

        // Generate nomor UM
        $no_uang_muka = $this->generateNomorUangMuka($po->id_perusahaan);

        DB::transaction(function () use ($validated, $po, $no_uang_muka, $request) {
            $filePath = null;
            if ($request->hasFile('file_path')) {
                $filePath = $request->file('file_path')->store('uang-muka-pembelian', 'public');
            }

            $uangMuka = UangMukaPembelian::create([
                'no_uang_muka' => $no_uang_muka,
                'tanggal' => $validated['tanggal'],
                'po_id' => $validated['po_id'],
                'id_supplier' => $po->id_supplier,
                'nama_supplier' => $po->nama_supplier,
                'id_perusahaan' => $po->id_perusahaan,
                'id_proyek' => $po->id_proyek,
                'nominal' => $validated['nominal'],
                'metode_pembayaran' => null, // Will be filled on BKK payment
                'no_rekening_bank' => null,
                'nama_bank' => null,
                'tanggal_transfer' => null,
                'no_bukti_transfer' => null,
                'keterangan' => $validated['keterangan'],
                'status' => 'draft',
                'file_path' => $filePath,
            ]);

            // Jangan posting jurnal dulu sampai di-approve
        });

        return redirect()->route('uang-muka-pembelian.show', $no_uang_muka)
                        ->with('success', 'Uang Muka berhasil dibuat');
    }

    public function show($id)
    {
        // Support either numeric ID or code like UM-2025-0002
        $uangMuka = UangMukaPembelian::with(['po', 'supplier', 'perusahaan', 'proyek'])
            ->when(is_numeric($id), function($q) use ($id) {
                $q->where('id', $id);
            }, function($q) use ($id) {
                $q->where('no_uang_muka', $id);
            })
            ->firstOrFail();

        // Payment is considered done when a BKK journal exists for this UM
        $isPaid = $uangMuka->status === 'approved' && Jurnal::where('ref_table', 'uang_muka_pembelian')
            ->where('ref_id', $uangMuka->id)
            ->exists();

        return view('uang-muka-pembelian.show', compact('uangMuka', 'isPaid'));
    }

    public function edit($id)
    {
        $uangMuka = UangMukaPembelian::findOrFail($id);
        
        // Hanya draft yang bisa diedit
        if ($uangMuka->status !== 'draft') {
            return redirect()->route('uang-muka-pembelian.show', $uangMuka->id)
                            ->with('error', 'Hanya UM berstatus draft yang bisa diedit');
        }

        $perusahaans = Perusahaan::all();
        $suppliers = Supplier::all();

        return view('uang-muka-pembelian.edit', compact('uangMuka', 'perusahaans', 'suppliers'));
    }

    public function update(Request $request, $id)
    {
        $uangMuka = UangMukaPembelian::findOrFail($id);

        if ($uangMuka->status !== 'draft') {
            return redirect()->route('uang-muka-pembelian.show', $uangMuka->id)
                            ->with('error', 'Hanya UM berstatus draft yang bisa diupdate');
        }

        $validated = $request->validate([
            'tanggal' => 'required|date',
            'nominal' => 'required|numeric|min:0.01',
            'metode_pembayaran' => 'required|in:transfer,cek,tunai,giro',
            'no_rekening_bank' => 'nullable|string',
            'nama_bank' => 'nullable|string',
            'tanggal_transfer' => 'nullable|date',
            'no_bukti_transfer' => 'nullable|string',
            'keterangan' => 'nullable|string',
            'file_path' => 'nullable|file|mimes:pdf|max:30000',
        ]);

        DB::transaction(function () use ($validated, $uangMuka, $request) {
            if ($request->hasFile('file_path')) {
                $filePath = $request->file('file_path')->store('uang-muka-pembelian', 'public');
                $validated['file_path'] = $filePath;
            }

            $uangMuka->update($validated);
        });

        return redirect()->route('uang-muka-pembelian.show', $uangMuka->id)
                        ->with('success', 'Uang Muka berhasil diupdate');
    }

    public function approve(Request $request, $id)
    {
        $uangMuka = UangMukaPembelian::findOrFail($id);

        if ($uangMuka->status !== 'draft') {
            return back()->with('error', 'Hanya UM draft yang bisa di-approve');
        }

        $uangMuka->update(['status' => 'approved']);
        return back()->with('success', 'Uang Muka berhasil di-approve. Silakan lakukan pembayaran melalui menu BKK.');
    }

    public function printBkk($id)
    {
        $uangMuka = UangMukaPembelian::with(['po', 'supplier', 'perusahaan', 'proyek'])->findOrFail($id);

        if ($uangMuka->status !== 'approved') {
            return redirect()->route('uang-muka-pembelian.show', $uangMuka->id)
                             ->with('error', 'BKK hanya tersedia untuk Uang Muka berstatus Approved');
        }

        // Compute DPP/PPN breakdown for display (same logic as journal)
        $ppnRate = 0.0;
        try {
            $ppnRate = floatval($uangMuka->po?->ppn_persen ?? 0) / 100.0;
        } catch (\Throwable $e) {
            $ppnRate = 0.0;
        }
        $umTotal = floatval($uangMuka->nominal);
        $umPpn = $ppnRate > 0 ? round($umTotal * ($ppnRate / (1 + $ppnRate)), 2) : 0.0;
        $umDpp = max(0, round($umTotal - $umPpn, 2));

        return view('uang-muka-pembelian.bkk', compact('uangMuka', 'umDpp', 'umPpn', 'ppnRate'));
    }

    public function createBkk($id)
    {
        $uangMuka = UangMukaPembelian::with(['perusahaan', 'supplier'])->findOrFail($id);
        if ($uangMuka->status !== 'approved') {
            return redirect()->route('uang-muka-pembelian.show', $uangMuka->id)
                             ->with('error', 'Hanya UM berstatus Approved yang dapat dibayar via BKK');
        }
        $coaKas = Coa::where('tipe', 'Aset Lancar')
                     ->orWhere('nama_akun', 'like', '%Kas%')
                     ->orWhere('nama_akun', 'like', '%Bank%')
                     ->get();
        return view('uang-muka-pembelian.bkk-create', compact('uangMuka', 'coaKas'));
    }

    public function storeBkk(Request $request, $id)
    {
        $uangMuka = UangMukaPembelian::findOrFail($id);
        if ($uangMuka->status !== 'approved') {
            return redirect()->route('uang-muka-pembelian.show', $uangMuka->id)
                             ->with('error', 'Hanya UM berstatus Approved yang dapat dibayar via BKK');
        }

        $validated = $request->validate([
            'coa_id'  => 'required|exists:coa,id',
            'tanggal' => 'required|date',
            'metode_pembayaran' => 'required|in:transfer,cek,tunai,giro',
            'nama_bank' => 'nullable|string',
            'no_rekening_bank' => 'nullable|string',
            'tanggal_transfer' => 'nullable|date',
            'no_bukti_transfer' => 'nullable|string',
            'file_path' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        DB::transaction(function () use ($validated, $uangMuka, $request) {
            // Update UM payment details
            $updateData = [
                'metode_pembayaran' => $validated['metode_pembayaran'],
                'nama_bank' => $validated['nama_bank'] ?? null,
                'no_rekening_bank' => $validated['no_rekening_bank'] ?? null,
                'tanggal_transfer' => $validated['tanggal_transfer'] ?? null,
                'no_bukti_transfer' => $validated['no_bukti_transfer'] ?? null,
            ];

            // Handle file upload
            if ($request->hasFile('file_path')) {
                if ($uangMuka->file_path) {
                    \Storage::disk('public')->delete($uangMuka->file_path);
                }
                $updateData['file_path'] = $request->file('file_path')->store('uang_muka', 'public');
            }

            $uangMuka->update($updateData);

            // Create BKK Journal
            $coaUangMukaId = AccountMapping::getCoaId('uang_muka_vendor') 
                             ?? Coa::where('no_akun', '1-150')->first()?->id;
            
            $coaBankId = $validated['coa_id'];
            if (!$coaUangMukaId || !$coaBankId) {
                throw new \Exception('COA Uang Muka (1-150) atau Kas/Bank tidak ditemukan');
            }

            $jurnal = Jurnal::create([
                'no_jurnal'     => Jurnal::generateNomor(),
                'tanggal'       => $validated['tanggal'],
                'keterangan'    => "Pembayaran UM {$uangMuka->no_uang_muka} - {$uangMuka->nama_supplier}",
                'id_perusahaan' => $uangMuka->id_perusahaan,
                'total'         => $uangMuka->nominal,
                'tipe'          => 'Jurnal Umum',
                'ref_table'     => 'uang_muka_pembelian',
                'ref_id'        => $uangMuka->id,
            ]);

            // Split UM into DPP + PPN based on PO's PPN rate
            $ppnRate = 0.0;
            try {
                $ppnRate = floatval($uangMuka->po?->ppn_persen ?? 0) / 100.0;
            } catch (\Throwable $e) {
                $ppnRate = 0.0;
            }
            $umTotal = floatval($uangMuka->nominal);
            $umPpn = $ppnRate > 0 ? round($umTotal * ($ppnRate / (1 + $ppnRate)), 2) : 0.0;
            $umDpp = max(0, round($umTotal - $umPpn, 2));

            // Debit Uang Muka (DPP)
            $jurnal->details()->create([
                'coa_id' => $coaUangMukaId,
                'debit'  => $umDpp,
                'kredit' => 0,
            ]);

            // Debit PPN Masukan untuk komponen PPN dari UM (jika ada)
            if ($umPpn > 0) {
                $coaPpnMasukan = \App\Services\AccountService::getPpnMasukan($uangMuka->id_perusahaan);
                if (!$coaPpnMasukan) {
                    throw new \Exception('COA PPN Masukan tidak ditemukan');
                }
                $jurnal->details()->create([
                    'coa_id' => $coaPpnMasukan,
                    'debit'  => $umPpn,
                    'kredit' => 0,
                ]);
            }

            // Credit Kas/Bank sebesar total UM
            $jurnal->details()->create([
                'coa_id' => $coaBankId,
                'debit'  => 0,
                'kredit' => $umTotal,
            ]);
        });

        return redirect()->route('uang-muka-pembelian.show', $uangMuka->id)
                         ->with('success', 'Pembayaran UM berhasil diposting melalui BKK.');
    }

    public function destroy($id)
    {
        $uangMuka = UangMukaPembelian::findOrFail($id);

        if ($uangMuka->status !== 'draft') {
            return back()->with('error', 'Hanya UM draft yang bisa dihapus');
        }

        $uangMuka->delete();

        return redirect()->route('uang-muka-pembelian.index')
                        ->with('success', 'Uang Muka berhasil dihapus');
    }

    public function revisi($id)
    {
        $uangMuka = UangMukaPembelian::findOrFail($id);

        if ($uangMuka->status !== 'approved') {
            return back()->with('error', 'Hanya UM approved yang bisa direvisi');
        }

        // Check if UM has been paid (has BKK journal)
        $hasBkkJournal = Jurnal::where('ref_table', 'uang_muka_pembelian')
                              ->where('ref_id', $uangMuka->id)
                              ->exists();
        
        if ($hasBkkJournal) {
            return back()->with('error', 'UM yang sudah dibayar tidak bisa direvisi. Gunakan "Batalkan Pembayaran" atau "Edit Detail"');
        }

        // Check if UM has been used in faktur
        if ($uangMuka->nominal_digunakan > 0) {
            return back()->with('error', 'UM yang sudah digunakan di faktur tidak bisa direvisi');
        }

        $uangMuka->update(['status' => 'draft']);

        return back()->with('success', 'Status Uang Muka berhasil dikembalikan ke Draft untuk direvisi');
    }

    public function editPaid($id)
    {
        $uangMuka = UangMukaPembelian::with(['po', 'supplier', 'perusahaan'])->findOrFail($id);
        
        // Verify UM is paid
        $hasBkkJournal = Jurnal::where('ref_table', 'uang_muka_pembelian')
                              ->where('ref_id', $uangMuka->id)
                              ->exists();
        
        if (!$hasBkkJournal) {
            return redirect()->route('uang-muka-pembelian.show', $uangMuka->id)
                           ->with('error', 'UM ini belum dibayar, gunakan Edit biasa');
        }

        return view('uang-muka-pembelian.edit-paid', compact('uangMuka'));
    }

    public function updatePaid(Request $request, $id)
    {
        $uangMuka = UangMukaPembelian::findOrFail($id);

        // Only allow updating non-critical fields
        $validated = $request->validate([
            'keterangan' => 'nullable|string',
            'tanggal_transfer' => 'nullable|date',
            'no_bukti_transfer' => 'nullable|string',
            'file_path' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        if ($request->hasFile('file_path')) {
            if ($uangMuka->file_path) {
                \Storage::disk('public')->delete($uangMuka->file_path);
            }
            $validated['file_path'] = $request->file('file_path')->store('uang_muka', 'public');
        }

        $uangMuka->update($validated);

        return redirect()->route('uang-muka-pembelian.show', $uangMuka->id)
                        ->with('success', 'Detail Uang Muka berhasil diperbarui');
    }

    public function cancelPayment($id)
    {
        $uangMuka = UangMukaPembelian::findOrFail($id);

        if ($uangMuka->status !== 'approved') {
            return back()->with('error', 'UM belum approved');
        }

        // Check if used in faktur
        if ($uangMuka->nominal_digunakan > 0) {
            return back()->with('error', 'UM yang sudah digunakan di faktur tidak bisa dibatalkan pembayarannya');
        }

        DB::transaction(function () use ($uangMuka) {
            // Delete BKK journal
            $jurnal = Jurnal::where('ref_table', 'uang_muka_pembelian')
                           ->where('ref_id', $uangMuka->id)
                           ->first();
            
            if ($jurnal) {
                $jurnal->details()->delete();
                $jurnal->delete();
            }
        });

        return back()->with('success', 'Pembayaran berhasil dibatalkan. UM kembali ke status Approved - Belum Dibayar');
    }

    private function generateNomorUangMuka($id_perusahaan)
    {
        $year = Carbon::now()->year;
        $prefix = 'UM-' . $year . '-';
        
        $latest = UangMukaPembelian::where('no_uang_muka', 'like', $prefix . '%')
                                   ->orderBy('id', 'desc')
                                   ->first();
        
        $seq = 1;
        if ($latest && preg_match('/(\d{4})$/', $latest->no_uang_muka, $m)) {
            $seq = intval($m[1]) + 1;
        }

        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}

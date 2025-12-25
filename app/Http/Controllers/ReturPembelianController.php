<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ReturPembelian;
use App\Models\ReturPembelianDetail;
use App\Models\PenerimaanPembelian;
use App\Models\PenerimaanPembelianDetail;
use App\Models\PoDetail;
use App\Models\Jurnal;
use App\Models\JurnalDetail;
use App\Models\Faktur;
use DB;

class ReturPembelianController extends Controller
{
    public function index()
    {
        $returs = ReturPembelian::with(['penerimaan.po', 'supplier', 'proyek'])
                    ->orderByDesc('tanggal')
                    ->get();
        
        return view('retur.index', compact('returs'));
    }

    public function create($penerimaan_id)
    {
        $penerimaan = PenerimaanPembelian::with(['details', 'po', 'supplier', 'proyek'])
                        ->findOrFail($penerimaan_id);

        return view('retur.create', compact('penerimaan'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'penerimaan_id' => 'required|exists:penerimaan_pembelian,id',
            'tanggal'       => 'required|date',
            'no_retur'      => 'required|unique:retur_pembelian,no_retur',
            'items'         => 'required|array|min:1',
        ]);

        DB::beginTransaction();
        try {
            $penerimaan = PenerimaanPembelian::with(['details', 'po'])->findOrFail($request->penerimaan_id);

            // Buat Header Retur
            $retur = ReturPembelian::create([
                'no_retur'       => $request->no_retur,
                'tanggal'        => $request->tanggal,
                'penerimaan_id'  => $penerimaan->id,
                'id_supplier'    => $penerimaan->id_supplier,
                'nama_supplier'  => $penerimaan->nama_supplier,
                'id_proyek'      => $penerimaan->id_proyek,
                'id_perusahaan'  => $penerimaan->id_perusahaan,
                'alasan'         => $request->alasan,
                'status'         => 'draft',
            ]);

            $totalRetur = 0;

            // Simpan Detail Retur
            foreach ($request->items as $item) {
                $qtyRetur = floatval($item['qty_retur'] ?? 0);
                if ($qtyRetur <= 0) continue;

                $penerimaanDetail = $penerimaan->details->firstWhere('id', $item['penerimaan_detail_id']);
                if (!$penerimaanDetail) continue;

                // Validasi: qty_retur tidak boleh melebihi qty_diterima
                if ($qtyRetur > $penerimaanDetail->qty_diterima) {
                    throw new \Exception("Qty retur untuk item {$penerimaanDetail->kode_item} melebihi qty yang diterima.");
                }

                // Ambil harga dari PO Detail
                $poDetail = PoDetail::find($penerimaanDetail->po_detail_id);
                $harga = $poDetail ? $poDetail->harga : 0;
                $totalItem = $qtyRetur * $harga;

                // Simpan detail retur
                ReturPembelianDetail::create([
                    'retur_id'              => $retur->id,
                    'penerimaan_detail_id'  => $penerimaanDetail->id,
                    'kode_item'             => $penerimaanDetail->kode_item,
                    'uraian'                => $penerimaanDetail->uraian,
                    'qty_retur'             => $qtyRetur,
                    'uom'                   => $penerimaanDetail->uom,
                    'harga'                 => $harga,
                    'total'                 => $totalItem,
                    'alasan'                => $item['alasan'] ?? null,
                ]);

                // Update qty_diretur di po_detail
                if ($poDetail) {
                    $poDetail->qty_diretur += $qtyRetur;
                    $poDetail->save();
                }

                $totalRetur += $totalItem;
            }

            DB::commit();
            return redirect()->route('retur.index')
                ->with('success', 'Retur pembelian berhasil dicatat.');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->withInput()
                ->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $retur = ReturPembelian::with(['details', 'penerimaan.po', 'supplier', 'proyek'])
                    ->findOrFail($id);
        
        return view('retur.show', compact('retur'));
    }

    public function approve($id)
    {
        DB::beginTransaction();
        try {
            $retur = ReturPembelian::with('details')->findOrFail($id);

            if ($retur->status !== 'draft') {
                return back()->with('warning', 'Retur sudah diproses.');
            }

            // Hitung total retur
            $totalRetur = $retur->details->sum('total');

            // Buat Jurnal untuk Retur
            // Logika: Mengurangi Persediaan/Beban dan Mengurangi Hutang
            $jurnal = Jurnal::create([
                'id_perusahaan' => $retur->id_perusahaan,
                'no_jurnal'     => 'JV-RTR-' . date('YmdHis'),
                'tanggal'       => $retur->tanggal,
                'keterangan'    => 'Retur Pembelian: ' . $retur->no_retur . ' (' . $retur->nama_supplier . ')',
                'total'         => $totalRetur,
                'tipe'          => 'Jurnal Umum'
            ]);

            // DEBIT: Hutang Usaha (mengurangi hutang)
            JurnalDetail::create([
                'jurnal_id' => $jurnal->id,
                'coa_id'    => 158, // ID Hutang Usaha
                'debit'     => $totalRetur,
                'kredit'    => 0
            ]);

            // KREDIT: Persediaan/Beban (mengurangi aset/beban)
            // Idealnya, setiap item retur memiliki COA sendiri dari barang
            // Untuk simplifikasi, kita gunakan satu COA (sesuaikan dengan kebutuhan)
            foreach ($retur->details as $detail) {
                $penerimaanDetail = PenerimaanPembelianDetail::find($detail->penerimaan_detail_id);
                $poDetail = $penerimaanDetail ? PoDetail::find($penerimaanDetail->po_detail_id) : null;
                $barang = $poDetail ? $poDetail->barang : null;
                
                $coaId = $barang->coa_persediaan_id ?? $barang->coa_beban_id ?? 159; // Default jika tidak ada

                JurnalDetail::create([
                    'jurnal_id' => $jurnal->id,
                    'coa_id'    => $coaId,
                    'debit'     => 0,
                    'kredit'    => $detail->total
                ]);
            }

            $retur->status = 'approved';
            $retur->jurnal_id = $jurnal->id;
            $retur->save();

            // Alokasikan nota kredit retur ke faktur-faktur terkait PO secara FIFO
            $penerimaan = PenerimaanPembelian::with('po')->find($retur->penerimaan_id);
            if ($penerimaan && $penerimaan->po) {
                $remainingCredit = $totalRetur;
                $fakturs = Faktur::where('id_po', $penerimaan->po->id)
                    ->orderBy('tanggal', 'asc')
                    ->get();
                foreach ($fakturs as $f) {
                    if ($remainingCredit <= 0) break;
                    $netTagihan = ($f->total - ($f->total_kredit_retur ?? 0));
                    $maxApplicable = max(0, $netTagihan - $f->sudah_dibayar);
                    if ($maxApplicable <= 0) continue;
                    $apply = min($remainingCredit, $maxApplicable);
                    $f->total_kredit_retur = ($f->total_kredit_retur ?? 0) + $apply;

                    // Update status pembayaran berdasarkan net tagihan
                    $netAfterCredit = $f->total - $f->total_kredit_retur;
                    if ($f->sudah_dibayar >= $netAfterCredit) {
                        $f->status_pembayaran = 'lunas';
                        $f->status = 'lunas';
                    } elseif ($f->sudah_dibayar > 0) {
                        $f->status_pembayaran = 'sebagian';
                    } else {
                        $f->status_pembayaran = 'belum';
                    }
                    $f->save();

                    $remainingCredit -= $apply;
                }
            }

            // Recalculate receipt billing status considering approved returns
            $penerimaan = PenerimaanPembelian::with('details')->find($retur->penerimaan_id);
            if ($penerimaan) {
                $sumDiterimaNet = 0;
                foreach ($penerimaan->details as $pd) {
                    $returQtyApproved = ReturPembelianDetail::where('penerimaan_detail_id', $pd->id)
                        ->whereHas('retur', function($q){ $q->where('status','approved'); })
                        ->sum('qty_retur');
                    $sumDiterimaNet += max(0, ($pd->qty_diterima - $returQtyApproved));
                }
                $sumTerfaktur = $penerimaan->details->sum('qty_terfaktur');

                $statusPenagihan = 'belum';
                if ($sumTerfaktur >= $sumDiterimaNet && $sumDiterimaNet > 0) {
                    $statusPenagihan = 'lunas';
                } elseif ($sumTerfaktur > 0) {
                    $statusPenagihan = 'sebagian';
                }
                $penerimaan->status_penagihan = $statusPenagihan;
                $penerimaan->save();
            }

            DB::commit();
            return redirect()->route('retur.index')
                ->with('success', 'Retur disetujui & jurnal berhasil dibuat.');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    public function revisi($id)
    {
        DB::beginTransaction();
        try {
            $retur = ReturPembelian::with(['details', 'penerimaan.po'])->findOrFail($id);

            if ($retur->status !== 'approved') {
                return back()->with('warning', 'Hanya retur yang sudah di-approve yang bisa direvisi.');
            }

            // Hapus jurnal jika ada
            if ($retur->jurnal_id) {
                JurnalDetail::where('jurnal_id', $retur->jurnal_id)->delete();
                Jurnal::where('id', $retur->jurnal_id)->delete();
            }

            // Balik alokasi nota kredit retur dari faktur (LIFO)
            $penerimaan = $retur->penerimaan; // dengan po
            if ($penerimaan && $penerimaan->po) {
                $remainingCredit = $retur->details->sum('total');
                $fakturs = Faktur::where('id_po', $penerimaan->po->id)
                    ->orderBy('tanggal', 'desc')
                    ->get();
                foreach ($fakturs as $f) {
                    if ($remainingCredit <= 0) break;
                    $available = $f->total_kredit_retur ?? 0;
                    if ($available <= 0) continue;
                    $reduce = min($available, $remainingCredit);
                    $f->total_kredit_retur = max(0, $available - $reduce);

                    // Re-evaluate status pembayaran
                    $netTagihan = $f->total - ($f->total_kredit_retur ?? 0);
                    if ($f->sudah_dibayar >= $netTagihan && $netTagihan > 0) {
                        $f->status_pembayaran = 'lunas';
                        $f->status = 'lunas';
                    } elseif ($f->sudah_dibayar > 0) {
                        $f->status_pembayaran = 'sebagian';
                        if ($f->status === 'lunas') $f->status = 'sedang diproses';
                    } else {
                        $f->status_pembayaran = 'belum';
                        if ($f->status === 'lunas') $f->status = 'sedang diproses';
                    }
                    $f->save();

                    $remainingCredit -= $reduce;
                }
            }

            // Ubah status retur jadi draft dan kosongkan jurnal_id
            $retur->status = 'draft';
            $retur->jurnal_id = null;
            $retur->save();

            // Recalculate receipt billing status (retur ini tidak dihitung karena kembali draft)
            $penerimaan = PenerimaanPembelian::with('details')->find($retur->penerimaan_id);
            if ($penerimaan) {
                $sumDiterimaNet = 0;
                foreach ($penerimaan->details as $pd) {
                    $returQtyApproved = ReturPembelianDetail::where('penerimaan_detail_id', $pd->id)
                        ->whereHas('retur', function($q){ $q->where('status','approved'); })
                        ->sum('qty_retur');
                    $sumDiterimaNet += max(0, ($pd->qty_diterima - $returQtyApproved));
                }
                $sumTerfaktur = $penerimaan->details->sum('qty_terfaktur');

                $statusPenagihan = 'belum';
                if ($sumTerfaktur >= $sumDiterimaNet && $sumDiterimaNet > 0) {
                    $statusPenagihan = 'lunas';
                } elseif ($sumTerfaktur > 0) {
                    $statusPenagihan = 'sebagian';
                }
                $penerimaan->status_penagihan = $statusPenagihan;
                $penerimaan->save();
            }

            DB::commit();
            return redirect()->route('retur.index')->with('success', 'Retur berhasil direvisi. Jurnal dan alokasi nota kredit dikembalikan.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Gagal merevisi retur: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $retur = ReturPembelian::with('details')->findOrFail($id);

            // Hapus jurnal jika ada
            if ($retur->jurnal_id) {
                JurnalDetail::where('jurnal_id', $retur->jurnal_id)->delete();
                Jurnal::where('id', $retur->jurnal_id)->delete();
            }

            // Kembalikan qty_diretur di po_detail
            foreach ($retur->details as $detail) {
                $penerimaanDetail = PenerimaanPembelianDetail::find($detail->penerimaan_detail_id);
                if ($penerimaanDetail) {
                    $poDetail = PoDetail::find($penerimaanDetail->po_detail_id);
                    if ($poDetail) {
                        $poDetail->qty_diretur -= $detail->qty_retur;
                        if ($poDetail->qty_diretur < 0) $poDetail->qty_diretur = 0;
                        $poDetail->save();
                    }
                }
            }

            // Balik alokasi nota kredit retur dari faktur (LIFO)
            $penerimaan = PenerimaanPembelian::with('po')->find($retur->penerimaan_id);
            if ($penerimaan && $penerimaan->po) {
                $remainingCredit = $retur->details->sum('total');
                $fakturs = Faktur::where('id_po', $penerimaan->po->id)
                    ->orderBy('tanggal', 'desc')
                    ->get();
                foreach ($fakturs as $f) {
                    if ($remainingCredit <= 0) break;
                    $available = $f->total_kredit_retur ?? 0;
                    if ($available <= 0) continue;
                    $reduce = min($available, $remainingCredit);
                    $f->total_kredit_retur = max(0, $available - $reduce);

                    // Re-evaluate status pembayaran
                    $netTagihan = $f->total - ($f->total_kredit_retur ?? 0);
                    if ($f->sudah_dibayar >= $netTagihan && $netTagihan > 0) {
                        $f->status_pembayaran = 'lunas';
                        $f->status = 'lunas';
                    } elseif ($f->sudah_dibayar > 0) {
                        $f->status_pembayaran = 'sebagian';
                        if ($f->status === 'lunas') $f->status = 'sedang diproses';
                    } else {
                        $f->status_pembayaran = 'belum';
                        if ($f->status === 'lunas') $f->status = 'sedang diproses';
                    }
                    $f->save();

                    $remainingCredit -= $reduce;
                }
            }

            // Hapus retur
            $retur->details()->delete();
            $retur->delete();

            DB::commit();
            return redirect()->route('retur.index')
                ->with('success', 'Retur berhasil dihapus.');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }
}

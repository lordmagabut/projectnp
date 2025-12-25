<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Coa;
use App\Models\JurnalDetail;
use App\Models\Perusahaan;

class LaporanController extends Controller
{

    public function neraca(Request $request)
    {
        $user = auth()->user();
        $perusahaans = $user->perusahaans;
    
        $selectedPerusahaanId = $request->id_perusahaan ?? $perusahaans->first()->id ?? null;
        if (!$selectedPerusahaanId) {
            return back()->with('error', 'Tidak ada perusahaan yang tersedia.');
        }
    
        $perusahaanAktif = $perusahaans->firstWhere('id', $selectedPerusahaanId);
        $nama_perusahaan = $perusahaanAktif ? $perusahaanAktif->nama_perusahaan : 'Tanpa Nama';
    
        $tanggalAwal = $request->tanggal_awal ?? now()->startOfYear()->format('Y-m-d');
        $tanggalAkhir = $request->tanggal_akhir ?? now()->endOfMonth()->format('Y-m-d');
    
        $jurnal = \App\Models\JurnalDetail::selectRaw('coa_id, SUM(debit) as total_debit, SUM(kredit) as total_kredit')
            ->whereHas('jurnal', function ($q) use ($tanggalAwal, $tanggalAkhir, $selectedPerusahaanId) {
                $q->whereBetween('tanggal', [$tanggalAwal, $tanggalAkhir])
                  ->where('id_perusahaan', $selectedPerusahaanId);
            })
            ->groupBy('coa_id')
            ->get()
            ->keyBy('coa_id');
    
        $coas = \App\Models\Coa::orderBy('no_akun')->get();
        $coaTree = $this->buildCoaTree(null, $coas, $jurnal);
    
        return view('laporan.neraca', compact(
            'coaTree', 'perusahaans', 'selectedPerusahaanId',
            'tanggalAwal', 'tanggalAkhir', 'nama_perusahaan'
        ));
    }
    
    protected function buildCoaTree($parentId, $coas, $jurnal)
    {
        $children = $coas->where('parent_id', $parentId);
        $result = [];
    
        foreach ($children as $coa) {
            $childTree = $this->buildCoaTree($coa->id, $coas, $jurnal);
            $saldoSendiri = ($jurnal[$coa->id]->total_debit ?? 0) - ($jurnal[$coa->id]->total_kredit ?? 0);
            $saldoAnak = collect($childTree)->sum('saldo');
            $totalSaldo = $saldoSendiri + $saldoAnak;
    
            $result[] = [
                'id' => $coa->id,
                'no_akun' => $coa->no_akun,
                'nama_akun' => $coa->nama_akun,
                'tipe' => $coa->tipe,
                'parent_id' => $coa->parent_id,
                'saldo' => $totalSaldo,
                'children' => $childTree,
            ];
        }
    
        return $result;
    }
    



    public function labaRugi(Request $request)
    {
        $tanggalAwal = $request->tanggal_awal ?? now()->startOfMonth()->toDateString();
        $tanggalAkhir = $request->tanggal_akhir ?? now()->endOfMonth()->toDateString();

        $akun = Coa::whereIn('tipe', ['Pendapatan','Penjualan', 'Beban'])->get();

        $data = $akun->map(function ($coa) use ($tanggalAwal, $tanggalAkhir) {
            $jurnal = JurnalDetail::with('jurnal')
                ->where('coa_id', $coa->id)
                ->whereHas('jurnal', fn ($q) => $q->whereBetween('tanggal', [$tanggalAwal, $tanggalAkhir]))
                ->get();

            $saldo = $jurnal->sum('debit') - $jurnal->sum('kredit');

            if (in_array($coa->tipe, ['Pendapatan', 'Penjualan'])) {
                $saldo *= -1;
            }

            return [
                'no_akun' => $coa->no_akun,
                'nama_akun' => $coa->nama_akun,
                'tipe' => $coa->tipe,
                'saldo' => $saldo,
            ];
        });

        $totalPendapatan = $data->whereIn('tipe', ['Pendapatan', 'Penjualan'])->sum('saldo');
        $totalBeban = $data->where('tipe', 'Beban')->sum('saldo');
        $labaBersih = $totalPendapatan - $totalBeban;

        return view('laporan.laba-rugi', compact('data', 'tanggalAwal', 'tanggalAkhir', 'totalPendapatan', 'totalBeban', 'labaBersih'));
    }

    public function generalLedger(Request $request)
    {
        $user = auth()->user();
        $perusahaans = $user->perusahaans;
    
        $selectedPerusahaanId = $request->id_perusahaan ?? $perusahaans->first()->id ?? null;
        if (!$selectedPerusahaanId) {
            return back()->with('error', 'Tidak ada perusahaan yang tersedia.');
        }
    
        $perusahaanAktif = $perusahaans->firstWhere('id', $selectedPerusahaanId);
        $nama_perusahaan = $perusahaanAktif ? $perusahaanAktif->nama_perusahaan : 'Tanpa Nama';
    
        $tanggalAwal = $request->tanggal_awal ?? now()->startOfMonth()->format('Y-m-d');
        $tanggalAkhir = $request->tanggal_akhir ?? now()->endOfMonth()->format('Y-m-d');

        // Ambil semua COA dengan saldo
        $coas = Coa::orderBy('no_akun')->get();
        
        $glData = [];
        foreach ($coas as $coa) {
            // Hitung saldo awal (sebelum tanggal_awal)
            $saldoAwal = JurnalDetail::where('coa_id', $coa->id)
                ->whereHas('jurnal', function($q) use ($tanggalAwal, $selectedPerusahaanId) {
                    $q->where('tanggal', '<', $tanggalAwal)
                      ->where('id_perusahaan', $selectedPerusahaanId);
                })
                ->selectRaw('SUM(debit) - SUM(kredit) as saldo')
                ->value('saldo') ?? 0;

            // Hitung mutasi periode
            $mutasi = JurnalDetail::where('coa_id', $coa->id)
                ->whereHas('jurnal', function($q) use ($tanggalAwal, $tanggalAkhir, $selectedPerusahaanId) {
                    $q->whereBetween('tanggal', [$tanggalAwal, $tanggalAkhir])
                      ->where('id_perusahaan', $selectedPerusahaanId);
                })
                ->selectRaw('SUM(debit) as total_debit, SUM(kredit) as total_kredit')
                ->first();

            $totalDebit = $mutasi->total_debit ?? 0;
            $totalKredit = $mutasi->total_kredit ?? 0;
            $saldoAkhir = $saldoAwal + $totalDebit - $totalKredit;

            // Skip COA yang tidak ada transaksi sama sekali
            if ($saldoAwal == 0 && $totalDebit == 0 && $totalKredit == 0) {
                continue;
            }

            $glData[] = [
                'coa_id' => $coa->id,
                'no_akun' => $coa->no_akun,
                'nama_akun' => $coa->nama_akun,
                'tipe' => $coa->tipe,
                'saldo_awal' => $saldoAwal,
                'debit' => $totalDebit,
                'kredit' => $totalKredit,
                'saldo_akhir' => $saldoAkhir,
            ];
        }

        return view('laporan.general-ledger', compact(
            'glData', 'perusahaans', 'selectedPerusahaanId',
            'tanggalAwal', 'tanggalAkhir', 'nama_perusahaan'
        ));
    }
}

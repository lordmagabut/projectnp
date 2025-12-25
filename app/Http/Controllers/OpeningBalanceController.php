<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OpeningBalance;
use App\Models\Coa;
use App\Models\Perusahaan;
use App\Models\Jurnal;
use App\Models\JurnalDetail;
use DB;

class OpeningBalanceController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $perusahaans = $user->perusahaans;
        
        $selectedPerusahaanId = request('id_perusahaan') ?? $perusahaans->first()->id ?? null;
        if (!$selectedPerusahaanId) {
            return back()->with('error', 'Tidak ada perusahaan yang tersedia.');
        }

        $openingBalances = OpeningBalance::where('id_perusahaan', $selectedPerusahaanId)
                                        ->with(['coa'])
                                        ->orderBy('tanggal')
                                        ->get();

        return view('opening-balance.index', compact('openingBalances', 'perusahaans', 'selectedPerusahaanId'));
    }

    public function create()
    {
        $user = auth()->user();
        $perusahaans = $user->perusahaans;
        
        $selectedPerusahaanId = request('id_perusahaan') ?? $perusahaans->first()->id ?? null;
        if (!$selectedPerusahaanId) {
            return back()->with('error', 'Tidak ada perusahaan yang tersedia.');
        }

        $coas = Coa::orderBy('no_akun')->get();

        return view('opening-balance.create', compact('coas', 'perusahaans', 'selectedPerusahaanId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_perusahaan' => 'required|exists:perusahaan,id',
            'coa_id' => 'required|exists:coa,id',
            'saldo_awal' => 'required|numeric',
            'tanggal' => 'required|date',
            'keterangan' => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            // Cek apakah sudah ada opening balance untuk COA ini di tanggal yang sama
            $existing = OpeningBalance::where('id_perusahaan', $validated['id_perusahaan'])
                                     ->where('coa_id', $validated['coa_id'])
                                     ->where('tanggal', $validated['tanggal'])
                                     ->first();

            if ($existing) {
                throw new \Exception("Saldo awal untuk akun ini pada tanggal tersebut sudah ada.");
            }

            // Ambil informasi COA dan Perusahaan
            $coa = Coa::findOrFail($validated['coa_id']);
            $perusahaan = Perusahaan::findOrFail($validated['id_perusahaan']);

            // Buat jurnal "Saldo Awal"
            $jurnal = Jurnal::create([
                'id_perusahaan' => $validated['id_perusahaan'],
                'no_jurnal' => 'SA-' . date('YmdHis'),
                'tanggal' => $validated['tanggal'],
                'keterangan' => 'Saldo Awal - ' . $coa->no_akun . ' ' . $coa->nama_akun,
                'total' => abs($validated['saldo_awal']),
                'tipe' => 'Saldo Awal',
            ]);

            // Jika saldo_awal positif (debit), posting DEBIT ke akun terkait
            if ($validated['saldo_awal'] > 0) {
                JurnalDetail::create([
                    'jurnal_id' => $jurnal->id,
                    'coa_id' => $validated['coa_id'],
                    'debit' => $validated['saldo_awal'],
                    'kredit' => 0,
                ]);

                // KREDIT ke Laba Ditahan (3-200)
                $labaditahan = Coa::where('no_akun', '3-200')->first();
                if ($labaditahan) {
                    JurnalDetail::create([
                        'jurnal_id' => $jurnal->id,
                        'coa_id' => $labaditahan->id,
                        'debit' => 0,
                        'kredit' => $validated['saldo_awal'],
                    ]);
                }
            } else {
                // Jika saldo_awal negatif (kredit)
                JurnalDetail::create([
                    'jurnal_id' => $jurnal->id,
                    'coa_id' => $validated['coa_id'],
                    'debit' => 0,
                    'kredit' => abs($validated['saldo_awal']),
                ]);

                // DEBIT ke Laba Ditahan (3-200)
                $labaditahan = Coa::where('no_akun', '3-200')->first();
                if ($labaditahan) {
                    JurnalDetail::create([
                        'jurnal_id' => $jurnal->id,
                        'coa_id' => $labaditahan->id,
                        'debit' => abs($validated['saldo_awal']),
                        'kredit' => 0,
                    ]);
                }
            }

            // Simpan opening balance record
            OpeningBalance::create([
                'id_perusahaan' => $validated['id_perusahaan'],
                'coa_id' => $validated['coa_id'],
                'saldo_awal' => $validated['saldo_awal'],
                'tanggal' => $validated['tanggal'],
                'keterangan' => $validated['keterangan'],
                'jurnal_id' => $jurnal->id,
            ]);

            DB::commit();
            return redirect()->route('opening-balance.index', ['id_perusahaan' => $validated['id_perusahaan']])
                           ->with('success', 'Saldo awal berhasil disimpan!');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->withInput()->with('error', 'Gagal menyimpan saldo awal: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $openingBalance = OpeningBalance::findOrFail($id);
            $perusahaanId = $openingBalance->id_perusahaan;

            // Hapus jurnal terkait
            if ($openingBalance->jurnal_id) {
                Jurnal::destroy($openingBalance->jurnal_id);
            }

            // Hapus opening balance record
            $openingBalance->delete();

            DB::commit();
            return redirect()->route('opening-balance.index', ['id_perusahaan' => $perusahaanId])
                           ->with('success', 'Saldo awal berhasil dihapus!');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Gagal menghapus saldo awal: ' . $e->getMessage());
        }
    }
}

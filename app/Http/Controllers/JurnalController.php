<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Jurnal;
use App\Models\JurnalDetail;
use App\Models\Coa;

class JurnalController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $perusahaans = $user->perusahaans()->get();
        $coaList = \App\Models\Coa::all();
    
        // Default perusahaan dari akses pertama user
        $defaultPerusahaanId = $perusahaans->first()->id ?? null;
        $selectedPerusahaanId = $request->id_perusahaan ?? $defaultPerusahaanId;
    
        $tanggalAwal = $request->tanggal_awal ?? now()->startOfYear()->format('Y-m-d');
        $tanggalAkhir = $request->tanggal_akhir ?? now()->endOfMonth()->format('Y-m-d');
    
        $query = \App\Models\JurnalDetail::with(['jurnal', 'coa'])
            ->whereHas('jurnal', function ($q) use ($tanggalAwal, $tanggalAkhir, $selectedPerusahaanId) {
                $q->whereBetween('tanggal', [$tanggalAwal, $tanggalAkhir]);
    
                if ($selectedPerusahaanId) {
                    $q->where('id_perusahaan', $selectedPerusahaanId);
                }
            })
            ->join('jurnal', 'jurnal.id', '=', 'jurnal_details.jurnal_id')
            ->orderBy('jurnal.tanggal', 'desc')
            ->select('jurnal_details.*');
    
        if ($request->filled('coa_id')) {
            $query->where('coa_id', $request->coa_id);
        }
    
        $jurnalDetails = $query->get();
    
        return view('jurnal.index', compact('jurnalDetails', 'coaList', 'request', 'perusahaans', 'selectedPerusahaanId'));
    }
    

    public function create()
    {
        if (auth()->user()->buat_jurnal != 1) {
            abort(403, 'Anda tidak memiliki izin.');
        }

        $user = auth()->user();
        $perusahaans = $user->perusahaans()->get(); // relasi many-to-many
        $coa = Coa::all();
    
        return view('jurnal.create', compact('perusahaans', 'coa'));
    }
    

    public function store(Request $request)
    {
        $request->validate([
            'id_perusahaan' => 'required|exists:perusahaan,id',
            'tanggal' => 'required|date',
            'keterangan' => 'nullable|string',
            'rows' => 'required|array|min:2',
            'rows.*.coa_id' => 'required|exists:coa,id',
            'rows.*.debit' => 'nullable|numeric|min:0',
            'rows.*.kredit' => 'nullable|numeric|min:0',
        ]);
    
        $totalDebit = 0;
        $totalKredit = 0;
    
        foreach ($request->rows as $row) {
            $totalDebit += floatval($row['debit'] ?? 0);
            $totalKredit += floatval($row['kredit'] ?? 0);
        }
    
        if (round($totalDebit, 2) !== round($totalKredit, 2)) {
            return back()->with('error', 'Jumlah debit dan kredit harus sama.')->withInput();
        }
    
        $jurnal = Jurnal::create([
            'id_perusahaan' => $request->id_perusahaan,
            'no_jurnal' => $this->generateNoJurnal(),
            'tanggal' => $request->tanggal,
            'keterangan' => $request->keterangan,
            'tipe' => 'Jurnal Umum',
            'total' => $totalDebit
        ]);
    
        foreach ($request->rows as $row) {
            JurnalDetail::create([
                'jurnal_id' => $jurnal->id,
                'coa_id' => $row['coa_id'],
                'debit' => $row['debit'] ?? 0,
                'kredit' => $row['kredit'] ?? 0
            ]);
        }
    
        return redirect()->route('jurnal.create')->with('success', 'Jurnal berhasil disimpan.');
    }
    

    private function generateNoJurnal()
    {
        $tanggal = now()->format('ymd');
        $prefix = "JV-" . $tanggal;

        $last = Jurnal::where('no_jurnal', 'like', $prefix . '%')
            ->orderByDesc('no_jurnal')
            ->first();

        if (!$last) {
            return $prefix . '001';
        }

        $lastNumber = (int)substr($last->no_jurnal, -3);
        $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        return $prefix . $newNumber;
    }

    public function destroy($id)
    {
        if (auth()->user()->hapus_jurnal != 1) {
            abort(403, 'Anda tidak memiliki izin.');
        }
        $jurnal = \App\Models\Jurnal::findOrFail($id);
        $jurnal->details()->delete(); // opsional, karena relasi bisa cascade
        $jurnal->delete();

        return redirect()->route('jurnal.index')->with('success', 'Jurnal berhasil dihapus.');
    }

    public function edit($id)
    {
        if (auth()->user()->edit_jurnal != 1) {
            abort(403, 'Anda tidak memiliki izin.');
        }
        $jurnal = Jurnal::with('details')->findOrFail($id);
        $coa = Coa::all();
        $perusahaans = auth()->user()->perusahaans()->get();
    
        return view('jurnal.edit', compact('jurnal', 'coa', 'perusahaans'));
    }
    
    public function update(Request $request, $id)
{
    $request->validate([
        'id_perusahaan' => 'required|exists:perusahaan,id',
        'tanggal' => 'required|date',
        'keterangan' => 'nullable|string',
        'rows' => 'required|array|min:2',
        'rows.*.coa_id' => 'required|exists:coa,id',
        'rows.*.debit' => 'nullable|numeric|min:0',
        'rows.*.kredit' => 'nullable|numeric|min:0',
    ]);

    $totalDebit = 0;
    $totalKredit = 0;

    foreach ($request->rows as $row) {
        $totalDebit += floatval($row['debit'] ?? 0);
        $totalKredit += floatval($row['kredit'] ?? 0);
    }

    if (round($totalDebit, 2) !== round($totalKredit, 2)) {
        return back()->with('error', 'Jumlah debit dan kredit harus sama.')->withInput();
    }

    $jurnal = Jurnal::findOrFail($id);
    $jurnal->update([
        'id_perusahaan' => $request->id_perusahaan,
        'tanggal' => $request->tanggal,
        'keterangan' => $request->keterangan,
        'total' => $totalDebit,
    ]);

    $jurnal->details()->delete();

    foreach ($request->rows as $row) {
        JurnalDetail::create([
            'jurnal_id' => $jurnal->id,
            'coa_id' => $row['coa_id'],
            'debit' => $row['debit'] ?? 0,
            'kredit' => $row['kredit'] ?? 0,
        ]);
    }

    return redirect()->route('jurnal.index')->with('success', 'Jurnal berhasil diupdate.');
}
    
public function showDetail($id)
{
    $jurnal = \App\Models\Jurnal::with(['details.coa', 'perusahaan'])->findOrFail($id);

    return response()->json([
        'jurnal' => $jurnal,
        'details' => $jurnal->details->map(function ($row) {
            return [
                'akun' => $row->coa->no_akun . ' - ' . $row->coa->nama_akun,
                'debit' => number_format($row->debit, 0, ',', '.'),
                'kredit' => number_format($row->kredit, 0, ',', '.'),
            ];
        })
    ]);
}



}

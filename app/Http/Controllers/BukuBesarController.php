<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\JurnalDetail;
use App\Models\Coa;

class BukuBesarController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $perusahaans = $user->perusahaans()->get();
        $coaList = Coa::all();

        $selectedPerusahaanId = $request->id_perusahaan ?? $perusahaans->first()->id ?? null;
        $selectedCoaId = $request->coa_id;
        $tanggalAwal = $request->tanggal_awal ?? now()->startOfYear()->format('Y-m-d');
        $tanggalAkhir = $request->tanggal_akhir ?? now()->endOfMonth()->format('Y-m-d');

        $data = [];

        if ($selectedPerusahaanId && $selectedCoaId) {
            $data = JurnalDetail::with(['jurnal', 'coa'])
                ->where('coa_id', $selectedCoaId)
                ->join('jurnal', 'jurnal_details.jurnal_id', '=', 'jurnal.id')
                ->where('jurnal.id_perusahaan', $selectedPerusahaanId)
                ->whereBetween('jurnal.tanggal', [$tanggalAwal, $tanggalAkhir])
                ->orderBy('jurnal.tanggal')
                ->select('jurnal_details.*')
                ->get();
        }

        return view('buku-besar.index', compact(
            'data',
            'perusahaans',
            'coaList',
            'selectedPerusahaanId',
            'selectedCoaId',
            'tanggalAwal',
            'tanggalAkhir',
            'request'
        ));
    }
}


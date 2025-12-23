<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RabPenawaranHeader;

class SalesOrderController extends Controller
{
    public function index(Request $request)
    {
        // Daftar penawaran yang sudah disetujui (status = 'final')
        $query = RabPenawaranHeader::with(['proyek','salesOrder'])
            ->where('status', 'final')
            ->orderBy('approved_at', 'desc');

        // Simple search by nama_penawaran atau proyek
        if ($q = $request->get('q')) {
            $query->where(function($s) use ($q) {
                $s->where('nama_penawaran', 'like', "%{$q}%")
                  ->orWhereHas('proyek', function($p) use ($q){
                      $p->where('nama_proyek', 'like', "%{$q}%");
                  });
            });
        }

        $penawarans = $query->paginate(20)->withQueryString();

        return view('so.index', compact('penawarans'));
    }

    public function show($id)
    {
        $so = \App\Models\SalesOrder::with(['lines', 'penawaran.proyek'])->findOrFail($id);
        return view('so.show', compact('so'));
    }
}

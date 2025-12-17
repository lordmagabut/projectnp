<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PembayaranPembelian;
use App\Models\Faktur;
use App\Models\Coa;
use App\Models\Jurnal;
use App\Models\JurnalDetail;
use DB;

class PembayaranPembelianController extends Controller
{
    public function index()
    {
        // Mengambil data pembayaran beserta relasi faktur dan coa
        $pembayarans = PembayaranPembelian::with(['faktur', 'coa'])
                        ->orderBy('tanggal', 'desc')
                        ->get();

        return view('pembayaran.index', compact('pembayarans'));
    }

    public function create($faktur_id)
    {
        $faktur = Faktur::findOrFail($faktur_id);
        
        // Ambil akun Kas/Bank untuk sumber dana (Biasanya tipe 'Aset Lancar' atau sesuai struktur COA Anda)
        $coaKas = Coa::where('tipe', 'Aset Lancar')
                     ->orWhere('nama_akun', 'like', '%Kas%')
                     ->orWhere('nama_akun', 'like', '%Bank%')
                     ->get();

        return view('pembayaran.create', compact('faktur', 'coaKas'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'faktur_id' => 'required',
            'coa_id'    => 'required', // Akun Kas/Bank
            'tanggal'   => 'required|date',
            'nominal'   => 'required|numeric|min:1',
        ]);

        DB::beginTransaction();
        try {
            $faktur = Faktur::findOrFail($request->faktur_id);

            // 1. Simpan Transaksi Pembayaran
            $pembayaran = PembayaranPembelian::create([
                'no_pembayaran' => 'BKK-' . date('YmdHis'),
                'tanggal' => $request->tanggal,
                'faktur_id' => $faktur->id,
                'id_perusahaan' => $faktur->id_perusahaan,
                'coa_id' => $request->coa_id,
                'nominal' => $request->nominal,
                'keterangan' => $request->keterangan ?? "Pembayaran Faktur " . $faktur->no_faktur,
            ]);

            // 2. Update Faktur (Status & Nominal Terbayar)
            $totalSudahBayar = $faktur->sudah_dibayar + $request->nominal;
            $statusBayar = ($totalSudahBayar >= $faktur->total) ? 'lunas' : 'sebagian';

            $faktur->update([
                'sudah_dibayar' => $totalSudahBayar,
                'status_pembayaran' => $statusBayar
            ]);

            // 3. Buat Jurnal Akuntansi (Hutang pada Kas)
            $jurnal = Jurnal::create([
                'id_perusahaan' => $faktur->id_perusahaan,
                'no_jurnal' => 'JV-PAY-' . date('YmdHis'),
                'tanggal' => $request->tanggal,
                'keterangan' => 'Pelunasan Faktur ' . $faktur->no_faktur,
                'total' => $request->nominal,
                'tipe' => 'Jurnal Umum'
            ]);

            // DEBIT: Hutang Usaha (ID 77)
            JurnalDetail::create([
                'jurnal_id' => $jurnal->id,
                'coa_id' => 158, 
                'debit' => $request->nominal,
                'kredit' => 0
            ]);

            // KREDIT: Kas/Bank (Sesuai pilihan di form)
            JurnalDetail::create([
                'jurnal_id' => $jurnal->id,
                'coa_id' => $request->coa_id,
                'debit' => 0,
                'kredit' => $request->nominal
            ]);

            DB::commit();
            return redirect()->route('faktur.index')->with('success', 'Pembayaran berhasil dicatat dan status faktur diperbarui.');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }
}
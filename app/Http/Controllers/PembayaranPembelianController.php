<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PembayaranPembelian;
use App\Models\Faktur;
use App\Models\Coa;
use App\Models\Jurnal;
use App\Models\JurnalDetail;
use App\Services\AccountService;
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

    public function show($id)
    {
        $pembayaran = PembayaranPembelian::with(['faktur', 'coa'])->findOrFail($id);
        return view('pembayaran.show', compact('pembayaran'));
    }

   public function store(Request $request)
{
    $request->validate([
        'faktur_id' => 'required|exists:faktur,id',
        'coa_id'    => 'required|exists:coa,id', // Akun Kas/Bank dari form
        'tanggal'   => 'required|date',
        'nominal'   => 'required|numeric|min:1',
    ]);

    DB::beginTransaction();
    try {
        // Ambil data faktur terbaru dengan lock untuk menghindari double payment di waktu bersamaan
        $faktur = Faktur::lockForUpdate()->findOrFail($request->faktur_id);

        // Validasi: Jangan biarkan bayar melebihi total tagihan
        // Net hutang = total faktur - retur kredit - uang muka yang dipakai - pembayaran sebelumnya
        $netTagihan = ($faktur->total - ($faktur->total_kredit_retur ?? 0) - ($faktur->uang_muka_dipakai ?? 0));
        $sisaHutang = $netTagihan - $faktur->sudah_dibayar;
        if ($request->nominal > $sisaHutang) {
            throw new \Exception("Nominal pembayaran (Rp " . number_format($request->nominal) . ") melebihi sisa hutang (Rp " . number_format($sisaHutang) . ")");
        }

        // 1. Simpan Transaksi Pembayaran
        $pembayaran = PembayaranPembelian::create([
            'no_pembayaran' => 'BKK-' . date('YmdHis'),
            'tanggal'       => $request->tanggal,
            'faktur_id'     => $faktur->id,
            'id_perusahaan' => $faktur->id_perusahaan,
            'coa_id'        => $request->coa_id,
            'nominal'       => $request->nominal,
            'keterangan'    => $request->keterangan ?? "Pembayaran Faktur " . $faktur->no_faktur,
        ]);

        // 2. Update Faktur (Nominal Terbayar & Status Enum)
        $totalSudahBayar = $faktur->sudah_dibayar + $request->nominal;
        
        // Logika Status Pembayaran berdasarkan DDL Enum: belum, sebagian, lunas
        $netTagihan = ($faktur->total - ($faktur->total_kredit_retur ?? 0) - ($faktur->uang_muka_dipakai ?? 0));
        if ($totalSudahBayar >= $netTagihan) {
            $statusBayar = 'lunas';
        } elseif ($totalSudahBayar > 0) {
            $statusBayar = 'sebagian';
        } else {
            $statusBayar = 'belum';
        }

        $faktur->update([
            'sudah_dibayar'     => $totalSudahBayar,
            'status_pembayaran' => $statusBayar,
            // Jika lunas, update status utama faktur juga (jika diinginkan)
            'status'            => ($statusBayar == 'lunas') ? 'lunas' : $faktur->status 
        ]);

        // 3. Buat Jurnal Akuntansi
        $jurnal = Jurnal::create([
            'id_perusahaan' => $faktur->id_perusahaan,
            'no_jurnal'     => 'JV-PAY-' . date('YmdHis'),
            'tanggal'       => $request->tanggal,
            'keterangan'    => 'Pelunasan Faktur ' . $faktur->no_faktur . ' (' . $faktur->nama_supplier . ')',
            'total'         => $request->nominal,
            'tipe'          => 'Jurnal Umum'
        ]);

        // DEBIT: Hutang Usaha (Dinamis sesuai perusahaan)
        JurnalDetail::create([
            'jurnal_id' => $jurnal->id,
            'coa_id'    => AccountService::getHutangUsaha($faktur->id_perusahaan), 
            'debit'     => $request->nominal,
            'kredit'    => 0
        ]);

        // KREDIT: Kas/Bank (Sesuai pilihan di form)
        JurnalDetail::create([
            'jurnal_id' => $jurnal->id,
            'coa_id'    => $request->coa_id,
            'debit'     => 0,
            'kredit'    => $request->nominal
        ]);

        DB::commit();
        return redirect()->route('faktur.index')->with('success', 'Pembayaran Rp ' . number_format($request->nominal) . ' berhasil diposting.');

    } catch (\Exception $e) {
        DB::rollback();
        return back()->withInput()->with('error', 'Gagal: ' . $e->getMessage());
    }
}

public function destroy($id)
{
    DB::beginTransaction();
    try {
        // 1. Ambil data pembayaran
        $pembayaran = PembayaranPembelian::findOrFail($id);
        
        // 2. Ambil data faktur terkait dengan Lock (mencegah tabrakan data)
        $faktur = Faktur::lockForUpdate()->findOrFail($pembayaran->faktur_id);

        // 3. Hitung balik saldo sudah_dibayar
        // Contoh: saldo sekarang 1jt, pembayaran yang dihapus 500rb -> saldo baru jadi 500rb
        $saldoTerbayarBaru = $faktur->sudah_dibayar - $pembayaran->nominal;

        // 4. Tentukan kembali status berdasarkan saldo baru
        $netTagihan = ($faktur->total - ($faktur->total_kredit_retur ?? 0) - ($faktur->uang_muka_dipakai ?? 0));
        if ($saldoTerbayarBaru <= 0) {
            $statusBaru = 'belum';
            $saldoTerbayarBaru = 0; // Pastikan tidak minus
        } elseif ($saldoTerbayarBaru < $netTagihan) {
            $statusBaru = 'sebagian';
        } else {
            $statusBaru = 'lunas';
        }

        // 5. Update Faktur ke kondisi semula
        $faktur->update([
            'sudah_dibayar'     => $saldoTerbayarBaru,
            'status_pembayaran' => $statusBaru,
            'status'            => ($statusBaru == 'lunas') ? 'lunas' : 'sedang diproses' 
        ]);

        // 6. Hapus Jurnal Terkait (Agar laporan laba rugi & neraca kembali sinkron)
        // Kita cari jurnal yang no_jurnal-nya mengandung info pembayaran ini
        // Atau cari berdasarkan no_jurnal yang polanya unik (JV-PAY-...)
        $jurnal = Jurnal::where('id_perusahaan', $faktur->id_perusahaan)
                        ->where('keterangan', 'like', '%' . $faktur->no_faktur . '%')
                        ->where('total', $pembayaran->nominal)
                        ->first();
        
        if ($jurnal) {
            // JurnalDetail akan terhapus otomatis jika Anda pakai 'on delete cascade' 
            // Jika tidak, hapus manual detailnya dulu:
            \App\Models\JurnalDetail::where('jurnal_id', $jurnal->id)->delete();
            $jurnal->delete();
        }

        // 7. Hapus record pembayaran
        $pembayaran->delete();

        DB::commit();
        return redirect()->back()->with('success', 'Pembayaran berhasil dihapus. Status faktur telah kembali ke "' . $statusBaru . '".');

    } catch (\Exception $e) {
        DB::rollback();
        return redirect()->back()->with('error', 'Gagal membatalkan pembayaran: ' . $e->getMessage());
    }
}
}
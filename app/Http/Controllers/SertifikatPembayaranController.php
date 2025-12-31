<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

use App\Models\SertifikatPembayaran;
use App\Models\Bapp;
use App\Models\ProyekTaxProfile;

class SertifikatPembayaranController extends Controller
{
    public function index()
    {
        $list = SertifikatPembayaran::with('bapp.proyek')->latest()->paginate(20);
        return view('sertifikat.index', compact('list'));
    }

    public function edit($id)
    {
        $sp = SertifikatPembayaran::with('bapp.proyek')->findOrFail($id);
        return view('sertifikat.edit', compact('sp'));
    }

    public function update(Request $request, $id)
    {
        $sp = SertifikatPembayaran::findOrFail($id);

        $data = $request->validate([
            'nomor'       => 'required|string|max:255',
            'tanggal'     => 'required|date',
            'termin_ke'   => 'required|integer|min:1',
        ]);

        $sp->fill($data);
        $sp->save();

        return redirect()->route('sertifikat.index')->with('success', 'Sertifikat diperbarui.');
    }

    public function destroy($id)
    {
        $sp = SertifikatPembayaran::findOrFail($id);
        $sp->delete();

        return redirect()->route('sertifikat.index')->with('success', 'Sertifikat dihapus.');
    }

    public function create(Request $r)
    {
        // Hanya BAPP yang approved
        $bapps = Bapp::where('status', 'approved')
            ->with(['proyek','penawaran'])
            ->latest('tanggal_bapp')
            ->get();

        // Siapkan payload JSON untuk autofill
        $payload = $bapps->map(function($b){
            $nilaiMaterial = 0; $nilaiUpah = 0; $nilaiTotal = 0;
            $uangMukaPersenFromSO = 0;
            $uangMukaIdFromSO = null;

            if ($b->penawaran) {
                // Hitung split Material & Upah dari rab_penawaran_items × volume
                $sum = DB::table('rab_penawaran_items as i')
                    ->join('rab_penawaran_sections as s','s.id','=','i.rab_penawaran_section_id')
                    ->where('s.rab_penawaran_header_id', $b->penawaran->id)
                    ->selectRaw('
                        SUM(COALESCE(i.harga_material_penawaran_item,0) * COALESCE(i.volume,0)) as mat,
                        SUM(COALESCE(i.harga_upah_penawaran_item,0) * COALESCE(i.volume,0)) as uph,
                        SUM(COALESCE(i.total_penawaran_item,0)) as tot
                    ')
                    ->first();
                $nilaiMaterial = (float)($sum->mat ?? 0);
                $nilaiUpah     = (float)($sum->uph ?? 0);
                $nilaiTotal    = (float)($sum->tot ?? 0);

                // Cari SO & UM penjualan dari penawaran
                $so = \App\Models\SalesOrder::where('penawaran_id', $b->penawaran->id)->first();
                if ($so) {
                    $uangMukaPersenFromSO = (float)($so->uang_muka_persen ?? 0);
                    if ($so->uangMuka) {
                        $uangMukaIdFromSO = $so->uangMuka->id;
                    }
                }
            }

            // Get UM penjualan details if exist
            $umNominal = 0;
            $umDigunakan = 0;
            if ($uangMukaIdFromSO) {
                $um = \App\Models\UangMukaPenjualan::find($uangMukaIdFromSO);
                if ($um) {
                    $umNominal = (float)($um->nominal ?? 0);
                    $umDigunakan = (float)($um->nominal_digunakan ?? 0);
                }
            }

            $label = sprintf(
                '%s — %s — %s',
                $b->nomor_bapp,
                optional($b->proyek)->nama_proyek ?? 'Proyek',
                optional($b->tanggal_bapp)->format('Y-m-d') ?? $b->tanggal_bapp
            );

            return [
                'id'                    => $b->id,
                'label'                 => $label,
                'nilai_wo_material'     => round($nilaiMaterial,2),
                'nilai_wo_jasa'         => round($nilaiUpah,2),
                'final_total'           => round($nilaiTotal,2),
                'uang_muka_persen'      => $uangMukaPersenFromSO,
                'uang_muka_penjualan_id' => $uangMukaIdFromSO,
                'uang_muka_nominal'     => $umNominal,
                'uang_muka_digunakan'   => $umDigunakan,
                'retensi_persen'        => 5,
                'ppn_persen'            => 11,
                'persen_progress'       => (float)($b->total_now_pct ?? 0),       // kumulatif
                'persen_progress_delta' => (float)($b->total_delta_pct ?? 0),     // periode ini
                'termin_ke'             => (int)($b->minggu_ke ?? 1),

                // tanda tangan (kosong: user isi manual)
                'pt_nama'           => '',
                'pt_perusahaan'     => '',
                'pt_jabatan'        => '',
                'pk_nama'           => '',
                'pk_perusahaan'     => '',
                'pk_jabatan'        => '',
            ];
        });

        return view('sertifikat.create', [
            'bappsPayload'  => $payload,
            'prefillBappId' => $r->query('bapp_id')
        ]);
    }

    protected function generateNomor(): string
    {
        $prefix = 'SP-'.now()->format('ym');
        $latest = SertifikatPembayaran::where('nomor','like',$prefix.'%')->latest('id')->value('nomor');
        $seq = 1;
        if ($latest && preg_match('/(\d{3})$/',$latest,$m)) $seq = intval($m[1]) + 1;
        return sprintf('%s%03d', $prefix, $seq);
    }

    public function store(Request $request)
{
    $data = $request->validate([
        'bapp_id'               => 'required|exists:bapps,id',
        'uang_muka_penjualan_id' => 'nullable|exists:uang_muka_penjualan,id',
        'tanggal'               => 'required|date',
        'termin_ke'             => 'required|integer|min:1',
        'persen_progress'       => 'required|numeric|min:0',   // KUMULATIF
        'nilai_wo_material'     => 'required|numeric|min:0',
        'nilai_wo_jasa'         => 'required|numeric|min:0',
        'uang_muka_persen'      => 'required|numeric|min:0',
        'retensi_persen'        => 'required|numeric|min:0',
        'ppn_persen'            => 'required|numeric|min:0',
        // pihak terkait (opsional)
        'pemberi_tugas_nama'        => 'nullable|string',
        'pemberi_tugas_jabatan'     => 'nullable|string',
        'pemberi_tugas_perusahaan'  => 'nullable|string',
        'penerima_tugas_nama'       => 'nullable|string',
        'penerima_tugas_jabatan'    => 'nullable|string',
        'penerima_tugas_perusahaan' => 'nullable|string',
    ]);

    // ---- Ambil konteks kontrak via BAPP (proyek + penawaran) ----
    $bapp = Bapp::with(['proyek','penawaran'])->findOrFail($data['bapp_id']);
    $proyekId    = optional($bapp->proyek)->id;
    $penawaranId = optional($bapp->penawaran)->id;
    $umMode      = (string) (optional($bapp->proyek)->uang_muka_mode ?? 'proporsional');

    // Cari progress kumulatif sebelumnya untuk kontrak yang sama
    // (ambil SP ber-tanggal < tanggal ini; kalau mau lebih ketat, batasi ke status "approved")
    $spQuery = SertifikatPembayaran::whereHas('bapp', function($q) use ($proyekId, $penawaranId) {
        $q->where('proyek_id', $proyekId)
            ->where('penawaran_id', $penawaranId);
    });

    $prevPct = (float) (clone $spQuery)
        ->where('tanggal', '<', $data['tanggal'])
        ->max('persen_progress');
    $prevUmCutTotal = (float) (clone $spQuery)
        ->where('tanggal', '<', $data['tanggal'])
        ->sum('pemotongan_um_nilai');

    // Progress kumulatif sekarang & delta periode ini
    $currPct  = (float) $data['persen_progress'];           // kumulatif
    $deltaPct = max(0, round($currPct - $prevPct, 2));      // periode ini (jika turun, nolkan; atau izinkan negatif jika ingin CN)

    // ---- Nilai kontrak & proporsi ----
    $nilai_wo_material = (float) $data['nilai_wo_material'];
    $nilai_wo_jasa     = (float) $data['nilai_wo_jasa'];
    $nilai_wo_total    = round($nilai_wo_material + $nilai_wo_jasa, 2);

    $propM = $nilai_wo_total > 0 ? $nilai_wo_material / $nilai_wo_total : 0;
    $propJ = $nilai_wo_total > 0 ? $nilai_wo_jasa     / $nilai_wo_total : 0;

    // ---- Uang Muka total kontrak ----
    $umPct           = (float) $data['uang_muka_persen']; // % dari nilai WO total
    $uang_muka_total = round($nilai_wo_total * $umPct/100, 2);
    $uang_muka_M     = round($nilai_wo_material * $umPct/100, 2);
    $uang_muka_J     = round($nilai_wo_jasa     * $umPct/100, 2);

    // ---- Kumulatif SEKARANG (hingga currPct) ----
    $progress_cum_now = round($nilai_wo_total * $currPct/100, 2);
    $retensiPct       = (float) $data['retensi_persen'];
    $retensi_cum_now  = round($progress_cum_now * $retensiPct/100, 2);
    // Pemotongan UM kumulatif diasumsikan proporsional terhadap progress kumulatif
    $um_cut_cum_now   = round($uang_muka_total * $currPct/100, 2);
    $subtotal_cum_now = $progress_cum_now - $um_cut_cum_now - $retensi_cum_now;

    // ---- Kumulatif SEBELUMNYA (hingga prevPct) ----
    $progress_cum_prev = round($nilai_wo_total * $prevPct/100, 2);
    $retensi_cum_prev  = round($progress_cum_prev * $retensiPct/100, 2);
    $um_cut_cum_prev   = round($uang_muka_total * $prevPct/100, 2);
    $subtotal_cum_prev = $progress_cum_prev - $um_cut_cum_prev - $retensi_cum_prev;

    // ---- PERIODE INI (delta) ----
    $nilai_progress_rp = $progress_cum_now - $progress_cum_prev; // progress periode ini
    $pemotongan_um_nilai = $um_cut_cum_now - $um_cut_cum_prev;   // pot UM periode ini
    $retensi_nilai = $retensi_cum_now - $retensi_cum_prev;       // retensi periode ini
    $total_dibayar = $subtotal_cum_now - $subtotal_cum_prev;     // DPP (basis PPh) periode ini total

    // ---- Split material / jasa utk DPP (periode ini) ----
    // Cara yang “tepat” adalah delta per komponen juga dipisah per M/J:
    $progress_M_cum_now = round($nilai_wo_material * $currPct/100, 2);
    $progress_M_cum_prev= round($nilai_wo_material * $prevPct/100, 2);
    $progress_M_now     = $progress_M_cum_now - $progress_M_cum_prev;

    $progress_J_cum_now = round($nilai_wo_jasa * $currPct/100, 2);
    $progress_J_cum_prev= round($nilai_wo_jasa * $prevPct/100, 2);
    $progress_J_now     = $progress_J_cum_now - $progress_J_cum_prev;

    $retensi_M_now = round($progress_M_now * $retensiPct/100, 2);
    $retensi_J_now = round($progress_J_now * $retensiPct/100, 2);

    $um_cut_M_cum_now = round($uang_muka_M * $currPct/100, 2);
    $um_cut_M_cum_prev= round($uang_muka_M * $prevPct/100, 2);
    $um_cut_M_now     = $um_cut_M_cum_now - $um_cut_M_cum_prev;

    $um_cut_J_cum_now = round($uang_muka_J * $currPct/100, 2);
    $um_cut_J_cum_prev= round($uang_muka_J * $prevPct/100, 2);
    $um_cut_J_now     = $um_cut_J_cum_now - $um_cut_J_cum_prev;

    // ---- Override jika mode UM utuh (potong penuh di sertifikat/invoice) ----
    if ($umMode === 'utuh') {
        $umCutPrevCapped = round(min($uang_muka_total, $prevUmCutTotal), 2);

        $um_cut_cum_prev = $umCutPrevCapped;
        $um_cut_cum_now  = round($uang_muka_total, 2);
        $pemotongan_um_nilai = round(max(0, $uang_muka_total - $umCutPrevCapped), 2);

        $subtotal_cum_now  = $progress_cum_now - $um_cut_cum_now - $retensi_cum_now;
        $subtotal_cum_prev = $progress_cum_prev - $um_cut_cum_prev - $retensi_cum_prev;
        $nilai_progress_rp = $progress_cum_now - $progress_cum_prev;
        $retensi_nilai     = $retensi_cum_now - $retensi_cum_prev;
        $total_dibayar     = $subtotal_cum_now - $subtotal_cum_prev;

        $ratioM = $uang_muka_total > 0 ? $uang_muka_M / $uang_muka_total : 0.0;
        $ratioJ = $uang_muka_total > 0 ? $uang_muka_J / $uang_muka_total : 0.0;

        $um_cut_M_cum_prev = round(min($uang_muka_M, $um_cut_cum_prev * $ratioM), 2);
        $um_cut_J_cum_prev = round(min($uang_muka_J, $um_cut_cum_prev * $ratioJ), 2);
        $um_cut_M_cum_now  = round($uang_muka_M, 2);
        $um_cut_J_cum_now  = round($uang_muka_J, 2);

        $um_cut_M_now = round(max(0, $um_cut_M_cum_now - $um_cut_M_cum_prev), 2);
        $um_cut_J_now = round(max(0, $um_cut_J_cum_now - $um_cut_J_cum_prev), 2);
    }

    // ---- DPP material / jasa (periode ini) ----
    $dpp_material = max(0, round($progress_M_now - $um_cut_M_now - $retensi_M_now, 2));
    $dpp_jasa     = max(0, round($progress_J_now - $um_cut_J_now - $retensi_J_now, 2));

    // Rekonsiliasi pembulatan agar DPP_M + DPP_J = total_dibayar
    $sumDpp = round($dpp_material + $dpp_jasa, 2);
    if ($sumDpp !== round($total_dibayar, 2)) {
        $delta = round($total_dibayar - $sumDpp, 2);
        if ($dpp_jasa >= $dpp_material) $dpp_jasa += $delta; else $dpp_material += $delta;
    }

    // ---- PPN & total tagihan (PERIODE INI) ----
    // Jika proyek punya profil pajak aktif dan tidak kena PPN, paksa persen PPN = 0
    $ppnPct = (float) ($data['ppn_persen'] ?? 0);
    if ($proyekId) {
        $tax = ProyekTaxProfile::where('proyek_id', $proyekId)->where('aktif', 1)->first();
        if ($tax && !($tax->is_taxable ?? false)) {
            $ppnPct = 0.0;
            $data['ppn_persen'] = 0;
        }
    }
    $ppn_nilai = round($total_dibayar * $ppnPct/100, 2);
    $total_tagihan = $total_dibayar + $ppn_nilai;

    // ---- PPh (PERIODE INI) berdasarkan profil pajak aktif proyek ----
    $pph_nilai = 0.0;
    if ($proyekId) {
        $tax = ProyekTaxProfile::where('proyek_id', $proyekId)->where('aktif', 1)->first();
        $applyPph = (int)($tax->apply_pph ?? 0) === 1;
        if ($tax && $applyPph) {
            $pphRate     = (float)($tax->pph_rate ?? 0);
            $pphBaseKind = (string)($tax->pph_base ?? 'dpp'); // 'dpp' | 'subtotal'
            $extra       = is_array($tax->extra_options ?? null) ? $tax->extra_options : [];
            $src         = (string)($extra['pph_dpp_source'] ?? 'jasa'); // 'jasa' | 'material_jasa'

            if ($pphRate > 0) {
                if ($src === 'material_jasa') {
                    $baseM = ($pphBaseKind === 'dpp') ? $dpp_material : $progress_M_now;
                    $baseJ = ($pphBaseKind === 'dpp') ? $dpp_jasa     : $progress_J_now;
                    $pph_nilai = round($baseM * $pphRate/100, 2) + round($baseJ * $pphRate/100, 2);
                } else { // jasa saja
                    $baseJ = ($pphBaseKind === 'dpp') ? $dpp_jasa : $progress_J_now;
                    $pph_nilai = round($baseJ * $pphRate/100, 2);
                }
            }
        }
    }

    // Net tagihan (TOTAL + PPN − PPh) untuk narasi terbilang
    $total_nett = max(0, round($total_tagihan - $pph_nilai, 2));

    // ---- Payload simpan (isi kolom sesuai makna "PERIODE INI") ----
    $payload = array_merge($data, [
        'nomor'               => $this->generateNomor(),
        'nilai_wo_total'      => $nilai_wo_total,
        // progress/UM/retensi PERIODE INI
        'nilai_progress_rp'   => $nilai_progress_rp,   // progress delta
        'pemotongan_um_nilai' => $pemotongan_um_nilai, // UM delta
        'retensi_nilai'       => $retensi_nilai,       // retensi delta
        'uang_muka_nilai'     => $uang_muka_total,     // nilai UM total kontrak (boleh disimpan sebagai info)
        'sisa_uang_muka'      => max(0, $uang_muka_total - $um_cut_cum_now), // sisa UM setelah potongan kumulatif sekarang
        // DPP per periode (basis PPh)
        'dpp_material'        => $dpp_material,
        'dpp_jasa'            => $dpp_jasa,
        // PPN & total INVOICE PERIODE INI
        'ppn_nilai'           => $ppn_nilai,
        'total_dibayar'       => $total_dibayar,
        'total_tagihan'       => $total_tagihan,
        // Snapshoot progress
        'persen_progress_prev'  => $prevPct,
        'persen_progress_delta' => $deltaPct,
        'subtotal_cum'          => $subtotal_cum_now,
        'subtotal_prev_cum'     => $subtotal_cum_prev,
        // narasi terbilang utk total NETT periode ini (sesuai pemotongan PPh)
        'terbilang'           => $this->terbilangRupiah($total_nett).' Rupiah',
        'dibuat_oleh_id'      => auth()->id(),
    ]);

    $sp = SertifikatPembayaran::create($payload);
    // Track UM penjualan usage
    if (!empty($data['uang_muka_penjualan_id'])) {
        $umPenjualan = \App\Models\UangMukaPenjualan::find($data['uang_muka_penjualan_id']);
        if ($umPenjualan) {
            $umPenjualan->updateNominalDigunakan($pemotongan_um_nilai);
        }
    }
    return redirect()->route('sertifikat.show', $sp->id)->with('success','Sertifikat tersimpan (delta).');
}

    public function show($id)
    {
        $sp = SertifikatPembayaran::with('bapp.proyek', 'uangMukaPenjualan')->findOrFail($id);
        return view('sertifikat.show', compact('sp'));
    }

    public function cetak($id)
    {
        $sp = SertifikatPembayaran::with('bapp.proyek')->findOrFail($id);
    
        // Pastikan locale Indonesia konsisten di proses PDF
        \Carbon\Carbon::setLocale('id');
        @setlocale(LC_TIME, 'id_ID.UTF-8', 'id_ID', 'id');
    
        $pdf = Pdf::loadView('sertifikat.pdf', compact('sp'))
        ->setPaper('a4', 'portrait')
        ->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled'      => true,
            'enable_php'           => true,   // <-- wajib untuk <script type="text/php">
            'defaultFont'          => 'DejaVu Sans',
        ]);
    
    
        return $pdf->download('Sertifikat-Pembayaran-'.$sp->nomor.'.pdf');
    }
    
    

    // -------- util terbilang sederhana (ID) ----------
    protected function terbilangRupiah($angka): string
    {
        $angka = (int) round($angka,0);
        $bil = ["", "Satu","Dua","Tiga","Empat","Lima","Enam","Tujuh","Delapan","Sembilan","Sepuluh","Sebelas"];
        if ($angka < 12) return $bil[$angka];
        if ($angka < 20) return $this->terbilangRupiah($angka - 10) . " Belas";
        if ($angka < 100) return $this->terbilangRupiah(intval($angka/10)) . " Puluh " . $this->terbilangRupiah($angka % 10);
        if ($angka < 200) return "Seratus " . $this->terbilangRupiah($angka - 100);
        if ($angka < 1000) return $this->terbilangRupiah(intval($angka/100)) . " Ratus " . $this->terbilangRupiah($angka % 100);
        if ($angka < 2000) return "Seribu " . $this->terbilangRupiah($angka - 1000);
        if ($angka < 1000000) return $this->terbilangRupiah(intval($angka/1000)) . " Ribu " . $this->terbilangRupiah($angka % 1000);
        if ($angka < 1000000000) return $this->terbilangRupiah(intval($angka/1000000)) . " Juta " . $this->terbilangRupiah($angka % 1000000);
        if ($angka < 1000000000000) return $this->terbilangRupiah(intval($angka/1000000000)) . " Miliar " . $this->terbilangRupiah($angka % 1000000000);
        return $this->terbilangRupiah(intval($angka/1000000000000)) . " Triliun " . $this->terbilangRupiah($angka % 1000000000000);
    }
}

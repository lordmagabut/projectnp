<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

use App\Models\SertifikatPembayaran;
use App\Models\Bapp;

class SertifikatPembayaranController extends Controller
{
    public function index()
    {
        $list = SertifikatPembayaran::with('bapp.proyek')->latest()->paginate(20);
        return view('sertifikat.index', compact('list'));
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
                'uang_muka_persen'      => 0,
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

    public function store(\Illuminate\Http\Request $request)
    {
        $data = $request->validate([
            'bapp_id'               => 'required|exists:bapps,id',
            'tanggal'               => 'required|date',
            'termin_ke'             => 'required|integer|min:1',
            'persen_progress'       => 'required|numeric|min:0',
            'nilai_wo_material'     => 'required|numeric|min:0',
            'nilai_wo_jasa'         => 'required|numeric|min:0',
            'uang_muka_persen'      => 'required|numeric|min:0',
            'pemotongan_um_persen'  => 'required|numeric|min:0',
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

        // --- Hitungan dasar
        $nilai_wo_total    = round($data['nilai_wo_material'] + $data['nilai_wo_jasa'], 2);
        $uang_muka_nilai   = round($nilai_wo_total * ($data['uang_muka_persen']/100), 2);
        $pot_um_nilai      = round($uang_muka_nilai * ($data['pemotongan_um_persen']/100), 2);
        $sisa_uang_muka    = $uang_muka_nilai - $pot_um_nilai;

        $nilai_progress_rp = round($nilai_wo_total * ($data['persen_progress']/100), 2);
        $retensi_nilai     = round($nilai_progress_rp * ($data['retensi_persen']/100), 2);

        $total_dibayar     = $nilai_progress_rp - $pot_um_nilai - $retensi_nilai;

        // --- Split proporsional utk DPP (acuan PPh)
        $propM = $nilai_wo_total > 0 ? ($data['nilai_wo_material'] / $nilai_wo_total) : 0;
        $propJ = $nilai_wo_total > 0 ? ($data['nilai_wo_jasa']     / $nilai_wo_total) : 0;

        $progress_M = round($data['nilai_wo_material'] * ($data['persen_progress']/100), 2);
        $progress_J = round($data['nilai_wo_jasa']     * ($data['persen_progress']/100), 2);

        $pot_um_M   = round($pot_um_nilai * $propM, 2);
        $pot_um_J   = round($pot_um_nilai * $propJ, 2);

        $retensi_M  = round($retensi_nilai * $propM, 2);
        $retensi_J  = round($retensi_nilai * $propJ, 2);

        $dpp_material = max(0, round($progress_M - $pot_um_M - $retensi_M, 2));
        $dpp_jasa     = max(0, round($progress_J - $pot_um_J - $retensi_J, 2));

        // Sesuaikan delta pembulatan agar pas dengan total_dibayar
        $sumDpp = round($dpp_material + $dpp_jasa, 2);
        if ($sumDpp !== round($total_dibayar, 2)) {
            $delta = round($total_dibayar - $sumDpp, 2);
            if ($dpp_jasa >= $dpp_material) $dpp_jasa += $delta; else $dpp_material += $delta;
        }

        $ppn_nilai     = round($total_dibayar * ($data['ppn_persen']/100), 2);
        $total_tagihan = $total_dibayar + $ppn_nilai;

        $payload = array_merge($data, [
            'nomor'               => $this->generateNomor(),  // metode pembuat nomor milikmu
            'nilai_wo_total'      => $nilai_wo_total,
            'uang_muka_nilai'     => $uang_muka_nilai,
            'pemotongan_um_nilai' => $pot_um_nilai,
            'sisa_uang_muka'      => $sisa_uang_muka,
            'nilai_progress_rp'   => $nilai_progress_rp,
            'retensi_nilai'       => $retensi_nilai,
            'total_dibayar'       => $total_dibayar,
            'dpp_material'        => $dpp_material,   // <-- DISIMPAN
            'dpp_jasa'            => $dpp_jasa,       // <-- DISIMPAN
            'ppn_nilai'           => $ppn_nilai,
            'total_tagihan'       => $total_tagihan,
            'terbilang'           => $this->terbilangRupiah($total_tagihan).' Rupiah',
            'dibuat_oleh_id'      => auth()->id(),
        ]);

        $sp = \App\Models\SertifikatPembayaran::create($payload);

        return redirect()->route('sertifikat.show', $sp->id)->with('success','Sertifikat tersimpan.');
    }


    public function show($id)
    {
        $sp = SertifikatPembayaran::with('bapp.proyek')->findOrFail($id);
        return view('sertifikat.show', compact('sp'));
    }

    public function cetak($id)
    {
        $sp = SertifikatPembayaran::with('bapp.proyek')->findOrFail($id);
        $pdf = Pdf::loadView('sertifikat.pdf', compact('sp'))
                  ->setPaper('a4','portrait');
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

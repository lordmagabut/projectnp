<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

use App\Models\SertifikatPembayaran;
use App\Models\Bapp;
use App\Models\ProyekTaxProfile;
use App\Models\SalesOrder;
use App\Models\FakturPenjualan;
use App\Services\BastService;

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

        return redirect()->to($this->redirectToProyekTab($sp, $request))->with('success', 'Sertifikat diperbarui.');
    }

    public function destroy($id)
    {
        $sp = SertifikatPembayaran::findOrFail($id);
        
        // Rollback UM penjualan usage
        if ($sp->uang_muka_penjualan_id) {
            $umPenjualan = \App\Models\UangMukaPenjualan::find($sp->uang_muka_penjualan_id);
            if ($umPenjualan) {
                // Kurangi nominal_digunakan dengan pemotongan_um_nilai yang sudah dipakai
                $umPenjualan->updateNominalDigunakan(-1 * (float)$sp->pemotongan_um_nilai);
            }
        }
        
        $sp->delete();

        return redirect()->to($this->redirectToProyekTab($sp, request()))->with('success', 'Sertifikat dihapus dan Uang Muka telah dikembalikan.');
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
            $priceMode = 'pisah'; // default: pisah (material + upah terpisah)

            // Jika BAPP adalah final account, gunakan nilai_realisasi_total
            if ($b->is_final_account && $b->nilai_realisasi_total > 0) {
                $nilaiTotal = (float)$b->nilai_realisasi_total;
                // Bagi 50:50 untuk perhitungan material dan jasa
                $nilaiMaterial = $nilaiTotal / 2;
                $nilaiUpah = $nilaiTotal / 2;
                $priceMode = 'gabung'; // treat as gabung untuk simplicity
            } elseif ($b->penawaran) {
                // Tentukan price mode dari proyek
                $priceMode = (string) (optional($b->proyek)->penawaran_price_mode ?? 'pisah');

                if ($priceMode === 'gabung') {
                    // Mode GABUNG: semua harga ada di material, upah = 0
                    // Gunakan total_penawaran_item untuk nilai sebenarnya
                    $sum = DB::table('rab_penawaran_items as i')
                        ->join('rab_penawaran_sections as s','s.id','=','i.rab_penawaran_section_id')
                        ->where('s.rab_penawaran_header_id', $b->penawaran->id)
                        ->selectRaw('
                            SUM(COALESCE(i.total_penawaran_item,0)) as tot
                        ')
                        ->first();
                    $nilaiTotal = (float)($sum->tot ?? 0);
                    // Bagi 50:50 untuk sertifikat (karena sistem hitungnya via material & jasa terpisah)
                    $nilaiMaterial = $nilaiTotal / 2;
                    $nilaiUpah = $nilaiTotal / 2;
                } else {
                    // Mode PISAH: material dan upah terpisah
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

            // Ambil ppn_persen dari ProyekTaxProfile yang aktif
            $ppnPersen = 0;
            $proyekId = optional($b->proyek)->id;
            if ($proyekId) {
                $taxProfile = \App\Models\ProyekTaxProfile::where('proyek_id', $proyekId)
                    ->where('aktif', 1)
                    ->first();
                if ($taxProfile) {
                    $ppnPersen = (float)($taxProfile->ppn_rate ?? 0);
                }
            }

            // Ambil retensi_persen dari proyek
            $retensiPersen = (float)(optional($b->proyek)->persen_retensi ?? 0);

            return [
                'id'                    => $b->id,
                'label'                 => $label,
                'price_mode'            => $priceMode,
                'uang_muka_mode'        => (string) (optional($b->proyek)->uang_muka_mode ?? 'proporsional'),
                'nilai_wo_material'     => round($nilaiMaterial,2),
                'nilai_wo_jasa'         => round($nilaiUpah,2),
                'final_total'           => round($nilaiTotal,2),
                'uang_muka_persen'      => $uangMukaPersenFromSO,
                'uang_muka_penjualan_id' => $uangMukaIdFromSO,
                'uang_muka_nominal'     => $umNominal,
                'uang_muka_digunakan'   => $umDigunakan,
                'retensi_persen'        => $retensiPersen,
                'ppn_persen'            => $ppnPersen,
                'persen_progress'       => (float)($b->total_now_pct ?? 0),       // kumulatif
                'persen_progress_delta' => (float)($b->total_delta_pct ?? 0),     // periode ini
                // Termin selalu urut berdasarkan jumlah sertifikat yang sudah ada (approved/draft)
                'termin_ke'             => $this->nextTermin(optional($b->proyek)->id, optional($b->penawaran)->id),

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

    // Termin ditentukan dari urutan sertifikat pada proyek/penawaran (draft/approved semuanya dihitung)
    protected function nextTermin(?int $proyekId, ?int $penawaranId): int
    {
        if (!$proyekId) return 1;

        $count = SertifikatPembayaran::whereHas('bapp', function($q) use ($proyekId, $penawaranId) {
                $q->where('proyek_id', $proyekId)
                  ->when($penawaranId, fn($qq)=>$qq->where('penawaran_id', $penawaranId));
            })
            ->count();

        return $count + 1;
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
        'price_mode'            => 'nullable|in:pisah,gabung',
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
    $penawaranId = $bapp->penawaran_id;  // Ambil langsung dari kolom, bukan dari relation
    $umMode      = strtolower((string) (optional($bapp->proyek)->uang_muka_mode ?? 'proporsional'));

    // Termin harus berurutan berdasarkan sertifikat yang sudah ada (tidak tergantung minggu progress)
    $data['termin_ke'] = $this->nextTermin($proyekId, $penawaranId);

    // ---- Paksa nilai UM kontrak & UM Penjualan dari data proyek/SO ----
    $umPenjualan = null;
    $so = $penawaranId ? SalesOrder::where('penawaran_id', $penawaranId)->first() : null;
    $data['uang_muka_persen'] = (float) ($so->uang_muka_persen ?? 0);
    $data['uang_muka_penjualan_id'] = $so && $so->uangMuka ? $so->uangMuka->id : null;

    // ---- Validasi Uang Muka Penjualan (setelah dipaksa dari proyek) ----
    if (!empty($data['uang_muka_penjualan_id'])) {
        $umPenjualan = \App\Models\UangMukaPenjualan::find($data['uang_muka_penjualan_id']);
        
        if (!$umPenjualan) {
            return back()->withErrors(['uang_muka_penjualan_id' => 'Uang Muka Penjualan tidak ditemukan.'])->withInput();
        }
        
        // Validasi: UM harus sudah dibayar
        if ($umPenjualan->payment_status !== 'dibayar') {
            return back()->withErrors([
                'uang_muka_penjualan_id' => 'Uang Muka Penjualan belum dibayar. Status pembayaran: ' . ucfirst($umPenjualan->payment_status)
            ])->withInput();
        }
    } elseif ($umMode === 'utuh') {
        return back()->withErrors([
            'bapp_id' => 'Mode pemotongan UM adalah UTUH, tetapi tidak ditemukan Uang Muka Penjualan untuk proyek/penawaran ini.'
        ])->withInput();
    }

    // Cari progress kumulatif sebelumnya untuk penawaran yang sama
    // Gunakan termin_ke untuk memastikan urutan yang benar
    $currentTermin = (int)$data['termin_ke'];
    
    $prevSpQuery = SertifikatPembayaran::query();
    
    if ($penawaranId) {
        // Jika ada penawaran_id, cari dari penawaran yang sama
        $prevSpQuery->where('penawaran_id', $penawaranId);
    } else {
        // Jika tidak ada penawaran_id, fallback ke BAPP dari proyek yang sama
        $prevSpQuery->whereHas('bapp', function($q) use ($proyekId) {
            $q->where('proyek_id', $proyekId);
        });
    }
    
    // Ambil SP dengan termin sebelumnya (termin_ke < current)
    $prevSp = $prevSpQuery
        ->where('termin_ke', '<', $currentTermin)
        ->orderBy('termin_ke', 'desc')
        ->orderBy('tanggal', 'desc')
        ->first();
    
    $prevPct = $prevSp ? (float)$prevSp->persen_progress : 0.0;
    
    // Hitung total UM yang sudah dipotong sebelumnya  
    $prevUmQuery = SertifikatPembayaran::query();
    
    if ($penawaranId) {
        $prevUmQuery->where('penawaran_id', $penawaranId);
    } else {
        $prevUmQuery->whereHas('bapp', function($q) use ($proyekId) {
            $q->where('proyek_id', $proyekId);
        });
    }
    
    $prevUmCutTotal = $prevUmQuery
        ->where('termin_ke', '<', $currentTermin)
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

    // ---- Uang Muka: pakai nominal UM Penjualan sebagai sumber utama ----
    $umTotalNominal = $umPenjualan ? (float)$umPenjualan->nominal : round($nilai_wo_total * ((float)$data['uang_muka_persen'])/100, 2);
    // Jika WO > 0, turunkan persen dari nominal; simpan kembali agar konsisten di DB
    $umPct = $nilai_wo_total > 0 ? round($umTotalNominal / $nilai_wo_total * 100, 4) : 0;
    $data['uang_muka_persen'] = $umPct;
    $uang_muka_total = round($umTotalNominal, 2);
    $uang_muka_M     = round($nilai_wo_material * $umPct/100, 2);
    $uang_muka_J     = round($nilai_wo_jasa     * $umPct/100, 2);

    // ---- Cek apakah BAPP adalah Final Account ----
    $isFinalAccount = $bapp->is_final_account ?? false;
    
    if ($isFinalAccount) {
        // ==== FINAL ACCOUNT LOGIC ====
        // Nilai tagihan = Nilai Akhir - DP - (Nilai Sertifikat 1 setelah dikurangi DP, sebelum retensi) - (Nilai Sertifikat 2...) - dst
        
        // 1. Hitung total nilai yang sudah ditagih periode sebelumnya (nilai_progress_rp)
        $prevNilaiProgressQuery = SertifikatPembayaran::query();
        if ($penawaranId) {
            $prevNilaiProgressQuery->where('penawaran_id', $penawaranId);
        } else {
            $prevNilaiProgressQuery->whereHas('bapp', function($q) use ($proyekId) {
                $q->where('proyek_id', $proyekId);
            });
        }
        $prevNilaiProgressTotal = $prevNilaiProgressQuery
            ->where('termin_ke', '<', $currentTermin)
            ->sum('nilai_progress_rp');
        
        // 2. Nilai akhir (realisasi) dari BAPP
        $nilaiAkhir = (float)$bapp->nilai_realisasi_total;
        
        // 3. Hitung UM kumulatif
        $prevUmCutCapped = min($uang_muka_total, $prevUmCutTotal);
        $prevUmUsed      = $umPenjualan ? (float)$umPenjualan->nominal_digunakan : $prevUmCutCapped;
        $umSisaAvailable = max(0, $uang_muka_total - max($prevUmCutCapped, $prevUmUsed));
        
        if ($umMode === 'utuh') {
            $um_cut_cum_prev     = $prevUmCutCapped;
            $pemotongan_um_nilai = $umSisaAvailable;
            $um_cut_cum_now      = $um_cut_cum_prev + $pemotongan_um_nilai;
        } else {
            // Proporsional
            $desired_cum         = min($uang_muka_total, round($uang_muka_total * $currPct/100, 2));
            $um_cut_cum_prev     = $prevUmCutCapped;
            $pemotongan_um_nilai = max(0, $desired_cum - $um_cut_cum_prev);
            if ($pemotongan_um_nilai > $umSisaAvailable) {
                $pemotongan_um_nilai = $umSisaAvailable;
            }
            $um_cut_cum_now = $um_cut_cum_prev + $pemotongan_um_nilai;
        }
        
        // 4. Nilai progress periode ini = Sisa yang belum ditagih
        // Nilai Akhir - Total yang sudah ditagih sebelumnya - UM yang dipotong sekarang
        $nilai_progress_rp = max(0, round($nilaiAkhir - $prevNilaiProgressTotal - $pemotongan_um_nilai, 2));
        
        // 5. Progress kumulatif (untuk display/tracking saja)
        $progress_cum_now  = round($prevNilaiProgressTotal + $nilai_progress_rp + $um_cut_cum_now, 2);
        $progress_cum_prev = round($prevNilaiProgressTotal + $um_cut_cum_prev, 2);
        
        // 6. Retensi
        $retensiPct = (float) $data['retensi_persen'];
        $retensi_cum_now  = round($progress_cum_now * $retensiPct/100, 2);
        $retensi_cum_prev = round($progress_cum_prev * $retensiPct/100, 2);
        $retensi_nilai    = $retensi_cum_now - $retensi_cum_prev;
        
        // 7. Total dibayar periode ini (DPP)
        $total_dibayar = max(0, round($nilai_progress_rp - $retensi_nilai, 2));
        
        // 8. Split material/jasa proporsional
        $progress_M_now = round($nilai_progress_rp * $propM, 2);
        $progress_J_now = round($nilai_progress_rp * $propJ, 2);
        
        $retensi_M_now = round($retensi_nilai * $propM, 2);
        $retensi_J_now = round($retensi_nilai * $propJ, 2);
        
        $dpp_material = max(0, round($progress_M_now - $retensi_M_now, 2));
        $dpp_jasa     = max(0, round($progress_J_now - $retensi_J_now, 2));
        
        // Rekonsiliasi pembulatan
        $sumDpp = round($dpp_material + $dpp_jasa, 2);
        if ($sumDpp !== round($total_dibayar, 2)) {
            $delta = round($total_dibayar - $sumDpp, 2);
            if ($dpp_jasa >= $dpp_material) $dpp_jasa += $delta; else $dpp_material += $delta;
        }
        
        // 9. Pemotongan UM persen (untuk display)
        $pemotongan_um_persen = ($umMode === 'utuh') ? 100.0 : $currPct;
        
        // 10. Subtotal kumulatif
        $subtotal_cum_now  = $progress_cum_now - $um_cut_cum_now - $retensi_cum_now;
        $subtotal_cum_prev = $progress_cum_prev - $um_cut_cum_prev - $retensi_cum_prev;
        
    } else {
        // ==== BAPP NORMAL (Progress %) ====

    // ---- Progress & retensi kumulatif ----
    $progress_cum_now = round($nilai_wo_total * $currPct/100, 2);
    $retensiPct       = (float) $data['retensi_persen'];
    $retensi_cum_now  = round($progress_cum_now * $retensiPct/100, 2);

    $progress_cum_prev = round($nilai_wo_total * $prevPct/100, 2);
    $retensi_cum_prev  = round($progress_cum_prev * $retensiPct/100, 2);

    // ---- UM kumulatif (proporsional default) ----
    $prevUmCutCapped = min($uang_muka_total, $prevUmCutTotal);
    $prevUmUsed      = $umPenjualan ? (float)$umPenjualan->nominal_digunakan : $prevUmCutCapped;
    $umSisaAvailable = max(0, $uang_muka_total - max($prevUmCutCapped, $prevUmUsed));

    if ($umMode === 'utuh') {
        // Potong seluruh sisa UM penjualan pada sertifikat ini
        $um_cut_cum_prev    = $prevUmCutCapped;
        $pemotongan_um_nilai= $umSisaAvailable;
        $um_cut_cum_now     = $um_cut_cum_prev + $pemotongan_um_nilai;
    } else {
        // Proporsional terhadap progress kumulatif, dibatasi sisa UM
        $desired_cum        = min($uang_muka_total, round($uang_muka_total * $currPct/100, 2));
        $um_cut_cum_prev    = $prevUmCutCapped;
        $pemotongan_um_nilai= max(0, $desired_cum - $um_cut_cum_prev);
        if ($pemotongan_um_nilai > $umSisaAvailable) {
            $pemotongan_um_nilai = $umSisaAvailable;
            $um_cut_cum_now = $um_cut_cum_prev + $pemotongan_um_nilai;
        } else {
            $um_cut_cum_now = $desired_cum;
        }
    }

    // Persisted pemotongan UM % (informasi): utuh = 100%, proporsional = % progress kumulatif
    $pemotongan_um_persen = ($umMode === 'utuh') ? 100.0 : $currPct;

    // Subtotal kumulatif (setelah potongan UM & retensi)
    $subtotal_cum_now  = $progress_cum_now - $um_cut_cum_now - $retensi_cum_now;
    $subtotal_cum_prev = $progress_cum_prev - $um_cut_cum_prev - $retensi_cum_prev;

    // ---- PERIODE INI (delta) ----
    $nilai_progress_rp   = $progress_cum_now - $progress_cum_prev; // progress periode ini
    $retensi_nilai       = $retensi_cum_now - $retensi_cum_prev;   // retensi periode ini
    $total_dibayar       = $subtotal_cum_now - $subtotal_cum_prev; // DPP (basis PPh) periode ini total

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

    // Bagi pemotongan UM kumulatif sesuai proporsi M/J
    $ratioM = $uang_muka_total > 0 ? $uang_muka_M / $uang_muka_total : 0.0;
    $ratioJ = $uang_muka_total > 0 ? $uang_muka_J / $uang_muka_total : 0.0;

    $um_cut_M_cum_prev = round($um_cut_cum_prev * $ratioM, 2);
    $um_cut_J_cum_prev = round($um_cut_cum_prev * $ratioJ, 2);
    $um_cut_M_cum_now  = round($um_cut_cum_now  * $ratioM, 2);
    $um_cut_J_cum_now  = round($um_cut_cum_now  * $ratioJ, 2);

    $um_cut_M_now     = $um_cut_M_cum_now - $um_cut_M_cum_prev;
    $um_cut_J_now     = $um_cut_J_cum_now - $um_cut_J_cum_prev;

    // ---- DPP material / jasa (periode ini) ----
    $dpp_material = max(0, round($progress_M_now - $um_cut_M_now - $retensi_M_now, 2));
    $dpp_jasa     = max(0, round($progress_J_now - $um_cut_J_now - $retensi_J_now, 2));

    // Rekonsiliasi pembulatan agar DPP_M + DPP_J = total_dibayar
    $sumDpp = round($dpp_material + $dpp_jasa, 2);
    if ($sumDpp !== round($total_dibayar, 2)) {
        $delta = round($total_dibayar - $sumDpp, 2);
        if ($dpp_jasa >= $dpp_material) $dpp_jasa += $delta; else $dpp_material += $delta;
    }
    
    } // end if ($isFinalAccount) - tutup blok final account vs normal

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
        'penawaran_id'        => $penawaranId,  // Simpan penawaran_id untuk tracking lintas BAPP
        // progress/UM/retensi PERIODE INI
        'nilai_progress_rp'   => $nilai_progress_rp,   // progress delta
        'pemotongan_um_nilai' => $pemotongan_um_nilai, // UM delta
        'pemotongan_um_persen'=> $pemotongan_um_persen,
        'retensi_nilai'       => $retensi_nilai,       // retensi delta
        'uang_muka_nilai'     => $uang_muka_total,     // nilai UM total kontrak (boleh disimpan sebagai info)
        'uang_muka_mode'      => $umMode,
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

    $sp = SertifikatPembayaran::create(array_merge($payload, [
        'status' => 'draft',
    ]));
    
    // Track UM penjualan usage
    if (!empty($data['uang_muka_penjualan_id'])) {
        $umPenjualan = \App\Models\UangMukaPenjualan::find($data['uang_muka_penjualan_id']);
        if ($umPenjualan) {
            // Validasi sisa UM mencukupi
            $sisaUm = $umPenjualan->getSisaUangMuka();
            if ($sisaUm < $pemotongan_um_nilai) {
                $sp->delete(); // Rollback
                return back()->withErrors([
                    'uang_muka_penjualan_id' => sprintf(
                        'Sisa Uang Muka (Rp %s) tidak mencukupi untuk pemotongan sebesar Rp %s',
                        number_format($sisaUm, 2, ',', '.'),
                        number_format($pemotongan_um_nilai, 2, ',', '.')
                    )
                ])->withInput();
            }
            
            $umPenjualan->updateNominalDigunakan($pemotongan_um_nilai);
        }
    }

    // Auto-generate BAST 1 & BAST 2 ketika progress kumulatif sudah 100%
    try {
        BastService::ensureForSertifikat($sp);
    } catch (\Throwable $e) {
        Log::warning('Gagal auto-generate BAST dari Sertifikat Pembayaran', [
            'sp_id' => $sp->id ?? null,
            'error' => $e->getMessage(),
        ]);
    }
    
    return redirect()->route('sertifikat.show', $sp->id)->with('success','Sertifikat tersimpan (delta).');
}

    public function show($id)
    {
        $sp = SertifikatPembayaran::with('bapp.proyek', 'uangMukaPenjualan')->findOrFail($id);
        return view('sertifikat.show', compact('sp'));
    }

    public function approve($id)
    {
        $sp = SertifikatPembayaran::with('bapp.proyek')->findOrFail($id);
        if ($sp->status === 'approved') {
            return redirect()->to($this->redirectToProyekTab($sp, request()))->with('info', 'Sertifikat sudah disetujui.');
        }

        $sp->status = 'approved';
        $sp->disetujui_oleh_id = auth()->id();
        $sp->save();

        // Buat Faktur Penjualan draft setelah approval
        try {
            $proyek = optional($sp->bapp)->proyek;
            $gunakanUM = (bool)($proyek->gunakan_uang_muka ?? false);
            $gunakanRetensi = (bool)($proyek->gunakan_retensi ?? false);
            
            $fakturPenjualan = new FakturPenjualan();
            $fakturPenjualan->no_faktur          = FakturPenjualan::generateNomorFaktur();
            $fakturPenjualan->tanggal            = $sp->tanggal;
            $fakturPenjualan->sertifikat_pembayaran_id = $sp->id;
            $fakturPenjualan->id_proyek          = optional($sp->bapp)->proyek_id;
            $fakturPenjualan->id_perusahaan      = optional(optional($sp->bapp)->proyek)->perusahaan_id;
            $fakturPenjualan->subtotal           = $sp->total_dibayar;   // DPP periode ini
            $fakturPenjualan->total_diskon       = 0;
            $fakturPenjualan->total_ppn          = $sp->ppn_nilai;
            $fakturPenjualan->total              = $sp->total_tagihan;   // DPP + PPN periode ini
            $fakturPenjualan->status_pembayaran  = 'belum';
            $fakturPenjualan->status             = 'draft';
            $fakturPenjualan->uang_muka_dipakai  = $gunakanUM ? ($sp->pemotongan_um_nilai ?? 0) : 0; // potongan UM periode ini
            
            // Capture retensi dari sertifikat
            $fakturPenjualan->retensi_persen     = $gunakanRetensi ? ($sp->retensi_persen ?? 0) : 0;
            $fakturPenjualan->retensi_nilai      = $gunakanRetensi ? ($sp->retensi_nilai ?? 0) : 0;
            $fakturPenjualan->ppn_persen         = $sp->ppn_persen;
            $fakturPenjualan->ppn_nilai          = $sp->ppn_nilai;
            
            // Calculate & capture PPh dari tax profile
            $pph_persen = 0;
            $pph_nilai = 0;
            if ($fakturPenjualan->id_proyek) {
                $proyek = \App\Models\Proyek::find($fakturPenjualan->id_proyek);
                $pphDipungut = ($proyek->pph_dipungut ?? 'ya') === 'ya';
                
                if ($pphDipungut) {
                    $tax = \App\Models\ProyekTaxProfile::where('proyek_id', $fakturPenjualan->id_proyek)
                        ->where('aktif', 1)->first();
                    if ($tax && ($tax->apply_pph ?? 0) == 1) {
                        $pph_persen = (float)($tax->pph_rate ?? 0);
                        if ($pph_persen > 0) {
                            // Simplified: apply to total_dibayar
                            $pph_nilai = round($sp->total_dibayar * $pph_persen / 100, 2);
                        }
                    }
                }
            }
            $fakturPenjualan->pph_persen = $pph_persen;
            $fakturPenjualan->pph_nilai  = $pph_nilai;
            
            $fakturPenjualan->save();
        } catch (\Throwable $e) {
            \Log::warning('Gagal auto-create Faktur Penjualan saat approve Sertifikat', [
                'sp_id' => $sp->id,
                'error' => $e->getMessage(),
            ]);
        }

        return redirect()->to($this->redirectToProyekTab($sp, request()))->with('success', 'Sertifikat disetujui dan faktur dibuat.');
    }

    public function cetak($id)
    {
        $sp = SertifikatPembayaran::with('bapp.proyek')->findOrFail($id);
        // Force fresh data dari database (clear any cache)
        $sp->refresh();
    
        // Pastikan locale Indonesia konsisten di proses PDF
        \Carbon\Carbon::setLocale('id');
        @setlocale(LC_TIME, 'id_ID.UTF-8', 'id_ID', 'id');
        $priceMode = strtolower(optional(optional($sp->bapp)->proyek)->penawaran_price_mode ?? 'pisah');
        $view = $priceMode === 'gabung' ? 'sertifikat.pdf_gabung' : 'sertifikat.pdf';

        $pdf = Pdf::loadView($view, compact('sp'))
        ->setPaper('a4', 'portrait')
        ->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled'      => true,
            'enable_php'           => true,   // <-- wajib untuk <script type="text/php">
            'defaultFont'          => 'DejaVu Sans',
        ]);
    
    
        return $pdf->download('Sertifikat-Pembayaran-'.$sp->nomor.'.pdf');
    }
    
    protected function redirectToProyekTab(SertifikatPembayaran $sp, ?Request $request = null): string
    {
        if ($request && $request->filled('redirect_to')) {
            return $request->input('redirect_to');
        }

        $proyekId = optional($sp->bapp)->proyek_id;
        $penawaranId = optional($sp->bapp)->penawaran_id;

        if ($proyekId) {
            $query = ['tab' => 'sertifikat'];
            if ($penawaranId) {
                $query['penawaran_id'] = $penawaranId;
            }
            return route('proyek.show', $proyekId) . '?' . http_build_query($query);
        }

        return route('sertifikat.index');
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

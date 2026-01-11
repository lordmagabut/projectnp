<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Proyek;
use App\Models\RabScheduleDetail;
use App\Models\RabProgress;
use App\Models\RabPenawaranHeader;
use App\Models\Bast;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $proyeks = Proyek::orderBy('nama_proyek')->get();
        $selectedProyek = $request->get('proyek_id') ?? ($proyeks->first()->id ?? null);

        // Ringkasan status proyek
        $total = Proyek::count();
        $aktif = Proyek::where('status','aktif')->count();
        $selesai = Proyek::where('status','selesai')->count();
        $perencanaan = Proyek::where('status','perencanaan')->count();
        $tertunda = Proyek::where('status','tertunda')->count();

        // Dummy keuangan (ganti dengan query asli jika ada)
        $totalPembelian = 125000000;
        $totalPendapatan = 175000000;

        // Ambil daftar penawaran FINAL untuk proyek yang dipilih
        $finalPenawarans = collect();
        $selectedPenawaran = null;
        $selectedPenawaranId = null;
        
        if ($selectedProyek) {
            $finalPenawarans = RabPenawaranHeader::where('proyek_id', $selectedProyek)
                ->where('status', 'final')
                ->orderBy('tanggal_penawaran')
                ->get();
            
            // Prioritas: penawaran_id dari request, fallback penawaran terakhir
            $selectedPenawaranId = $request->get('penawaran_id');
            if (!$selectedPenawaranId && $finalPenawarans->isNotEmpty()) {
                $selectedPenawaranId = $finalPenawarans->last()->id;
            }
            $selectedPenawaran = $finalPenawarans->firstWhere('id', $selectedPenawaranId);
        }

        // Kurva S (ambil dari schedule detail & progress, mirip ProyekController)
        $minggu = $akumulasi = $realisasi = [];
        if ($selectedProyek && $selectedPenawaranId) {
            $selectedId = (int) $selectedPenawaranId;

            // Rencana
            $scheduleDetailQ = RabScheduleDetail::where('proyek_id', $selectedProyek)
                ->when($selectedId, fn ($q) => $q->where('penawaran_id', $selectedId));
            $sdTable  = (new RabScheduleDetail)->getTable();
            $bobotCol = \Schema::hasColumn($sdTable, 'bobot_mingguan')
                ? 'bobot_mingguan'
                : (\Schema::hasColumn($sdTable, 'bobot') ? 'bobot' : null);
            $grouped = $scheduleDetailQ->orderBy('minggu_ke')->get()->groupBy('minggu_ke');
            if ($grouped->isNotEmpty()) {
                $maxMinggu = $grouped->keys()->max();
                $minggu    = range(1, $maxMinggu);
                $total = 0.0;
                foreach ($minggu as $m) {
                    $bobotMinggu = 0.0;
                    if ($bobotCol && isset($grouped[$m])) {
                        $bobotMinggu = (float) $grouped[$m]->sum($bobotCol);
                    }
                    $total       += $bobotMinggu;
                    $akumulasi[]  = round($total, 4);
                }
            }

            // Realisasi
            $pdTable = (new \App\Models\RabProgressDetail)->getTable();
            $valCol  = \Schema::hasColumn($pdTable, 'bobot_minggu_ini')
                ? 'bobot_minggu_ini'
                : (\Schema::hasColumn($pdTable, 'bobot') ? 'bobot' : null);
            $progressRaw = RabProgress::where('proyek_id', $selectedProyek)
                ->when($selectedId, fn ($q) => $q->where('penawaran_id', $selectedId))
                ->orderBy('minggu_ke')
                ->orderBy('id')
                ->get();
            $finalWeekly = $valCol
                ? \DB::table($pdTable.' as d')
                    ->join('rab_progress as p', 'p.id', '=', 'd.rab_progress_id')
                    ->where('p.proyek_id', $selectedProyek)
                    ->when($selectedId, fn ($q) => $q->where('p.penawaran_id', $selectedId))
                    ->whereIn('p.status', ['final','approved'])
                    ->groupBy('p.minggu_ke')
                    ->pluck(\DB::raw("SUM(d.$valCol) as s"), 'p.minggu_ke')
                    ->toArray()
                : [];
            $weeksSorted = array_keys($finalWeekly);
            sort($weeksSorted, SORT_NUMERIC);
            $cumBefore = [];
            $running   = 0.0;
            $lastW     = 0;
            foreach ($weeksSorted as $w) {
                for ($i = $lastW + 1; $i < $w; $i++) {
                    if ($i > 0) $cumBefore[$i] = $running;
                }
                if ($w > 0) $cumBefore[$w] = $running;
                $running += (float) $finalWeekly[$w];
                $lastW = $w;
            }
            for ($i = $lastW + 1; $i <= 10000; $i++) {
                $cumBefore[$i] = $running;
                if ($i - $lastW > 2000) break;
            }
            foreach ($minggu as $m) {
                $prev = $cumBefore[$m] ?? 0.0;
                $deltaThis = $valCol
                    ? (float) \DB::table($pdTable)->where('rab_progress_id', optional($progressRaw->firstWhere('minggu_ke', $m))->id)->sum($valCol)
                    : 0.0;
                $realisasi[] = round($prev + $deltaThis, 4);
            }
        }

        // BAST 2 Retention Reminder: cari BAST 2 yang masa retensinya akan berakhir dalam 30 hari
        $upcomingRetention = Bast::where('jenis_bast', 'bast_2')
            ->whereNotNull('tanggal_bast')
            ->whereNotNull('durasi_retensi_hari')
            ->with('proyek', 'sertifikatPembayaran')
            ->get()
            ->filter(function($bast) {
                if (!$bast->tanggal_bast || !$bast->durasi_retensi_hari) return false;
                $tanggalBast = Carbon::parse($bast->tanggal_bast);
                $tanggalJatuhTempo = $tanggalBast->copy()->addDays($bast->durasi_retensi_hari);
                $selisihHari = now()->diffInDays($tanggalJatuhTempo, false);
                // Tampilkan jika 0-30 hari lagi jatuh tempo (positif = belum jatuh tempo)
                return $selisihHari >= 0 && $selisihHari <= 30;
            })
            ->map(function($bast) {
                $tanggalBast = Carbon::parse($bast->tanggal_bast);
                $tanggalJatuhTempo = $tanggalBast->copy()->addDays($bast->durasi_retensi_hari);
                $selisihHari = now()->diffInDays($tanggalJatuhTempo, false);
                return [
                    'id' => $bast->id,
                    'proyek_id' => $bast->proyek_id,
                    'proyek_nama' => $bast->proyek->nama_proyek ?? '-',
                    'penawaran_nama' => $bast->sertifikatPembayaran?->penawaran?->nama_penawaran ?? '-',
                    'nomor_bast' => $bast->nomor,
                    'tanggal_bast' => $tanggalBast->format('d-m-Y'),
                    'tanggal_jatuh_tempo' => $tanggalJatuhTempo->format('d-m-Y'),
                    'sisa_hari' => (int)$selisihHari,
                    'nilai_retensi' => $bast->nilai_retensi ?? 0,
                ];
            })
            ->sortBy('sisa_hari')
            ->values();

        return view('dashboard', compact(
            'proyeks','selectedProyek','finalPenawarans','selectedPenawaran','selectedPenawaranId',
            'total','aktif','selesai','perencanaan','tertunda',
            'totalPembelian','totalPendapatan','minggu','akumulasi','realisasi',
            'upcomingRetention'
        ));
    }
}

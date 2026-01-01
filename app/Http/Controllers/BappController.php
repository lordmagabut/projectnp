<?php

namespace App\Http\Controllers;

use App\Models\{
    Bapp, BappDetail, Proyek, RabDetail, RabProgress, RabProgressDetail,
    RabScheduleDetail, RabPenawaranItem, RabPenawaranHeader
};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PDF;

class BappController extends Controller
{
    public function index(Proyek $proyek)
    {
        $rows = Bapp::where('proyek_id', $proyek->id)->latest()->get();
        return view('bapp.index', compact('proyek','rows'));
    }

    // Pre-fill dari minggu & penawaran terpilih → tampilkan preview sebelum simpan
    public function create(Proyek $proyek, Request $r)
{
    $penawaranId = (int) $r->query('penawaran_id');
    $mingguKe    = (int) $r->query('minggu_ke', 1);

    $progress = RabProgress::where('proyek_id', $proyek->id)
        ->when($penawaranId, fn($q)=>$q->where('penawaran_id', $penawaranId))
        ->where('minggu_ke', $mingguKe)
        ->latest('id')
        ->first();

    // dataset: [items, Wi, prevProj(%proyek< N), deltaProj(%proyek N), prevItem(%item< N), deltaItem(%item N)]
    [$items,$Wi,$prevProj,$deltaProj,$prevItem,$deltaItem] =
        $this->dataset($proyek->id, $penawaranId, $mingguKe, $progress);

    // === Rakit baris: berikan nama kolom yang DIPAKAI blade detail ===
    $rows = $items->map(function($it) use ($Wi,$prevProj,$deltaProj,$prevItem,$deltaItem){
        $id    = $it->id;
        $Wi_i  = (float)($Wi[$id] ?? 0);
        $bPrev = (float)($prevProj[$id] ?? 0);            // % proyek kumulatif < N
        $bDel  = (float)($deltaProj[$id] ?? 0);           // % proyek minggu N
        $bNow  = round($bPrev + $bDel, 4);

        $pPrev = (float)($prevItem[$id]  ?? 0);           // % item kumulatif < N
        $pDel  = (float)($deltaItem[$id] ?? 0);           // % item minggu N
        $pNow  = round($pPrev + $pDel, 4);

        return (object)[
            'id'     => $id,
            'kode'   => $it->kode,
            'uraian' => $it->uraian,

            // === Nama yang dipakai Blade DETAIL/BAPP (sinkron) ===
            'Wi'        => $Wi_i,      // bobot item (% proyek)
            'bPrev'     => $bPrev,     // bobot s/d minggu lalu
            'bDelta'    => $bDel,      // Δ bobot minggu ini
            'bNow'      => $bNow,      // bobot saat ini
            'pPrevItem' => $pPrev,     // progress s/d minggu lalu (% item)
            'pDeltaItem'=> $pDel,      // progress minggu ini (% item)
            'pNowItem'  => $pNow,      // progress saat ini (% item)

            // === Nama lama (jika ada view lain yang masih memakainya) ===
            'bobot_item'     => $Wi_i,
            'prev_pct'       => $bPrev,
            'delta_pct'      => $bDel,
            'now_pct'        => $bNow,
            'prev_item_pct'  => $pPrev,
            'delta_item_pct' => $pDel,
            'now_item_pct'   => $pNow,
        ];
    })
    // baris yang benar-benar kosong boleh disembunyikan
    ->filter(fn($r)=>($r->Wi??0)>0 || ($r->bPrev??0)>0 || ($r->bDelta??0)>0)
    ->values();

    // === Total yang dibutuhkan footer (HANYA kolom bobot) ===
    $totPrev  = round($rows->sum('bPrev'),  4);
    $totDelta = round($rows->sum('bDelta'), 4);
    $totNow   = round($rows->sum('bNow'),   4);

    // Hitung total minggu dan tanggal progress untuk validasi
    $totalWeeks = 1;
    if ($proyek->tanggal_mulai && $proyek->tanggal_selesai) {
        $start = \Carbon\Carbon::parse($proyek->tanggal_mulai);
        $end = \Carbon\Carbon::parse($proyek->tanggal_selesai);
        $days = $start->diffInDays($end) + 1;
        $totalWeeks = (int) ceil($days / 7);
    }

    $tanggalProgress = $progress ? \Carbon\Carbon::parse($progress->tanggal)->toDateString() : now()->toDateString();

    return view('bapp.create', [
        'proyek'          => $proyek,
        'penawaran'       => $penawaranId ? RabPenawaranHeader::find($penawaranId) : null,
        'progress'        => $progress,
        'mingguKe'        => $mingguKe,
        'rows'            => $rows,
        'totPrev'         => $totPrev,
        'totDelta'        => $totDelta,
        'totNow'          => $totNow,
        'totalWeeks'      => $totalWeeks,
        'tanggalProgress' => $tanggalProgress,
    ]);
}


    // Simpan + terbit PDF
    public function store(Proyek $proyek, Request $r)
    {
        $data = $r->validate([
            'penawaran_id' => ['nullable','integer','exists:rab_penawaran_headers,id'],
            'progress_id'  => ['nullable','integer','exists:rab_progress,id'],
            'minggu_ke'    => ['required','integer','min:1'],
            'tanggal_bapp' => ['required','date'],
            'nomor_bapp'   => ['required','string','max:100','unique:bapps,nomor_bapp'],
              'notes'        => ['nullable','string','max:1000'],
              'sign_by'      => ['required','in:sm,pm'],
        ]);

        $penawaranId = (int)($data['penawaran_id'] ?? 0);
        $mingguKe    = (int)$data['minggu_ke'];
        $progress    = !empty($data['progress_id']) ? RabProgress::find($data['progress_id']) : null;

        // dataset ulang (agar yang tersimpan == yang dipreview)
        [$items,$Wi,$prevProj,$deltaProj,$prevItem,$deltaItem] =
            $this->dataset($proyek->id, $penawaranId, $mingguKe, $progress);

        $bapp = null; // penting: siapkan referensi untuk dipakai setelah transaksi

        DB::transaction(function () use ($proyek,$data,$items,$Wi,$prevProj,$deltaProj,$prevItem,$deltaItem,&$bapp,$penawaranId) {
            $bapp = Bapp::create([
                'proyek_id'        => $proyek->id,
                'penawaran_id'     => $penawaranId ?: null,
                'progress_id'      => $data['progress_id'] ?? null,
                'minggu_ke'        => $data['minggu_ke'],
                'tanggal_bapp'     => $data['tanggal_bapp'],
                'nomor_bapp'       => $data['nomor_bapp'],
                'status'           => 'draft',
                'total_prev_pct'   => 0,
                'total_delta_pct'  => 0,
                'total_now_pct'    => 0,
                'created_by'       => auth()->id(),
                'notes'            => $data['notes'] ?? null,
                'sign_by'         => $data['sign_by'],
            ]);

            $details = [];
            // Gunakan integer arithmetic untuk menghindari floating point error
            $totPrevAccum = $totDeltaAccum = $totNowAccum = 0;  // integer (dalam perseratusan)

            foreach ($items as $it) {
                $id   = $it->id;
                $Wi_i = (float)($Wi[$id] ?? 0);
                $prev = (float)($prevProj[$id] ?? 0);   // % proyek kumulatif < N
                $dlt  = (float)($deltaProj[$id] ?? 0);  // % proyek minggu N
                $now  = $prev + $dlt;  // Jangan bulatkan dulu

                if ($Wi_i == 0 && $prev == 0 && $dlt == 0) continue; // skip baris kosong

                // Simpan dengan 2 desimal
                $prev_rounded = round($prev, 2);
                $dlt_rounded = round($dlt, 2);
                $now_rounded = round($now, 2);

                $details[] = [
                    'bapp_id'         => $bapp->id,
                    'rab_detail_id'   => $id,
                    'kode'            => $it->kode,
                    'uraian'          => $it->uraian,
                    'bobot_item'      => $Wi_i,
                    'prev_pct'        => $prev_rounded,
                    'delta_pct'       => $dlt_rounded,
                    'now_pct'         => $now_rounded,
                    'prev_item_pct'   => round((float)($prevItem[$id]  ?? 0), 2),
                    'delta_item_pct'  => round((float)($deltaItem[$id] ?? 0), 2),
                    'now_item_pct'    => round(((float)($prevItem[$id] ?? 0) + (float)($deltaItem[$id] ?? 0)), 2),
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ];

                // Akumulasi menggunakan integer arithmetic (nilai * 100)
                // untuk menghindari floating point precision error
                $totPrevAccum  += (int)round($prev_rounded * 100);
                $totDeltaAccum += (int)round($dlt_rounded * 100);
                $totNowAccum   += (int)round($now_rounded * 100);
            }

            if (!empty($details)) {
                BappDetail::insert($details);
            }

            $bapp->update([
                'total_prev_pct'   => round($totPrevAccum / 100, 2),
                'total_delta_pct'  => round($totDeltaAccum / 100, 2),
                'total_now_pct'    => round($totNowAccum / 100, 2),
            ]);

        });

        return redirect()
            ->route('bapp.show', [$proyek->id, $bapp->id])
            ->with('success', 'BAPP diterbitkan.');
    }

    public function show(Proyek $proyek, Bapp $bapp)
    {
        abort_if($bapp->proyek_id !== $proyek->id, 404);
        return view('bapp.show', compact('proyek','bapp'));
    }

    public function pdf(Proyek $proyek, Bapp $bapp)
    {
        abort_if($bapp->proyek_id !== $proyek->id, 404);
        $bapp->loadMissing('details','penawaran','proyek.pemberiKerja');
        $pdf = PDF::loadView('bapp.pdf', [
            'bapp'   => $bapp,
            'proyek' => $proyek,
        ])->setPaper('a4','landscape');

        $nomorSafe = preg_replace('/[\\\\\/]+/', '-', $bapp->nomor_bapp ?? '');
        $filename = 'BAPP-'.$nomorSafe.'.pdf';
        return $pdf->stream($filename);
    }

    public function submit(Proyek $proyek, Bapp $bapp, Request $r)
    {
        abort_if($bapp->proyek_id !== $proyek->id, 404);

        $data = $r->validate([
            'tanda_terima_pdf' => ['required','file','mimes:pdf','max:10240'], // max ~10MB
        ]);

        $file = $data['tanda_terima_pdf'];
        $slugNomor = Str::slug($bapp->nomor_bapp ?? 'bapp', '-');
        $filename = 'tanda-terima-'.$slugNomor.'-'.$bapp->id.'-'.now()->format('YmdHis').'.'.$file->getClientOriginalExtension();

        // hapus file lama hanya jika berasal dari folder tanda terima
        if ($bapp->file_pdf_path && Str::startsWith($bapp->file_pdf_path, 'bapp/tanda-terima/')) {
            Storage::disk('public')->delete($bapp->file_pdf_path);
        }

        $path = $file->storeAs('bapp/tanda-terima', $filename, 'public');

        $bapp->update([
            'status'         => 'submitted',
            'file_pdf_path'  => $path,
        ]);

        return back()->with('success','BAPP dikirim untuk persetujuan.');
    }

    public function approve(Proyek $proyek, Bapp $bapp)
    {
        abort_if($bapp->proyek_id !== $proyek->id, 404);
        $bapp->update([
            'status'      => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);
        return back()->with('success','BAPP disetujui.');
    }

    public function revise(Proyek $proyek, Bapp $bapp)
    {
        abort_if($bapp->proyek_id !== $proyek->id, 404);
        
        // Hanya bisa revisi jika status submitted atau approved
        if (!in_array($bapp->status, ['submitted', 'approved'])) {
            return back()->with('error', 'BAPP hanya bisa direvisi jika statusnya Submitted atau Approved.');
        }
        
        $bapp->update([
            'status' => 'draft',
            'approved_by' => null,
            'approved_at' => null,
        ]);
        
        return back()->with('success', 'BAPP dikembalikan ke status Draft untuk revisi.');
    }

    /**
     * Dataset perhitungan:
     * - Wi: bobot item total (% proyek) dari schedule_detail (sum semua minggu)
     * - prevProj: kumulatif < N, % proyek dari rab_progress_detail (FINAL)
     * - deltaProj: minggu N (dari progress header terpilih jika ada; jika tidak → sum detail progress minggu N FINAL)
     * - prevItem / deltaItem: versi % terhadap item (opsional, untuk display)
     */

    // helper pilih kolom yang ada
        // ===== helper pilih kolom yang tersedia =====
private function pickCol(string $table, array $candidates): ?string
{
    foreach ($candidates as $c) if (Schema::hasColumn($table, $c)) return $c;
    return null;
}

/**
 * Dataset untuk BAPP:
 * - Wi         : bobot item (% proyek) dari schedule_detail (sum semua minggu)
 * - prevProj   : % proyek KUMULATIF dari BAPP TERAKHIR (< N). Kalau belum ada → 0.
 * - deltaProj  : (Kumulatif realisasi s/d minggu N) − (prevProj). Tidak negatif.
 * - prevItem / deltaItem : versi % terhadap item (opsional untuk display)
 */
private function dataset(int $proyekId, ?int $penawaranId, int $mingguKe, ?RabProgress $progress = null)
{
    $sdTbl = (new RabScheduleDetail)->getTable();
    $piTbl = (new RabPenawaranItem)->getTable();

    // kolom kunci & nilai di schedule_detail
    $sdKey = $this->pickCol($sdTbl, ['rab_detail_id','detail_id','rab_penawaran_item_id','penawaran_item_id']);
    $sdVal = $this->pickCol($sdTbl, ['bobot_mingguan','bobot','porsi']);
    $hasSdPenawaran = Schema::hasColumn($sdTbl, 'penawaran_id');
    if (!$sdKey || !$sdVal) return [collect(), [], [], [], [], []];

    /* --- daftar rab_detail_id yang ada di schedule_detail --- */
    if (in_array($sdKey, ['rab_detail_id','detail_id'], true)) {
        $detIds = DB::table("$sdTbl as sd")
            ->where('sd.proyek_id', $proyekId)
            ->when($penawaranId && $hasSdPenawaran, fn($q)=>$q->where('sd.penawaran_id', $penawaranId))
            ->selectRaw("sd.$sdKey as id")
            ->groupBy("sd.$sdKey")
            ->pluck('id')->map(fn($v)=>(int)$v)->unique()->values()->all();
    } else {
        $detIds = DB::table("$sdTbl as sd")
            ->join("$piTbl as pi", 'pi.id', '=', "sd.$sdKey")
            ->where('sd.proyek_id', $proyekId)
            ->when($penawaranId && $hasSdPenawaran, fn($q)=>$q->where('sd.penawaran_id', $penawaranId))
            ->when($penawaranId && !$hasSdPenawaran && Schema::hasColumn($piTbl,'penawaran_id'),
                fn($q)=>$q->where('pi.penawaran_id', $penawaranId))
            ->selectRaw('pi.rab_detail_id as id')
            ->groupBy('pi.rab_detail_id')
            ->pluck('id')->map(fn($v)=>(int)$v)->unique()->values()->all();
    }

    /* --- master item (kode/uraian) --- */
    $detTbl  = (new RabDetail)->getTable();
    $codeCol = $this->pickCol($detTbl, ['kode','wbs_kode','kode_wbs','no','nomor']) ?? 'id';
    $nameCol = $this->pickCol($detTbl, ['uraian','deskripsi','nama','judul']); // bisa null
    $codeSel = DB::raw(($codeCol==='id' ? 'CAST(id AS CHAR)' : $codeCol).' as kode');
    $nameSel = DB::raw($nameCol ? "$nameCol as uraian" : "'' as uraian");

    $items = RabDetail::whereIn('id',$detIds)
        ->select('id','rab_header_id',$codeSel,$nameSel)
        ->orderBy($codeCol==='id' ? 'id' : $codeCol, 'asc')
        ->get()
        ->sortBy('kode', SORT_NATURAL)
        ->values();

    /* --- Wi: bobot total item (% proyek) --- */
    if (in_array($sdKey, ['rab_detail_id','detail_id'], true)) {
        $Wi = DB::table("$sdTbl as sd")
            ->where('sd.proyek_id', $proyekId)
            ->when($penawaranId && $hasSdPenawaran, fn($q)=>$q->where('sd.penawaran_id', $penawaranId))
            ->selectRaw("sd.$sdKey as id, SUM(sd.$sdVal) as s")
            ->groupBy("sd.$sdKey")
            ->pluck('s','id')->toArray();
    } else {
        $Wi = DB::table("$sdTbl as sd")
            ->join("$piTbl as pi", 'pi.id', '=', "sd.$sdKey")
            ->where('sd.proyek_id', $proyekId)
            ->when($penawaranId && $hasSdPenawaran, fn($q)=>$q->where('sd.penawaran_id', $penawaranId))
            ->when($penawaranId && !$hasSdPenawaran && Schema::hasColumn($piTbl,'penawaran_id'),
                fn($q)=>$q->where('pi.penawaran_id', $penawaranId))
            ->selectRaw('pi.rab_detail_id as id, SUM(sd.'.$sdVal.') as s')
            ->groupBy('pi.rab_detail_id')
            ->pluck('s','id')->toArray();
    }

    // NORMALISASI: total bobot harus tepat 100 (koreksi drift dari schedule)
    $totalWi = array_sum($Wi);
    if ($totalWi > 0 && abs($totalWi - 100) > 0.0001) {
        $factor = 100 / $totalWi;
        foreach ($Wi as $id => $val) {
            $Wi[$id] = round($val * $factor, 2); // round ke 2 desimal (sesuai DB decimal(6,2))
        }
        // koreksi item pertama jika masih ada sisa
        $newTotal = array_sum($Wi);
        if ($newTotal != 100 && count($Wi) > 0) {
            $firstId = array_key_first($Wi);
            $Wi[$firstId] = round($Wi[$firstId] + (100 - $newTotal), 2);
        }
    }

    /* --- cari BAPP TERAKHIR sebelum minggu N (prev kumulatif) --- */
    $lastBapp = Bapp::where('proyek_id',$proyekId)
        ->when($penawaranId, fn($q)=>$q->where('penawaran_id',$penawaranId))
        ->where('minggu_ke','<',$mingguKe)
        ->orderByDesc('minggu_ke')->orderByDesc('id')
        ->first();

    $prevProj = []; // % proyek kumulatif dari BAPP terakhir
    $prevItem = []; // % item kumulatif dari BAPP terakhir
    if ($lastBapp) {
        foreach (BappDetail::where('bapp_id',$lastBapp->id)
            ->get(['rab_detail_id','now_pct','now_item_pct']) as $r) {
            $prevProj[(int)$r->rab_detail_id] = (float)$r->now_pct;
            $prevItem[(int)$r->rab_detail_id] = (float)$r->now_item_pct;
        }
    }

    /* --- kumulatif realisasi FINAL < N --- */
    $cumPrevProj = DB::table('rab_progress_detail as rpd')
        ->join('rab_progress as rp','rp.id','=','rpd.rab_progress_id')
        ->where('rp.proyek_id',$proyekId)
        ->when($penawaranId, fn($q)=>$q->where('rp.penawaran_id',$penawaranId))
        ->where('rp.status','final')
        ->where('rp.minggu_ke','<',$mingguKe)
        ->selectRaw('rpd.rab_detail_id as id, SUM(rpd.bobot_minggu_ini) as s')
        ->groupBy('rpd.rab_detail_id')
        ->pluck('s','id')->toArray();

    $cumPrevItem = DB::table('rab_progress_detail as rpd')
        ->join('rab_progress as rp','rp.id','=','rpd.rab_progress_id')
        ->where('rp.proyek_id',$proyekId)
        ->when($penawaranId, fn($q)=>$q->where('rp.penawaran_id',$penawaranId))
        ->where('rp.status','final')
        ->where('rp.minggu_ke','<',$mingguKe)
        ->selectRaw('rpd.rab_detail_id as id, SUM(rpd.pct_item_minggu_ini) as s')
        ->groupBy('rpd.rab_detail_id')
        ->pluck('s','id')->toArray();

    /* --- delta minggu N (pakai header progress jika diberikan; else FINAL minggu N) --- */
    if ($progress) {
        $dNProj = RabProgressDetail::where('rab_progress_id',$progress->id)
            ->selectRaw('rab_detail_id as id, SUM(bobot_minggu_ini) as s')
            ->groupBy('rab_detail_id')->pluck('s','id')->toArray();

        $dNItem = RabProgressDetail::where('rab_progress_id',$progress->id)
            ->selectRaw('rab_detail_id as id, SUM(pct_item_minggu_ini) as s')
            ->groupBy('rab_detail_id')->pluck('s','id')->toArray();
    } else {
        $dNProj = DB::table('rab_progress_detail as rpd')
            ->join('rab_progress as rp','rp.id','=','rpd.rab_progress_id')
            ->where('rp.proyek_id',$proyekId)
            ->when($penawaranId, fn($q)=>$q->where('rp.penawaran_id',$penawaranId))
            ->where('rp.status','final')
            ->where('rp.minggu_ke',$mingguKe)
            ->selectRaw('rpd.rab_detail_id as id, SUM(rpd.bobot_minggu_ini) as s')
            ->groupBy('rpd.rab_detail_id')->pluck('s','id')->toArray();

        $dNItem = DB::table('rab_progress_detail as rpd')
            ->join('rab_progress as rp','rp.id','=','rpd.rab_progress_id')
            ->where('rp.proyek_id',$proyekId)
            ->when($penawaranId, fn($q)=>$q->where('rp.penawaran_id',$penawaranId))
            ->where('rp.minggu_ke',$mingguKe)
            ->selectRaw('rpd.rab_detail_id as id, SUM(rpd.pct_item_minggu_ini) as s')
            ->groupBy('rpd.rab_detail_id')->pluck('s','id')->toArray();
    }

    // kumulatif s/d N
    $cumToNProj = [];
    $cumToNItem = [];
    foreach ($detIds as $id) {
        $cumToNProj[$id] = (float)($cumPrevProj[$id] ?? 0) + (float)($dNProj[$id] ?? 0);
        $cumToNItem[$id] = (float)($cumPrevItem[$id] ?? 0) + (float)($dNItem[$id] ?? 0);
    }

    // delta BAPP = kumulatif s/d N − prev BAPP (clamp >= 0)
    $deltaProj = [];
    $deltaItem = [];
    foreach ($detIds as $id) {
        $pPrev = (float)($prevProj[$id] ?? 0);
        $iPrev = (float)($prevItem[$id] ?? 0);
        $dP = $cumToNProj[$id] - $pPrev;
        $dI = $cumToNItem[$id] - $iPrev;
        $deltaProj[$id] = $dP > 0 ? $dP : 0;
        $deltaItem[$id] = $dI > 0 ? $dI : 0;
        // "now" di BAPP = $pPrev + $delta = $cumToN*
    }

    return [$items,$Wi,$prevProj,$deltaProj,$prevItem,$deltaItem];
}

public function destroy(Proyek $proyek, Bapp $bapp, Request $request)
{
    abort_if($bapp->proyek_id !== $proyek->id, 404);

    // amankan: yang sudah disetujui tidak boleh dihapus
    if ($bapp->status === 'approved') {
        return back()->withErrors('BAPP yang sudah disetujui tidak dapat dihapus.');
    }

    DB::transaction(function () use ($bapp) {
        // hapus detail (jaga-jaga jika FK tidak ON DELETE CASCADE)
        try { $bapp->details()->delete(); } catch (\Throwable $e) {}

        // hapus header
        $bapp->delete();
    });

    // arahkan balik ke tab BAPP proyek (atau ke URL asal jika dikirimkan)
    $redirectTo = $request->input('redirect_to');
    if ($redirectTo) {
        return redirect($redirectTo)->with('success', 'BAPP berhasil dihapus.');
    }

    return redirect()->route('proyek.show', [
        'proyek'       => $proyek->id,
        'tab'          => 'bapp',
        'penawaran_id' => $bapp->penawaran_id,
    ])->with('success', 'BAPP berhasil dihapus.');
}

}

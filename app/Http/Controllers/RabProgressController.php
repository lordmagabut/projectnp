<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

use App\Models\Proyek;
use App\Models\RabDetail;
use App\Models\RabHeader;
use App\Models\RabScheduleDetail;
use App\Models\RabPenawaranHeader;
use App\Models\RabPenawaranItem;
use App\Models\RabProgress;
use App\Models\RabProgressDetail;

class RabProgressController extends Controller
{
    public function create(Proyek $proyek, Request $request)
    {
        // --- dropdown penawaran final ---
        $finalPenawarans = RabPenawaranHeader::where('proyek_id', $proyek->id)
            ->where('status', 'final')
            ->orderBy('tanggal_penawaran')
            ->get();

        $penawaranId = (int) $request->query('penawaran_id', optional($finalPenawarans->last())->id);

        // --- minggu aktif (default: next) ---
        $mingguKe = (int) RabProgress::where('proyek_id', $proyek->id)
            ->when($penawaranId && Schema::hasColumn((new RabProgress)->getTable(), 'penawaran_id'),
                fn($q)=>$q->where('penawaran_id', $penawaranId))
            ->max('minggu_ke');
        $mingguKe = $mingguKe ? $mingguKe + 1 : 1;

        // --- util pilih kolom yang ada ---
        $pick = function(string $table, array $cands){
            foreach ($cands as $c) if (Schema::hasColumn($table, $c)) return $c;
            return null;
        };

        // ===============================
        // 1) Ambil daftar detail_id untuk penawaran terpilih
        // ===============================
        $sd      = new RabScheduleDetail;
        $sdTable = $sd->getTable();              // rab_schedule_detail
        $sdDetCol= $pick($sdTable, ['rab_detail_id','detail_id','rab_penawaran_item_id','penawaran_item_id']);
        if (!$sdDetCol) { abort(400, 'Kolom detail id di rab_schedule_detail tidak ditemukan'); }

        // Map: schedule_detail row → rab_detail_id
        $detailIds = [];
        if (in_array($sdDetCol, ['rab_detail_id','detail_id'])) {
            $detailIds = RabScheduleDetail::where('proyek_id', $proyek->id)
                ->when($penawaranId && Schema::hasColumn($sdTable, 'penawaran_id'),
                    fn($q)=>$q->where('penawaran_id', $penawaranId))
                ->selectRaw("$sdDetCol as did")->groupBy('did')->pluck('did')->all();
        } else {
            // sd menyimpan id item penawaran → perlu mapping ke rab_detail_id
            $pi      = new RabPenawaranItem;
            $piTable = $pi->getTable();          // rab_penawaran_items
            $piDetCol= $pick($piTable, ['rab_detail_id','detail_id']);
            if (!$piDetCol) abort(400, 'FK rab_penawaran_items → rab_detail tidak ditemukan.');

            $penItemIds = RabScheduleDetail::where('proyek_id', $proyek->id)
                ->when($penawaranId && Schema::hasColumn($sdTable, 'penawaran_id'),
                    fn($q)=>$q->where('penawaran_id', $penawaranId))
                ->selectRaw("$sdDetCol as pid")->groupBy('pid')->pluck('pid')->all();

            if (!empty($penItemIds)) {
                $detailIds = RabPenawaranItem::whereIn('id', $penItemIds)
                    ->selectRaw("$piDetCol as did")
                    ->groupBy('did')->pluck('did')->all();
            }
        }

        if (empty($detailIds)) {
            // tidak ada item di penawaran ini → tampilkan form kosong yang rapi
            return view('proyek.progress.create', [
                'proyek'          => $proyek,
                'finalPenawarans' => $finalPenawarans,
                'penawaranId'     => $penawaranId,
                'mingguKe'        => $mingguKe,
                'tanggal'         => now()->toDateString(),
                'rows'            => collect(),
                'prevMap'         => [],
                'realizedMap'     => [],
            ]);
        }

        // ===============================
        // 2) Ambil planned (bobot_mingguan) dari schedule_detail
        // ===============================
        $valCol   = $pick($sdTable, ['bobot_mingguan','bobot','porsi']);
        $weekCol  = $pick($sdTable, ['minggu_ke','week']);
        if (!$valCol || !$weekCol) abort(400, 'Kolom bobot_mingguan / minggu_ke tidak ada di rab_schedule_detail');

        $sdBase = RabScheduleDetail::where('proyek_id', $proyek->id)
            ->when($penawaranId && Schema::hasColumn($sdTable, 'penawaran_id'),
                fn($q)=>$q->where('penawaran_id', $penawaranId));

        // resolve ekspresi rab_detail_id (jika sd menyimpan id item penawaran)
        $resolveDetailExpr = in_array($sdDetCol, ['rab_detail_id','detail_id'])
            ? " $sdDetCol "
            : "(select ".($pick((new RabPenawaranItem)->getTable(), ['rab_detail_id','detail_id']) ?? '0')." 
                from ".(new RabPenawaranItem)->getTable()." pi 
                where pi.id = {$sdTable}.{$sdDetCol} limit 1)";

        // bobot total per item (sum semua minggu) → ini kita gunakan sebagai "bobot item"
        $bobotMap = (clone $sdBase)
            ->selectRaw("$resolveDetailExpr as did, SUM($valCol) as s")
            ->whereIn(DB::raw("$resolveDetailExpr"), $detailIds)
            ->groupBy('did')->pluck('s','did')->toArray();

        // target s/d minggu sebelumnya (kumulatif plan)
        $plannedPrevMap = (clone $sdBase)
            ->when($mingguKe > 1, fn($q)=>$q->where($weekCol, '<', $mingguKe), fn($q)=>$q->whereRaw('1=0'))
            ->selectRaw("$resolveDetailExpr as did, SUM($valCol) as s")
            ->whereIn(DB::raw("$resolveDetailExpr"), $detailIds)
            ->groupBy('did')->pluck('s','did')->toArray();

        // target s/d minggu aktif (kumulatif plan)
        $plannedToMap = (clone $sdBase)
            ->where($weekCol, '<=', $mingguKe)
            ->selectRaw("$resolveDetailExpr as did, SUM($valCol) as s")
            ->whereIn(DB::raw("$resolveDetailExpr"), $detailIds)
            ->groupBy('did')->pluck('s','did')->toArray();

        // (opsional) target MINGGU N saja = plannedTo - plannedPrev
        $plannedWeekMap = [];
        foreach ($detailIds as $did) {
            $plannedWeekMap[$did] = (float)($plannedToMap[$did] ?? 0) - (float)($plannedPrevMap[$did] ?? 0);
        }

        // ===============================
        // 3) Ambil master item + nama
        // ===============================
        $detTable   = (new RabDetail)->getTable();        // rab_detail
        $detCodeCol = $pick($detTable, ['kode','wbs_kode','kode_wbs','no','nomor']) ?: 'id';
        $detNameCol = $pick($detTable, ['uraian','deskripsi','nama','judul']);
        $detNameSel = $detNameCol ? DB::raw("$detNameCol as uraian") : DB::raw("'' as uraian");

        $items = RabDetail::where('proyek_id', $proyek->id)
            ->whereIn('id', $detailIds)
            ->select(['id','rab_header_id', DB::raw("$detCodeCol as kode"), $detNameSel])
            ->orderBy($detCodeCol === 'id' ? 'id' : $detCodeCol, 'asc')
            ->get();

        $rowsItem = $items->map(function($it) use ($bobotMap,$plannedPrevMap,$plannedToMap,$plannedWeekMap){
            $it->bobot            = (float)($bobotMap[$it->id] ?? 0);             // % bobot proyek (item)
            $it->planned_prev     = (float)($plannedPrevMap[$it->id] ?? 0);       // plan kumulatif < N
            $it->planned_to_week  = (float)($plannedToMap[$it->id] ?? 0);         // plan kumulatif ≤ N
            $it->planned_week     = (float)($plannedWeekMap[$it->id] ?? 0);       // plan minggu N saja
            return $it;
        });

        // ===============================
        // 4) Susun header L1 & L2 agar 1. dan 1.1 muncul
        // ===============================
        $hdrTable = (new RabHeader)->getTable();
        $hCodeCol = $pick($hdrTable, ['kode','wbs_kode','kode_wbs','no','nomor']) ?: 'id';
        $hNameCol = $pick($hdrTable, ['uraian','deskripsi','nama','judul']);
        $hNameSel = $hNameCol ? DB::raw("$hNameCol as uraian") : DB::raw("'' as uraian");

        $itemsByHeader = $rowsItem->groupBy('rab_header_id');

        $h2 = RabHeader::whereIn('id', $itemsByHeader->keys()->all())
            ->select('id','parent_id', DB::raw("$hCodeCol as kode"), $hNameSel)
            ->get();

        $parentIds = $h2->pluck('parent_id')->filter()->unique()->values();
        $h1 = RabHeader::whereIn('id', $parentIds)
            ->select('id','parent_id', DB::raw("$hCodeCol as kode"), $hNameSel)
            ->get();

        $h1 = $h1->concat($h2->whereNull('parent_id'))->unique('id');
        $h2ByParent = $h2->groupBy('parent_id');

        $displayRows = collect();

        foreach ($h1->sortBy('kode', SORT_NATURAL) as $hdr1) {
            $displayRows->push((object)[
                'is_header'       => true,
                'level'           => 1,
                'kode'            => $hdr1->kode,
                'uraian'          => $hdr1->uraian,
                'rab_header_id'   => $hdr1->id,
                'id'              => "H1-{$hdr1->id}",
                'bobot'           => 0,
                'planned_prev'    => 0,
                'planned_to_week' => 0,
                'planned_week'    => 0,
            ]);

            $children = $h2ByParent->get($hdr1->id, collect())->sortBy('kode', SORT_NATURAL);

            if ($children->isEmpty()) {
                foreach (($itemsByHeader->get($hdr1->id) ?? collect())->sortBy('kode', SORT_NATURAL) as $row) {
                    $row->is_header = false;
                    $row->level     = 3;
                    $displayRows->push($row);
                }
            } else {
                foreach ($children as $hdr2) {
                    $displayRows->push((object)[
                        'is_header'       => true,
                        'level'           => 2,
                        'kode'            => $hdr2->kode,
                        'uraian'          => $hdr2->uraian,
                        'rab_header_id'   => $hdr2->id,
                        'id'              => "H2-{$hdr2->id}",
                        'bobot'           => 0,
                        'planned_prev'    => 0,
                        'planned_to_week' => 0,
                        'planned_week'    => 0,
                    ]);

                    foreach (($itemsByHeader->get($hdr2->id) ?? collect())->sortBy('kode', SORT_NATURAL) as $row) {
                        $row->is_header = false;
                        $row->level     = 3;
                        $displayRows->push($row);
                    }
                }
            }
        }

        // ===============================
        // 5) PROGRESS S/D MINGGU LALU (realized) dari rab_progress_detail
        //     - kumulatif untuk minggu < N
        //     - status FINAL saja (default)
        //     - hormati penawaran_id bila kolom ada
        //     - NORMALISASI SATUAN: auto-detect %item vs %proyek
        // ===============================
        // --- PROGRESS S/D minggu (N-1) dari rab_progress_detail (status FINAL) ---
        // PROGRESS S/D minggu lalu (FINAL) — dari kolom baru
// % ITEM kumulatif < N (tanpa filter status)
$prevPctItemMap = DB::table('rab_progress_detail as rpd')
    ->join('rab_progress as rp', 'rp.id', '=', 'rpd.rab_progress_id')
    ->where('rp.proyek_id', $proyek->id)
    ->where('rp.minggu_ke', '<', $mingguKe)
    ->when($penawaranId, fn($q)=>$q->where('rp.penawaran_id', $penawaranId))
    ->whereIn('rpd.rab_detail_id', $detailIds)
    ->select('rpd.rab_detail_id', DB::raw('SUM(rpd.pct_item_minggu_ini) as s'))
    ->groupBy('rpd.rab_detail_id')
    ->pluck('s','rpd.rab_detail_id')
    ->toArray();

// % PROYEK kumulatif < N (tanpa filter status)
$prevProjMap = DB::table('rab_progress_detail as rpd')
    ->join('rab_progress as rp', 'rp.id', '=', 'rpd.rab_progress_id')
    ->where('rp.proyek_id', $proyek->id)
    ->where('rp.minggu_ke', '<', $mingguKe)
    ->when($penawaranId, fn($q)=>$q->where('rp.penawaran_id', $penawaranId))
    ->whereIn('rpd.rab_detail_id', $detailIds)
    ->select('rpd.rab_detail_id', DB::raw('SUM(rpd.bobot_minggu_ini) as s'))
    ->groupBy('rpd.rab_detail_id')
    ->pluck('s','rpd.rab_detail_id')
    ->toArray();

// Map untuk Blade
$prevMap = [];
foreach ($detailIds as $did) {
    $prevMap[$did] = [
        'prev_pct_of_item'       => (float)($prevPctItemMap[$did] ?? 0), // tampilkan di "PROG. S/D MINGGU LALU (%)"
        'prev_bobot_pct_project' => (float)($prevProjMap[$did] ?? 0),    // tampilkan di "BOBOT S/D MINGGU LALU"
    ];
}

// (opsional kompatibilitas lama)
$realizedMap = $prevMap;

        return view('proyek.progress.create', [
            'proyek'           => $proyek,
            'finalPenawarans'  => $finalPenawarans,
            'penawaranId'      => $penawaranId,
            'mingguKe'         => $mingguKe,
            'tanggal'          => now()->toDateString(),

            'rows'             => $displayRows,

            // plan helpers (kalau diperlukan di Blade)
            'plannedPrevMap'   => $plannedPrevMap,   // kumulatif < N
            'plannedToMap'     => $plannedToMap,     // kumulatif ≤ N
            'plannedWeekMap'   => $plannedWeekMap,   // minggu N saja
            'bobotMap'         => $bobotMap,         // total bobot item

            // realized (s/d minggu lalu)
            'prevMap'          => $prevMap,
            'realizedMap'      => $realizedMap,
        ]);
    }
   
    public function store(Request $request, Proyek $proyek)
{
    try {
        // --- Parse input kumulatif % item dari form ---
        $raw = (array) $request->input('details_pct', []); // name="details_pct[detail_id]"
        $nowPctById = [];
        foreach ($raw as $k => $v) {
            $id = (int) $k; if ($id <= 0) continue;
            $s = trim((string) $v);
            if ($s === '') continue;
            // normalisasi "1.234,56" / "12,5" → 1234.56 / 12.5
            $hasDot = str_contains($s,'.'); $hasCom = str_contains($s,',');
            if ($hasCom && !$hasDot) $s = str_replace(',', '.', $s);
            elseif ($hasCom && $hasDot) {
                if (strpos($s,'.') < strpos($s,',')) { $s = str_replace('.','',$s); $s = str_replace(',', '.',$s); }
                else { $s = str_replace(',','',$s); }
            }
            if (!is_numeric($s)) return back()->withErrors("Nilai item #$id tidak valid.")->withInput();
            $nowPctById[$id] = max(0.0, min(100.0, (float)$s));
        }
        if (empty($nowPctById)) return back()->withErrors('Tidak ada nilai progress yang diisi.')->withInput();

        $penawaranId = (int) $request->input('penawaran_id');
        $mingguKe    = (int) $request->input('minggu_ke');
        $tanggal     = $request->input('tanggal');
        $detailIds   = array_keys($nowPctById);

        // --- Snapshot bobot item dari schedule untuk setiap detail ---
        $bobotSnap = DB::table('rab_schedule_detail as sd')
            ->join('rab_penawaran_items as pi', 'pi.id', '=', 'sd.rab_penawaran_item_id')
            ->where('sd.proyek_id', $proyek->id)
            ->when($penawaranId, fn($q)=>$q->where('sd.penawaran_id', $penawaranId))
            ->whereIn('pi.rab_detail_id', $detailIds)
            ->select('pi.rab_detail_id as did', DB::raw('SUM(sd.bobot_mingguan) as s'))
            ->groupBy('pi.rab_detail_id')
            ->pluck('s', 'did')->toArray();

        // --- Prev % item s/d (N-1) dari kolom BARU (FINAL) — ini kunci sederhananya ---
        $prevPctItem = DB::table('rab_progress_detail as rpd')
            ->join('rab_progress as rp', 'rp.id', '=', 'rpd.rab_progress_id')
            ->where('rp.proyek_id', $proyek->id)
            ->where('rp.minggu_ke', '<', $mingguKe)
            ->when($penawaranId, fn($q)=>$q->where('rp.penawaran_id', $penawaranId))
            ->where('rp.status', 'final')
            ->whereIn('rpd.rab_detail_id', $detailIds)
            ->select('rpd.rab_detail_id', DB::raw('SUM(rpd.pct_item_minggu_ini) as s'))
            ->groupBy('rpd.rab_detail_id')
            ->pluck('s','rpd.rab_detail_id')->toArray();

        return DB::transaction(function() use ($request,$proyek,$penawaranId,$mingguKe,$tanggal,$detailIds,$nowPctById,$bobotSnap,$prevPctItem) {

            $rp = \App\Models\RabProgress::create([
                'proyek_id'    => $proyek->id,
                'penawaran_id' => $penawaranId ?: null,
                'minggu_ke'    => $mingguKe,
                'tanggal'      => $tanggal,
                'user_id'      => auth()->id(),
                'status'       => 'draft',   // atau 'final' sesuai tombol
            ]);

            $rows = [];
            foreach ($detailIds as $did) {
                $bobotItem = (float)($bobotSnap[$did] ?? 0.0);
                $prevPct   = (float)($prevPctItem[$did] ?? 0.0);
                $nowPct    = (float)$nowPctById[$did];

                $deltaPct  = max(0.0, $nowPct - $prevPct);               // % item minggu ini
                $deltaProj = $bobotItem * ($deltaPct / 100.0);           // % proyek minggu ini

                $rows[] = [
                    'rab_progress_id'      => $rp->id,
                    'rab_detail_id'        => $did,
                    'bobot_minggu_ini'     => $deltaProj,                 // % proyek (delta)
                    'pct_item_minggu_ini'  => $deltaPct,                  // % item (delta)
                    'bobot_item_snapshot'  => $bobotItem,                 // snapshot
                    'created_at'           => now(),
                    'updated_at'           => now(),
                ];
            }

            DB::table('rab_progress_detail')->insert($rows);

            return redirect()->to(
                route('proyek.show', $proyek->id).'?tab=progress'
                .($penawaranId ? '&penawaran_id='.$penawaranId : '')
                .'&minggu_ke='.$mingguKe
            )->with('success', "Progress minggu ke-{$mingguKe} tersimpan (draft).");
        });

    } catch (\Throwable $e) {
        report($e);
        return back()->withErrors('Error: '.$e->getMessage())->withInput();
    }
}


    private function pickCol(string $table, array $candidates): ?string
    {
        foreach ($candidates as $c) {
            if (\Illuminate\Support\Facades\Schema::hasColumn($table, $c)) return $c;
        }
        return null;
    }
    
    
/**
 * Peta target rencana (akumulatif) s/d <= $uptoWeek per item.
 * Ambil dari rab_schedule_detail.*bobot_mingguan* (asumsi ada).
 * Return: [detail_id => planned_bobot]
 */
private function plannedToDateMap(int $proyekId, ?int $penawaranId, int $uptoWeek): array
{
    if (!class_exists(\App\Models\RabScheduleDetail::class)) return [];

    $sd       = new \App\Models\RabScheduleDetail;
    $sdTable  = $sd->getTable();

    // kunci ke item/detail
    $detailKeyCol = null;
    foreach (['rab_detail_id','detail_id','rab_penawaran_item_id','penawaran_item_id'] as $c) {
        if (\Illuminate\Support\Facades\Schema::hasColumn($sdTable, $c)) { $detailKeyCol = $c; break; }
    }
    if (!$detailKeyCol) return [];

    // nilai bobot rencana per minggu
    $valueCol = \Illuminate\Support\Facades\Schema::hasColumn($sdTable, 'bobot_mingguan') ? 'bobot_mingguan' : null;
    if (!$valueCol) return []; // untuk saat ini kita andalkan kolom ini

    $q = \DB::table("$sdTable as sd")
        ->where('sd.proyek_id', $proyekId)
        ->when($penawaranId && \Illuminate\Support\Facades\Schema::hasColumn($sdTable, 'penawaran_id'),
            fn($qq)=>$qq->where('sd.penawaran_id', $penawaranId))
        ->when($uptoWeek > 0, fn($qq)=>$qq->where('sd.minggu_ke','<=',$uptoWeek))
        ->groupBy("sd.$detailKeyCol")
        ->selectRaw("sd.$detailKeyCol as did, SUM(sd.$valueCol) as planned");

    return $q->pluck('planned','did')->toArray();
}

private function plannedToWeekMap(int $proyekId, ?int $penawaranId, int $uptoWeek): array
{
    if (!class_exists(\App\Models\RabScheduleDetail::class)) return [];

    $sd     = new \App\Models\RabScheduleDetail;
    $sdTbl  = $sd->getTable();
    $keyCol = $this->pickCol($sdTbl, ['rab_detail_id','detail_id','rab_penawaran_item_id','penawaran_item_id']);
    $valCol = $this->pickCol($sdTbl, ['bobot_mingguan','bobot','persen','persentase']);
    $wkCol  = $this->pickCol($sdTbl, ['minggu_ke','week_no','week']);

    if (!$keyCol || !$valCol || !$wkCol) return [];

    $q = \DB::table("$sdTbl as sd")
        ->where('sd.proyek_id', $proyekId)
        ->when($penawaranId && \Illuminate\Support\Facades\Schema::hasColumn($sdTbl, 'penawaran_id'),
            fn($qq) => $qq->where('sd.penawaran_id', $penawaranId))
        ->when($uptoWeek > 0, fn($qq) => $qq->where("sd.$wkCol", '<=', $uptoWeek));

    // Jika schedule_detail sudah simpan id detail langsung
    if (in_array($keyCol, ['rab_detail_id','detail_id'], true)) {
        $q->groupBy("sd.$keyCol")
          ->selectRaw("sd.$keyCol as did, SUM(sd.$valCol) as planned");
        return $q->pluck('planned', 'did')->toArray();
    }

    // Kalau schedule_detail simpan id penawaran_item → join untuk ambil rab_detail_id
    if (!class_exists(\App\Models\RabPenawaranItem::class)) return [];
    $pi     = new \App\Models\RabPenawaranItem;
    $piTbl  = $pi->getTable();
    $piDet  = $this->pickCol($piTbl, ['rab_detail_id','detail_id']);
    if (!$piDet) return [];

    $q->join("$piTbl as pi", "pi.id", "=", "sd.$keyCol")
      ->when($penawaranId && \Illuminate\Support\Facades\Schema::hasColumn($piTbl, 'penawaran_id'),
          fn($qq) => $qq->where('pi.penawaran_id', $penawaranId))
      ->groupBy("pi.$piDet")
      ->selectRaw("pi.$piDet as did, SUM(sd.$valCol) as planned");

    return $q->pluck('planned', 'did')->toArray();
}



/** Map rencana: 'this' (minggu ini) atau 'to' (kumulatif <= minggu ini) */
private function plannedMap(int $proyekId, ?int $penawaranId, int $week, string $mode='this'): array
{
    if (!class_exists(\App\Models\RabScheduleDetail::class)) return [];

    $sd      = new RabScheduleDetail;
    $sdTable = $sd->getTable();

    $pick = function(string $table, array $cands){ foreach($cands as $c) if (Schema::hasColumn($table,$c)) return $c; return null; };

    $keyCol = $pick($sdTable, ['rab_detail_id','detail_id','rab_penawaran_item_id','penawaran_item_id']);
    $valCol = $pick($sdTable, ['bobot_mingguan','bobot','porsi','percent']);
    if (!$keyCol || !$valCol || !Schema::hasColumn($sdTable,'minggu_ke')) return [];

    $q = DB::table($sdTable)->where('proyek_id',$proyekId)
        ->when($penawaranId && Schema::hasColumn($sdTable,'penawaran_id'),
            fn($qq)=>$qq->where('penawaran_id',$penawaranId));

    if ($mode==='to')  $q->where('minggu_ke','<=',$week);
    else               $q->where('minggu_ke','=',$week);

    // langsung detail id
    if (in_array($keyCol,['rab_detail_id','detail_id'],true)) {
        return $q->groupBy($keyCol)
            ->selectRaw("$keyCol as did, SUM($valCol) as planned")
            ->pluck('planned','did')->toArray();
    }

    // map via penawaran_item → detail
    if (!class_exists(\App\Models\RabPenawaranItem::class)) return [];
    $pi      = new RabPenawaranItem; $piTable = $pi->getTable();
    $piDetailCol = $pick($piTable, ['rab_detail_id','detail_id']); if (!$piDetailCol) return [];

    $tmp = $q->groupBy($keyCol)
        ->selectRaw("$keyCol as pid, SUM($valCol) as planned")->pluck('planned','pid')->toArray();
    if (empty($tmp)) return [];

    $map = DB::table($piTable)->whereIn('id', array_keys($tmp))
        ->select('id', DB::raw("$piDetailCol as did"))->pluck('did','id')->toArray();

    $out=[]; foreach($tmp as $pid=>$val){ $did=$map[$pid]??null; if($did) $out[$did]=($out[$did]??0)+(float)$val; }
    return $out;
}

/** Map rencana untuk rentang minggu [min,max] */
private function plannedForRange(int $proyekId, ?int $penawaranId, int $minWeek, int $maxWeek): array
{
    if (!class_exists(\App\Models\RabScheduleDetail::class)) return [];
    $sd      = new RabScheduleDetail; $sdTable = $sd->getTable();

    $pick = function(string $table, array $cands){ foreach($cands as $c) if (Schema::hasColumn($table,$c)) return $c; return null; };

    $keyCol = $pick($sdTable, ['rab_detail_id','detail_id','rab_penawaran_item_id','penawaran_item_id']);
    $valCol = $pick($sdTable, ['bobot_mingguan','bobot','porsi','percent']);
    if (!$keyCol || !$valCol || !Schema::hasColumn($sdTable,'minggu_ke')) return [];

    $q = DB::table($sdTable)->where('proyek_id',$proyekId)
        ->when($penawaranId && Schema::hasColumn($sdTable,'penawaran_id'),
            fn($qq)=>$qq->where('penawaran_id',$penawaranId))
        ->whereBetween('minggu_ke', [$minWeek,$maxWeek]);

    if (in_array($keyCol,['rab_detail_id','detail_id'],true)) {
        return $q->groupBy($keyCol)
            ->selectRaw("$keyCol as did, SUM($valCol) as planned")
            ->pluck('planned','did')->toArray();
    }

    if (!class_exists(\App\Models\RabPenawaranItem::class)) return [];
    $pi      = new RabPenawaranItem; $piTable = $pi->getTable();
    $piDetailCol = $pick($piTable, ['rab_detail_id','detail_id']); if (!$piDetailCol) return [];

    $tmp = $q->groupBy($keyCol)
        ->selectRaw("$keyCol as pid, SUM($valCol) as planned")->pluck('planned','pid')->toArray();
    if (empty($tmp)) return [];

    $map = DB::table($piTable)->whereIn('id', array_keys($tmp))
        ->select('id', DB::raw("$piDetailCol as did"))->pluck('did','id')->toArray();

    $out=[]; foreach($tmp as $pid=>$val){ $did=$map[$pid]??null; if($did) $out[$did]=($out[$did]??0)+(float)$val; }
    return $out;
}

private function realizedToDateMap(int $proyekId, ?int $penawaranId, int $uptoWeek): array
{
    if (!class_exists(\App\Models\RabProgressDetail::class)) return [];
    $p      = new \App\Models\RabProgress;
    $pd     = new \App\Models\RabProgressDetail;
    $pTbl   = $p->getTable();
    $pdTbl  = $pd->getTable();

    $detailKey = $this->pickCol($pdTbl, ['rab_detail_id','detail_id']);
    if (!$detailKey) return [];

    $valueCol  = $this->pickCol($pdTbl, ['bobot_minggu_ini','bobot','progress','qty_minggu_ini']);
    if (!$valueCol) return [];

    $weekColP  = $this->pickCol($pTbl, ['minggu_ke','week_no','week']);
    if (!$weekColP) $weekColP = 'minggu_ke'; // default umum

    $q = \DB::table("$pdTbl as pd")
        ->join("$pTbl as p", 'p.id', '=', 'pd.rab_progress_id')
        ->where('p.proyek_id', $proyekId)
        ->when($penawaranId && \Illuminate\Support\Facades\Schema::hasColumn($pTbl, 'penawaran_id'),
            fn($qq) => $qq->where('p.penawaran_id', $penawaranId))
        ->when(\Illuminate\Support\Facades\Schema::hasColumn($pTbl, 'status'),
            fn($qq) => $qq->where('p.status', 'final'))
        ->when($uptoWeek > 0, fn($qq) => $qq->where("p.$weekColP", '<', $uptoWeek))
        ->groupBy("pd.$detailKey")
        ->selectRaw("pd.$detailKey as did, SUM(pd.$valueCol) as realized");

    return $q->pluck('realized', 'did')->toArray();
}



    public function sahkan($proyekId, $mingguKe, Request $request)
    {
        $q = RabProgress::where('proyek_id', $proyekId)
            ->where('minggu_ke', $mingguKe);
    
        if (Schema::hasColumn((new RabProgress)->getTable(), 'penawaran_id') && $request->filled('penawaran_id')) {
            $q->where('penawaran_id', (int)$request->penawaran_id);
        }
    
        $progress = $q->first();
        if (!$progress) {
            // buat shell header kalau belum ada (tanpa detail); biasanya user sahkan dari form
            $progress = RabProgress::create([
                'proyek_id'    => $proyekId,
                'penawaran_id' => Schema::hasColumn((new RabProgress)->getTable(), 'penawaran_id')
                                    ? (int)$request->penawaran_id : null,
                'minggu_ke'    => (int)$mingguKe,
                'tanggal'      => now()->toDateString(),
                'status'       => 'final',
            ]);
        } else {
            $progress->update(['status' => 'final']);
        }
    
        return back()->with('success', "Progress minggu ke-{$mingguKe} disahkan.");
    }

    public function finalize(Proyek $proyek, RabProgress $progress)
    {
        abort_if($progress->proyek_id !== $proyek->id, 404);

        $progress->status = 'final';
        $progress->save();

        return redirect()
            ->route('proyek.show', $proyek->id)
            ->with('success', "Progress minggu ke-{$progress->minggu_ke} disahkan.");
    }

    private function scheduleDetailWeightCol(): ?string
    {
        $t = (new RabScheduleDetail)->getTable();
        if (Schema::hasColumn($t, 'bobot_mingguan')) return 'bobot_mingguan';
        if (Schema::hasColumn($t, 'bobot'))          return 'bobot';
        return null;
    }

    public function detail($proyekId, $progressId, Request $request)
    {
        $proyek   = Proyek::findOrFail($proyekId);
        $progress = RabProgress::findOrFail($progressId);

        $penawaranId = $progress->penawaran_id ?: null;
        $mingguKe    = (int) $progress->minggu_ke;

        // item yang muncul di progress ini
        $detailIds = RabProgressDetail::where('rab_progress_id', $progress->id)
            ->pluck('rab_detail_id')->map(fn($v)=>(int)$v)->all();

        if (empty($detailIds)) {
            return view('proyek.progress.detail', [
                'proyek'   => $proyek,
                'progress' => $progress,
                'rows'     => collect(),
                'totWi'    => 0, 'totTarget' => 0, 'totPrev' => 0, 'totDelta' => 0, 'totNow' => 0,
            ]);
        }

        /* ===== util pick kolom yang tersedia ===== */
        $pick = function(string $table, array $cands){
            foreach ($cands as $c) if (Schema::hasColumn($table, $c)) return $c;
            return null;
        };

        /* ====== PLAN dari schedule_detail ====== */
        $sdTable   = (new RabScheduleDetail)->getTable();           // rab_schedule_detail
        $valCol    = $pick($sdTable, ['bobot_mingguan','bobot','porsi']);
        $weekCol   = $pick($sdTable, ['minggu_ke','week']);
        $sdDetCol  = $pick($sdTable, ['rab_detail_id','detail_id','rab_penawaran_item_id','penawaran_item_id']);

        if (!$valCol || !$weekCol || !$sdDetCol) {
            abort(400, 'Kolom di rab_schedule_detail tidak lengkap.');
        }

        $sdBase = RabScheduleDetail::where('proyek_id', $proyek->id)
            ->when($penawaranId && Schema::hasColumn($sdTable,'penawaran_id'),
                fn($q)=>$q->where('penawaran_id', $penawaranId));

        // sd → rab_detail_id
        if (in_array($sdDetCol, ['rab_detail_id','detail_id'])) {
            $detailExpr = "{$sdTable}.{$sdDetCol}";
        } else {
            // lewat penawaran item
            $piTable = (new RabPenawaranItem)->getTable();
            $piDet   = $pick($piTable, ['rab_detail_id','detail_id']) ?? 'rab_detail_id';
            $detailExpr =
                "(select {$piDet} from {$piTable} pi where pi.id = {$sdTable}.{$sdDetCol} limit 1)";
        }

        // bobot item (total plan semua minggu)
        $WiMap = (clone $sdBase)
            ->selectRaw("$detailExpr as did, SUM($valCol) as s")
            ->whereIn(DB::raw($detailExpr), $detailIds)
            ->groupBy('did')->pluck('s','did')->toArray();

        // target kumulatif ≤ N
        $targetToMap = (clone $sdBase)
            ->where($weekCol, '<=', $mingguKe)
            ->selectRaw("$detailExpr as did, SUM($valCol) as s")
            ->whereIn(DB::raw($detailExpr), $detailIds)
            ->groupBy('did')->pluck('s','did')->toArray();

        /* ====== REALISASI dari progress_detail ====== */

        // delta minggu ini (% proyek)
        $deltaMap = RabProgressDetail::where('rab_progress_id', $progress->id)
            ->select('rab_detail_id', DB::raw('SUM(bobot_minggu_ini) as s'))
            ->groupBy('rab_detail_id')
            ->pluck('s','rab_detail_id')->toArray();

        // kumulatif s/d minggu (N-1) FINAL saja
        $prevMap  = DB::table('rab_progress_detail as rpd')
            ->join('rab_progress as rp', 'rp.id', '=', 'rpd.rab_progress_id')
            ->where('rp.proyek_id', $proyek->id)
            ->when($penawaranId, fn($q)=>$q->where('rp.penawaran_id', $penawaranId))
            ->where('rp.status', 'final')
            ->where('rp.minggu_ke', '<', $mingguKe)
            ->whereIn('rpd.rab_detail_id', $detailIds)
            ->select('rpd.rab_detail_id', DB::raw('SUM(rpd.bobot_minggu_ini) as s'))
            ->groupBy('rpd.rab_detail_id')
            ->pluck('s','rpd.rab_detail_id')->toArray();

        /* ====== Master item untuk nama/kode ====== */
        $detTable   = (new RabDetail)->getTable();
        $detCodeCol = $pick($detTable, ['kode','wbs_kode','kode_wbs','no','nomor']) ?: 'id';
        $detNameCol = $pick($detTable, ['uraian','deskripsi','nama','judul']);
        $detNameSel = $detNameCol ? DB::raw("$detNameCol as uraian") : DB::raw("'' as uraian");

        $items = RabDetail::where('proyek_id', $proyek->id)
            ->whereIn('id', $detailIds)
            ->select(['id', DB::raw("$detCodeCol as kode"), $detNameSel])
            ->orderBy($detCodeCol === 'id' ? 'id' : $detCodeCol)
            ->get();

        // rakit baris untuk Blade
        $rows = $items->map(function($it) use ($WiMap,$targetToMap,$prevMap,$deltaMap){
            $Wi    = (float)($WiMap[$it->id]        ?? 0);
            $tgt   = (float)($targetToMap[$it->id]  ?? 0);
            $bPrev = (float)($prevMap[$it->id]      ?? 0);
            $bDelta= (float)($deltaMap[$it->id]     ?? 0);
            $bNow  = $bPrev + $bDelta;
            $pNow  = $bNow; // % proyek

            $it->Wi   = $Wi;
            $it->tgt  = $tgt;
            $it->bPrev= $bPrev;
            $it->bDelta=$bDelta;
            $it->bNow = $bNow;
            $it->pNow = $pNow;
            return $it;
        });

        // totals
        $totWi    = $rows->sum('Wi');
        $totTarget= $rows->sum('tgt');
        $totPrev  = $rows->sum('bPrev');
        $totDelta = $rows->sum('bDelta');
        $totNow   = $rows->sum('bNow');

        return view('proyek.progress.detail', [
            'proyek'    => $proyek,
            'progress'  => $progress,
            'rows'      => $rows,
            'totWi'     => $totWi,
            'totTarget' => $totTarget,
            'totPrev'   => $totPrev,
            'totDelta'  => $totDelta,
            'totNow'    => $totNow,
        ]);
    }



// app/Http/Controllers/RabProgressController.php
public function destroy(Proyek $proyek, RabProgress $progress)
    {
        abort_if($progress->proyek_id !== $proyek->id, 404);

        DB::transaction(function() use ($progress){
            RabProgressDetail::where('rab_progress_id', $progress->id)->delete();
            $progress->delete();
        });

        return back()->with('success', 'Progress dihapus.');
    }

    /* ============================================================
     * =======================  HELPERS  ===========================
     * ============================================================ */

    /** Ambil daftar item (leaf) pada penawaran terpilih. */
    private function itemsForPenawaran(int $proyekId, int $penawaranId)
    {
        $detTable   = (new RabDetail)->getTable();
        $codeCol    = $this->pick($detTable, ['kode','wbs_kode','kode_wbs','no','nomor']) ?? 'id';
        $nameCol    = $this->pick($detTable, ['uraian','deskripsi','nama','judul']);

        $q = RabDetail::where('proyek_id', $proyekId)
            ->select(['id','rab_header_id'])
            ->addSelect(DB::raw($codeCol === 'id' ? 'CAST(id AS CHAR) as kode' : "$codeCol as kode"))
            ->addSelect(DB::raw($nameCol ? "$nameCol as uraian" : "'' as uraian"));

        if ($penawaranId && class_exists(RabPenawaranItem::class)) {
            $piTable  = (new RabPenawaranItem)->getTable();
            if (Schema::hasColumn($piTable,'rab_detail_id') && Schema::hasColumn($piTable,'penawaran_id')) {
                $q->whereIn('id', function($qq) use ($piTable,$penawaranId){
                    $qq->from($piTable)->where('penawaran_id',$penawaranId)->select('rab_detail_id');
                });
            }
        }

        return $q->orderBy($codeCol==='id' ? 'id' : $codeCol)->get();
    }

    /** Pilih kolom pertama yang tersedia pada tabel. */
    private function pick(string $table, array $candidates): ?string
    {
        foreach ($candidates as $c) if (Schema::hasColumn($table, $c)) return $c;
        return null;
    }

    /** Kolom nilai progress detail yang menyimpan Δ% item. */
    private function pdValueColumn(): string
    {
        $pdTable = (new RabProgressDetail)->getTable();
        foreach (['bobot_minggu_ini','bobot','progress','qty_minggu_ini'] as $c) {
            if (Schema::hasColumn($pdTable, $c)) return $c;
        }
        return 'bobot_minggu_ini';
    }

    /** Map Wi (bobot total item, % proyek) dari schedule_detail. */
    private function itemWeightMapFromSchedule(int $proyekId, int $penawaranId): array
    {
        $sdTable = (new \App\Models\RabScheduleDetail)->getTable();
        $piTable = class_exists(\App\Models\RabPenawaranItem::class)
            ? (new \App\Models\RabPenawaranItem)->getTable()
            : null;
    
        $bobotCol = Schema::hasColumn($sdTable,'bobot_mingguan') ? 'bobot_mingguan'
                  : (Schema::hasColumn($sdTable,'bobot') ? 'bobot' : null);
        if (!$bobotCol) return [];
    
        // A) langsung rab_detail_id
        if (Schema::hasColumn($sdTable,'rab_detail_id')) {
            return DB::table("$sdTable as sd")
                ->where('sd.proyek_id', $proyekId)
                ->where('sd.penawaran_id', $penawaranId)
                ->groupBy('sd.rab_detail_id')
                ->selectRaw("sd.rab_detail_id as did, SUM(sd.$bobotCol) as s")
                ->pluck('s','did')
                ->toArray();
        }
    
        // B) via rab_penawaran_item_id
        if ($piTable && Schema::hasColumn($sdTable,'rab_penawaran_item_id') && Schema::hasColumn($piTable,'rab_detail_id')) {
            return DB::table("$sdTable as sd")
                ->join("$piTable as pi", 'pi.id', '=', 'sd.rab_penawaran_item_id')
                ->where('sd.proyek_id', $proyekId)
                ->where('sd.penawaran_id', $penawaranId)
                ->groupBy('pi.rab_detail_id')
                ->selectRaw("pi.rab_detail_id as did, SUM(sd.$bobotCol) as s")
                ->pluck('s','did')
                ->toArray();
        }
    
        return [];
    }
    

    /** Target bobot kumulatif s/d minggu ke-N (Σ bobot_mingguan minggu ≤ N). */
    private function plannedCumBobotToWeek(int $proyekId, int $penawaranId, int $week): array
    {
        $sdTable = (new RabScheduleDetail)->getTable();
        $piTable = class_exists(RabPenawaranItem::class) ? (new RabPenawaranItem)->getTable() : null;

        $bobotCol = Schema::hasColumn($sdTable,'bobot_mingguan') ? 'bobot_mingguan'
                  : (Schema::hasColumn($sdTable,'bobot') ? 'bobot' : null);
        if (!$bobotCol) return [];

        if (Schema::hasColumn($sdTable,'rab_detail_id')) {
            return DB::table($sdTable)
                ->where('proyek_id', $proyekId)
                ->where('penawaran_id', $penawaranId)
                ->where('minggu_ke', '<=', $week)
                ->groupBy('rab_detail_id')
                ->pluck(DB::raw("SUM($bobotCol)"), 'rab_detail_id')
                ->toArray();
        }

        if ($piTable && Schema::hasColumn($sdTable,'rab_penawaran_item_id') && Schema::hasColumn($piTable,'rab_detail_id')) {
            return DB::table("$sdTable as sd")
                ->join("$piTable as pi", 'pi.id', '=', 'sd.rab_penawaran_item_id')
                ->where('sd.proyek_id', $proyekId)
                ->where('sd.penawaran_id', $penawaranId)
                ->where('sd.minggu_ke', '<=', $week)
                ->groupBy('pi.rab_detail_id')
                ->pluck(DB::raw("SUM(sd.$bobotCol)"), 'pi.rab_detail_id')
                ->toArray();
        }

        return [];
    }

    /** % kumulatif item s/d (< $uptoWeek) dari progress FINAL. */
    private function realizedToDatePctMap(int $proyekId, int $penawaranId, int $uptoWeek): array
    {
        $pTable  = (new RabProgress)->getTable();
        $pdTable = (new RabProgressDetail)->getTable();
        $valCol  = $this->pdValueColumn();

        return DB::table("$pdTable as pd")
            ->join("$pTable as p", 'p.id', '=', 'pd.rab_progress_id')
            ->where('p.proyek_id', $proyekId)
            ->where('p.status', 'final')
            ->when($penawaranId, fn($q)=>$q->where('p.penawaran_id', $penawaranId))
            ->where('p.minggu_ke', '<', $uptoWeek)
            ->groupBy('pd.rab_detail_id')
            ->pluck(DB::raw("SUM(pd.$valCol)"), 'pd.rab_detail_id')
            ->toArray();
    }

    /**
     * Ambil Δ% minggu ini dari progress_detail + bentuk % kumulatif minggu ini:
     * nowPct = prevPct + deltaPct
     */
    private function deltaAndNowPctForProgress(int $progressId, array $prevPct): array
    {
        $pdTable = (new RabProgressDetail)->getTable();
        $valCol  = $this->pdValueColumn();

        $delta = DB::table($pdTable)
            ->where('rab_progress_id', $progressId)
            ->pluck($valCol, 'rab_detail_id')->toArray();

        $now = [];
        foreach ($delta as $did => $d) {
            $now[(int)$did] = (float)($prevPct[$did] ?? 0) + (float)$d;
        }
        return [$delta, $now];
    }
}


<?php

namespace App\Http\Controllers;

use App\Models\Proyek;
use App\Models\RabHeader;
use App\Models\RabSchedule;
use App\Models\RabScheduleDetail;
use App\Models\RabPenawaranHeader;
use App\Models\RabPenawaranItem;
use App\Models\RabScheduleMeta;   // <-- tambahkan
use Illuminate\Support\Carbon;    // <-- tambahkan
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ScheduleExport;
use App\Imports\ScheduleImport;

class RabScheduleController extends Controller
{
    public function pdf(Proyek $proyek, RabPenawaranHeader $penawaran)
    {
        $start = $proyek->tanggal_mulai ? Carbon::parse($proyek->tanggal_mulai)->startOfDay() : now()->startOfDay();
        $end = $proyek->tanggal_selesai ? Carbon::parse($proyek->tanggal_selesai)->endOfDay() : now()->addWeeks(4)->endOfDay();
        $days = $start->diffInDays($end) + 1;
        $weeks = (int) ceil($days / 7);

        $meta = RabScheduleMeta::updateOrCreate(
            ['proyek_id' => $proyek->id, 'penawaran_id' => $penawaran->id],
            [
                'start_date'  => $start->toDateString(),
                'end_date'    => $end->toDateString(),
                'total_weeks' => max(1, $weeks),
            ]
        );

        $sdTable = (new RabScheduleDetail)->getTable();
        $bobotCol = \Schema::hasColumn($sdTable, 'bobot_mingguan')
            ? 'bobot_mingguan'
            : (\Schema::hasColumn($sdTable, 'bobot') ? 'bobot' : null);

        $weeklyByHeader = [];
        $weeklyTotals = array_fill(1, $meta->total_weeks, 0.0);

        if ($bobotCol) {
            $rows = RabScheduleDetail::where('proyek_id', $proyek->id)
                ->where('penawaran_id', $penawaran->id)
                ->select('rab_header_id', 'minggu_ke', DB::raw("SUM($bobotCol) as bobot"))
                ->groupBy('rab_header_id', 'minggu_ke')
                ->get();

            foreach ($rows as $r) {
                $hid = (int) $r->rab_header_id;
                $wk = (int) $r->minggu_ke;
                $val = (float) $r->bobot;
                if ($wk < 1 || $wk > $meta->total_weeks) continue;

                $weeklyByHeader[$hid][$wk] = ($weeklyByHeader[$hid][$wk] ?? 0) + $val;
                $weeklyTotals[$wk] = ($weeklyTotals[$wk] ?? 0) + $val;
            }
        }

        $headers = RabHeader::where('proyek_id', $proyek->id)
            ->orderBy('kode_sort')
            ->get();

        $byId = $headers->keyBy('id');
        $depths = [];

        $calcDepth = function ($id) use (&$calcDepth, &$depths, $byId) {
            if (isset($depths[$id])) return $depths[$id];
            $h = $byId[$id] ?? null;
            if (!$h || !$h->parent_id) return $depths[$id] = 0;
            return $depths[$id] = 1 + $calcDepth($h->parent_id);
        };

        foreach ($headers as $h) {
            $calcDepth($h->id);
        }

        $headersByDepth = $headers->sortByDesc(fn($h) => $depths[$h->id] ?? 0);

        foreach ($headersByDepth as $h) {
            $pid = $h->parent_id;
            if (!$pid) continue;
            if (!isset($weeklyByHeader[$h->id])) continue;
            foreach ($weeklyByHeader[$h->id] as $wk => $val) {
                $weeklyByHeader[$pid][$wk] = ($weeklyByHeader[$pid][$wk] ?? 0) + $val;
            }
        }

        $headerTotals = [];
        foreach ($weeklyByHeader as $hid => $weeksArr) {
            $headerTotals[$hid] = array_sum($weeksArr);
        }

        $rows = [];
        foreach ($headers as $h) {
            $total = (float) ($headerTotals[$h->id] ?? 0);
            if ($total <= 0) continue;
            $rows[] = [
                'kode' => $h->kode,
                'deskripsi' => $h->deskripsi,
                'depth' => $depths[$h->id] ?? 0,
                'weight' => $total,
                'weeks' => $weeklyByHeader[$h->id] ?? [],
            ];
        }

        $weeklyCumulative = [];
        $acc = 0.0;
        for ($w = 1; $w <= $meta->total_weeks; $w++) {
            $acc += (float) ($weeklyTotals[$w] ?? 0);
            $weeklyCumulative[$w] = $acc;
        }

        $pdf = Pdf::loadView('rab_schedule.pdf_schedule', [
            'proyek' => $proyek,
            'penawaran' => $penawaran,
            'meta' => $meta,
            'rows' => $rows,
            'totalWeeks' => $meta->total_weeks,
            'weeklyTotals' => $weeklyTotals,
            'weeklyCumulative' => $weeklyCumulative,
        ])->setPaper('A4', 'landscape');

        $filename = 'Schedule_' . str_replace(' ', '_', $penawaran->nama_penawaran) . '.pdf';
        return $pdf->download($filename);
    }

    // LIST penawaran final untuk proyek (tab RAB Schedule)
    public function index(Proyek $proyek)
    {
        $penawarans = RabPenawaranHeader::where('proyek_id', $proyek->id)
            ->orderByDesc('tanggal_penawaran')
            ->get();
    
        // status snapshot bobot
        $hasSnapshots = \DB::table('rab_penawaran_weight')
            ->whereIn('penawaran_id', $penawarans->pluck('id'))
            ->select('penawaran_id', \DB::raw('COUNT(*) as cnt'))
            ->groupBy('penawaran_id')
            ->pluck('cnt', 'penawaran_id');
    
        // status setup jadwal (sudah isi minggu/durasi?)
        $hasSetup = \DB::table('rab_schedule')
            ->where('proyek_id', $proyek->id)
            ->whereIn('penawaran_id', $penawarans->pluck('id'))
            ->select('penawaran_id', \DB::raw('COUNT(*) as cnt'))
            ->groupBy('penawaran_id')
            ->pluck('cnt', 'penawaran_id');
    
        return view('rab_schedule.index', compact('proyek', 'penawarans', 'hasSnapshots', 'hasSetup'));
    }
    
   public function edit(Proyek $proyek, RabPenawaranHeader $penawaran)
{
    // Cek sudah ada snapshot bobot atau belum
    $hasSnapshot = \DB::table('rab_penawaran_weight')
        ->where('penawaran_id', $penawaran->id)
        ->exists();

    // ====== SORT WBS: pakai kode_sort dari rab_detail jika ada; fallback: rakit dari kode ====
    // Pakai agregat (MAX) agar lolos ONLY_FULL_GROUP_BY
   // … di atas tetap sama

$codeExpr = "COALESCE(d.kode, i.kode)";
$itemSortExpr = "COALESCE(
    NULLIF(MAX(d.kode_sort), ''),
    CONCAT(
        LPAD(SUBSTRING_INDEX($codeExpr, '.', 1), 4, '0'), '.',
        LPAD(SUBSTRING_INDEX(SUBSTRING_INDEX($codeExpr, '.', 2), '.', -1), 4, '0'), '.',
        LPAD(SUBSTRING_INDEX(SUBSTRING_INDEX($codeExpr, '.', 3), '.', -1), 4, '0'), '.',
        LPAD(SUBSTRING_INDEX(SUBSTRING_INDEX($codeExpr, '.', 4), '.', -1), 4, '0')
    )
)";

$items = DB::table('rab_penawaran_weight as w')
  ->join('rab_penawaran_items as i','i.id','=','w.rab_penawaran_item_id')
  ->leftJoin('rab_detail as d','d.id','=','i.rab_detail_id')
  ->leftJoin('rab_header as h','h.id','=','w.rab_header_id')   // leaf
  ->leftJoin('rab_header as p','p.id','=','h.parent_id')       // parent
  ->leftJoin('rab_header as g','g.id','=','p.parent_id')       // root
  ->where('w.penawaran_id', $penawaran->id)
  ->where('w.level', 'item')
  ->groupBy(
      'i.id','d.kode','i.kode','d.deskripsi','i.deskripsi',
      'h.id','h.kode','h.deskripsi','h.kode_sort',
      'p.id','p.kode','p.deskripsi','p.kode_sort',
      'g.id','g.kode','g.deskripsi','g.kode_sort',
      'i.area'
  )
  ->selectRaw("
      i.id as item_id,
      $codeExpr as kode,
      COALESCE(d.deskripsi, i.deskripsi)   as deskripsi,

      h.id   as leaf_id,   h.kode as leaf_kode,   h.deskripsi as leaf_desc,   h.kode_sort as leaf_sort,
      p.id   as parent_id, p.kode as parent_kode, p.deskripsi as parent_desc, p.kode_sort as parent_sort,
      g.id   as root_id,   g.kode as root_kode,   g.deskripsi as root_desc,   g.kode_sort as root_sort,

      $itemSortExpr as item_sort,
      i.area as area,
      ROUND(SUM(w.weight_pct_project),4) as pct
  ")
  ->orderBy('g.kode_sort')
  ->orderBy('p.kode_sort')
  ->orderBy('h.kode_sort')
  ->orderBy('item_sort')   // ⬅️ 2.1.9 lalu 2.1.10 (tidak loncat)
  ->get();

  
    // Setup minggu/durasi yang sudah tersimpan per item
    $existing = \App\Models\RabSchedule::where('proyek_id', $proyek->id)
        ->where('penawaran_id', $penawaran->id)
        ->get()
        ->keyBy('rab_penawaran_item_id');

    // Ambil tanggal dari proyek
    $start = $proyek->tanggal_mulai ? Carbon::parse($proyek->tanggal_mulai)->startOfDay() : now()->startOfDay();
    $end = $proyek->tanggal_selesai ? Carbon::parse($proyek->tanggal_selesai)->endOfDay() : now()->addWeeks(4)->endOfDay();
    $days = $start->diffInDays($end) + 1;
    $weeks = (int) ceil($days / 7);

    // Meta tanggal (disimpan untuk konsistensi, tapi source: proyek)
    $meta = \App\Models\RabScheduleMeta::updateOrCreate(
        ['proyek_id' => $proyek->id, 'penawaran_id' => $penawaran->id],
        [
            'start_date'  => $start->toDateString(),
            'end_date'    => $end->toDateString(),
            'total_weeks' => max(1, $weeks),
        ]
    );

    return view('rab_schedule.edit', [
        'proyek'        => $proyek,
        'penawaran'     => $penawaran,
        'hasSnapshot'   => $hasSnapshot,
        'items'         => $items,
        'existingSched' => $existing,
        'meta'          => $meta,
    ]);
}

    // Snapshot bobot dari penawaran (dipanggil dari tombol di penawaran.show)
    public function snapshot(Proyek $proyek, RabPenawaranHeader $penawaran)
    {
        $this->snapshotWeightsFromOffer($penawaran);
        return back()->with('success', 'Snapshot bobot berhasil dibuat dari penawaran.');
    }

    public function saveSetup(Request $request, Proyek $proyek, RabPenawaranHeader $penawaran)
    {
        $data = $request->validate([
            'rows'       => ['required','array','min:1'],
    
            'rows.*.rab_penawaran_item_id' => ['required','exists:rab_penawaran_items,id'],
            'rows.*.minggu_ke'             => ['required','integer','min:1'],
            'rows.*.durasi'                => ['required','integer','min:1'],
        ]);
    
        DB::transaction(function () use ($data, $proyek, $penawaran) {
    
            // 1) Ambil tanggal dari proyek
            abort_if(!$proyek->tanggal_mulai || !$proyek->tanggal_selesai, 400, 'Tanggal mulai & selesai proyek harus diisi terlebih dahulu');
            
            $start = Carbon::parse($proyek->tanggal_mulai)->startOfDay();
            $end   = Carbon::parse($proyek->tanggal_selesai)->endOfDay();
            $days  = $start->diffInDays($end) + 1; // inklusif
            $weeks = (int) ceil($days / 7);
    
            RabScheduleMeta::updateOrCreate(
                ['proyek_id' => $proyek->id, 'penawaran_id' => $penawaran->id],
                [
                    'start_date'  => $start->toDateString(),
                    'end_date'    => $end->toDateString(),
                    'total_weeks' => max(1, $weeks),
                ]
            );
    
            // 2) Buat mapping item -> leaf rab_header_id (diambil dari section penawaran)
            //    i.rab_penawaran_section_id -> s.rab_header_id
            $leafByItem = DB::table('rab_penawaran_items as i')
                ->join('rab_penawaran_sections as s', 's.id', '=', 'i.rab_penawaran_section_id')
                ->where('s.rab_penawaran_header_id', $penawaran->id) // jaga hanya milik penawaran ini
                ->pluck('s.rab_header_id', 'i.id'); // [item_id => leaf_header_id]
    
            // 3) Simpan setup per item + hitung start/end per item
            foreach ($data['rows'] as $row) {
                $itemId      = (int) $row['rab_penawaran_item_id'];
                $mulaiMgg    = (int) $row['minggu_ke'];     // 1-based
                $durasiMgg   = (int) $row['durasi'];
    
                // DAPATKAN leaf header id untuk item ini
                $leafHeaderId = (int) ($leafByItem[$itemId] ?? 0);
                if ($leafHeaderId <= 0) {
                    // kalau mapping tidak ketemu, amankan: SKIP atau bisa lempar exception sesuai kebijakan
                    // di sini saya pilih SKIP agar tidak menabrak NOT NULL
                    continue;
                }
    
                // Tanggal mulai/selesai item dari meta
                $itemStart = $start->copy()->addWeeks($mulaiMgg - 1)->startOfDay();
                $itemEnd   = $itemStart->copy()->addWeeks($durasiMgg)->subDay()->endOfDay();
                if ($itemEnd->gt($end)) {
                    $itemEnd = $end->copy(); // clamp bila lewat meta end
                }
    
                RabSchedule::updateOrCreate(
                    [
                        'proyek_id'             => $proyek->id,
                        'penawaran_id'          => $penawaran->id,
                        'rab_penawaran_item_id' => $itemId,
                    ],
                    [
                        'rab_header_id' => $leafHeaderId,                 // ⬅️ tidak null lagi
                        'minggu_ke'     => $mulaiMgg,
                        'durasi'        => $durasiMgg,
                        'start_date'    => $itemStart->toDateString(),
                        'end_date'      => $itemEnd->toDateString(),
                    ]
                );
            }
        });
    
        return back()->with('success', 'Setup jadwal & tanggal proyek tersimpan.');
    }

    // Generate detail mingguan (Kurva-S)
    public function generate(Proyek $proyek, RabPenawaranHeader $penawaran)
    {
        // Setup per-item yang sudah kamu simpan via saveSetup()
        $sched = \App\Models\RabSchedule::where('proyek_id', $proyek->id)
            ->where('penawaran_id', $penawaran->id)
            ->get();

        if ($sched->isEmpty()) {
            return back()->with('error', 'Belum ada setup jadwal untuk penawaran ini.');
        }

        // % proyek per ITEM dari snapshot
        $pctPerItem = \DB::table('rab_penawaran_weight')
            ->where('penawaran_id', $penawaran->id)
            ->where('level', 'item')
            ->groupBy('rab_penawaran_item_id')
            ->pluck(\DB::raw('ROUND(SUM(weight_pct_project),4)'), 'rab_penawaran_item_id');

        // Mapping ITEM → LEAF HEADER (WBS 1.1.1 dst) supaya rab_header_id tidak null
        $leafByItem = \DB::table('rab_penawaran_weight')
            ->where('penawaran_id', $penawaran->id)
            ->where('level', 'item')
            ->pluck('rab_header_id', 'rab_penawaran_item_id'); // [item_id => leaf_header_id]

        \DB::transaction(function() use ($proyek, $penawaran, $sched, $pctPerItem, $leafByItem) {
            // Bersihkan detail sebelumnya untuk penawaran ini
            \App\Models\RabScheduleDetail::where('proyek_id', $proyek->id)
                ->where('penawaran_id', $penawaran->id)
                ->delete();

            foreach ($sched as $s) {
                $itemId = (int) $s->rab_penawaran_item_id;
                $leafId = (int) ($leafByItem[$itemId] ?? 0); // Wajib terisi agar FK & NOT NULL aman
                $pct    = (float)($pctPerItem[$itemId] ?? 0);
                $dur    = max((int)$s->durasi, 0);
                $start  = max((int)$s->minggu_ke, 1);

                // Skip kalau datanya tidak valid
                if ($leafId <= 0 || $pct <= 0 || $dur <= 0) {
                    continue;
                }

                // Bagi rata bobot per minggu, pastikan minggu terakhir mengakomodir pembulatan
                $per = round($pct / $dur, 4);
                $acc = 0.0;

                for ($i = 0; $i < $dur; $i++) {
                    $last = ($i === $dur - 1);
                    $val  = $last ? round($pct - $acc, 4) : $per;
                    $acc += $val;

                    \App\Models\RabScheduleDetail::create([
                        'proyek_id'             => $proyek->id,
                        'penawaran_id'          => $penawaran->id,
                        'rab_header_id'         => $leafId,     // ← BUKAN NULL lagi
                        'rab_penawaran_item_id' => $itemId,
                        'minggu_ke'             => $start + $i,
                        'bobot_mingguan'        => $val,
                    ]);
                }
            }
        });

        return back()->with('success', 'Schedule detail (per item) berhasil dibuat.');
    }

    /* ===== Helpers ===== */

    protected function snapshotWeightsFromOffer(RabPenawaranHeader $penawaran): void
    {
        $penawaran->loadMissing('sections.rabHeader','sections.items.rabDetail.ahsp.details');

        // total bruto (tanpa diskon)
        $total = 0.0;
        foreach ($penawaran->sections as $sec) {
            foreach ($sec->items as $it) {
                $v = (float)$it->volume;
                $baseUnit = $this->getBaseUnitPriceFromItem($it);
                $total += $baseUnit * $v;
            }
        }

        DB::table('rab_penawaran_weight')->where('penawaran_id', $penawaran->id)->delete();
        if ($total <= 0) return;

        DB::transaction(function() use ($penawaran, $total) {
            // akumulasi per LEAF header (yaitu header yang dipakai di section penawaran)
            $grossPerLeaf = [];

            foreach ($penawaran->sections as $sec) {
                $leafHeaderId = $sec->rab_header_id; // <-- langsung header pada section (WBS 1.1.1, dst)

                foreach ($sec->items as $it) {
                    $v = (float)$it->volume;
                    $baseUnit = $this->getBaseUnitPriceFromItem($it);
                    $gross = $baseUnit * $v;

                    DB::table('rab_penawaran_weight')->insert([
                        'proyek_id'                => $penawaran->proyek_id,
                        'penawaran_id'             => $penawaran->id,
                        'rab_header_id'            => $leafHeaderId,     // <-- simpan ke leaf
                        'rab_penawaran_section_id' => $sec->id,
                        'rab_penawaran_item_id'    => $it->id,
                        'level'                    => 'item',
                        'gross_value'              => $gross,
                        'weight_pct_project'       => $gross > 0 ? round(($gross / $total) * 100, 4) : 0,
                        'weight_pct_in_header'     => null,
                        'computed_at'              => now(),
                        'created_at'               => now(),
                        'updated_at'               => now(),
                    ]);

                    $grossPerLeaf[$leafHeaderId] = ($grossPerLeaf[$leafHeaderId] ?? 0) + $gross;
                }
            }

            // buat baris 'header' untuk masing-masing leaf WBS
            foreach ($grossPerLeaf as $leafHeaderId => $g) {
                DB::table('rab_penawaran_weight')->insert([
                    'proyek_id'                => $penawaran->proyek_id,
                    'penawaran_id'             => $penawaran->id,
                    'rab_header_id'            => $leafHeaderId,       // <-- tetap leaf
                    'rab_penawaran_section_id' => null,
                    'rab_penawaran_item_id'    => null,
                    'level'                    => 'header',
                    'gross_value'              => $g,
                    'weight_pct_project'       => round(($g / $total) * 100, 4),
                    'weight_pct_in_header'     => null,
                    'computed_at'              => now(),
                    'created_at'               => now(),
                    'updated_at'               => now(),
                ]);

                if ($g > 0) {
                    DB::table('rab_penawaran_weight')
                    ->where('penawaran_id', $penawaran->id)
                    ->where('level', 'item')
                    ->where('rab_header_id', $leafHeaderId)
                    ->update([
                        'weight_pct_in_header' => DB::raw('ROUND((gross_value / '.$g.') * 100, 4)')
                    ]);
                }
            }
        });
    }

    private function getBaseUnitPriceFromItem($item): float
    {
        $rabDetail = $item->rabDetail ?? null;

        if ($rabDetail) {
            if ($rabDetail->relationLoaded('ahsp') ? $rabDetail->ahsp : $rabDetail->ahsp()->with('details')->first()) {
                $ahsp = $rabDetail->ahsp;
                if ($ahsp && $ahsp->relationLoaded('details') && $ahsp->details->isNotEmpty()) {
                    $material = $ahsp->details
                        ->where('tipe', 'material')
                        ->sum(fn($d) => (float)$d->koefisien * (float)$d->harga_satuan);

                    $upah = $ahsp->details
                        ->where('tipe', 'upah')
                        ->sum(fn($d) => (float)$d->koefisien * (float)$d->harga_satuan);

                    return (float)$material + (float)$upah;
                }
            }

            $material = (float)($rabDetail->harga_material ?? 0);
            $upah     = (float)($rabDetail->harga_upah ?? 0);

            if ($material == 0.0 && $upah == 0.0) {
                return (float)($rabDetail->harga_satuan ?? 0);
            }

            return $material + $upah;
        }

        return (float)($item->harga_satuan_dasar ?? 0);
    }


    protected function buildScheduleFromSnapshot(Proyek $proyek, RabPenawaranHeader $penawaran): void
    {
        // % proyek per header
        $pctHeader = DB::table('rab_penawaran_weight')
            ->select('rab_header_id', DB::raw('ROUND(SUM(weight_pct_project),4) as pct'))
            ->where('penawaran_id', $penawaran->id)
            ->where('level', 'header')
            ->groupBy('rab_header_id')
            ->pluck('pct','rab_header_id');

        // setup manual
        $sched = RabSchedule::where('proyek_id', $proyek->id)
            ->where('penawaran_id', $penawaran->id)
            ->get();

        DB::transaction(function() use ($proyek, $penawaran, $pctHeader, $sched) {
            RabScheduleDetail::where('proyek_id',$proyek->id)
                ->where('penawaran_id',$penawaran->id)->delete();

            foreach ($sched as $s) {
                $pct = (float)($pctHeader[$s->rab_header_id] ?? 0);
                $dur = max((int)$s->durasi, 0);
                $start = max((int)$s->minggu_ke, 1);
                if ($pct <= 0 || $dur <= 0) continue;

                $per = round($pct / $dur, 4);
                $acc = 0.0;
                for ($i=0; $i<$dur; $i++) {
                    $last = ($i === $dur-1);
                    $val = $last ? round($pct - $acc, 4) : $per;
                    $acc += $val;

                    RabScheduleDetail::create([
                        'proyek_id'      => $proyek->id,
                        'penawaran_id'   => $penawaran->id,
                        'rab_header_id'  => $s->rab_header_id,
                        'minggu_ke'      => $start + $i,
                        'bobot_mingguan' => $val,
                    ]);
                }
            }
        });
    }

    /**
     * Export schedule to Excel
     */
    public function export(Proyek $proyek, RabPenawaranHeader $penawaran)
    {
        $fileName = 'Schedule_' . str_replace(' ', '_', $penawaran->nama_penawaran) . '_' . date('Ymd_His') . '.xlsx';
        return Excel::download(new ScheduleExport($proyek->id, $penawaran->id), $fileName);
    }

    /**
     * Import schedule from Excel
     */
    public function import(Request $request, Proyek $proyek, RabPenawaranHeader $penawaran)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls',
        ]);

        try {
            Excel::import(new ScheduleImport($proyek->id, $penawaran->id), $request->file('file'));
            
            return redirect()->route('rabSchedule.edit', [$proyek->id, $penawaran->id])
                ->with('success', 'Schedule berhasil diimport!');
        } catch (\Exception $e) {
            return redirect()->route('rabSchedule.edit', [$proyek->id, $penawaran->id])
                ->with('error', 'Gagal import schedule: ' . $e->getMessage());
        }
    }

    /**
     * Download template Excel untuk import schedule
     */
    public function downloadTemplate(Proyek $proyek, RabPenawaranHeader $penawaran)
    {
        $path = storage_path('app/templates/schedule_import_template.xlsx');

        // Generate template jika belum ada
        if (!file_exists($path)) {
            $this->generateTemplateXlsx($path, $proyek, $penawaran);
        }

        return response()->download($path, 'schedule_import_template.xlsx');
    }

    /**
     * Generate template XLSX untuk import schedule
     */
    private function generateTemplateXlsx(string $path, Proyek $proyek, RabPenawaranHeader $penawaran)
    {
        @mkdir(dirname($path), 0775, true);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        
        // Sheet 1: Schedule_Meta
        $metaSheet = $spreadsheet->getActiveSheet();
        $metaSheet->setTitle('Schedule_Meta');
        $metaSheet->fromArray(['proyek_id', 'penawaran_id', 'start_date', 'end_date', 'total_weeks'], null, 'A1');
        $metaSheet->fromArray([
            $proyek->id,
            $penawaran->id,
            $proyek->tanggal_mulai ?? date('Y-m-d'),
            $proyek->tanggal_selesai ?? date('Y-m-d', strtotime('+3 months')),
            13 // default 13 minggu (3 bulan)
        ], null, 'A2');
        
        foreach (['A'=>12, 'B'=>14, 'C'=>14, 'D'=>14, 'E'=>14] as $col => $w) {
            $metaSheet->getColumnDimension($col)->setWidth($w);
        }
        $metaSheet->freezePane('A2');
        $metaSheet->getStyle('A1:E1')->getFont()->setBold(true);

        // Sheet 2: Schedule_Setup (DURASI - PALING PENTING!)
        $setupSheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, 'Schedule_Setup');
        $spreadsheet->addSheet($setupSheet, 1);
        $setupSheet->fromArray([
            'penawaran_item_id', 'kode_item', 'deskripsi_item', 'minggu_ke', 'durasi'
        ], null, 'A1');
        
        // Get items with existing schedule data
        $scheduledItems = \App\Models\RabSchedule::where('proyek_id', $proyek->id)
            ->where('penawaran_id', $penawaran->id)
            ->with(['item.rabDetail'])
            ->get();
        
        if ($scheduledItems->isNotEmpty()) {
            $row = 2;
            foreach ($scheduledItems as $schedule) {
                $item = $schedule->item;
                $setupSheet->fromArray([
                    $schedule->rab_penawaran_item_id,
                    $item && $item->rabDetail ? $item->rabDetail->kode : '',
                    $item && $item->rabDetail ? $item->rabDetail->deskripsi : '',
                    $schedule->minggu_ke ?? 1,
                    $schedule->durasi ?? 1
                ], null, 'A' . $row);
                $row++;
            }
        } else {
            // Jika belum ada schedule, ambil sample items
            $items = RabPenawaranItem::where('penawaran_id', $penawaran->id)
                ->with('rabDetail')
                ->limit(5)
                ->get();
            
            $row = 2;
            foreach ($items as $item) {
                $setupSheet->fromArray([
                    $item->id,
                    $item->rabDetail->kode ?? '',
                    $item->rabDetail->deskripsi ?? '',
                    1, // minggu_ke default
                    1  // durasi default (user harus isi)
                ], null, 'A' . $row);
                $row++;
            }
        }
        
        foreach (['A'=>18, 'B'=>14, 'C'=>44, 'D'=>12, 'E'=>12] as $col => $w) {
            $setupSheet->getColumnDimension($col)->setWidth($w);
        }
        $setupSheet->freezePane('A2');
        $setupSheet->getStyle('A1:E1')->getFont()->setBold(true);
        $setupSheet->getStyle('A1:E1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFFFCC00'); // Highlight kuning untuk sheet penting

        // Sheet 3: Schedule_Detail (hasil generate dari durasi)
        $detailSheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, 'Schedule_Detail');
        $spreadsheet->addSheet($detailSheet, 2);
        $detailSheet->fromArray([
            'penawaran_item_id', 'kode_item', 'deskripsi_item', 'minggu_ke', 'bobot_mingguan'
        ], null, 'A1');
        
        // Get sample detail data (hasil generate)
        $detailData = \App\Models\RabScheduleDetail::where('proyek_id', $proyek->id)
            ->where('penawaran_id', $penawaran->id)
            ->with(['item.rabDetail'])
            ->limit(10)
            ->get();
        
        if ($detailData->isNotEmpty()) {
            $row = 2;
            foreach ($detailData as $detail) {
                $item = $detail->item;
                $detailSheet->fromArray([
                    $detail->rab_penawaran_item_id,
                    $item && $item->rabDetail ? $item->rabDetail->kode : '',
                    $item && $item->rabDetail ? $item->rabDetail->deskripsi : '',
                    $detail->minggu_ke,
                    $detail->bobot_mingguan
                ], null, 'A' . $row);
                $row++;
            }
        } else {
            // Jika belum ada detail, kosongkan saja (akan di-generate dari Setup)
            $detailSheet->fromArray([
                '', '', '(Data akan terisi setelah Generate Schedule dari Setup)', '', ''
            ], null, 'A2');
        }
        
        foreach (['A'=>18, 'B'=>14, 'C'=>44, 'D'=>12, 'E'=>16] as $col => $w) {
            $detailSheet->getColumnDimension($col)->setWidth($w);
        }
        $detailSheet->freezePane('A2');
        $detailSheet->getStyle('A1:E1')->getFont()->setBold(true);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($path);
    }
}

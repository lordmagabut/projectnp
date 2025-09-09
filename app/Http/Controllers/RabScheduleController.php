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

class RabScheduleController extends Controller
{
    // LIST penawaran final untuk proyek (tab RAB Schedule)
    public function index(Proyek $proyek)
    {
        $penawarans = RabPenawaranHeader::where('proyek_id', $proyek->id)
            ->where('status', 'final')
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
    
    // Halaman EDIT schedule: tampilkan header top-level + bobot + input minggu/durasi
    public function edit(Proyek $proyek, RabPenawaranHeader $penawaran)
    {
        $hasSnapshot = \DB::table('rab_penawaran_weight')
            ->where('penawaran_id', $penawaran->id)
            ->exists();
    
        // Ambil ITEM (level=item) beserta header LEAF (h), PARENT (p = 1.1), ROOT (g = 1)
        $items = \DB::table('rab_penawaran_weight as w')
            ->join('rab_penawaran_items as i','i.id','=','w.rab_penawaran_item_id')
            ->leftJoin('rab_detail as d','d.id','=','i.rab_detail_id')
            ->leftJoin('rab_header as h','h.id','=','w.rab_header_id')        // leaf
            ->leftJoin('rab_header as p','p.id','=','h.parent_id')            // parent (1.1)
            ->leftJoin('rab_header as g','g.id','=','p.parent_id')            // root (1)
            ->where('w.penawaran_id', $penawaran->id)
            ->where('w.level','item')
            ->groupBy(
                'i.id','d.kode','i.kode','d.deskripsi','i.deskripsi',
                'h.id','h.kode','h.deskripsi','h.kode_sort',
                'p.id','p.kode','p.deskripsi','p.kode_sort',
                'g.id','g.kode','g.deskripsi','g.kode_sort'
            )
            ->orderBy('g.kode_sort')
            ->orderBy('p.kode_sort')
            ->orderBy('h.kode_sort')
            ->orderByRaw('COALESCE(d.kode, i.kode)')
            ->selectRaw('
                i.id as item_id,
                COALESCE(d.kode, i.kode)       as kode,
                COALESCE(d.deskripsi, i.deskripsi) as deskripsi,
    
                h.id   as leaf_id,
                h.kode as leaf_kode,
                h.deskripsi as leaf_desc,
                h.kode_sort as leaf_sort,
    
                p.id   as parent_id,
                p.kode as parent_kode,
                p.deskripsi as parent_desc,
                p.kode_sort as parent_sort,
    
                g.id   as root_id,
                g.kode as root_kode,
                g.deskripsi as root_desc,
                g.kode_sort as root_sort,
    
                ROUND(SUM(w.weight_pct_project),4) as pct
            ')
            ->get();
    
        // setup yg sudah tersimpan per item
        $existing = \App\Models\RabSchedule::where('proyek_id',$proyek->id)
            ->where('penawaran_id',$penawaran->id)
            ->get()->keyBy('rab_penawaran_item_id');
    
        // meta tanggal: 1 baris per (proyek, penawaran)
        $meta = RabScheduleMeta::firstOrCreate(
            ['proyek_id' => $proyek->id, 'penawaran_id' => $penawaran->id],
            [
                'start_date'  => now()->toDateString(),
                'end_date'    => now()->addWeeks(4)->toDateString(),
                'total_weeks' => 5,
            ]
        );

        return view('rab_schedule.edit', [
            'proyek'        => $proyek,
            'penawaran'     => $penawaran,
            'hasSnapshot'   => $hasSnapshot,
            'items'         => $items,
            'existingSched' => $existing,
            'meta'          => $meta,    // <-- kirim ke Blade
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
            'start_date' => ['required','date'],
            'end_date'   => ['required','date','after_or_equal:start_date'],
            'rows'       => ['required','array','min:1'],
    
            'rows.*.rab_penawaran_item_id' => ['required','exists:rab_penawaran_items,id'],
            'rows.*.minggu_ke'             => ['required','integer','min:1'],
            'rows.*.durasi'                => ['required','integer','min:1'],
        ]);
    
        DB::transaction(function () use ($data, $proyek, $penawaran) {
    
            // 1) Simpan meta tanggal penawaran
            $start = Carbon::parse($data['start_date'])->startOfDay();
            $end   = Carbon::parse($data['end_date'])->endOfDay();
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
        $penawaran->loadMissing('sections.rabHeader','sections.items');

        // total bruto (tanpa diskon)
        $total = 0.0;
        foreach ($penawaran->sections as $sec) {
            foreach ($sec->items as $it) {
                $v = (float)$it->volume;
                $m = (float)($it->harga_material_penawaran_item ?? 0);
                $j = (float)($it->harga_upah_penawaran_item ?? 0);
                $total += ($m + $j) * $v;
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
                    $m = (float)($it->harga_material_penawaran_item ?? 0);
                    $j = (float)($it->harga_upah_penawaran_item ?? 0);
                    $gross = ($m + $j) * $v;

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
}

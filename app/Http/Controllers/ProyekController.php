<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

use App\Models\Proyek;
use App\Models\PemberiKerja;
use App\Models\Perusahaan;
use App\Models\User;
use App\Models\RabHeader;
use App\Models\RabPenawaranHeader;
use App\Models\RabDetail;
use App\Models\RabSchedule;
use App\Models\RabScheduleDetail;
use App\Models\RabProgress;
use App\Models\RabScheduleMeta;
use App\Models\RabPenawaranItem;

use App\Services\ProyekService;
use App\Helpers\FileUploadHelper;

class ProyekController extends Controller
{
    public function index()
    {
        $proyeks = Proyek::with(['pemberiKerja'])->get();
        return view('proyek.index', compact('proyeks'));
    }

    public function create()
    {
        if (auth()->user()->buat_proyek != 1) {
            abort(403, 'Anda tidak memiliki izin untuk menambah proyek.');
        }
        $pemberiKerja = PemberiKerja::all();
        return view('proyek.create', compact('pemberiKerja'));
    }

    public function store(Request $request)
    {
        $validated = ProyekService::validateRequest($request);

        if ($request->hasFile('file_spk')) {
            $validated['file_spk'] = FileUploadHelper::upload($request->file('file_spk'), 'spk');
        }

        Proyek::create($validated);
        return redirect()->route('proyek.index')->with('success', 'Proyek berhasil ditambahkan.');
    }

    public function edit($id)
    {
        if (auth()->user()->edit_proyek != 1) {
            abort(403, 'Anda tidak memiliki izin untuk edit proyek.');
        }
        $proyek = Proyek::findOrFail($id);
        $pemberiKerja = PemberiKerja::all();
        return view('proyek.edit', compact('proyek', 'pemberiKerja'));
    }

    public function update(Request $request, $id)
    {
        $proyek    = Proyek::findOrFail($id);
        $validated = ProyekService::validateUpdateRequest($request);
        $hitung    = ProyekService::hitungKontrak($proyek, $request);

        $data = array_merge($validated, [
            'diskon_rab'    => $hitung['diskon_rab'],
            'nilai_kontrak' => $hitung['nilai_kontrak'],
        ]);

        $proyek->update($data);

        // Status otomatis
        $proyek->status = ($request->tanggal_mulai && $request->tanggal_selesai && $proyek->status === 'perencanaan')
            ? 'berjalan'
            : $request->status;
        $proyek->save();

        // File SPK (opsional)
        if ($request->hasFile('file_spk')) {
            if ($proyek->file_spk && Storage::exists('public/' . $proyek->file_spk)) {
                Storage::delete('public/' . $proyek->file_spk);
            }
            $path = $request->file('file_spk')->store('spk', 'public');
            $proyek->file_spk = $path;
            $proyek->save();
        }

        return redirect()->route('proyek.show', $proyek->id)->with('success', 'Proyek berhasil diperbarui.');
    }

    public function destroy($id)
    {
        if (auth()->user()->hapus_proyek != 1) {
            abort(403, 'Anda tidak memiliki izin untuk edit proyek.');
        }
        $proyek = Proyek::findOrFail($id);
        if ($proyek->file_spk && \Storage::disk('public')->exists($proyek->file_spk)) {
            \Storage::disk('public')->delete($proyek->file_spk);
        }
        $proyek->delete();
        return redirect()->route('proyek.index')->with('success', 'Data proyek berhasil dihapus.');
    }

    public function show($id)
    {
        $proyek = Proyek::with(['pemberiKerja'])->findOrFail($id);

        // Sinkron nilai penawaran (existing behavior)
        \App\Helpers\ProyekHelper::updateNilaiPenawaran($proyek->id);

        // RAB proyek (existing)
        $headers    = RabHeader::where('proyek_id', $id)->with(['rabDetails', 'schedule'])->get();
        $grandTotal = $headers->flatMap->rabDetails->sum('total');

        /* ============================
        Penawaran FINAL & pilihan
        ============================ */
        $finalPenawarans = RabPenawaranHeader::where('proyek_id', $id)
            ->where('status', 'final')
            ->orderBy('tanggal_penawaran')
            ->get();

        $selectedId = (int) request('penawaran_id', optional($finalPenawarans->last())->id);

        /* ============================
        Kurva-S (PLANNED) dari detail
        ============================ */
        $scheduleDetailQ = RabScheduleDetail::where('proyek_id', $id)
            ->when($selectedId, fn ($q) => $q->where('penawaran_id', $selectedId));

        $hasScheduleSelected = false;
        $minggu     = [];
        $akumulasi  = [];
        $realisasi  = [];

        // deteksi kolom bobot
        $sdTable  = (new RabScheduleDetail)->getTable();
        $bobotCol = \Schema::hasColumn($sdTable, 'bobot_mingguan')
            ? 'bobot_mingguan'
            : (\Schema::hasColumn($sdTable, 'bobot') ? 'bobot' : null);

        $grouped = $scheduleDetailQ->orderBy('minggu_ke')->get()->groupBy('minggu_ke');
        $hasScheduleSelected = $grouped->isNotEmpty();

        if ($hasScheduleSelected) {
            $maxMinggu = $grouped->keys()->max();
            $minggu    = range(1, $maxMinggu);

            // Planned akumulatif (kurva-S rencana)
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

        /* ============================
        PROGRESS (tabel ringkasan)
        -> Pakai angka yang kita simpan:
            - pertumbuhan   = SUM(rpd.bobot_minggu_ini) untuk progress_id itu
            - kumulatif saat ini = kumulatif sebelumnya + pertumbuhan
        ============================ */
        $progressRaw = RabProgress::with(['details'])
            ->where('proyek_id', $id)
            ->when($selectedId, fn ($q) => $q->where('penawaran_id', $selectedId))
            ->orderBy('minggu_ke')
            ->get();

        $progressSummary      = [];
        $progressSebelumnya   = 0.0;

        foreach ($progressRaw as $row) {
            // !!! TIDAK perlu dikalikan bobot item lagi.
            $bobotMingguIni = (float) $row->details->sum('bobot_minggu_ini'); // % proyek (delta)

            $progressSummary[] = [
                'id'                  => $row->id,
                'minggu_ke'           => (int) $row->minggu_ke,
                'tanggal'             => $row->tanggal,
                'progress_sebelumnya' => round($progressSebelumnya, 2),
                'pertumbuhan'         => round($bobotMingguIni, 2),
                'progress_saat_ini'   => round($progressSebelumnya + $bobotMingguIni, 2),
                'status'              => $row->status,
            ];

            $progressSebelumnya += $bobotMingguIni;
        }

        /* ============================
        Kurva-S REALISASI (FINAL saja)
        -> Setiap minggu = SUM(rpd.bobot_minggu_ini) (FINAL)
        -> Lalu akumulasi; untuk minggu yang belum FINAL, isi null
        ============================ */
        if (!empty($minggu)) {
            $finalWeekly = [];
            foreach ($progressRaw as $row) {
                if ($row->status !== 'final') continue;
                $val = (float) $row->details->sum('bobot_minggu_ini'); // % proyek (delta)
                $week = (int) $row->minggu_ke;
                $finalWeekly[$week] = ($finalWeekly[$week] ?? 0.0) + $val;
            }

            $cumFinal = 0.0;
            foreach ($minggu as $w) {
                if (isset($finalWeekly[$w])) {
                    $cumFinal   += (float) $finalWeekly[$w];
                    $realisasi[] = round($cumFinal, 2);
                } else {
                    // null supaya garis realisasi putus pada minggu yang belum final
                    $realisasi[] = null;
                }
            }
        }

        /* ============================
        META jadwal penawaran terpilih
        ============================ */
        $selectedMeta = $selectedId
            ? RabScheduleMeta::where('proyek_id', $id)->where('penawaran_id', $selectedId)->first()
            : null;

        /* ============================
        EVENT KALENDER per item (RabSchedule)
        ============================ */
        $calendarEvents = [];
        $calendarRows   = [];

        if ($selectedId && $hasScheduleSelected && $selectedMeta) {
            $metaStart = \Carbon\Carbon::parse($selectedMeta->start_date)->startOfDay();

            $schedRows = RabSchedule::where('proyek_id', $id)
                ->where('penawaran_id', $selectedId)
                ->with(['penawaranItem.rabDetail'])
                ->get();

            $pickProp = function ($obj, array $cands) {
                foreach ($cands as $k) {
                    $v = data_get($obj, $k);
                    if (!is_null($v) && $v !== '') return $v;
                }
                return null;
            };

            foreach ($schedRows as $row) {
                $pi = $row->penawaranItem;
                $rd = optional($pi)->rabDetail;

                $kode = $pickProp($pi, ['kode', 'wbs_kode', 'kode_wbs', 'no', 'nomor'])
                    ?? $pickProp($rd, ['kode', 'wbs_kode', 'kode_wbs', 'no', 'nomor'])
                    ?? '';

                $desc = $pickProp($pi, ['uraian', 'deskripsi', 'nama', 'judul'])
                    ?? $pickProp($rd, ['uraian', 'deskripsi', 'nama', 'judul'])
                    ?? '';

                $start = (clone $metaStart)->addWeeks(max(0, (int) $row->minggu_ke - 1));
                $end   = (clone $start)->addWeeks(max(1, (int) $row->durasi))->subDay();
                $endExclusive = (clone $end)->addDay(); // FullCalendar end eksklusif

                $calendarEvents[] = [
                    'title'  => trim(($kode ? ($kode . ' — ') : '') . \Illuminate\Support\Str::limit($desc, 60)),
                    'start'  => $start->toDateString(),
                    'end'    => $endExclusive->toDateString(),
                    'allDay' => true,
                ];

                $calendarRows[] = [
                    'kode'        => $kode ?: '—',
                    'deskripsi'   => $desc,
                    'minggu_ke'   => (int) $row->minggu_ke,
                    'durasi'      => (int) $row->durasi,
                    'tgl_mulai'   => $start->format('d-m-Y'),
                    'tgl_selesai' => $end->format('d-m-Y'),
                ];
            }

            usort($calendarRows, function ($a, $b) {
                if ($a['tgl_mulai'] === $b['tgl_mulai']) return strcmp($a['kode'], $b['kode']);
                $da = \Carbon\Carbon::createFromFormat('d-m-Y', $a['tgl_mulai']);
                $db = \Carbon\Carbon::createFromFormat('d-m-Y', $b['tgl_mulai']);
                return $da <=> $db;
            });
        }

        return view('proyek.show', compact(
            'proyek',
            'headers',
            'grandTotal',
            'progressSummary',

            // schedule tab (dropdown & chart)
            'finalPenawarans',
            'selectedId',
            'selectedMeta',
            'hasScheduleSelected',
            'minggu',
            'akumulasi',
            'realisasi',

            // kalender
            'calendarEvents',
            'calendarRows'
        ));
    }


    public function generateSchedule(Request $request, $proyek_id)
    {
        $data = $request->input('jadwal');

        DB::beginTransaction();
        try {
            $proyek        = Proyek::findOrFail($proyek_id);
            $tanggal_mulai = Carbon::parse($proyek->tanggal_mulai);

            // bersihkan schedule lama
            RabScheduleDetail::where('proyek_id', $proyek_id)->delete();

            foreach ($data as $rab_header_id => $jadwal) {
                $minggu_ke = (int) $jadwal['minggu_ke'];
                $durasi    = (int) $jadwal['durasi'];

                $header = RabHeader::find($rab_header_id);
                if (!$header) continue;

                $bobot_mingguan = $header->bobot / max(1, $durasi);

                for ($i = 0; $i < $durasi; $i++) {
                    RabScheduleDetail::create([
                        'rab_header_id' => $header->id,
                        'proyek_id'     => $proyek_id,
                        'minggu_ke'     => $minggu_ke + $i,
                        'bobot'         => $bobot_mingguan,
                    ]);
                }

                $header->minggu_ke = $minggu_ke;
                $header->durasi    = $durasi;
                $header->save();
            }

            DB::commit();
            return redirect()->back()->with('success', 'Schedule mingguan berhasil digenerate.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Terjadi kesalahan saat generate schedule: ' . $e->getMessage());
        }
    }

    public function resetRab($id)
    {
        $proyek = Proyek::findOrFail($id);

        try {
            RabDetail::where('proyek_id', $id)->delete();
            RabHeader::where('proyek_id', $id)->delete();
            RabSchedule::where('proyek_id', $id)->delete();
            RabScheduleDetail::where('proyek_id', $id)->delete();

            RabProgress::where('proyek_id', $id)->delete();

            return redirect()->back()->with('success', 'Data RAB dan jadwal berhasil direset.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal reset RAB: ' . $e->getMessage());
        }
    }

    /* =========================================================
       Calendar Events (header-level, adaptif kolom kode/uraian)
    ========================================================== */
    public function calendarEvents(\App\Models\Proyek $proyek, \Illuminate\Http\Request $request)
    {
        abort_if(!$proyek->tanggal_mulai, 400, 'tanggal_mulai proyek belum diisi');
        $penawaranId = $request->integer('penawaran_id');

        $pick = function (string $table, array $candidates): ?string {
            foreach ($candidates as $c) if (Schema::hasColumn($table, $c)) return $c;
            return null;
        };

        $hdrCodeCol = $pick('rab_header', ['kode', 'wbs_kode', 'kode_wbs', 'no', 'nomor']);
        $hdrNameCol = $pick('rab_header', ['uraian', 'deskripsi', 'nama', 'judul']);

        $rows = RabSchedule::select([
                'rab_schedule.rab_header_id',
                DB::raw('MIN(minggu_ke) as start_week'),
                DB::raw('MAX(minggu_ke) as end_week'),
                DB::raw('SUM(bobot_mingguan) as total_bobot'),
            ])
            ->where('proyek_id', $proyek->id)
            ->when($penawaranId, fn ($q) => $q->where('penawaran_id', $penawaranId))
            ->groupBy('rab_schedule.rab_header_id')
            ->with(['rabHeader' => function ($q) use ($hdrCodeCol, $hdrNameCol) {
                $q->select('id');
                $q->addSelect($hdrCodeCol ? DB::raw("$hdrCodeCol as kode") : DB::raw("CAST(id AS CHAR) as kode"));
                $q->addSelect($hdrNameCol ? DB::raw("$hdrNameCol as uraian") : DB::raw("'' as uraian"));
            }])
            ->get();

        $base = Carbon::parse($proyek->tanggal_mulai)->startOfDay();

        $events = [];
        foreach ($rows as $r) {
            $hdr = $r->rabHeader;
            if (!$hdr) continue;
            $sw = (int) $r->start_week;
            $ew = (int) $r->end_week;

            $events[] = [
                'id'    => 'hdr-' . $hdr->id,
                'title' => mb_strimwidth(trim(($hdr->kode ?? '') . ' ' . ($hdr->uraian ?? '')), 0, 80, '…'),
                'start' => (clone $base)->addWeeks(max(1, $sw) - 1)->toDateString(),
                'end'   => (clone $base)->addWeeks($ew)->toDateString(), // allDay → end eksklusif
                'allDay'=> true,
            ];
        }

        return response()->json($events);
    }

    /* =========================================================
       Ringkasan Tree (header → subheader → item)
    ========================================================== */
    public function scheduleSummaryTree(Proyek $proyek, Request $request)
{
    $proyekId   = $proyek->id;
    $penawaranId= (int) $request->query('penawaran_id');

    // Helper: pilih kolom yang ada pada tabel
    $pickCol = function (string $table, array $cands) {
        foreach ($cands as $c) if (Schema::hasColumn($table, $c)) return $c;
        return null;
    };

    try {
        /* =================================
           1) Siapkan alias kolom per tabel
        ==================================*/
        // RabSchedule
        $schTable   = (new RabSchedule)->getTable();
        $schWeekCol = $pickCol($schTable, ['minggu_ke','week']);
        $schDurCol  = $pickCol($schTable, ['durasi','duration_weeks','lama_minggu']);
        $schFkCol   = $pickCol($schTable, ['rab_penawaran_item_id','penawaran_item_id','rab_detail_id','detail_id']);
        if (!$schWeekCol || !$schDurCol || !$schFkCol) {
            return response('<tr><td colspan="5" class="text-muted py-3 text-center">Struktur kolom jadwal tidak lengkap.</td></tr>', 200);
        }

        // RabPenawaranItem (dipakai jika jadwal simpan id item penawaran)
        $piTable  = (new RabPenawaranItem)->getTable();
        $piDetCol = $pickCol($piTable, ['rab_detail_id','detail_id']);

        // RabDetail
        $detTable   = (new RabDetail)->getTable();
        $detCodeCol = $pickCol($detTable, ['kode','wbs_kode','kode_wbs','no','nomor']) ?: 'id';
        $detNameCol = $pickCol($detTable, ['uraian','deskripsi','nama','judul']);
        $detNameSel = $detNameCol ? DB::raw("$detNameCol as uraian") : DB::raw("'' as uraian");

        // RabHeader
        $hdrTable   = (new RabHeader)->getTable();
        $hCodeCol   = $pickCol($hdrTable, ['kode','wbs_kode','kode_wbs','no','nomor']) ?: 'id';
        $hNameCol   = $pickCol($hdrTable, ['uraian','deskripsi','nama','judul']);
        $hNameSel   = $hNameCol ? DB::raw("$hNameCol as uraian") : DB::raw("'' as uraian");

        /* =================================
           2) Agregasi jadwal per detail (FIX)
           - Jika jadwal punya rab_detail_id langsung → groupBy kolom itu
           - Jika jadwal simpan id item penawaran → LEFT JOIN ke rab_penawaran_items,
             lalu groupBy pi.$piDetCol (menghindari subquery di SELECT)
        ==================================*/
        if (in_array($schFkCol, ['rab_detail_id','detail_id'])) {
            // Langsung pakai kolom detail_id di jadwal
            $schedAgg = DB::table("$schTable as sch")
                ->where('sch.proyek_id', $proyekId)
                ->when($penawaranId && Schema::hasColumn($schTable,'penawaran_id'),
                    fn($q)=>$q->where('sch.penawaran_id',$penawaranId))
                ->selectRaw("sch.$schFkCol as did, MIN(sch.$schWeekCol) as minggu_mulai, SUM(sch.$schDurCol) as durasi_total")
                ->groupBy("sch.$schFkCol")
                ->get();
        } else {
            // Harus map via item penawaran → JOIN
            if (!$piDetCol) {
                return response('<tr><td colspan="5" class="text-muted py-3 text-center">Tidak bisa memetakan item penawaran ke detail (kolom FK tidak ditemukan).</td></tr>', 200);
            }

            $schedAgg = DB::table("$schTable as sch")
                ->leftJoin("$piTable as pi", "pi.id", "=", "sch.$schFkCol")
                ->where('sch.proyek_id', $proyekId)
                ->when($penawaranId && Schema::hasColumn($schTable,'penawaran_id'),
                    fn($q)=>$q->where('sch.penawaran_id',$penawaranId))
                ->whereNotNull("pi.$piDetCol")
                ->selectRaw("pi.$piDetCol as did, MIN(sch.$schWeekCol) as minggu_mulai, SUM(sch.$schDurCol) as durasi_total")
                ->groupBy("pi.$piDetCol")
                ->get();
        }

        if ($schedAgg->isEmpty()) {
            return response('<tr><td colspan="5" class="text-muted py-3 text-center">Belum ada schedule detail untuk penawaran terpilih.</td></tr>', 200);
        }

        $detailIds = $schedAgg->pluck('did')->filter()->unique()->values()->all();
        if (empty($detailIds)) {
            return response('<tr><td colspan="5" class="text-muted py-3 text-center">Belum ada schedule detail untuk penawaran terpilih.</td></tr>', 200);
        }

        // Map: detail_id => (minggu_mulai, durasi_total)
        $aggByDetail = [];
        foreach ($schedAgg as $a) {
            $aggByDetail[(int)$a->did] = [
                'wmin' => max(1, (int)$a->minggu_mulai),
                'wdur' => max(1, (int)$a->durasi_total),
            ];
        }

        /* =================================
           3) Ambil meta (start_date) utk tanggal
        ==================================*/
        $meta = RabScheduleMeta::where('proyek_id', $proyekId)
            ->when($penawaranId && Schema::hasColumn((new RabScheduleMeta)->getTable(),'penawaran_id'),
                fn($q)=>$q->where('penawaran_id',$penawaranId))
            ->first();
        $metaStart = $meta ? Carbon::parse($meta->start_date)->startOfDay() : null;

        /* =================================
           4) Ambil detail + header
        ==================================*/
        $details = RabDetail::where('proyek_id', $proyekId)
            ->whereIn('id', $detailIds)
            ->select('id','rab_header_id', DB::raw("$detCodeCol as kode"), $detNameSel)
            ->get();

        if ($details->isEmpty()) {
            return response('<tr><td colspan="5" class="text-muted py-3 text-center">Detail RAB tidak ditemukan.</td></tr>', 200);
        }

        $byHeader = $details->groupBy('rab_header_id');  // header level-2
        $h2Ids    = $byHeader->keys()->filter()->unique()->values();

        $h2 = RabHeader::whereIn('id', $h2Ids)
            ->select('id','parent_id', DB::raw("$hCodeCol as kode"), $hNameSel)
            ->get();

        $h1Ids = $h2->pluck('parent_id')->filter()->unique()->values();
        $h1 = RabHeader::whereIn('id', $h1Ids)
            ->select('id','parent_id', DB::raw("$hCodeCol as kode"), $hNameSel)
            ->get();

        // Header L1 yang langsung punya item (ketika tidak ada L2)
        $hdrNoL2 = $details->filter(fn($d)=>!$h2->contains('id',$d->rab_header_id))
                           ->groupBy('rab_header_id');

        /* =================================
           5) Render HTML <tbody>
        ==================================*/
        $esc = fn($s)=>e((string)$s);

        $rowHeader = function(string $key, ?string $parentKey, string $kode, string $nama, bool $canToggle, int $level=1) use ($esc) {
            $caret = $canToggle ? '<span class="caret">▼</span>' : '<span class="caret disabled">•</span>';
            $cls   = $level===1 ? 'level-1' : 'level-2';
            return '<tr data-key="'.$esc($key).'"'.($parentKey?' data-parent="'.$esc($parentKey).'"':'').' class="'.$cls.'">'.
                     '<td class="tree-cell">'.$caret.'<span class="wbs">'.$esc($kode).'</span> '.$esc($nama).'</td>'.
                     '<td class="text-end">—</td>'.
                     '<td class="text-end">—</td>'.
                     '<td>—</td>'.
                     '<td>—</td>'.
                   '</tr>';
        };

        $rowItem = function($key,$parentKey,$kode,$nama,$wmin,$wdur,?Carbon $metaStart) use ($esc){
            $tgl1='—'; $tgl2='—';
            if ($metaStart) {
                $start = (clone $metaStart)->addWeeks(max(0,$wmin-1));
                $end   = (clone $start)->addWeeks(max(1,$wdur))->subDay();
                $tgl1 = $start->format('d-m-Y');
                $tgl2 = $end->format('d-m-Y');
            }
            return '<tr data-key="'.$esc($key).'" data-parent="'.$esc($parentKey).'" class="level-3">'.
                     '<td class="tree-cell"><span class="dot">·</span><span class="wbs">'.$esc($kode).'</span> '.$esc($nama).'</td>'.
                     '<td class="text-end">'.$esc($wmin).'</td>'.
                     '<td class="text-end">'.$esc($wdur).'</td>'.
                     '<td>'.$esc($tgl1).'</td>'.
                     '<td>'.$esc($tgl2).'</td>'.
                   '</tr>';
        };

        $html = '';

        foreach ($h1->sortBy('kode', SORT_NATURAL|SORT_FLAG_CASE) as $L1) {
            $keyL1 = 'H1-'.$L1->id;
            $childL2 = $h2->where('parent_id',$L1->id)->sortBy('kode', SORT_NATURAL|SORT_FLAG_CASE);
            $itemsUnderL1Direct = $hdrNoL2->get($L1->id, collect());

            $canToggleL1 = $childL2->isNotEmpty() || $itemsUnderL1Direct->isNotEmpty();
            $html .= $rowHeader($keyL1, null, (string)$L1->kode, (string)$L1->uraian, $canToggleL1, 1);

            foreach ($childL2 as $L2) {
                $keyL2 = 'H2-'.$L2->id;
                $items = $byHeader->get($L2->id, collect())->sortBy('kode', SORT_NATURAL|SORT_FLAG_CASE);
                $html .= $rowHeader($keyL2, $keyL1, (string)$L2->kode, (string)$L2->uraian, $items->isNotEmpty(), 2);

                foreach ($items as $it) {
                    $agg = $aggByDetail[(int)$it->id] ?? ['wmin'=>1,'wdur'=>1];
                    $html .= $rowItem('IT-'.$it->id, $keyL2, (string)$it->kode, (string)$it->uraian, (int)$agg['wmin'], (int)$agg['wdur'], $metaStart);
                }
            }

            if ($itemsUnderL1Direct->isNotEmpty()) {
                foreach ($itemsUnderL1Direct->sortBy('kode', SORT_NATURAL|SORT_FLAG_CASE) as $it) {
                    $agg = $aggByDetail[(int)$it->id] ?? ['wmin'=>1,'wdur'=>1];
                    $html .= $rowItem('IT-'.$it->id, $keyL1, (string)$it->kode, (string)$it->uraian, (int)$agg['wmin'], (int)$agg['wdur'], $metaStart);
                }
            }
        }

        if ($html === '') {
            // Fallback: flat list items (jika tidak ada header terhubung)
            foreach ($details->sortBy('kode', SORT_NATURAL|SORT_FLAG_CASE) as $it) {
                $agg = $aggByDetail[(int)$it->id] ?? ['wmin'=>1,'wdur'=>1];
                $html .= $rowItem('IT-'.$it->id, '', (string)$it->kode, (string)$it->uraian, (int)$agg['wmin'], (int)$agg['wdur'], $metaStart);
            }
        }

        return response($html ?: '<tr><td colspan="5" class="text-muted py-3 text-center">Tidak ada data untuk ditampilkan.</td></tr>', 200);

    } catch (\Throwable $e) {
        Log::error('scheduleSummaryTree error', [
            'proyek_id'    => $proyekId,
            'penawaran_id' => $penawaranId,
            'message'      => $e->getMessage(),
            'file'         => $e->getFile(),
            'line'         => $e->getLine(),
        ]);

        return response('<tr><td colspan="5" class="text-danger py-3 text-center">Gagal memuat ringkasan.</td></tr>', 500);
    }
}



    private function e($v) { return e($v ?? ''); }

    private function rangeByWeeks(?int $sw, ?int $ew, Carbon $base): array
    {
        if (!$sw || !$ew) {
            return ['weeks' => '—', 'd1' => '—', 'd2' => '—'];
        }
        $weeks = max(1, $ew - $sw + 1);
        $d1 = (clone $base)->addWeeks($sw - 1)->format('d-m-Y');
        $d2 = (clone $base)->addWeeks($ew)->subDay()->format('d-m-Y');
        return ['weeks' => $weeks, 'd1' => $d1, 'd2' => $d2];
    }
/**
 * Total bobot item rencana per rab_detail_id dari rab_schedule_detail.
 * Adaptif: kolom bobot_mingguan / bobot, dan kunci item bisa rab_detail_id
 * atau via rab_penawaran_item_id -> rab_penawaran_items.rab_detail_id.
 *
 * @return array [detail_id => total_bobot_item]
 */
    private function itemWeightMapFromSchedule(int $proyekId, ?int $penawaranId): array
    {
        if (!$penawaranId) return [];

        $sdTable = (new \App\Models\RabScheduleDetail)->getTable();
        $piTable = class_exists(\App\Models\RabPenawaranItem::class)
            ? (new \App\Models\RabPenawaranItem)->getTable()
            : null;

        // deteksi kolom bobot
        $col = \Illuminate\Support\Facades\Schema::hasColumn($sdTable, 'bobot_mingguan')
            ? 'bobot_mingguan'
            : (\Illuminate\Support\Facades\Schema::hasColumn($sdTable, 'bobot') ? 'bobot' : null);

        if (!$col) return [];

        // CASE A: schedule_detail menyimpan rab_detail_id langsung
        if (\Illuminate\Support\Facades\Schema::hasColumn($sdTable, 'rab_detail_id')) {
            $q = \Illuminate\Support\Facades\DB::table($sdTable)
                ->where('proyek_id', $proyekId);

            if (\Illuminate\Support\Facades\Schema::hasColumn($sdTable, 'penawaran_id')) {
                $q->where('penawaran_id', $penawaranId);
            }

            return $q->select('rab_detail_id', \Illuminate\Support\Facades\DB::raw("SUM($col) as total"))
                ->groupBy('rab_detail_id')
                ->pluck('total', 'rab_detail_id')
                ->toArray();
        }

        // CASE B: pakai rab_penawaran_item_id -> map ke rab_detail_id
        if (
            \Illuminate\Support\Facades\Schema::hasColumn($sdTable, 'rab_penawaran_item_id') &&
            $piTable && \Illuminate\Support\Facades\Schema::hasColumn($piTable, 'rab_detail_id')
        ) {
            $q = \Illuminate\Support\Facades\DB::table("$sdTable as sd")
                ->join("$piTable as pi", 'pi.id', '=', 'sd.rab_penawaran_item_id')
                ->where('sd.proyek_id', $proyekId);

            if (\Illuminate\Support\Facades\Schema::hasColumn($sdTable, 'penawaran_id')) {
                $q->where('sd.penawaran_id', $penawaranId);
            }

            return $q->select('pi.rab_detail_id', \Illuminate\Support\Facades\DB::raw("SUM(sd.$col) as total"))
                ->groupBy('pi.rab_detail_id')
                ->pluck('total', 'pi.rab_detail_id')
                ->toArray();
        }

        return [];
    }

}

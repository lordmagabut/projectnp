<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;

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
        $bobotCol = Schema::hasColumn($sdTable, 'bobot_mingguan')
            ? 'bobot_mingguan'
            : (Schema::hasColumn($sdTable, 'bobot') ? 'bobot' : null);

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
           - tampilkan semua, termasuk draft
           - bobot realisasi = sum( bobotItemRencana(detail) * %minggu_ini / 100 )
        ============================ */
        $progressRaw = RabProgress::with(['details'])
            ->where('proyek_id', $id)
            ->when($selectedId, fn ($q) => $q->where('penawaran_id', $selectedId))
            ->orderBy('minggu_ke')
            ->get();

        // Bobot item rencana per detail_id dari schedule
        $itemWeight = $this->itemWeightMapFromSchedule($id, $selectedId); // [detail_id => total bobot]

        $progressSummary = [];
        $progressSebelumnya = 0.0;
        
        foreach ($progressRaw as $row) {
            $bobotMingguIni = 0.0;
            foreach ($row->details as $d) {
                $w = (float)($itemWeight[$d->rab_detail_id] ?? 0.0);
                $bobotMingguIni += ($w * (float)$d->bobot_minggu_ini) / 100.0;
            }
        
            $progressSummary[] = [
                'id'                  => $row->id,             // <<< tambahkan ini
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
        ============================ */
        if (!empty($minggu)) {
            // peta minggu => bobot final minggu tsb
            $finalWeekly = [];
            foreach ($progressRaw as $row) {
                if ($row->status !== 'final') continue;

                $val = 0.0;
                foreach ($row->details as $d) {
                    $w = (float) ($itemWeight[$d->rab_detail_id] ?? 0.0);
                    $val += ($w * (float) $d->bobot_minggu_ini) / 100.0;
                }
                $finalWeekly[(int) $row->minggu_ke] = ($finalWeekly[(int) $row->minggu_ke] ?? 0) + $val;
            }

            $cumFinal = 0.0;
            foreach ($minggu as $w) {
                if (isset($finalWeekly[$w])) {
                    $cumFinal  += (float) $finalWeekly[$w];
                    $realisasi[] = round($cumFinal, 2);
                } else {
                    // null supaya garis realisasi putus di minggu yg belum final
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
            $metaStart = Carbon::parse($selectedMeta->start_date)->startOfDay();

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
                    'title'  => trim(($kode ? ($kode . ' — ') : '') . Str::limit($desc, 60)),
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
                $da = Carbon::createFromFormat('d-m-Y', $a['tgl_mulai']);
                $db = Carbon::createFromFormat('d-m-Y', $b['tgl_mulai']);
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
    public function scheduleSummaryTree(\App\Models\Proyek $proyek, \Illuminate\Http\Request $request)
    {
        try {
            if (!$proyek->tanggal_mulai) {
                return response('tanggal_mulai proyek belum diisi', 400);
            }

            $base        = Carbon::parse($proyek->tanggal_mulai)->startOfDay();
            $penawaranId = $request->integer('penawaran_id');

            $pick = function (string $table, array $candidates): ?string {
                foreach ($candidates as $c) if (Schema::hasColumn($table, $c)) return $c;
                return null;
            };

            // 1) Header
            $hdrCodeCol = $pick('rab_header', ['kode', 'wbs_kode', 'kode_wbs', 'no', 'nomor']);
            $hdrNameCol = $pick('rab_header', ['uraian', 'deskripsi', 'nama', 'judul']);

            $headersQ = RabHeader::where('proyek_id', $proyek->id)->select(['id', 'parent_id']);
            $headersQ->addSelect($hdrCodeCol ? DB::raw("$hdrCodeCol as kode") : DB::raw("CAST(id AS CHAR) as kode"));
            $headersQ->addSelect($hdrNameCol ? DB::raw("$hdrNameCol as uraian") : DB::raw("'' as uraian"));
            $headersQ->orderBy($hdrCodeCol ?: 'id');
            $headers = $headersQ->get();
            $headersById = $headers->keyBy('id');

            $children = [];
            foreach ($headers as $h) {
                $pid    = $h->parent_id;
                $isRoot = ($pid === null || $pid === 0 || $pid === '0' || $pid === '' || !isset($headersById[$pid]));
                $children[$isRoot ? 0 : (int) $pid][] = $h;
            }

            // 2) Range minggu header dari rab_schedule
            $hMin = RabSchedule::where('proyek_id', $proyek->id)
                ->when($penawaranId, fn ($q) => $q->where('penawaran_id', $penawaranId))
                ->select('rab_header_id', DB::raw('MIN(minggu_ke) as mn'))
                ->groupBy('rab_header_id')->pluck('mn', 'rab_header_id')->toArray();

            $hMax = RabSchedule::where('proyek_id', $proyek->id)
                ->when($penawaranId, fn ($q) => $q->where('penawaran_id', $penawaranId))
                ->select('rab_header_id', DB::raw('MAX(minggu_ke) as mx'))
                ->groupBy('rab_header_id')->pluck('mx', 'rab_header_id')->toArray();

            // 3) Ambil agregat item dari rab_schedule_detail (adaptif)
            $iMin = $iMax = [];
            $allowedDetailIds = [];

            if (class_exists(\App\Models\RabScheduleDetail::class)) {
                $sd      = new RabScheduleDetail;
                $sdTable = $sd->getTable();
                $sdKeyCol = $pick($sdTable, ['rab_detail_id','detail_id','rab_penawaran_item_id','penawaran_item_id']);

                if ($sdKeyCol) {
                    $baseQ = RabScheduleDetail::where('proyek_id', $proyek->id)
                        ->when($penawaranId, fn ($q) => $q->where('penawaran_id', $penawaranId));

                    if (in_array($sdKeyCol, ['rab_detail_id','detail_id'], true)) {
                        $iMin = (clone $baseQ)->selectRaw("$sdKeyCol as k, MIN(minggu_ke) as mn")->groupBy('k')->pluck('mn', 'k')->toArray();
                        $iMax = (clone $baseQ)->selectRaw("$sdKeyCol as k, MAX(minggu_ke) as mx")->groupBy('k')->pluck('mx', 'k')->toArray();
                        $allowedDetailIds = array_unique(array_merge(array_keys($iMin), array_keys($iMax)));

                    } elseif (in_array($sdKeyCol, ['rab_penawaran_item_id','penawaran_item_id'], true)
                        && class_exists(\App\Models\RabPenawaranItem::class)) {

                        $tmpMin = (clone $baseQ)->selectRaw("$sdKeyCol as k, MIN(minggu_ke) as mn")->groupBy('k')->pluck('mn', 'k')->toArray();
                        $tmpMax = (clone $baseQ)->selectRaw("$sdKeyCol as k, MAX(minggu_ke) as mx")->groupBy('k')->pluck('mx', 'k')->toArray();
                        $pids   = array_unique(array_merge(array_keys($tmpMin), array_keys($tmpMax)));

                        $pi      = new \App\Models\RabPenawaranItem;
                        $piTable = $pi->getTable();
                        $piDetailCol = $pick($piTable, ['rab_detail_id','detail_id']);

                        if ($piDetailCol && !empty($pids)) {
                            $map = \App\Models\RabPenawaranItem::whereIn('id', $pids)
                                ->select('id', DB::raw("$piDetailCol as did"))
                                ->pluck('did', 'id')->toArray();

                            foreach ($tmpMin as $pid => $mn) {
                                $did = $map[$pid] ?? null;
                                if ($did) {
                                    $iMin[$did] = isset($iMin[$did]) ? min($iMin[$did], $mn) : $mn;
                                    $allowedDetailIds[] = $did;
                                }
                            }
                            foreach ($tmpMax as $pid => $mx) {
                                $did = $map[$pid] ?? null;
                                if ($did) {
                                    $iMax[$did] = isset($iMax[$did]) ? max($iMax[$did], $mx) : $mx;
                                    $allowedDetailIds[] = $did;
                                }
                            }
                            $allowedDetailIds = array_values(array_unique($allowedDetailIds));
                        }
                    }
                }
            }

            // 4) Ambil item (RabDetail) hanya utk allowed IDs
            $detCodeCol = $pick('rab_detail', ['kode', 'wbs_kode', 'kode_wbs', 'no', 'nomor']);
            $detNameCol = $pick('rab_detail', ['uraian', 'deskripsi', 'nama', 'judul']);

            $itemsQ = RabDetail::where('proyek_id', $proyek->id)->select(['id', 'rab_header_id']);
            $itemsQ->addSelect($detCodeCol ? DB::raw("$detCodeCol as kode") : DB::raw("CAST(id AS CHAR) as kode"));
            $itemsQ->addSelect($detNameCol ? DB::raw("$detNameCol as uraian") : DB::raw("'' as uraian"));
            if ($penawaranId && !empty($allowedDetailIds)) {
                $itemsQ->whereIn('id', $allowedDetailIds);
            } elseif ($penawaranId) {
                $itemsQ->whereRaw('1=0');
            }
            $itemsQ->orderBy($detCodeCol ?: 'id');
            $items = $itemsQ->get();

            $itemsByHeader = [];
            foreach ($items as $it) { $itemsByHeader[$it->rab_header_id][] = $it; }

            // 5) DFS range per header
            $rangeByHeader = [];
            $visiting = [];
            $calc = function ($hdrId) use (&$calc, $children, $itemsByHeader, &$rangeByHeader, &$visiting, $hMin, $hMax, $iMin, $iMax) {
                if (isset($visiting[$hdrId])) return $rangeByHeader[$hdrId] ?? ['sw' => null, 'ew' => null];
                $visiting[$hdrId] = true;

                $mn = $hMin[$hdrId] ?? null;
                $mx = $hMax[$hdrId] ?? null;

                foreach ($children[$hdrId] ?? [] as $ch) {
                    $r = $rangeByHeader[$ch->id] ?? $calc($ch->id);
                    if ($r['sw'] !== null) $mn = ($mn === null) ? $r['sw'] : min($mn, $r['sw']);
                    if ($r['ew'] !== null) $mx = ($mx === null) ? $r['ew'] : max($mx, $r['ew']);
                }
                foreach ($itemsByHeader[$hdrId] ?? [] as $it) {
                    $imn = $iMin[$it->id] ?? null;
                    $imx = $iMax[$it->id] ?? null;
                    if ($imn !== null) $mn = ($mn === null) ? $imn : min($mn, $imn);
                    if ($imx !== null) $mx = ($mx === null) ? $imx : max($mx, $imx);
                }

                unset($visiting[$hdrId]);
                return $rangeByHeader[$hdrId] = ['sw' => $mn, 'ew' => $mx];
            };
            foreach ($headers as $h) { $calc($h->id); }

            // 6) Header visible?
            $visibleHeader = [];
            $isVisible = function ($hdrId) use (&$isVisible, $children, $itemsByHeader, $rangeByHeader, &$visibleHeader) {
                if (isset($visibleHeader[$hdrId])) return $visibleHeader[$hdrId];
                $vis = false;

                $r = $rangeByHeader[$hdrId] ?? ['sw' => null, 'ew' => null];
                if ($r['sw'] || $r['ew']) $vis = true;
                if (!empty($itemsByHeader[$hdrId])) $vis = true;

                foreach ($children[$hdrId] ?? [] as $ch) {
                    if ($isVisible($ch->id)) { $vis = true; break; }
                }
                return $visibleHeader[$hdrId] = $vis;
            };
            foreach ($headers as $h) { $isVisible($h->id); }

            // 7) Render tbody
            $html = '';
            $esc  = fn ($v) => e($v ?? '');

            $renderHeader = function ($hdr, $level, $parentKey) use (&$renderHeader, $children, $itemsByHeader, $rangeByHeader, $base, $esc, &$html, $visibleHeader) {
                if (empty($visibleHeader[$hdr->id])) return;

                $key = "H-{$hdr->id}";

                $hasChild = false;
                foreach ($children[$hdr->id] ?? [] as $ch) { if (!empty($visibleHeader[$ch->id])) { $hasChild = true; break; } }
                if (!$hasChild && !empty($itemsByHeader[$hdr->id])) $hasChild = true;

                $indentCls  = $level === 1 ? 'level-1' : 'level-2';
                $parentAttr = $parentKey ? ' data-parent="' . $esc($parentKey) . '" style="display:none"' : '';
                $caret      = $hasChild ? '<span class="caret">▼</span>' : '<span class="caret disabled">•</span>';

                $html .= '
                <tr class="' . $indentCls . '" data-key="' . $esc($key) . '"' . $parentAttr . '>
                  <td class="tree-cell">' . $caret . '
                    <span class="wbs">' . $esc($hdr->kode) . '</span>
                    <span class="uraian">' . $esc($hdr->uraian) . '</span>
                  </td>
                  <td class="text-end">—</td>
                  <td class="text-end">—</td>
                  <td>—</td>
                  <td>—</td>
                </tr>';

                foreach ($children[$hdr->id] ?? [] as $child) {
                    if (!empty($visibleHeader[$child->id])) $renderHeader($child, $level + 1, $key);
                }

                foreach ($itemsByHeader[$hdr->id] ?? [] as $it) {
                    $ik = "D-{$it->id}";
                    $html .= '
                    <tr class="level-3" data-key="' . $esc($ik) . '" data-parent="' . $esc($key) . '" style="display:none">
                      <td class="tree-cell"><span class="dot">•</span>
                        <span class="wbs">' . $esc($it->kode) . '</span>
                        <span class="uraian">' . $esc($it->uraian) . '</span>
                      </td>
                      <td class="text-end">—</td>
                      <td class="text-end">—</td>
                      <td>—</td>
                      <td>—</td>
                    </tr>';
                }
            };

            $roots = $children[0] ?? [];
            foreach ($roots as $root) { $renderHeader($root, 1, null); }

            if ($html === '') {
                $html = '<tr><td colspan="5" class="text-center text-muted py-3">Tidak ada data ringkasan untuk penawaran terpilih.</td></tr>';
            }

            return response($html, 200)->header('Content-Type', 'text/html; charset=UTF-8');
        } catch (\Throwable $e) {
            \Log::error('scheduleSummaryTree error', [
                'proyek_id' => $proyek->id ?? null,
                'message'   => $e->getMessage(),
                'line'      => $e->getLine(),
            ]);
            return response('Terjadi kesalahan saat memuat ringkasan.', 500);
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

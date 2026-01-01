<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Carbon\Carbon;

use App\Models\Proyek;
use App\Models\PemberiKerja;
use App\Models\RabHeader;
use App\Models\RabPenawaranHeader;
use App\Models\RabDetail;
use App\Models\RabSchedule;
use App\Models\RabScheduleDetail;
use App\Models\RabProgress;
use App\Models\RabScheduleMeta;
use App\Models\RabPenawaranItem;
use App\Models\ProyekTaxProfile;
use App\Models\RabProgressDetail;

use App\Services\ProyekService;
use App\Helpers\FileUploadHelper;

class ProyekController extends Controller
{
    /* =========================
       LIST & CREATE VIEWS
    ========================= */
    public function index()
    {
        $proyeks = Proyek::with(['pemberiKerja'])->get();
        return view('proyek.index', compact('proyeks'));
    }

    public function create()
    {
        $pemberiKerja = PemberiKerja::all();
        return view('proyek.create', compact('pemberiKerja'));
    }

    /* =========================
       STORE (with Tax Profile)
    ========================= */
    public function store(Request $request)
    {
        $validated = ProyekService::validateRequest($request);

        if ($request->hasFile('file_spk')) {
            $validated['file_spk'] = FileUploadHelper::upload($request->file('file_spk'), 'spk');
        }

        $taxInput = $this->validateTaxPayload($request->input('tax', []));
        $taxData  = $this->normalizeTax($taxInput);
        $taxData['aktif'] = $taxData['aktif'] ?? true;

        DB::transaction(function () use ($validated, $taxData) {
            $proyek = Proyek::create($validated);

            if (!empty($taxData)) {
                ProyekTaxProfile::where('proyek_id', $proyek->id)->where('aktif', 1)->update(['aktif' => 0]);
                $taxData['proyek_id']  = $proyek->id;
                $taxData['created_by'] = auth()->id();
                $taxData['updated_by'] = auth()->id();
                ProyekTaxProfile::create($taxData);
            }
        });

        return redirect()->route('proyek.index')->with('success', 'Proyek berhasil ditambahkan.');
    }

    /* =========================
       EDIT VIEW (eager tax)
    ========================= */
    public function edit($id)
    {
        if (auth()->user()->edit_proyek != 1) {
            abort(403, 'Anda tidak memiliki izin untuk edit proyek.');
        }
        $proyek = Proyek::with('taxProfileAktif')->findOrFail($id);
        $pemberiKerja = PemberiKerja::all();
        return view('proyek.edit', compact('proyek', 'pemberiKerja'));
    }

   /* =========================
    UPDATE (with Tax Profile)
========================= */
public function update(Request $request, $id)
{
    $proyek = Proyek::findOrFail($id);

    // 1. Validasi Input (perlu diperbarui di ProyekService::validateUpdateRequest)
    // Asumsi ProyekService::validateUpdateRequest sudah diperbarui untuk menerima 'file_gambar_kerja'
    $validated = ProyekService::validateUpdateRequest($request);
    $hitung = ProyekService::hitungKontrak($proyek, $request);

    $data = array_merge($validated, [
        'diskon_rab'    => $hitung['diskon_rab'],
        'nilai_kontrak' => $hitung['nilai_kontrak'],
    ]);

    // Handle Tax Profile (Logika lama tetap dipertahankan)
    $taxInput = $this->validateTaxPayload($request->input('tax', []));
    $taxData  = $this->normalizeTax($taxInput);
    if (!array_key_exists('aktif', $taxData)) {
        $taxData['aktif'] = true;
    }

    // 2. Transaksi Database
    DB::transaction(function () use ($proyek, $request, $data, $taxData) {
        
        // Update data proyek non-file
        $proyek->update($data);

        // Update status proyek
        $proyek->status = ($request->tanggal_mulai && $request->tanggal_selesai && $proyek->status === 'perencanaan')
            ? 'berjalan'
            : ($request->status ?? $proyek->status);
        $proyek->save();

        // 3. Handle Upload File SPK (Logika lama tetap dipertahankan)
        if ($request->hasFile('file_spk')) {
            // Hapus file lama jika ada
            if ($proyek->file_spk && Storage::disk('public')->exists($proyek->file_spk)) {
                Storage::disk('public')->delete($proyek->file_spk);
            }
            // Simpan file baru menggunakan helper untuk konsistensi disk
            $path = \App\Helpers\FileUploadHelper::upload($request->file('file_spk'), 'spk');
            $proyek->file_spk = $path;
            $proyek->save();
        }

        // ===========================================
        // 4. Handle Upload File Gambar Kerja (FITUR BARU)
        // ===========================================
        if ($request->hasFile('file_gambar_kerja')) {
            // Hapus file lama jika ada
            if ($proyek->file_gambar_kerja && Storage::disk('public')->exists($proyek->file_gambar_kerja)) {
                Storage::disk('public')->delete($proyek->file_gambar_kerja);
            }
            // Simpan file baru menggunakan helper untuk konsistensi
            $path = \App\Helpers\FileUploadHelper::upload($request->file('file_gambar_kerja'), 'proyek/gambar_kerja');
            $proyek->file_gambar_kerja = $path;
            $proyek->save();
        }
        // ===========================================

        // 5. Handle Tax Profile Update (Logika lama tetap dipertahankan)
        if (!empty($taxData)) {
            $active = ProyekTaxProfile::where('proyek_id', $proyek->id)->where('aktif', 1)->first();
            $taxData['updated_by'] = auth()->id();

            if ($active) {
                $active->update($taxData);
            } else {
                ProyekTaxProfile::where('proyek_id', $proyek->id)->where('aktif', 1)->update(['aktif' => 0]);
                $taxData['proyek_id']  = $proyek->id;
                $taxData['created_by'] = auth()->id();
                ProyekTaxProfile::create($taxData);
            }
        }
    });

    return redirect()->route('proyek.show', $proyek->id)->with('success', 'Proyek berhasil diperbarui.');
}

    /* =========================
       DESTROY
    ========================= */
    public function destroy($id)
    {
        if (auth()->user()->hapus_proyek != 1) {
            abort(403, 'Anda tidak memiliki izin untuk hapus proyek.');
        }
        $proyek = Proyek::findOrFail($id);
        if ($proyek->file_spk && \Storage::disk('public')->exists($proyek->file_spk)) {
            \Storage::disk('public')->delete($proyek->file_spk);
        }
        if ($proyek->file_gambar_kerja && \Storage::disk('public')->exists($proyek->file_gambar_kerja)) {
            \Storage::disk('public')->delete($proyek->file_gambar_kerja);
        }
        $proyek->delete();
        return redirect()->route('proyek.index')->with('success', 'Data proyek berhasil dihapus.');
    }

    /* =========================
       SHOW
    ========================= */
    public function show($id)
    {
        $proyek = Proyek::with(['pemberiKerja'])->findOrFail($id);

        \App\Helpers\ProyekHelper::updateNilaiPenawaran($proyek->id);

        $headers    = RabHeader::where('proyek_id', $id)->with(['rabDetails', 'schedule'])->get();
        $grandTotal = $headers->flatMap->rabDetails->sum('total');

        $finalPenawarans = RabPenawaranHeader::where('proyek_id', $id)
            ->where('status', 'final')
            ->orderBy('tanggal_penawaran')
            ->get();

        $selectedId = (int) request('penawaran_id', optional($finalPenawarans->last())->id);

        // Kurva-S: Planned (dari schedule detail)
        $scheduleDetailQ = RabScheduleDetail::where('proyek_id', $id)
            ->when($selectedId, fn ($q) => $q->where('penawaran_id', $selectedId));

        $hasScheduleSelected = false;
        $minggu     = [];
        $akumulasi  = [];
        $realisasi  = [];

        $sdTable  = (new RabScheduleDetail)->getTable();
        $bobotCol = \Schema::hasColumn($sdTable, 'bobot_mingguan')
            ? 'bobot_mingguan'
            : (\Schema::hasColumn($sdTable, 'bobot') ? 'bobot' : null);

        $grouped = $scheduleDetailQ->orderBy('minggu_ke')->get()->groupBy('minggu_ke');
        $hasScheduleSelected = $grouped->isNotEmpty();

        if ($hasScheduleSelected) {
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

       /* ============================
        PROGRESS (tabel ringkasan)
        ============================ */
        $progressRaw = RabProgress::where('proyek_id', $id)
        ->when($selectedId, fn ($q) => $q->where('penawaran_id', $selectedId))
        ->orderBy('minggu_ke')
        ->orderBy('id') // jaga konsistensi urutan
        ->get();

        $pdTable = (new \App\Models\RabProgressDetail)->getTable();
        $valCol  = \Schema::hasColumn($pdTable, 'bobot_minggu_ini')
                ? 'bobot_minggu_ini'
                : (\Schema::hasColumn($pdTable, 'bobot') ? 'bobot' : null);

        /**
        * 1) Hitung total final per minggu (hanya status final/approved)
        *    -> ini yang dijadikan “dasar akumulasi sebelumnya”.
        */
        // Hindari SUM langsung untuk mengurangi error float: akumulasi manual
        $finalWeekly = [];
        if ($valCol) {
            $finalDetails = \DB::table($pdTable.' as d')
                ->join('rab_progress as p', 'p.id', '=', 'd.rab_progress_id')
                ->where('p.proyek_id', $id)
                ->when($selectedId, fn ($q) => $q->where('p.penawaran_id', $selectedId))
                ->whereIn('p.status', ['final','approved'])
                ->select('p.minggu_ke', "d.$valCol")
                ->get();

            $finalWeeklyInt = [];
            foreach ($finalDetails as $fd) {
                $wk = (int)$fd->minggu_ke;
                $finalWeeklyInt[$wk] = ($finalWeeklyInt[$wk] ?? 0) + (int)round((float)$fd->{$valCol} * 100);
            }
            foreach ($finalWeeklyInt as $wk => $val) {
                $finalWeekly[$wk] = round($val / 100, 2);
            }
        }

        /**
        * 2) Siapkan akumulasi “sebelum minggu X”
        *    -> sum(finalWeekly[w]) untuk semua w < X.
        */
        $weeksSorted = array_keys($finalWeekly);
        sort($weeksSorted, SORT_NUMERIC);
        $cumBefore = []; // minggu => akumulasi s/d < minggu tsb
        $running   = 0.0;
        $lastW     = 0;
        foreach ($weeksSorted as $w) {
        // isi gap minggu yang kosong
        for ($i = $lastW + 1; $i < $w; $i++) {
            if ($i > 0) $cumBefore[$i] = $running;
        }
        // sebelum minggu w masih ‘running’ lama
        if ($w > 0) $cumBefore[$w] = $running;
        // tambahkan kontribusi minggu w (final saja)
        $running += (float) $finalWeekly[$w];
        $lastW = $w;
        }
        // extend untuk minggu setelah max
        for ($i = $lastW + 1; $i <= 10000; $i++) { // batas aman
        $cumBefore[$i] = $running;
        if ($i - $lastW > 2000) break; // hindari loop panjang
        }

        /**
        * 3) Susun ringkasan per baris.
        *    - progress_sebelumnya = akumulasi FINAL s/d minggu < N
        *    - pertumbuhan         = total detail pada progress ini (status apapun)
        *    - progress_saat_ini   = progress_sebelumnya + pertumbuhan (hanya untuk tampilan baris ini)
        */
        $progressSummary = [];
        foreach ($progressRaw as $row) {
        $mingguN = (int) $row->minggu_ke;

        $prev = $cumBefore[$mingguN] ?? 0.0;

        // KOREKSI: Hitung dengan integer arithmetic untuk presisi
        // Ambil semua items, hitung raw total, baru bulatkan
        $deltaThis = 0.0;
        if ($valCol) {
                // JANGAN gunakan SUM dari database, ambil individual values
                $itemDetails = \DB::table($pdTable)->where('rab_progress_id', $row->id)->select($valCol)->get();
                $itemValues = $itemDetails->pluck($valCol)->toArray();
            if (!empty($itemValues)) {
                // Akumulasi dengan integer arithmetic (x100)
                $totInt = 0;
                foreach ($itemValues as $val) {
                    $totInt += (int)round((float)$val * 100);
                }
                $deltaThis = round($totInt / 100, 2);
            }
        }

        $progressSummary[] = [
            'id'                  => $row->id,
            'minggu_ke'           => $mingguN,
            'tanggal'             => $row->tanggal,
            'progress_sebelumnya' => round($prev, 2),
            'pertumbuhan'         => $deltaThis,
            'progress_saat_ini'   => round($prev + $deltaThis, 2),
            'status'              => $row->status,
        ];
        }

        /* ============================
        Kurva-S REALISASI (FINAL saja)
        ============================ */
        $realisasi = [];
        if (!empty($minggu) && $valCol) {
        // kelompokkan per minggu untuk progress berstatus final
            // JANGAN gunakan SUM, ambil individual values dan hitung dengan integer arithmetic
            $finalDetails = \DB::table($pdTable . ' as d')
            ->join('rab_progress as p', 'p.id', '=', 'd.rab_progress_id')
            ->where('p.proyek_id', $id)
            ->when($selectedId, fn ($q) => $q->where('p.penawaran_id', $selectedId))
            ->where('p.status', 'final')
                ->select('p.minggu_ke', "d.$valCol")
                ->get();
        
            $finalWeekly = [];
            foreach ($finalDetails as $row) {
                $w = (int)$row->minggu_ke;
                if (!isset($finalWeekly[$w])) $finalWeekly[$w] = 0;
                $finalWeekly[$w] += (float)$row->{$valCol};
            }

        $cum = 0.0;
        foreach ($minggu as $w) {
            if (isset($finalWeekly[$w])) {
                $cum       += (float) $finalWeekly[$w];
                $realisasi[] = round($cum, 2);
            } else {
                $realisasi[] = null; // tampilkan gap pada minggu tanpa data final
            }
        }
        }

        // ============================
        // META jadwal penawaran terpilih
        // ============================
        $selectedMeta = $selectedId
            ? RabScheduleMeta::where('proyek_id', $id)->where('penawaran_id', $selectedId)->first()
            : null;

        // ============================
        // EVENT KALENDER per item (RabSchedule)
        // ============================
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
                $endExclusive = (clone $end)->addDay();

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
            'finalPenawarans',
            'selectedId',
            'selectedMeta',
            'hasScheduleSelected',
            'minggu',
            'akumulasi',
            'realisasi',
            'calendarEvents',
            'calendarRows'
        ));
    }

    /* =========================
       GENERATE SCHEDULE (existing)
    ========================= */
    public function generateSchedule(Request $request, $proyek_id)
    {
        $data = $request->input('jadwal');

        DB::beginTransaction();
        try {
            $proyek        = Proyek::findOrFail($proyek_id);
            $tanggal_mulai = Carbon::parse($proyek->tanggal_mulai);

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

    /* =========================
       Calendar Events (existing)
    ========================= */
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
                'end'   => (clone $base)->addWeeks($ew)->toDateString(),
                'allDay'=> true,
            ];
        }

        return response()->json($events);
    }

    /* =========================
       Ringkasan Tree (existing)
    ========================= */
    public function scheduleSummaryTree(Proyek $proyek, Request $request)
    {
        $proyekId   = $proyek->id;
        $penawaranId= (int) $request->query('penawaran_id');

        $pickCol = function (string $table, array $cands) {
            foreach ($cands as $c) if (Schema::hasColumn($table, $c)) return $c;
            return null;
        };

        try {
            $schTable   = (new RabSchedule)->getTable();
            $schWeekCol = $pickCol($schTable, ['minggu_ke','week']);
            $schDurCol  = $pickCol($schTable, ['durasi','duration_weeks','lama_minggu']);
            $schFkCol   = $pickCol($schTable, ['rab_penawaran_item_id','penawaran_item_id','rab_detail_id','detail_id']);
            if (!$schWeekCol || !$schDurCol || !$schFkCol) {
                return response('<tr><td colspan="5" class="text-muted py-3 text-center">Struktur kolom jadwal tidak lengkap.</td></tr>', 200);
            }

            $piTable  = (new RabPenawaranItem)->getTable();
            $piDetCol = $pickCol($piTable, ['rab_detail_id','detail_id']);

            $detTable   = (new RabDetail)->getTable();
            $detCodeCol = $pickCol($detTable, ['kode','wbs_kode','kode_wbs','no','nomor']) ?: 'id';
            $detNameCol = $pickCol($detTable, ['uraian','deskripsi','nama','judul']);
            $detNameSel = $detNameCol ? DB::raw("$detNameCol as uraian") : DB::raw("'' as uraian");

            $hdrTable   = (new RabHeader)->getTable();
            $hCodeCol   = $pickCol($hdrTable, ['kode','wbs_kode','kode_wbs','no','nomor']) ?: 'id';
            $hNameCol   = $pickCol($hdrTable, ['uraian','deskripsi','nama','judul']);
            $hNameSel   = $hNameCol ? DB::raw("$hNameCol as uraian") : DB::raw("'' as uraian");

            if (in_array($schFkCol, ['rab_detail_id','detail_id'])) {
                $schedAgg = DB::table("$schTable as sch")
                    ->where('sch.proyek_id', $proyekId)
                    ->when($penawaranId && Schema::hasColumn($schTable,'penawaran_id'),
                        fn($q)=>$q->where('sch.penawaran_id',$penawaranId))
                    ->selectRaw("sch.$schFkCol as did, MIN(sch.$schWeekCol) as minggu_mulai, SUM(sch.$schDurCol) as durasi_total")
                    ->groupBy("sch.$schFkCol")
                    ->get();
            } else {
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

            $aggByDetail = [];
            foreach ($schedAgg as $a) {
                $aggByDetail[(int)$a->did] = [
                    'wmin' => max(1, (int)$a->minggu_mulai),
                    'wdur' => max(1, (int)$a->durasi_total),
                ];
            }

            $meta = RabScheduleMeta::where('proyek_id', $proyekId)
                ->when($penawaranId && Schema::hasColumn((new RabScheduleMeta)->getTable(),'penawaran_id'),
                    fn($q)=>$q->where('penawaran_id',$penawaranId))
                ->first();
            $metaStart = $meta ? Carbon::parse($meta->start_date)->startOfDay() : null;

            $details = RabDetail::where('proyek_id', $proyekId)
                ->whereIn('id', $detailIds)
                ->select('id','rab_header_id', DB::raw("$detCodeCol as kode"), $detNameSel)
                ->get();

            if ($details->isEmpty()) {
                return response('<tr><td colspan="5" class="text-muted py-3 text-center">Detail RAB tidak ditemukan.</td></tr>', 200);
            }

            $byHeader = $details->groupBy('rab_header_id');
            $h2Ids    = $byHeader->keys()->filter()->unique()->values();

            $h2 = RabHeader::whereIn('id', $h2Ids)
                ->select('id','parent_id', DB::raw("$hCodeCol as kode"), $hNameSel)
                ->get();

            $h1Ids = $h2->pluck('parent_id')->filter()->unique()->values();
            $h1 = RabHeader::whereIn('id', $h1Ids)
                ->select('id','parent_id', DB::raw("$hCodeCol as kode"), $hNameSel)
                ->get();

            $hdrNoL2 = $details->filter(fn($d)=>!$h2->contains('id',$d->rab_header_id))
                               ->groupBy('rab_header_id');

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

    /* =========================
       Helpers – Tax Payload
    ========================= */
private function validateTaxPayload(array $tax): array
{
    $v = Validator::make($tax, [
        'is_taxable'     => ['nullable','boolean'],
        // Gunakan 'required_if:is_taxable,1'
        'ppn_mode'       => ['required_if:is_taxable,1', 'in:include,exclude'],
        'ppn_rate'       => ['required_if:is_taxable,1', 'numeric', 'min:0'],
        
        'apply_pph'      => ['nullable','boolean'],
        // Gunakan 'required_if:apply_pph,1'
        'pph_rate'       => ['required_if:apply_pph,1', 'numeric', 'min:0'],
        'pph_base'       => ['required_if:apply_pph,1', 'in:dpp,subtotal'],
        // Sumber DPP PPh (baru): jasa saja atau gabungan material+jasa
        'pph_dpp_source' => ['required_if:apply_pph,1', 'in:jasa,material_jasa'],
        
        'rounding'       => ['required', 'in:HALF_UP,FLOOR,CEIL'],
        'effective_from' => ['nullable', 'date'],
        'effective_to'   => ['nullable', 'date', 'after_or_equal:effective_from'],
        'aktif'          => ['nullable', 'boolean'],
        'extra_options'  => ['nullable', 'string'],
    ]);
    return $v->validate();
}

    private function normalizeTax(array $data): array
    {
        $data['is_taxable'] = (bool)($data['is_taxable'] ?? false);
        $data['apply_pph']  = (bool)($data['apply_pph'] ?? false);
        $data['aktif']      = (bool)($data['aktif'] ?? false);

        if (!$data['is_taxable']) {
            $data['ppn_mode'] = 'exclude';
            $data['ppn_rate'] = 0;
        }
        if (!$data['apply_pph']) {
            $data['pph_rate'] = 0;
        }

        if (isset($data['extra_options'])) {
            $json = trim((string)$data['extra_options']);
            if ($json === '') {
                $data['extra_options'] = null;
            } else {
                try {
                    $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
                    $data['extra_options'] = is_array($decoded) ? $decoded : null;
                } catch (\Throwable $e) {
                    $data['extra_options'] = null;
                }
            }
        }

        // Pastikan extra_options berbentuk array agar bisa menampung opsi baru
        $extra = is_array($data['extra_options'] ?? null) ? $data['extra_options'] : [];

        // Default sumber DPP PPh ke 'jasa' bila apply_pph aktif namun tidak ada input
        if ($data['apply_pph']) {
            $src = $data['pph_dpp_source'] ?? 'jasa';
            $extra['pph_dpp_source'] = in_array($src, ['jasa','material_jasa']) ? $src : 'jasa';
        } else {
            // Jika tidak ada PPh, hapus opsi sumber bila ada untuk menghindari kebingungan
            if (isset($extra['pph_dpp_source'])) {
                unset($extra['pph_dpp_source']);
            }
        }
        $data['extra_options'] = $extra ?: null;

        // Jangan kirim kolom non-schema ke model
        unset($data['pph_dpp_source']);

        return $data;
    }

    /* =========================
       Utilities (existing)
    ========================= */
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
     */
    private function itemWeightMapFromSchedule(int $proyekId, ?int $penawaranId): array
    {
        if (!$penawaranId) return [];

        $sdTable = (new \App\Models\RabScheduleDetail)->getTable();
        $piTable = class_exists(\App\Models\RabPenawaranItem::class)
            ? (new \App\Models\RabPenawaranItem)->getTable()
            : null;

        $col = \Illuminate\Support\Facades\Schema::hasColumn($sdTable, 'bobot_mingguan')
            ? 'bobot_mingguan'
            : (\Illuminate\Support\Facades\Schema::hasColumn($sdTable, 'bobot') ? 'bobot' : null);

        if (!$col) return [];

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

    /* ============================================================
       ======= Helper baru: prev kumulatif FINAL/APPROVED =========
       ============================================================ */
    /**
     * buildPrevBeforeMap:
     *  prevBefore[$week] = total % proyek dari progress berstatus final/approved
     *  kumulatif s/d (week-1).
     */
    private function buildPrevBeforeMap(int $proyekId, ?int $penawaranId): array
    {
        $pTbl  = (new \App\Models\RabProgress)->getTable();        // rab_progress
        $pdTbl = (new \App\Models\RabProgressDetail)->getTable();  // rab_progress_detail

        $sumByWeek = \DB::table("$pdTbl as pd")
            ->join("$pTbl as p", 'p.id', '=', 'pd.rab_progress_id')
            ->where('p.proyek_id', $proyekId)
            ->when($penawaranId, fn($q)=>$q->where('p.penawaran_id', $penawaranId))
            ->whereIn('p.status', ['final','approved'])
            ->groupBy('p.minggu_ke')
            ->orderBy('p.minggu_ke')
            ->pluck(\DB::raw('SUM(pd.bobot_minggu_ini)'), 'p.minggu_ke')
            ->map(fn($v)=>(float)$v)
            ->toArray();

        if (empty($sumByWeek)) return [];

        $maxWeek = (int)max(array_keys($sumByWeek));
        $prevBefore = [];
        $running = 0.0;
        for ($w=1; $w <= $maxWeek+1; $w++) {
            $prevBefore[$w] = $running;
            $running += $sumByWeek[$w] ?? 0.0;
        }
        return $prevBefore;
    }
}

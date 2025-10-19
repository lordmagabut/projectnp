<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Sertifikat Pembayaran</title>

  {{-- ============== QUICK SETTINGS (UBAH DI SINI SAJA) ============== --}}
  @php
    // Margin konten (dipakai sebagai padding .page) — satuan mm
    $P_TOP    = 20;   // mm
    $P_RIGHT  = 14;   // mm
    $P_BOTTOM = 20;   // mm
    $P_LEFT   = 14;   // mm

    // Halaman 1: kontrol jarak & ukuran huruf
    $FS_BODY_P1     = 12;   // px: ukuran huruf halaman 1
    $LINEHEIGHT_P1  = 1.45; // 1.35 - 1.60
    $P_MARGIN_P1    = 8;    // px, jarak antar paragraf
    $LEAD_MARGIN_P1 = 12;   // px, paragraf pembuka (class .lead)
    $META_PAD_P1    = 2;    // px, padding atas/bawah <td> di tabel meta
    $SIGN_MARGIN_P1 = 28;   // px, spasi sebelum blok tanda tangan

    // Halaman 2: lebar kolom & ukuran huruf
    $COL_NO    = 6;
    $COL_KET   = 48;
    $COL_MAT   = 18;
    $COL_JAS   = 18;
    $COL_TOTAL = 18;

    $FS_BODY_P2   = 11.5; // px dasar halaman 2
    $FS_TABLE_P2  = 11;   // px sel & header tabel hal. 2

    // Footer halaman
    $FOOTER_SIZE   = 9;   // pt
    $FOOTER_OFFSET = 28;  // px dari tepi bawah halaman PDF
  @endphp

  <style>
    /* ================== HALAMAN & DASAR ================== */
    @page { size: A4 portrait; margin: 0; }
    html, body { padding:0; margin:0; }
    body {
      font-family: DejaVu Sans, sans-serif;
      color: #111;
      line-height: 1.6;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }
    .page { padding: {{ $P_TOP }}mm {{ $P_RIGHT }}mm {{ $P_BOTTOM }}mm {{ $P_LEFT }}mm; }
    .page-break { page-break-before: always; }

    /* ================== TIPOGRAFI & BLOK ================== */
    .header-block { margin-bottom: 16px; padding-bottom: 6px; border-bottom: 1px solid #bbb; }
    h2 { font-size: 18px; font-weight: 800; text-align: center; margin: 0 0 4px; letter-spacing: .2px; }
    .subtitle { text-align:center; margin: 0; font-weight: 600; font-size: 13px; color:#333; }
    .fw-bold { font-weight:700; }
    p { margin: 0 0 10px; text-align: justify; }
    .lead { margin-bottom: 10px; }

    /* ================== HALAMAN 1: TUNING CEPAT ================== */
    .page-1 { font-size: {{ $FS_BODY_P1 }}px; line-height: {{ $LINEHEIGHT_P1 }}; }
    .page-1 p { margin-bottom: {{ $P_MARGIN_P1 }}px; }
    .page-1 .lead { margin-bottom: {{ $LEAD_MARGIN_P1 }}px; }
    .page-1 .meta td { padding: {{ $META_PAD_P1 }}px 0; }
    .page-1 .sign { margin-top: {{ $SIGN_MARGIN_P1 }}px; }

    /* ================== META ================== */
    .meta { width:100%; border-collapse: collapse; margin: 14px 0; table-layout: fixed; }
    .meta td { vertical-align:top; border: none; }
    .label { width: 20%; color:#444; }
    .sep   { width: 1%;  }
    .val   { width: 79%; word-break: break-word; color: #222; font-weight: 600; }

    /* ================== TABEL RINCIAN ================== */
    .page-2 { font-size: {{ $FS_BODY_P2 }}px; }
    table.tbl { width:100%; border-collapse:collapse; table-layout: fixed; margin-bottom: 18px; border: 1px solid #cfcfcf; }
    .tbl th, .tbl td { border: 1px solid #cfcfcf; padding: 8px 10px; line-height: 1.4; font-size: {{ $FS_TABLE_P2 }}px; }
    .tbl th { background: #f2f2f2; text-align:center; font-weight: 700; letter-spacing:.2px; color:#222; }
    .right  { text-align:right; }
    .center { text-align:center; }
    .money  { font-variant-numeric: tabular-nums; white-space: nowrap; }

    /* Lebar kolom via colgroup + fallback nth-child utk dompdf */
    .col-no    { width: {{ $COL_NO }}%; }
    .col-ket   { width: {{ $COL_KET }}%; }
    .col-mat   { width: {{ $COL_MAT }}%; }
    .col-jas   { width: {{ $COL_JAS }}%; }
    .col-total { width: {{ $COL_TOTAL }}%; }
    .page-2 .tbl th:nth-child(1), .page-2 .tbl td:nth-child(1) { width: {{ $COL_NO }}%; }
    .page-2 .tbl th:nth-child(2), .page-2 .tbl td:nth-child(2) { width: {{ $COL_KET }}%; word-break: break-word; hyphens: auto; }
    .page-2 .tbl th:nth-child(3), .page-2 .tbl td:nth-child(3) { width: {{ $COL_MAT }}%; }
    .page-2 .tbl th:nth-child(4), .page-2 .tbl td:nth-child(4) { width: {{ $COL_JAS }}%; }
    .page-2 .tbl th:nth-child(5), .page-2 .tbl td:nth-child(5) { width: {{ $COL_TOTAL }}%; }

    .subrow td { background: #fafafa; color: #333; }

    /* ================== TANDA TANGAN ================== */
    .sign { width:100%; }
    .sign td { width:50%; vertical-align:top; }
    .who { font-weight:700; margin-bottom: 4px; text-align:center; }
    .org { text-align:center; font-size: 11px; color: #555; }
    .spacer { height: 70px; } /* ruang TT basah */
  </style>
</head>
<body>
@php
  // ====== DATA INTI ======
  $bapp    = $sp->bapp;
  $proyek  = optional($bapp)->proyek;
  $penawar = optional($bapp)->penawaran;

  $nomorSP    = $sp->nomor;
  $namaProyek = $penawar->nama_penawaran ?? '—';
  $tglSP      = \Carbon\Carbon::parse($sp->tanggal)->translatedFormat('d F Y');

  $noPOWO     = $sp->po_wo_spk_no ?? ($proyek->no_spk ?? '-');
  $tglPOWO    = $sp->po_wo_spk_tanggal ?? ($proyek->tanggal_spk ?? null);
  $tglPOWOFmt = $tglPOWO ? \Carbon\Carbon::parse($tglPOWO)->translatedFormat('d F Y') : '-';

  $terminKe   = (int)$sp->termin_ke;
  $pctCum     = (float)$sp->persen_progress;   // kumulatif saat ini

  // ====== CARI KUMULATIF SEBELUMNYA (fallback di VIEW kalau controller belum supply) ======
  $pctPrev = 0.0;
  if (property_exists($sp, 'persen_progress_prev') && $sp->persen_progress_prev !== null) {
      $pctPrev = (float)$sp->persen_progress_prev;
  } else {
      try {
          $prevQuery = \App\Models\SertifikatPembayaran::query()
              ->where('id','!=',$sp->id)
              ->when(optional($sp->bapp)->proyek_id ?? null, function($q,$pid){
                  $q->whereHas('bapp', fn($qq)=> $qq->where('proyek_id',$pid));
              })
              ->when($sp->penerima_tugas_perusahaan, function($q,$perus){
                  $q->where('penerima_tugas_perusahaan',$perus);
              })
              ->where('tanggal','<',$sp->tanggal)
              ->orderBy('tanggal','desc');

          $prevSP = $prevQuery->first();
          if ($prevSP) $pctPrev = (float)$prevSP->persen_progress;
      } catch (\Throwable $e) {
          $pctPrev = 0.0;
      }
  }

  // Delta periode ini
  $pctNowRaw = (property_exists($sp,'persen_progress_delta') && $sp->persen_progress_delta !== null)
                ? (float)$sp->persen_progress_delta
                : ($pctCum - $pctPrev);
  $pctNow = max(0, round($pctNowRaw, 4)); // jaga-jaga tidak negatif

  // Parameter pajak/retensi/UM
  $umPct  = (float)$sp->uang_muka_persen;
  $retPct = (float)$sp->retensi_persen;
  $ppnPct = (float)$sp->ppn_persen;

  // WO (DPP) material/jasa
  $woMat = (float)$sp->nilai_wo_material;
  $woJas = (float)$sp->nilai_wo_jasa;
  $woTot = (float)$sp->nilai_wo_total;

  // UM total kontrak/kolom
  $umMatTotal = round($woMat * $umPct/100, 2);
  $umJasTotal = round($woJas * $umPct/100, 2);

  // Potongan UM PERIODE INI (proporsional delta progress)
  $umCutMat = round($umMatTotal * $pctNow/100, 2);
  $umCutJas = round($umJasTotal * $pctNow/100, 2);

  // DPP (basis PPh) PERIODE INI dari DB bila ada (supaya PDF = acuan pajak)
  $dppM_db = isset($sp->dpp_material) ? (float)$sp->dpp_material : null;
  $dppJ_db = isset($sp->dpp_jasa)     ? (float)$sp->dpp_jasa     : null;

  // Jika DPP DB tidak ada, hitung dari WO×delta% lalu kurangi UM & retensi
  $fallbackPrgMat = round($woMat * $pctNow/100, 2);
  $fallbackPrgJas = round($woJas * $pctNow/100, 2);
  $fallbackRetMat = round($fallbackPrgMat * $retPct/100, 2);
  $fallbackRetJas = round($fallbackPrgJas * $retPct/100, 2);
  $dppMat_fallback = $fallbackPrgMat - $umCutMat - $fallbackRetMat;
  $dppJas_fallback = $fallbackPrgJas - $umCutJas - $fallbackRetJas;

  // DPP yang dipakai
  $subMat = ($dppM_db !== null) ? $dppM_db : $dppMat_fallback;
  $subJas = ($dppJ_db !== null) ? $dppJ_db : $dppJas_fallback;

  // Dari DPP + potongan UM turunkan Progress PERIODE INI → progress = (DPP + UM) / (1 - retensi%)
  $den = max(0.0001, (1 - $retPct/100)); // hindari div/0
  $prgMat = round(($subMat + $umCutMat) / $den, 2);
  $prgJas = round(($subJas + $umCutJas) / $den, 2);

  // Retensi PERIODE INI hasil turunan (agar identitas 2−3=4 pas)
  $retMat = round($prgMat * $retPct/100, 2);
  $retJas = round($prgJas * $retPct/100, 2);

  // PPN dari DPP; rekonsiliasi ke ppn_nilai DB bila perlu
  $ppnMat = round($subMat * $ppnPct/100, 2);
  $ppnJas = round($subJas * $ppnPct/100, 2);
  if (isset($sp->ppn_nilai)) {
      $ppnSum = round($ppnMat + $ppnJas, 2);
      $ppnDb  = round((float)$sp->ppn_nilai, 2);
      if ($ppnSum !== $ppnDb) {
          $delta = round($ppnDb - $ppnSum, 2);
          if ($ppnJas >= $ppnMat) $ppnJas += $delta; else $ppnMat += $delta;
      }
  }

  // Total periode ini, rekonsiliasi dengan total_tagihan DB jika perlu
  $totMat = $subMat + $ppnMat;
  $totJas = $subJas + $ppnJas;
  $totAll_calc = round($totMat + $totJas, 2);
  $totAll_db   = isset($sp->total_tagihan) ? round((float)$sp->total_tagihan, 2) : $totAll_calc;
  if ($totAll_calc !== $totAll_db) {
      $delta = round($totAll_db - $totAll_calc, 2);
      if ($totJas >= $totMat) $totJas += $delta; else $totMat += $delta;
  }

  // Formatter
  $fmt = fn($n)=> number_format((float)$n, 0, ',', '.');
  $pct = fn($n,$d=2)=> rtrim(rtrim(number_format((float)$n,$d,',','.'),'0'),',');
@endphp

<!-- ================= HALAMAN 1 (narasi kumulatif, tampilkan delta) ================= -->
<div class="page page-1">
  <div class="header-block">
    <h2>SERTIFIKAT PEMBAYARAN</h2>
    <div class="subtitle">
      Nomor: {{ $nomorSP }} — {{ $tglSP }} — Progress ke-{{ $terminKe }}
      / Kumulatif {{ $pct($pctCum,2) }}% — Periode ini {{ $pct($pctNow,2) }}%
    </div>
  </div>

  <table class="meta">
    <tr><td class="label">Proyek</td><td class="sep">:</td><td class="val">{{ $namaProyek }}</td></tr>
    <tr><td class="label">Tanggal</td><td class="sep">:</td><td class="val">{{ $tglSP }}</td></tr>
    <tr><td class="label">NO PO / WO / SPK</td><td class="sep">:</td><td class="val">{{ $noPOWO }}</td></tr>
    <tr><td class="label">Termin</td><td class="sep">:</td>
        <td class="val">Kumulatif {{ $pct($pctCum,2) }}% (delta {{ $pct($pctNow,2) }}%)</td>
    </tr>
  </table>

  <p class="lead">
    Pada hari ini {{ \Carbon\Carbon::parse($sp->tanggal)->translatedFormat('l') }}, tanggal {{ $tglSP }},
    kami yang bertanda tangan di bawah ini:
  </p>

  <table class="meta">
    <tr><td class="label">Nama</td><td class="sep">:</td><td class="val">{{ $sp->pemberi_tugas_nama }}</td></tr>
    <tr><td class="label">Perusahaan</td><td class="sep">:</td><td class="val">{{ $sp->pemberi_tugas_perusahaan }}</td></tr>
    <tr><td class="label">Jabatan</td><td class="sep">:</td><td class="val">{{ $sp->pemberi_tugas_jabatan }}</td></tr>
  </table>

  <p>
    Selaku <strong>Pemberi Tugas</strong> (Pihak Pertama), berdasarkan nomor PO / WO / SPK ({{ $noPOWO }}),
    tertanggal ({{ $tglPOWOFmt }}), menyatakan bahwa pekerjaan
    {{ $namaProyek }} – {{ $proyek->pemberiKerja->nama_pemberi_kerja ?? '' }} dilaksanakan oleh:
  </p>

  <table class="meta" style="margin-top:5px;">
    <tr><td class="label">Nama</td><td class="sep">:</td><td class="val">{{ $sp->penerima_tugas_nama }}</td></tr>
    <tr><td class="label">Perusahaan</td><td class="sep">:</td><td class="val">{{ $sp->penerima_tugas_perusahaan }}</td></tr>
    <tr><td class="label">Jabatan</td><td class="sep">:</td><td class="val">{{ $sp->penerima_tugas_jabatan }}</td></tr>
  </table>

  <p>
    Selaku <strong>Penerima Tugas</strong> (Pihak Kedua), berdasarkan Berita Acara Progress Pekerjaan No:
    {{ $bapp->nomor_bapp ?? '-' }} telah mencapai progress pekerjaan kumulatif sebesar
    <strong>{{ $pct($pctCum,2) }}%</strong>.
  </p>

  <p style="margin-top:12px;">
    Berdasarkan data & rincian terlampir, Pihak Kedua berhak menerima pembayaran termin ke-{{ $terminKe }}
    sebesar <span class="money fw-bold" style="font-size: 14px;">Rp.&nbsp;{{ $fmt($sp->total_tagihan ?? ($totMat+$totJas)) }}</span>,
    {{ $sp->terbilang }}.
  </p>

  <p style="margin-top: 14px;">
    Demikian sertifikat pembayaran ini dibuat dengan sesungguhnya untuk digunakan sebagaimana mestinya.
  </p>

  <table class="sign">
    <tr>
      <td>
        <div class="org">{{ $sp->pemberi_tugas_perusahaan }}</div>
        <div class="who">Dibuat Oleh (Pihak Pertama)</div>
        <div class="spacer"></div>
        <div class="center fw-bold" style="border-bottom: 1px solid #333; display: inline-block; padding: 0 10px;">
          {{ $sp->pemberi_tugas_nama }}
        </div>
        <div class="center" style="font-size: 11px;">{{ $sp->pemberi_tugas_jabatan }}</div>
      </td>
      <td>
        <div class="org">{{ $sp->penerima_tugas_perusahaan }}</div>
        <div class="who">Diterima Oleh (Pihak Kedua)</div>
        <div class="spacer"></div>
        <div class="center fw-bold" style="border-bottom: 1px solid #333; display: inline-block; padding: 0 10px;">
          {{ $sp->penerima_tugas_nama }}
        </div>
        <div class="center" style="font-size: 11px;">{{ $sp->penerima_tugas_jabatan }}</div>
      </td>
    </tr>
  </table>
</div>

<!-- ================= HALAMAN 2 (semua angka = PERIODE INI / DELTA) ================= -->
<div class="page-break"></div>

<div class="page page-2">
  <div class="header-block">
    <h2>RINCIAN PERHITUNGAN PEMBAYARAN</h2>
    <div class="subtitle">Sertifikat Pembayaran No: {{ $nomorSP }}</div>
  </div>

  <table class="meta" style="margin-top:6px;">
    <tr><td class="label">Proyek</td><td class="sep">:</td><td class="val">{{ $namaProyek }}</td></tr>
    <tr><td class="label">Tanggal</td><td class="sep">:</td><td class="val">{{ $tglSP }}</td></tr>
    <tr><td class="label">Progress</td><td class="sep">:</td>
        <td class="val">Kumulatif {{ $pct($pctCum,2) }}% — Periode ini {{ $pct($pctNow,2) }}% (Termin ke-{{ $terminKe }})</td>
    </tr>
  </table>

  <table class="tbl">
    <colgroup>
      <col class="col-no"><col class="col-ket"><col class="col-mat"><col class="col-jas"><col class="col-total">
    </colgroup>
    <tr>
      <th>No</th><th>Keterangan</th><th>Material</th><th>Jasa</th><th>Total</th>
    </tr>

    {{-- 1. Nilai kontrak (informasi) --}}
    <tr>
      <td class="center">1</td><td>Nilai PO / WO / SPK (Informasi kontrak)</td>
      <td class="right money">Rp.&nbsp;{{ $fmt($woMat) }}</td>
      <td class="right money">Rp.&nbsp;{{ $fmt($woJas) }}</td>
      <td class="right money">Rp.&nbsp;{{ $fmt($woTot) }}</td>
    </tr>

    {{-- 2. Progress yang DITAGIHKAN PERIODE INI (delta) — dikembalikan dari DPP agar 2−3=4 --}}
    <tr>
      <td class="center">2</td>
      <td>Progress Pekerjaan yang Ditagihkan ({{ $pct($pctNow,2) }}% periode ini)</td>
      <td class="right money">Rp.&nbsp;{{ $fmt($prgMat) }}</td>
      <td class="right money">Rp.&nbsp;{{ $fmt($prgJas) }}</td>
      <td class="right money">Rp.&nbsp;{{ $fmt($prgMat + $prgJas) }}</td>
    </tr>

    {{-- 3. Potongan (UM & Retensi) PERIODE INI --}}
    <tr>
      <td class="center">3</td><td>Pengurangan</td>
      <td colspan="3" style="text-align:left; background:#f6f6f6; font-style: italic;">(Rincian Potongan Periode Ini)</td>
    </tr>
    <tr class="subrow">
      <td></td><td style="padding-left:22px">Pemotongan Uang Muka (proporsional {{ $pct($pctNow,2) }}% dari UM kontrak)</td>
      <td class="right money">Rp.&nbsp;{{ $fmt($umCutMat) }}</td>
      <td class="right money">Rp.&nbsp;{{ $fmt($umCutJas) }}</td>
      <td class="right money">Rp.&nbsp;{{ $fmt($umCutMat + $umCutJas) }}</td>
    </tr>
    <tr class="subrow">
      <td></td><td style="padding-left:22px">Retensi {{ $pct($retPct,2) }}% dari Progress periode ini</td>
      <td class="right money">Rp.&nbsp;{{ $fmt($retMat) }}</td>
      <td class="right money">Rp.&nbsp;{{ $fmt($retJas) }}</td>
      <td class="right money">Rp.&nbsp;{{ $fmt($retMat + $retJas) }}</td>
    </tr>

    {{-- 4. Nilai Dasar Tagihan (DPP) PERIODE INI --}}
    <tr>
      <td class="center">4</td><td class="fw-bold">Nilai Dasar Tagihan (2 − 3)</td>
      <td class="right money fw-bold">Rp.&nbsp;{{ $fmt($subMat) }}</td>
      <td class="right money fw-bold">Rp.&nbsp;{{ $fmt($subJas) }}</td>
      <td class="right money fw-bold">Rp.&nbsp;{{ $fmt($subMat + $subJas) }}</td>
    </tr>

    {{-- 5. PPN PERIODE INI --}}
    <tr>
      <td class="center">5</td><td>Pajak (PPN {{ $pct($ppnPct,2) }}%)</td>
      <td class="right money">Rp.&nbsp;{{ $fmt($ppnMat) }}</td>
      <td class="right money">Rp.&nbsp;{{ $fmt($ppnJas) }}</td>
      <td class="right money">Rp.&nbsp;{{ $fmt($ppnMat + $ppnJas) }}</td>
    </tr>

    {{-- 6. TOTAL PERIODE INI --}}
    <tr class="subrow">
      <td class="center fw-bold" style="background:#eee;">6</td>
      <td class="fw-bold" style="background:#eee;">TOTAL YANG DIBAYARKAN (4 + 5) — PERIODE INI</td>
      <td class="right money fw-bold" style="background:#eee;">Rp.&nbsp;{{ $fmt($subMat + $ppnMat) }}</td>
      <td class="right money fw-bold" style="background:#eee;">Rp.&nbsp;{{ $fmt($subJas + $ppnJas) }}</td>
      <td class="right money fw-bold" style="background:#eee;">Rp.&nbsp;{{ $fmt(($subMat + $ppnMat) + ($subJas + $ppnJas)) }}</td>
    </tr>
  </table>

  <table class="sign">
    <tr>
      <td>
        <div class="who">Dibuat Oleh</div>
        <div class="org">{{ $sp->pemberi_tugas_perusahaan }}</div>
        <div class="spacer"></div>
        <div class="center fw-bold" style="border-bottom: 1px solid #333; display: inline-block; padding: 0 10px;">
          {{ $sp->pemberi_tugas_nama }}
        </div>
        <div class="center" style="font-size: 11px;">{{ $sp->pemberi_tugas_jabatan }}</div>
      </td>
      <td>
        <div class="who">Diterima Oleh</div>
        <div class="org">{{ $sp->penerima_tugas_perusahaan }}</div>
        <div class="spacer"></div>
        <div class="center fw-bold" style="border-bottom: 1px solid #333; display: inline-block; padding: 0 10px;">
          {{ $sp->penerima_tugas_nama }}
        </div>
        <div class="center" style="font-size: 11px;">{{ $sp->penerima_tugas_jabatan }}</div>
      </td>
    </tr>
  </table>
</div>

{{-- ===== FOOTER NOMOR HALAMAN (aman) ===== --}}
<script type="text/php">
if (isset($pdf)) {
    $font  = $fontMetrics->get_font("DejaVu Sans", "normal");
    $size  = {{ $FOOTER_SIZE }};
    $color = [0.2, 0.2, 0.2];

    $w = $pdf->get_width();
    $h = $pdf->get_height();

    $text = "Halaman {PAGE_NUM} / {PAGE_COUNT}";
    $text_width = $fontMetrics->get_text_width($text, $font, $size);

    $x = ($w - $text_width) / 2;
    $y = $h - {{ $FOOTER_OFFSET }};

    $pdf->page_text($x, $y, $text, $font, $size, $color);
}
</script>
</body>
</html>

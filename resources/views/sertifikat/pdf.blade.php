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
    .sign td { width:50%; vertical-align:top; text-align:center; } /* <= fix: pastikan center di kolom */
    .who { font-weight:700; margin-bottom: 4px; }
    .org { font-size: 11px; color: #555; }
    .spacer { height: 70px; } /* ruang TT basah */
    .sig-line { display:inline-block; border-bottom:1px solid #333; padding:0 10px; font-weight:700; }
  </style>
</head>
<body>
@php
  // ====== DATA INTI ======
  $bapp    = $sp->bapp;
  $proyek  = optional($bapp)->proyek;
  $penawar = optional($bapp)->penawaran;
  $umPenj  = $sp->uangMukaPenjualan;

  $nomorSP    = $sp->nomor;
  $namaProyek = $penawar->nama_penawaran ?? '—';
  $tglSP      = \Carbon\Carbon::parse($sp->tanggal)->translatedFormat('d F Y');

  $noPOWO     = $sp->po_wo_spk_no ?? ($proyek->no_spk ?? '-');
  $tglPOWO    = $sp->po_wo_spk_tanggal ?? ($proyek->tanggal_spk ?? null);
  $tglPOWOFmt = $tglPOWO ? \Carbon\Carbon::parse($tglPOWO)->translatedFormat('d F Y') : '-';

  $terminKe   = (int)$sp->termin_ke;
  $pctCum     = (float)$sp->persen_progress;   // kumulatif saat ini

  $umMode     = strtolower(optional($proyek)->uang_muka_mode ?? 'proporsional');

  // ====== CARI KUMULATIF SEBELUMNYA (fallback di VIEW kalau controller belum supply) ======
    $pctPrev = 0.0;
    if (isset($sp->persen_progress_prev)) {
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
  $pctNowRaw = isset($sp->persen_progress_delta)
                ? (float)$sp->persen_progress_delta
                : ($pctCum - $pctPrev);
  $pctNow = max(0, round($pctNowRaw, 4)); // jaga-jaga tidak negatif

  // Parameter pajak/retensi/UM (UM dari Penjualan, turunkan % dari nominal jika perlu)
  $umTotal = isset($sp->uang_muka_nilai) ? (float)$sp->uang_muka_nilai : 0.0;
  if ($umTotal <= 0 && $umPenj) {
    $umTotal = (float)$umPenj->nominal;
  }
  $woTotSafe = (float)$sp->nilai_wo_total ?: 0.0001;
  $umPct  = $woTotSafe > 0 ? round($umTotal / $woTotSafe * 100, 4) : 0;

  $retPct = (float)$sp->retensi_persen;
  $ppnPct = (float)$sp->ppn_persen;

  // WO (DPP) material/jasa
  $woMat = (float)$sp->nilai_wo_material;
  $woJas = (float)$sp->nilai_wo_jasa;
  $woTot = (float)$sp->nilai_wo_total;

  // UM total per komponen & rasio
  $umMatTotal = round($woMat * $umPct/100, 2);
  $umJasTotal = round($woJas * $umPct/100, 2);
  $ratioM = $umTotal > 0 ? round($umMatTotal / $umTotal, 6) : 0.0;
  $ratioJ = $umTotal > 0 ? round($umJasTotal / $umTotal, 6) : 0.0;

  // Check toggle uang muka & retensi dari proyek
  $proyekPdf = optional($sp->bapp)->proyek;
  $gunakanUM_pdf = (bool)($proyekPdf->gunakan_uang_muka ?? false);
  $gunakanRetensi_pdf = (bool)($proyekPdf->gunakan_retensi ?? false);

  // Pemotongan UM PERIODE INI ambil dari DB, bagi sesuai rasio
  $umCutTotal = ($gunakanUM_pdf && isset($sp->pemotongan_um_nilai)) ? (float)$sp->pemotongan_um_nilai : 0.0;
  $umCutMat = round($umCutTotal * $ratioM, 2);
  $umCutJas = round($umCutTotal * $ratioJ, 2);

  // DPP (basis PPh) PERIODE INI dari DB bila ada (supaya PDF = acuan pajak)
  $dppM_db = isset($sp->dpp_material) ? (float)$sp->dpp_material : null;
  $dppJ_db = isset($sp->dpp_jasa)     ? (float)$sp->dpp_jasa     : null;

  // Jika DPP DB tidak ada, fallback gunakan nilai_progress_rp (delta rupiah) dibagi proporsi WO
  $fallbackPrgMat = isset($sp->nilai_progress_rp) ? round((float)$sp->nilai_progress_rp * ($woTotSafe > 0 ? $woMat/$woTotSafe : 0), 2) : round($woMat * $pctNow/100, 2);
  $fallbackPrgJas = isset($sp->nilai_progress_rp) ? round((float)$sp->nilai_progress_rp * ($woTotSafe > 0 ? $woJas/$woTotSafe : 0), 2) : round($woJas * $pctNow/100, 2);
  $fallbackRetMat = $gunakanRetensi_pdf ? round($fallbackPrgMat * $retPct/100, 2) : 0;
  $fallbackRetJas = $gunakanRetensi_pdf ? round($fallbackPrgJas * $retPct/100, 2) : 0;
  $dppMat_fallback = $fallbackPrgMat - $umCutMat - $fallbackRetMat;
  $dppJas_fallback = $fallbackPrgJas - $umCutJas - $fallbackRetJas;

  // DPP yang dipakai
  $subMat = ($dppM_db !== null) ? $dppM_db : $dppMat_fallback;
  $subJas = ($dppJ_db !== null) ? $dppJ_db : $dppJas_fallback;

  // Progress PERIODE INI: gunakan nilai dari DB (nilai_progress_rp sudah benar = delta)
  // Jangan hitung balik dari DPP untuk menghindari kesalahan pembulatan
  $prgMat = $fallbackPrgMat;
  $prgJas = $fallbackPrgJas;

  // Retensi PERIODE INI dari progress periode ini
  $retMat = $gunakanRetensi_pdf ? round($prgMat * $retPct/100, 2) : 0;
  $retJas = $gunakanRetensi_pdf ? round($prgJas * $retPct/100, 2) : 0;

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

    // ===== PPh (dipotong) berdasarkan profil pajak proyek =====
    $tax         = optional($proyek->taxProfileAktif);
    $applyPph    = (int)($tax->apply_pph ?? 0) === 1;
    $pphRate     = (float)($tax->pph_rate ?? 0);
    $pphBaseKind = (string)($tax->pph_base ?? 'dpp'); // 'dpp' | 'subtotal'
    $extraOpts   = is_array($tax->extra_options ?? null) ? $tax->extra_options : [];
    $pphSource   = (string)($extraOpts['pph_dpp_source'] ?? 'jasa'); // 'jasa' | 'material_jasa'
    $pphDipungut = ($proyekPdf->pph_dipungut ?? 'ya') === 'ya';

    $pphMat = 0.0; $pphJas = 0.0;
    if ($applyPph && $pphRate > 0 && $pphDipungut) {
      if ($pphSource === 'material_jasa') {
        $baseM = ($pphBaseKind === 'dpp') ? $subMat : $prgMat;
        $baseJ = ($pphBaseKind === 'dpp') ? $subJas : $prgJas;
        $pphMat = round($baseM * $pphRate/100, 2);
        $pphJas = round($baseJ * $pphRate/100, 2);
      } else { // jasa saja
        $baseJ = ($pphBaseKind === 'dpp') ? $subJas : $prgJas;
        $pphMat = 0.0;
        $pphJas = round($baseJ * $pphRate/100, 2);
      }
    }

    $netMat = $totMat - $pphMat;
    $netJas = $totJas - $pphJas;
    $netAll = round($netMat + $netJas, 2);

  // Formatter
  $fmt = fn($n)=> number_format((float)$n, 0, ',', '.');
  $pct = fn($n,$d=2)=> rtrim(rtrim(number_format((float)$n,$d,',','.'),'0'),',');

  // Generate terbilang dari netAll (nilai yang benar-benar ditampilkan)
  function terbilangRupiah($angka) {
      $angka = abs($angka);
      $huruf = ['', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas'];
      if ($angka < 12) return $huruf[$angka];
      if ($angka < 20) return terbilangRupiah($angka - 10) . ' Belas';
      if ($angka < 100) return terbilangRupiah($angka / 10) . ' Puluh ' . terbilangRupiah($angka % 10);
      if ($angka < 200) return 'Seratus ' . terbilangRupiah($angka - 100);
      if ($angka < 1000) return terbilangRupiah($angka / 100) . ' Ratus ' . terbilangRupiah($angka % 100);
      if ($angka < 2000) return 'Seribu ' . terbilangRupiah($angka - 1000);
      if ($angka < 1000000) return terbilangRupiah($angka / 1000) . ' Ribu ' . terbilangRupiah($angka % 1000);
      if ($angka < 1000000000) return terbilangRupiah($angka / 1000000) . ' Juta ' . terbilangRupiah($angka % 1000000);
      if ($angka < 1000000000000) return terbilangRupiah($angka / 1000000000) . ' Milyar ' . terbilangRupiah(fmod($angka, 1000000000));
      return terbilangRupiah($angka / 1000000000000) . ' Triliun ' . terbilangRupiah(fmod($angka, 1000000000000));
  }
  $terbilangPDF = trim(terbilangRupiah($netAll));

  // UM Penjualan audit info
  $umAfter  = isset($sp->sisa_uang_muka) ? (float)$sp->sisa_uang_muka : ($umPenj ? $umPenj->getSisaUangMuka() : 0);
  $umCutNow = $umCutTotal;
  $umBefore = max(0, round($umAfter + $umCutNow, 2));
  $umModeLabel = strtoupper($umMode);
  $umCutPct = ($umMode === 'utuh') ? 100 : (isset($sp->pemotongan_um_persen) ? (float)$sp->pemotongan_um_persen : $pctCum);
  
  // ==== FINAL ACCOUNT DATA ====
  $isFinalAccount = optional($bapp)->is_final_account ?? false;
  $nilaiAkhir = $isFinalAccount ? ((float)optional($bapp)->nilai_realisasi_total) : 0.0;
  
  // Hitung total progress sebelumnya untuk breakdown
  $prevNilaiProgressTotal = 0.0;
  if ($isFinalAccount && optional($bapp)->penawaran_id) {
    $prevNilaiProgressTotal = \App\Models\SertifikatPembayaran::query()
      ->where('penawaran_id', $bapp->penawaran_id)
      ->where('termin_ke', '<', $terminKe)
      ->sum('nilai_progress_rp');
  }
@endphp

<!-- ================= HALAMAN 1 (narasi kumulatif, tampilkan delta) ================= -->
<div class="page page-1">
  <div class="header-block">
    <h2>SERTIFIKAT PEMBAYARAN</h2>
    <div class="subtitle">
      Nomor: {{ $nomorSP }} — {{ $tglSP }} — Progress ke-{{ $terminKe }}
      @if($isFinalAccount)
        / FINAL ACCOUNT
      @else
        / Kumulatif {{ $pct($pctCum,2) }}% — Periode ini {{ $pct($pctNow,2) }}%
      @endif
    </div>
  </div>

  <table class="meta">
    <tr><td class="label">Proyek</td><td class="sep">:</td><td class="val">{{ $namaProyek }}</td></tr>
    <tr><td class="label">Tanggal</td><td class="sep">:</td><td class="val">{{ $tglSP }}</td></tr>
    <tr><td class="label">NO PO / WO / SPK</td><td class="sep">:</td><td class="val">{{ $noPOWO }}</td></tr>
    <tr><td class="label">Termin</td><td class="sep">:</td>
        <td class="val">
          @if($isFinalAccount)
            FINAL ACCOUNT (Termin ke-{{ $terminKe }})
          @else
            Kumulatif {{ $pct($pctCum,2) }}% (periode ini {{ $pct($pctNow,2) }}%)
          @endif
        </td>
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
    sebesar <span class="money fw-bold" style="font-size: 14px;">Rp.&nbsp;{{ $fmt($netAll) }}</span>,
    {{ $terbilangPDF }} Rupiah.
  </p>

  <p style="margin-top: 14px;">
    Demikian sertifikat pembayaran ini dibuat dengan sesungguhnya untuk digunakan sebagaimana mestinya.
  </p>

  <!-- (opsional) jika ingin blok tanda tangan di halaman 1, aktifkan bagian di bawah -->

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
        <td class="val">
          @if($isFinalAccount)
            FINAL ACCOUNT (Termin ke-{{ $terminKe }})
          @else
            Kumulatif {{ $pct($pctCum,2) }}% — Periode ini {{ $pct($pctNow,2) }}% (Termin ke-{{ $terminKe }})
          @endif
        </td>
    </tr>
  </table>

  @if($isFinalAccount)
  {{-- BREAKDOWN DETAIL FINAL ACCOUNT dengan Pendekatan Rupiah --}}
  <div style="background:#f0f8ff; border: 2px solid #2196F3; padding:14px; margin:16px 0; border-radius:4px;">
    <h3 style="margin:0 0 10px; font-size:14px; font-weight:700; color:#1976D2; text-align:center;">
      <span style="background:#2196F3; color:white; padding:4px 12px; border-radius:3px;">📊 FINAL ACCOUNT - BREAKDOWN DETAIL RUPIAH</span>
    </h3>
    <table style="width:100%; border-collapse:collapse; font-size:11.5px;">
      <tr>
        <td style="padding:6px 0; width:5%; vertical-align:top;">1.</td>
        <td style="padding:6px 0; width:55%;"><strong>Nilai Akhir Pekerjaan</strong> (Kontrak + Addendum + Adjustment)</td>
        <td style="padding:6px 0; width:40%; text-align:right; font-weight:700; color:#1976D2;">
          Rp.&nbsp;{{ $fmt($nilaiAkhir) }}
        </td>
      </tr>
      <tr>
        <td style="padding:6px 0; vertical-align:top;">2.</td>
        <td style="padding:6px 0;">Dikurangi: Total yang Sudah Ditagih Sebelumnya</td>
        <td style="padding:6px 0; text-align:right; color:#d32f2f;">
          - Rp.&nbsp;{{ $fmt($prevNilaiProgressTotal) }}
        </td>
      </tr>
      <tr style="border-top:1px dashed #bbb;">
        <td style="padding:6px 0; vertical-align:top;">3.</td>
        <td style="padding:6px 0;"><strong>Sisa yang Belum Ditagih</strong> (1 - 2)</td>
        <td style="padding:6px 0; text-align:right; font-weight:700;">
          Rp.&nbsp;{{ $fmt($nilaiAkhir - $prevNilaiProgressTotal) }}
        </td>
      </tr>
      <tr>
        <td style="padding:6px 0; vertical-align:top;">4.</td>
        <td style="padding:6px 0;">Dikurangi: Uang Muka Periode Ini ({{ $pct($umCutPct,2) }}%)</td>
        <td style="padding:6px 0; text-align:right; color:#d32f2f;">
          - Rp.&nbsp;{{ $fmt($umCutTotal) }}
        </td>
      </tr>
      <tr style="border-top:1px dashed #bbb;">
        <td style="padding:6px 0; vertical-align:top;">5.</td>
        <td style="padding:6px 0;"><strong>Nilai Progress Periode Ini</strong> (3 - 4)</td>
        <td style="padding:6px 0; text-align:right; font-weight:700; color:#0277BD;">
          Rp.&nbsp;{{ $fmt(max(0, $nilaiAkhir - $prevNilaiProgressTotal - $umCutTotal)) }}
        </td>
      </tr>
      <tr>
        <td style="padding:6px 0; vertical-align:top;">6.</td>
        <td style="padding:6px 0;">Dikurangi: Retensi {{ $pct($retPct,2) }}% dari Progress</td>
        <td style="padding:6px 0; text-align:right; color:#d32f2f;">
          - Rp.&nbsp;{{ $fmt($retMat + $retJas) }}
        </td>
      </tr>
      <tr style="border-top:2px solid #1976D2; background:#e3f2fd;">
        <td style="padding:8px 0; vertical-align:top;"><strong>7.</strong></td>
        <td style="padding:8px 0;"><strong style="font-size:12px;">NILAI TAGIHAN (DPP) Periode Ini</strong> (5 - 6)</td>
        <td style="padding:8px 0; text-align:right; font-weight:700; font-size:13px; color:#0D47A1;">
          Rp.&nbsp;{{ $fmt($subMat + $subJas) }}
        </td>
      </tr>
    </table>
    <div style="margin-top:8px; padding:8px; background:#fff3cd; border-left:4px solid #ff9800; font-size:10.5px; color:#856404;">
      <strong>ℹ️ Catatan:</strong> Breakdown ini menunjukkan perhitungan detail dengan pendekatan rupiah karena BAPP ini adalah <strong>Final Account</strong> dengan realisasi berbeda dari kontrak.
    </div>
  </div>
  @endif

  @if(!$isFinalAccount)
  {{-- Tabel Rincian untuk BAPP Normal (bukan Final Account) --}}
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
      <td></td>
      <td style="padding-left:22px">Pemotongan Uang Muka {{ $pct($umCutPct,2) }}%</td>
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

    {{-- 5. PPN PERIODE INI (hanya jika ada PPN) --}}
    @if($ppnPct > 0)
    <tr>
      <td class="center">5</td><td>Pajak (PPN {{ $pct($ppnPct,2) }}%)</td>
      <td class="right money">Rp.&nbsp;{{ $fmt($ppnMat) }}</td>
      <td class="right money">Rp.&nbsp;{{ $fmt($ppnJas) }}</td>
      <td class="right money">Rp.&nbsp;{{ $fmt($ppnMat + $ppnJas) }}</td>
    </tr>
    @endif

    {{-- 6. TOTAL + PPN PERIODE INI --}}
    <tr class="subrow">
      <td class="center fw-bold" style="background:#eee;">{{ $ppnPct > 0 ? '6' : '5' }}</td>
      <td class="fw-bold" style="background:#eee;">{{ $ppnPct > 0 ? 'TOTAL + PPN (4 + 5)' : 'TOTAL TAGIHAN (sama dengan 4)' }} — PERIODE INI</td>
      <td class="right money fw-bold" style="background:#eee;">Rp.&nbsp;{{ $fmt($totMat) }}</td>
      <td class="right money fw-bold" style="background:#eee;">Rp.&nbsp;{{ $fmt($totJas) }}</td>
      <td class="right money fw-bold" style="background:#eee;">Rp.&nbsp;{{ $fmt($totMat + $totJas) }}</td>
    </tr>

    {{-- 7. PPh PERIODE INI (dipotong) - hanya jika dipungut --}}
    @if($pphDipungut)
    <tr>
      <td class="center">{{ $ppnPct > 0 ? '7' : '6' }}</td>
      <td>PPh {{ $pphRate > 0 ? (rtrim(rtrim(number_format($pphRate,3,',','.'),'0'),',')) : '0' }}% — Sumber {{ $pphSource === 'material_jasa' ? 'Material + Jasa' : 'Jasa saja' }}, Basis {{ strtoupper($pphBaseKind) }}</td>
      <td class="right money">- Rp.&nbsp;{{ $fmt($pphMat) }}</td>
      <td class="right money">- Rp.&nbsp;{{ $fmt($pphJas) }}</td>
      <td class="right money">- Rp.&nbsp;{{ $fmt($pphMat + $pphJas) }}</td>
    </tr>
    @endif

    {{-- 8. TOTAL NETT PERIODE INI --}}
    <tr class="subrow">
      @php
        $lastRowNum = ($ppnPct > 0 ? 6 : 5) + ($pphDipungut ? 1 : 0) + 1;
        $prevRowNum = $lastRowNum - 1;
        $formulaLabel = $pphDipungut ? "($prevRowNum - 1)" : "sama dengan $prevRowNum";
      @endphp
      <td class="center fw-bold" style="background:#eee;">{{ $lastRowNum }}</td>
      <td class="fw-bold" style="background:#eee;">TOTAL DIBAYARKAN {{ $pphDipungut ? '(' . ($ppnPct > 0 ? '6' : '5') . ' − ' . ($ppnPct > 0 ? '7' : '6') . ')' : '(sama dengan ' . ($ppnPct > 0 ? '6' : '5') . ')' }} — PERIODE INI</td>
      <td class="right money fw-bold" style="background:#eee;">Rp.&nbsp;{{ $fmt($netMat) }}</td>
      <td class="right money fw-bold" style="background:#eee;">Rp.&nbsp;{{ $fmt($netJas) }}</td>
      <td class="right money fw-bold" style="background:#eee;">Rp.&nbsp;{{ $fmt($netAll) }}</td>
    </tr>
  </table>
  @endif

  <table class="sign">
    <tr>
      <td>
        <div class="who">Dibuat Oleh</div>
        <div class="org">{{ $sp->pemberi_tugas_perusahaan }}</div>
        <div class="spacer"></div>
        <span class="sig-line">{{ $sp->pemberi_tugas_nama }}</span>
        <div class="org" style="margin-top:4px;">{{ $sp->pemberi_tugas_jabatan }}</div>
      </td>
      <td>
        <div class="who">Diterima Oleh</div>
        <div class="org">{{ $sp->penerima_tugas_perusahaan }}</div>
        <div class="spacer"></div>
        <span class="sig-line">{{ $sp->penerima_tugas_nama }}</span>
        <div class="org" style="margin-top:4px;">{{ $sp->penerima_tugas_jabatan }}</div>
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

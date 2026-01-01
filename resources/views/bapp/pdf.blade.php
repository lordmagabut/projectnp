@php
  use App\Models\RabHeader;
  use Illuminate\Support\Facades\Schema;

  $fmt = fn($n)=>number_format((float)$n, 2, ',', '.');

  // helper pilih kolom nama yang tersedia
  $pick = function(string $table, array $cands){
    foreach ($cands as $c) if (Schema::hasColumn($table,$c)) return $c;
    return null;
  };

  // ambil nama header/subheader RabHeader berdasar kode (“2”, “2.1”, …)
  $hdrTbl     = class_exists(RabHeader::class) ? (new RabHeader)->getTable() : null;
  $hdrNameCol = $hdrTbl ? ($pick($hdrTbl, ['uraian','deskripsi','nama','judul','title']) ?? null) : null;

  $nameCache = [];
  $headerName = function(string $kode, int $proyekId) use (&$nameCache,$hdrTbl,$hdrNameCol) {
    if (isset($nameCache[$kode])) return $nameCache[$kode];
    if (!$hdrTbl || !$hdrNameCol) return $nameCache[$kode] = null;
    $row = RabHeader::where('proyek_id',$proyekId)->where('kode',$kode)->select($hdrNameCol)->first();
    return $nameCache[$kode] = ($row->$hdrNameCol ?? null);
  };

  // data & total
  $items = $bapp->details->sortBy('kode', SORT_NATURAL)->values();
  // pakai integer scaling untuk hindari drift (nilai x100)
  $totWiInt = $totPrevInt = $totDeltaInt = $totNowInt = 0;
  $totWi = $totPrev = $totDelta = $totNow = 0.0;

    $pemberiKerja = optional($proyek->pemberiKerja)->nama_pemberi_kerja ?? 'Pemberi Kerja';
    $pemberiKerjaPic = optional($proyek->pemberiKerja)->pic ?? '________________';
    $signBy = $bapp->sign_by ?? 'sm';
    $dibuatOleh = $signBy === 'pm'
    ? ($proyek->project_manager_name ?: (auth()->user()->name ?? '________________'))
    : ($proyek->site_manager_name ?: (auth()->user()->name ?? '________________'));
  $dibuatJabatan = $signBy === 'pm' ? 'Project Manager' : 'Site Manager';
@endphp
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>BAPP {{ $bapp->nomor_bapp }}</title>
  <style>
    @page { margin: 14mm 12mm; size: A4 landscape; }
    body  { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color:#222; }
    .header { border-bottom:2px solid #444; padding-bottom:8px; margin-bottom:12px; display:flex; gap:12px; align-items:center; }
    .logo   { width:60px; height:60px; object-fit:contain }
    .title  { font-size:16px; font-weight:700 }
    .subtitle{ font-size:12px; margin-top:2px; color:#666 }
    .meta   { width:100%; font-size:10.5px; margin-top:6px }
    .meta td:first-child{ width:22% }

    table.tbl{ width:100%; border-collapse:collapse; }
    .tbl th,.tbl td{ border:1px solid #9aa0a6; padding:6px 6px; }
    .tbl thead th{ background:#f1f3f4; font-weight:700; text-align:center; }
    .center{ text-align:center; }
    .right{ text-align:right; }
    .nowrap{ white-space:nowrap; }

    .row-area   { background:#e8f0fe; font-weight:700; }
    .row-header { background:#f6f9fe; font-weight:700; }
    .row-total  { background:#f1f3f4; font-weight:700; }

    .sign { width:100%; margin-top:18px; }
    .sign td { text-align:center; padding-top:4mm; vertical-align:top; }
    .sign .box { height:22mm; } /* ruang tanda tangan */
    .foot { position: fixed; bottom: -8mm; left:0; right:0; text-align:right; font-size:9px; color:#777; }
  </style>
</head>
<body>

  {{-- Header --}}
  <div class="header">
    @if(optional($proyek)->logo_path)
      <img class="logo" src="{{ public_path('storage/'.$proyek->logo_path) }}" alt="logo">
    @endif
    <div style="flex:1">
      <div class="title">BERITA ACARA PROGRESS PEKERJAAN (BAPP)</div>
      <div class="subtitle">Nomor: <strong>{{ $bapp->nomor_bapp }}</strong></div>
      <table class="meta" cellpadding="0" cellspacing="0">
        <tr><td>Proyek</td>       <td>: <strong>{{ $proyek->nama_proyek }}</strong></td></tr>
        <tr><td>Pemberi Kerja</td><td>: {{ $pemberiKerja }}</td></tr>
        <tr><td>Penawaran</td>    <td>: {{ optional($bapp->penawaran)->nama_penawaran ?? '—' }}</td></tr>
        <tr><td>Minggu</td>       <td>: Minggu ke-{{ $bapp->minggu_ke }} (Tanggal: {{ \Carbon\Carbon::parse($bapp->tanggal_bapp)->format('d-m-Y') }})</td></tr>
      </table>
    </div>
  </div>

  {{-- Tabel --}}
  <table class="tbl">
    <thead>
      <tr>
        <th style="width:10%">Kode</th>
        <th>Uraian</th>
        {{-- Bobot = % proyek (tanpa “%”) --}}
        <th style="width:9%">Bobot Item</th>
        <th style="width:11%">Bobot s/d Minggu Lalu</th>
        <th style="width:10%">Δ Bobot Minggu Ini</th>
        <th style="width:10%">Bobot Saat Ini</th>
        {{-- Progress = % terhadap item (dengan “%”) --}}
        <th style="width:11%">Prog. s/d Minggu Lalu</th>
        <th style="width:10%">Prog. Minggu Ini</th>
        <th style="width:10%">Prog. Saat Ini</th>
      </tr>
    </thead>
    <tbody>
      @php $currArea=null; $currHeader=null; @endphp
      @foreach($items as $it)
        @php
          // akumulasi integer
          $totWiInt    += (int) round(((float)$it->bobot_item) * 100);
          $totPrevInt  += (int) round(((float)$it->prev_pct) * 100);
          $totDeltaInt += (int) round(((float)$it->delta_pct) * 100);
          $totNowInt   += (int) round(((float)$it->now_pct) * 100);

          $kode  = trim((string)$it->kode);
          $parts = $kode !== '' ? preg_split('/\s*\.\s*/', $kode) : [];
          $areaCode   = $parts[0] ?? null;                           // "2"
          $headerCode = isset($parts[1]) ? $parts[0].'.'.$parts[1] : null; // "2.1"
        @endphp

        {{-- Baris AREA (span sampai ujung) --}}
        @if($areaCode && $areaCode !== $currArea)
          @php
            $currArea = $areaCode; $currHeader = null;
            $areaName = $headerName($areaCode, $bapp->proyek_id) ?? ('Pekerjaan '.$areaCode);
          @endphp
          <tr class="row-area">
            <td class="nowrap">{{ $areaCode }}</td>
            <td colspan="8">{{ strtoupper($areaName) }}</td>
          </tr>
        @endif

        {{-- Baris HEADER (span sampai ujung) --}}
        @if($headerCode && $headerCode !== $currHeader)
          @php
            $currHeader = $headerCode;
            $hdrName = $headerName($headerCode, $bapp->proyek_id) ?? ('Header '.$headerCode);
          @endphp
          <tr class="row-header">
            <td class="nowrap">{{ $headerCode }}</td>
            <td colspan="8">{{ $hdrName }}</td>
          </tr>
        @endif

        {{-- Baris ITEM --}}
        <tr>
          <td class="nowrap">{{ $it->kode }}</td>
          <td>{{ $it->uraian }}</td>

          {{-- Bobot (angka/desimal, % proyek) --}}
          <td class="right">{{ $fmt($it->bobot_item) }}</td>
          <td class="right">{{ $fmt($it->prev_pct) }}</td>
          <td class="right">{{ $fmt($it->delta_pct) }}</td>
          <td class="right">{{ $fmt($it->now_pct) }}</td>

          {{-- Progress (% terhadap item) --}}
          <td class="right">{{ $fmt($it->prev_item_pct) }} %</td>
          <td class="right">{{ $fmt($it->delta_item_pct) }} %</td>
          <td class="right">{{ $fmt($it->now_item_pct) }} %</td>
        </tr>
      @endforeach

      {{-- TOTAL (jumlah hanya kolom bobot) --}}
      @php
        // konversi kembali ke desimal 2 digit
        $totWi    = round($totWiInt / 100, 2);
        $totPrev  = round($totPrevInt / 100, 2);
        $totDelta = round($totDeltaInt / 100, 2);
        $totNow   = round($totNowInt / 100, 2);
      @endphp
      <tr class="row-total">
        <td colspan="2" class="right">TOTAL</td>
        <td class="right">{{ $fmt($totWi) }}</td>
        <td class="right">{{ $fmt($totPrev) }}</td>
        <td class="right">{{ $fmt($totDelta) }}</td>
        <td class="right">{{ $fmt($totNow) }}</td>
        <td colspan="3"></td>
      </tr>
    </tbody>
  </table>

  {{-- Tanda tangan --}}
  <table class="sign">
    <tr>
      <td>
        <div>Dibuat oleh</div>
        <div class="box"></div>
          <div><strong>______________________________________</strong></div>
          <div style="color:#666">{{ $dibuatOleh }} - {{ $dibuatJabatan }}</div>
      </td>
      <td>
        <div>Disetujui oleh</div>
        <div class="box"></div>
          <div><strong>______________________________________</strong></div>
          <div style="color:#666">{{ $pemberiKerjaPic }} - {{ $pemberiKerja }}</div>
      </td>
    </tr>
  </table>

  <div class="foot">BAPP {{ $bapp->nomor_bapp }} &nbsp;|&nbsp; Proyek: {{ $proyek->nama_proyek }}</div>
</body>
</html>
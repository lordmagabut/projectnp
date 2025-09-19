@php
  use App\Models\RabDetail;
  use App\Models\RabHeader;                       // <— pakai RAB HEADER untuk nama struktur
  use Illuminate\Support\Facades\Schema;

  $fmt = fn($n)=>number_format((float)$n, 2, ',', '.');

  // ===== helper pilih kolom nama yang tersedia
  $pick = function(string $table, array $cands){
    foreach ($cands as $c) if (Schema::hasColumn($table,$c)) return $c;
    return null;
  };

  // ===== ambil nama header/subheader dari RabHeader by kode (e.g. "2", "2.1")
  $hdrTbl  = class_exists(RabHeader::class) ? (new RabHeader)->getTable() : null;
  $hdrNameCol = $hdrTbl ? ($pick($hdrTbl, ['uraian','deskripsi','nama','judul','title']) ?? null) : null;

  $nameCache = [];
  $headerName = function(string $kode, int $proyekId) use (&$nameCache,$hdrTbl,$hdrNameCol) {
    if (isset($nameCache[$kode])) return $nameCache[$kode];
    if (!$hdrTbl || !$hdrNameCol) return $nameCache[$kode] = null;
    $row = RabHeader::where('proyek_id',$proyekId)
            ->where('kode',$kode)->select($hdrNameCol)->first();
    return $nameCache[$kode] = ($row->$hdrNameCol ?? null);
  };

  // ===== data item, urut natural
  $items = $bapp->details->sortBy('kode', SORT_NATURAL)->values();

  // ===== total akhir
  $grandPrev = $grandDelta = $grandNow = 0;
@endphp
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>BAPP {{ $bapp->nomor_bapp }}</title>
  <style>
    @page { margin: 20mm 14mm; }
    body  { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color:#222; }
    .header { border-bottom:2px solid #444; padding-bottom:8px; margin-bottom:12px; display:flex; gap:12px; align-items:center; }
    .logo   { width:68px; height:68px; object-fit:contain }
    .title  { font-size:16px; font-weight:700 }
    .subtitle{ font-size:12px; margin-top:2px; color:#666 }
    .meta   { width:100%; font-size:10.5px; margin-top:6px }
    .meta td:first-child{ width:28% }

    table.tbl{ width:100%; border-collapse:collapse; }
    .tbl th,.tbl td{ border:1px solid #9aa0a6; padding:6px 6px; }
    .tbl thead th{ background:#f1f3f4; font-weight:700; text-align:center; }
    .right{ text-align:right; }
    .nowrap{ white-space:nowrap; }

    /* baris info seperti penawaran */
    .row-area   { background:#e8f0fe; font-weight:700; }
    .row-header { background:#f6f9fe; font-weight:700; }

    .row-total  { background:#f1f3f4; font-weight:700; }
    .footer { position: fixed; bottom: -10mm; left:0; right:0; text-align:right; font-size:9px; color:#777; }
  </style>
</head>
<body>

  {{-- ===== Letterhead ===== --}}
  <div class="header">
    @if(optional($proyek)->logo_path)
      <img class="logo" src="{{ public_path('storage/'.$proyek->logo_path) }}" alt="logo">
    @endif
    <div style="flex:1">
      <div class="title">BERITA ACARA PROGRESS PEKERJAAN (BAPP)</div>
      <div class="subtitle">Nomor: <strong>{{ $bapp->nomor_bapp }}</strong></div>
      <table class="meta" cellpadding="0" cellspacing="0">
        <tr><td>Proyek</td>       <td>: <strong>{{ $proyek->nama_proyek }}</strong></td></tr>
        <tr><td>Pemberi Kerja</td><td>: {{ optional($proyek->pemberiKerja)->nama_pemberi_kerja ?? '—' }}</td></tr>
        <tr><td>Penawaran</td>    <td>: {{ optional($bapp->penawaran)->nama_penawaran ?? '—' }}</td></tr>
        <tr><td>Minggu</td>       <td>: Minggu ke-{{ $bapp->minggu_ke }} (Tanggal: {{ \Carbon\Carbon::parse($bapp->tanggal_bapp)->format('d-m-Y') }})</td></tr>
      </table>
    </div>
  </div>

  {{-- ===== Tabel mirip penawaran: header area/subheader hanya label ===== --}}
  <table class="tbl">
    <thead>
      <tr>
        <th style="width:12%">Kode</th>
        <th>Uraian</th>
        <th style="width:12%">Bobot Item (%)</th>
        <th style="width:14%">Prog. s/d Lalu (%)</th>
        <th style="width:14%">Prog. Minggu Ini (%)</th>
        <th style="width:14%">Prog. Saat Ini (%)</th>
      </tr>
    </thead>
    <tbody>
      @php $currArea = $currHeader = null; @endphp

      @foreach($items as $it)
        @php
          // kumpulkan total akhir
          $grandPrev  += (float)$it->prev_pct;
          $grandDelta += (float)$it->delta_pct;
          $grandNow   += (float)$it->now_pct;

          // posisi dalam struktur
          $kode  = trim((string)$it->kode);
          $parts = $kode !== '' ? preg_split('/\s*\.\s*/', $kode) : [];
          $areaCode   = $parts[0] ?? null;                           // "2"
          $headerCode = isset($parts[1]) ? $parts[0].'.'.$parts[1] : null; // "2.1"
        @endphp

        {{-- Baris AREA (label saja) --}}
        @if($areaCode && $areaCode !== $currArea)
          @php
            $currArea = $areaCode;  // reset header saat ganti area
            $currHeader = null;
            $areaName = $headerName($areaCode, $bapp->proyek_id) ?? ('Pekerjaan '.$areaCode);
          @endphp
          <tr class="row-area">
            <td class="nowrap">{{ $areaCode }}</td>
            <td>{{ strtoupper($areaName) }}</td>
            <td></td><td></td><td></td><td></td>
          </tr>
        @endif

        {{-- Baris HEADER (label saja) --}}
        @if($headerCode && $headerCode !== $currHeader)
          @php
            $currHeader = $headerCode;
            $hdrName = $headerName($headerCode, $bapp->proyek_id) ?? ('Header '.$headerCode);
          @endphp
          <tr class="row-header">
            <td class="nowrap">{{ $headerCode }}</td>
            <td>{{ $hdrName }}</td>
            <td></td><td></td><td></td><td></td>
          </tr>
        @endif

        {{-- Baris ITEM dengan angka --}}
        <tr>
          <td class="nowrap">{{ $it->kode }}</td>
          <td>{{ $it->uraian }}</td>
          <td class="right">{{ $fmt($it->bobot_item) }}</td>
          <td class="right">{{ $fmt($it->prev_pct) }}</td>
          <td class="right">{{ $fmt($it->delta_pct) }}</td>
          <td class="right">{{ $fmt($it->now_pct) }}</td>
        </tr>
      @endforeach

      {{-- TOTAL akhir saja --}}
      <tr class="row-total">
        <td colspan="3" class="right">TOTAL</td>
        <td class="right">{{ $fmt($grandPrev) }}</td>
        <td class="right">{{ $fmt($grandDelta) }}</td>
        <td class="right">{{ $fmt($grandNow) }}</td>
      </tr>
    </tbody>
  </table>

  <div style="margin-top:16px">
    <div style="font-size:10.5px;color:#666">Catatan:</div>
    <div style="font-size:10.5px">{!! nl2br(e($bapp->notes ?? '-')) !!}</div>
  </div>

  <table style="width:100%; margin-top:26px; font-size:11px">
    <tr>
      <td style="width:50%; text-align:center">
        <div>Disusun oleh</div><div style="color:#666">{{ config('app.name') }}</div>
        <div style="height:48px"></div>
        <div><strong>{{ auth()->user()->name ?? '_____________' }}</strong></div>
      </td>
      <td style="width:50%; text-align:center">
        <div>Disetujui oleh</div>
        <div style="color:#666">{{ optional($proyek->pemberiKerja)->nama_pemberi_kerja ?? 'Pemberi Kerja' }}</div>
        <div style="height:48px"></div>
        <div><strong>______________________</strong></div>
      </td>
    </tr>
  </table>

  <div class="footer">BAPP {{ $bapp->nomor_bapp }} &nbsp;|&nbsp; Proyek: {{ $proyek->nama_proyek }}</div>
</body>
</html>

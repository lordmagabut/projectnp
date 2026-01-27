<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    body { font-family:'DejaVu Sans',sans-serif; font-size:10px; line-height:1.4; color:#333; }
    @page landscape { size: A4 landscape; margin: 12mm; }
    .landscape { page: landscape; }
    .landscape table { font-size:9.5px; }
    .landscape th, .landscape td { padding:4px 6px; }
    .nowrap { white-space: nowrap; }
    .desc { font-size: 9.5px; }
    .spec { display: block; margin-top: 3px; font-size: 9px; color: #666; white-space: normal; }
    .row-subheader td { background:#f7fbff; font-weight:bold; }
    .row-area td { background:#eef8ff; font-weight:600; font-style:italic; }
    table { width:100%; border-collapse:collapse; margin-bottom:14px; }
    th,td { border:1px solid #ddd; padding:6px; vertical-align:top; }
    /* Ringankan baris seperti versi detail multi-bagian */
    .tbl-detail { font-size:9.5px; }
    .tbl-detail th, .tbl-detail td { padding:4px 6px; line-height:1.15; }
    th { background:#f2f2f2; font-weight:bold; color:#555; }
    .text-end { text-align:right; }
    .fw-bold { font-weight:bold; }
    .currency { white-space:nowrap; }
    .section-header { background:#e9f5ff; padding:8px; margin:15px 0 5px; border:1px solid #cce5ff; font-weight:bold; font-size:11px; }
    .totals-row td { background:#fafafa; font-weight:bold; }
    thead { display: table-header-group; }
    tr { page-break-inside: avoid; }
    .preline { white-space: pre-line; }
  </style>
</head>
<body class="landscape">
  <table style="margin-bottom:8px;">
    <tr>
      <th style="width:18%; text-align:left;">Nomor Penawaran</th>
      <td>{{ $penawaran->nomor_penawaran ?? '—' }}</td>
      <th style="width:14%; text-align:left;">Tanggal</th>
      <td>{{ optional(\Carbon\Carbon::parse($penawaran->tanggal_penawaran ?? now()))->translatedFormat('d F Y') }}</td>
    </tr>
  </table>

  <h3>Detail Bagian Penawaran</h3>

  @php
    $groupsDetail = collect($penawaran->sections)->groupBy(function($s){
        $k = (string)optional($s->rabHeader)->kode;
        return explode('.', $k)[0] ?? $k;
    });

    if (!function_exists('rupiah_or_blank')) {
      function rupiah_or_blank($n){ $n=(float)$n; return $n==0.0 ? '' : 'Rp&nbsp;'.number_format($n,0,',','.'); }
    }
  @endphp

  @foreach($groupsDetail as $top => $sections)
    @php
      $parent = $sections->first(function($s){
          $k = (string)optional($s->rabHeader)->kode;
          return $k !== '' && strpos($k, '.') === false;
      });
      $parentKode = optional(optional($parent)->rabHeader)->kode ?? $top;
      $parentDesc = optional(optional($parent)->rabHeader)->deskripsi ?? 'Bagian '.$top;
      $groupTotal = 0;
    @endphp

    <div class="section-wrapper">
      <div class="section-header">
          {{ $parentKode }} - {{ $parentDesc }}
      </div>

      <table class="tbl-detail">
          <colgroup>
            <col style="width:10%">
            <col style="width:40%">
            <col style="width:10%">
            <col style="width:10%">
            <col style="width:15%">
            <col style="width:15%">
          </colgroup>
          <thead>
            <tr>
              <th>Kode</th>
              <th>Uraian Pekerjaan</th>
              <th>Volume</th>
              <th>Satuan</th>
              <th>Harga Satuan</th>
              <th>Total Harga</th>
            </tr>
          </thead>
          <tbody>
          @foreach($sections->sortBy(fn($s)=> optional($s->rabHeader)->kode) as $section)
            @php
              $secKode = optional($section->rabHeader)->kode ?? '';
              $secDesc = optional($section->rabHeader)->deskripsi ?? '';
              $subTotalSec = 0;
              $secDepth = is_string($secKode) ? substr_count($secKode, '.') : 0;
            @endphp

            @if($secKode && strpos($secKode, '.') !== false)
              <tr class="row-subheader">
                <td colspan="6">{{ $secKode }} - {{ $secDesc }}</td>
              </tr>
            @endif

            @php
              $itemsByArea = $section->items->groupBy(fn($it) => trim($it->area) ?: '__NOAREA__');
            @endphp

            @foreach($itemsByArea as $areaName => $items)
              @if($areaName !== '__NOAREA__')
                <tr class="row-area"><td colspan="6">{{ $areaName }}</td></tr>
              @endif

              @foreach($items as $item)
                @php
                  $vol = (float) ($item->volume ?? 0);
                  $unitPrice = (float)($item->harga_material_penawaran_item ?? 0) + (float)($item->harga_upah_penawaran_item ?? 0);
                  $rowTotal = $unitPrice * $vol;
                  $subTotalSec += $rowTotal;
                  $uraian = mb_strimwidth($item->deskripsi ?? '', 0, 100, '…', 'UTF-8');
                @endphp
                <tr>
                  @php
                    $itemKode = (string)$item->kode;
                    $itemDepth = is_string($itemKode) ? substr_count($itemKode, '.') : 0;
                    $relative = max(0, $itemDepth - $secDepth);
                    $pad = $relative * 8;
                  @endphp
                  <td style="padding-left: {{ $pad }}px">{{ $item->kode }}</td>
                  <td class="desc">
                    {{ $uraian }}
                    @if(!empty($item->spesifikasi))
                      <span class="spec preline">{!! nl2br(e($item->spesifikasi)) !!}</span>
                    @endif
                  </td>
                  <td class="text-end">{{ number_format($vol,2,',','.') }}</td>
                  <td>{{ $item->satuan }}</td>
                  <td class="text-end currency">{!! rupiah_or_blank($unitPrice) !!}</td>
                  <td class="text-end currency">{!! rupiah_or_blank($rowTotal) !!}</td>
                </tr>
              @endforeach
            @endforeach

            @if($section->items->count() > 0)
              <tr class="totals-row">
                <td colspan="5" class="text-end">SUBTOTAL {{ $secDesc }}</td>
                <td class="text-end currency">{!! rupiah_or_blank($subTotalSec) !!}</td>
              </tr>
            @endif
            @php $groupTotal += $subTotalSec; @endphp
          @endforeach
          </tbody>
          <tfoot>
            <tr class="totals-row">
              <td colspan="5" class="text-end">TOTAL ({{ $parentDesc }})</td>
              <td class="text-end currency">{!! rupiah_or_blank($groupTotal) !!}</td>
            </tr>
          </tfoot>
      </table>
    </div>
  @endforeach

</body>
</html>
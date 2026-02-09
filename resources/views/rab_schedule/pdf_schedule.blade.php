<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    @page { size: A3 landscape; margin: 10mm; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 9px; color: #222; }
    h2 { text-align: center; margin: 0 0 8px; font-size: 12px; letter-spacing: .3px; }
    .meta { margin-bottom: 6px; }
    .meta td { padding: 2px 4px; border: none; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #333; padding: 2px 4px; }
    th { background: #f0f0f0; font-weight: 700; text-align: center; }
    .text-end { text-align: right; }
    .text-center { text-align: center; }
    .desc { white-space: nowrap; }
    .row-header { background: #f7f7f7; font-weight: 700; }
    .row-subheader { background: #fdf7e8; font-weight: 600; }
    .nowrap { white-space: nowrap; }
    .curve-box { margin: 6px 0 8px; border: 1px solid #333; padding: 6px; }
    .curve-title { font-weight: 700; margin-bottom: 4px; }
    .schedule-wrap { position: relative; }
    .schedule-table tbody tr { height: 14px; }
    .curve-overlay { position: absolute; left: 33%; width: 61%; pointer-events: none; opacity: .9; }
  </style>
</head>
<body>
  <h2>SCHEDULE OF WORK</h2>

  <table class="meta">
    <tr>
      <td class="nowrap"><strong>Proyek</strong></td>
      <td>: {{ $proyek->nama_proyek }}</td>
      <td class="nowrap"><strong>Penawaran</strong></td>
      <td>: {{ $penawaran->nama_penawaran }}</td>
    </tr>
    <tr>
      <td class="nowrap"><strong>Tanggal</strong></td>
      <td>: {{ optional(\Carbon\Carbon::parse($meta->start_date))->format('d-m-Y') }} s/d {{ optional(\Carbon\Carbon::parse($meta->end_date))->format('d-m-Y') }}</td>
      <td class="nowrap"><strong>Total Minggu</strong></td>
      <td>: {{ $totalWeeks }}</td>
    </tr>
  </table>

  @php
    $rowHeight = 14;
    $rootCount = count(array_filter($rows, fn($r) => (int)($r['depth'] ?? 0) === 0));
    $blankCount = max(0, $rootCount - 1);
    $rowCount = count($rows) + 2 + $blankCount; // include TOTAL + CUMULATIVE + blank separators
    $gridHeight = $rowCount * $rowHeight;
    $headerHeight = 22;

    $curveWidth = 1000;
    $curveHeight = max(80, $gridHeight);
    $maxX = max(1, (int)$totalWeeks);
    $maxY = 100;
    $stepX = $curveWidth / max(1, ($maxX - 1));
    $points = [];
    for ($w = 1; $w <= $totalWeeks; $w++) {
      $x = ($w - 1) * $stepX;
      $yVal = (float)($weeklyCumulative[$w] ?? 0);
      $y = $curveHeight - (($yVal / $maxY) * $curveHeight);
      $points[] = number_format($x, 2, '.', '') . ',' . number_format($y, 2, '.', '');
    }

    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="'.$curveWidth.'" height="'.$curveHeight.'" viewBox="0 0 '.$curveWidth.' '.$curveHeight.'">'
         . '<polyline points="'.implode(' ', $points).'" fill="none" stroke="#1f6feb" stroke-width="2" />'
         . '</svg>';
    $svgBase64 = base64_encode($svg);
  @endphp

  <div class="schedule-wrap">
    <img class="curve-overlay"
         style="top: {{ $headerHeight }}px; height: {{ $gridHeight }}px;"
         src="data:image/svg+xml;base64,{{ $svgBase64 }}"
         alt="Kurva S" />

    <table class="schedule-table">
    <thead>
      <tr>
        <th style="width:4%">NO</th>
        <th style="width:22%">WORK DESCRIPTION</th>
        <th style="width:7%">WEIGHT (%)</th>
        @for($w=1;$w<=$totalWeeks;$w++)
          <th>W{{ $w }}</th>
        @endfor
        <th style="width:6%">REMARKS</th>
      </tr>
    </thead>
    <tbody>
      @foreach($rows as $idx => $row)
        @if(($row['depth'] ?? 0) === 0 && $idx > 0)
          <tr>
            <td colspan="{{ 3 + $totalWeeks + 1 }}">&nbsp;</td>
          </tr>
        @endif
        @php
          $depth = (int)($row['depth'] ?? 0);
          $indent = str_repeat('&nbsp;', $depth * 4);
          $cls = $depth === 0 ? 'row-header' : 'row-subheader';
        @endphp
        <tr class="{{ $cls }}">
          <td class="text-center">{{ $row['kode'] }}</td>
          <td class="desc">{!! $indent !!}{{ $row['deskripsi'] }}</td>
          <td class="text-end">{{ number_format((float)$row['weight'], 2, ',', '.') }}</td>
          @for($w=1;$w<=$totalWeeks;$w++)
            @php $val = (float)($row['weeks'][$w] ?? 0); @endphp
            <td class="text-end">{{ $val > 0 ? number_format($val, 2, ',', '.') : '' }}</td>
          @endfor
          <td></td>
        </tr>
      @endforeach

      <tr class="row-header">
        <td colspan="2" class="text-center">TOTAL</td>
        <td class="text-end">{{ number_format(array_sum($weeklyTotals), 2, ',', '.') }}</td>
        @for($w=1;$w<=$totalWeeks;$w++)
          @php $val = (float)($weeklyTotals[$w] ?? 0); @endphp
          <td class="text-end">{{ $val > 0 ? number_format($val, 2, ',', '.') : '' }}</td>
        @endfor
        <td></td>
      </tr>

      <tr class="row-header">
        <td colspan="2" class="text-center">CUMULATIVE</td>
        <td class="text-end">{{ number_format(array_sum($weeklyTotals), 2, ',', '.') }}</td>
        @for($w=1;$w<=$totalWeeks;$w++)
          @php $val = (float)($weeklyCumulative[$w] ?? 0); @endphp
          <td class="text-end">{{ $val > 0 ? number_format($val, 2, ',', '.') : '' }}</td>
        @endfor
        <td></td>
      </tr>
    </tbody>
    </table>
  </div>
</body>
</html>
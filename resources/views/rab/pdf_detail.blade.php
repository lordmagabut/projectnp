<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    body { font-family:'DejaVu Sans',sans-serif; font-size:10px; line-height:1.4; color:#333; }
    .container { width:100%; margin:0 auto; padding:20px; }

    h2 { text-align:center; margin:0 0 16px; font-weight:700; color:#000; }
    h3 { font-size:14px; color:#0056b3; margin:16px 0 8px; }

    @page landscape { size: A4 landscape; margin: 12mm; }
    .landscape { page: landscape; }

    .landscape table { font-size: 9.5px; }
    .landscape th, .landscape td { padding: 4px 6px; }

    .tbl-detail th, .tbl-detail td { padding: 4px 6px; line-height: 1.15; }

    .nowrap { white-space: nowrap; word-break: normal; hyphens: none; }

    .desc { font-size: 9.5px; }
    .spec { display: block; margin-top: 3px; font-size: 9px; color: #666; white-space: normal; }

    .row-subheader td { background:#f7fbff; font-weight:600; }
    .row-area td      { background:#eef8ff; font-weight:600; font-style:italic; }

    table { width:100%; border-collapse:collapse; margin-bottom:14px; }
    th,td { border:1px solid #ddd; padding:6px; vertical-align:top; }
    th { background:#f2f2f2; font-weight:bold; color:#555; }

    .text-end { text-align:right; }
    .fw-bold { font-weight:bold; }
    .currency { white-space:nowrap; }

    .info-table { width:100%; border-collapse:collapse; margin-bottom:14px; }
    .info-table th, .info-table td { border:1px solid #ddd; padding:6px; }
    .info-table th { width:22%; white-space:nowrap; text-align:left; background:#f2f2f2; }
    .info-table td { width:78%; text-align:left; }

    .section-header { background:#e9f5ff; padding:8px; margin:15px 0 5px; border:1px solid #cce5ff; font-weight:bold; font-size:11px; display:flex; justify-content:space-between; }
    .totals-row td { background:#fafafa; font-weight:bold; }

    thead { display: table-header-group; }
    tr { page-break-inside: avoid; }

    .preline { white-space: pre-line; }
  </style>
</head>
<body class="landscape">
  @php
    $kontigensi = (float) data_get($proyek, 'kontingensi_persen', data_get($proyek, 'persen_kontingensi', 0));
    $kontFactor = 1 + ($kontigensi / 100);
    $GLOBALS['kontFactor'] = $kontFactor;
  @endphp
  <table style="margin-bottom:8px;">
    <tr>
      <th style="width:18%; text-align:left;">Proyek</th>
      <td>{{ $proyek->nama_proyek ?? '—' }}</td>
      <th style="width:14%; text-align:left;">Tanggal</th>
      <td>{{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</td>
    </tr>
    <tr>
      <th style="text-align:left;">Kontigensi</th>
      <td colspan="3">{{ number_format($kontigensi, 2, ',', '.') }}%</td>
    </tr>
  </table>
  <h3>Detail RAP Proyek</h3>

  @php
    if (!function_exists('rupiah_or_blank')) {
      function rupiah_or_blank($n){ $n=(float)$n; return $n==0.0 ? '' : 'Rp&nbsp;'.number_format($n,0,',','.'); }
    }
    if (!function_exists('rab_detail_mat_jasa_units')) {
      function rab_detail_mat_jasa_units($d){
        $unitMat = (float)($d->harga_material ?? 0);
        $unitJasa = (float)($d->harga_upah ?? 0);
        return [$unitMat, $unitJasa];
      }
    }
    if (!function_exists('rab_detail_mat_jasa_totals')) {
      function rab_detail_mat_jasa_totals($d){
        $kontFactor = $GLOBALS['kontFactor'] ?? 1;
        $vol = (float)($d->volume ?? 0);
        [$unitMat, $unitJasa] = rab_detail_mat_jasa_units($d);
        $totMat = (float)($d->total_material ?? 0);
        $totJasa = (float)($d->total_upah ?? 0);
        if ($totMat == 0.0 && $unitMat > 0) $totMat = $unitMat * $vol;
        if ($totJasa == 0.0 && $unitJasa > 0) $totJasa = $unitJasa * $vol;

        if ($unitMat > 0) $unitMat = $unitMat * $kontFactor;
        if ($unitJasa > 0) $unitJasa = $unitJasa * $kontFactor;
        if ($totMat > 0) $totMat = $totMat * $kontFactor;
        if ($totJasa > 0) $totJasa = $totJasa * $kontFactor;

        return [$totMat, $totJasa, $unitMat, $unitJasa];
      }
    }

    $groupsDetail = collect($headers)->groupBy(function($h){
      $k = (string)optional($h)->kode;
      return explode('.', $k)[0] ?? $k;
    });
  @endphp

  @foreach($groupsDetail as $top => $items)
    @php
      $parent = $items->first(function($h){
        $k = (string)optional($h)->kode;
        return $k !== '' && strpos($k, '.') === false;
      });
      $parentKode = optional($parent)->kode ?? $top;
      $parentDesc = optional($parent)->deskripsi ?? 'Bagian '.$top;

      $groupMaterial = 0; $groupJasa = 0;
    @endphp

    <div class="section-wrapper">
      <div class="section-header">
        <div>{{ $parentKode }} - {{ $parentDesc }}</div>
      </div>

      <table class="tbl-detail">
        <colgroup>
          <col style="width:9%">
          <col style="width:9%">
          <col style="width:31%">
          <col style="width:7%">
          <col style="width:7%">
          <col style="width:11%">
          <col style="width:11%">
          <col style="width:8%">
          <col style="width:7%">
        </colgroup>
        <thead>
          <tr>
            <th>Kode</th>
            <th>Kode AHSP</th>
            <th>Uraian Pekerjaan</th>
            <th>Volume</th>
            <th>Satuan</th>
            <th>Harga Material</th>
            <th>Harga Jasa</th>
            <th>Total Material</th>
            <th>Total Jasa</th>
          </tr>
        </thead>
        <tbody>
        @foreach($items->sortBy(fn($h)=> $h->kode_sort ?? $h->kode) as $hdr)
          @php
            $secKode = (string)optional($hdr)->kode;
            $secDesc = (string)optional($hdr)->deskripsi;
            $subMaterial = 0; $subJasa = 0;
            $secDepth = is_string($secKode) ? substr_count($secKode, '.') : 0;
          @endphp

          @if($secKode && (strpos($secKode, '.') !== false || optional($parent)->id !== $hdr->id))
            <tr class="row-subheader">
              <td colspan="9">{{ $secKode }} - {{ $secDesc }}</td>
            </tr>
          @endif

          @php
            $itemsByArea = collect($hdr->rabDetails ?? [])->groupBy(function($it){
              $a = is_string($it->area) ? trim($it->area) : '';
              return $a !== '' ? $a : '__NOAREA__';
            });
          @endphp

          @foreach($itemsByArea as $areaName => $details)
            @if($areaName !== '__NOAREA__')
              <tr class="row-area">
                <td colspan="9">{{ $areaName }}</td>
              </tr>
            @endif

            @foreach($details as $d)
              @php
                $vol = (float)($d->volume ?? 0);
                [$totMat, $totJasa, $unitMat, $unitJasa] = rab_detail_mat_jasa_totals($d);
                $subMaterial += $totMat; $subJasa += $totJasa;

                $uraian1baris = mb_strimwidth($d->deskripsi ?? '', 0, 100, '…', 'UTF-8');
              @endphp
              <tr>
                @php
                  $itemKode = (string)$d->kode;
                  $itemDepth = is_string($itemKode) ? substr_count($itemKode, '.') : 0;
                  $relative = max(0, $itemDepth - $secDepth);
                  $pad = $relative * 8;
                @endphp
                <td style="padding-left: {{ $pad }}px">{{ $d->kode }}</td>
                <td>{{ optional($d->ahsp)->kode_pekerjaan ?? 'MANUAL' }}</td>
                <td class="desc nowrap">
                  {{ $uraian1baris }}
                  @if(!empty($d->spesifikasi))
                    <span class="spec preline">{!! nl2br(e($d->spesifikasi)) !!}</span>
                  @endif
                </td>
                <td class="text-end">{{ number_format($vol,2,',','.') }}</td>
                <td>{{ $d->satuan }}</td>
                <td class="text-end currency">{!! rupiah_or_blank($unitMat) !!}</td>
                <td class="text-end currency">{!! rupiah_or_blank($unitJasa) !!}</td>
                <td class="text-end currency">{!! rupiah_or_blank($totMat) !!}</td>
                <td class="text-end currency">{!! rupiah_or_blank($totJasa) !!}</td>
              </tr>
            @endforeach
          @endforeach

          @if(($hdr->rabDetails ?? collect())->count() > 0)
            <tr class="totals-row">
              <td colspan="7" class="text-end">SUBTOTAL {{ $secDesc ?: '' }}</td>
              <td class="text-end currency">{!! rupiah_or_blank($subMaterial) !!}</td>
              <td class="text-end currency">{!! rupiah_or_blank($subJasa) !!}</td>
            </tr>
          @endif

          @php
            $groupMaterial += $subMaterial;
            $groupJasa     += $subJasa;
          @endphp
        @endforeach
        </tbody>
        <tfoot>
          <tr class="totals-row">
            <td colspan="7" class="text-end">SUBTOTAL ({{ $parentDesc }})</td>
            <td class="text-end currency">{!! rupiah_or_blank($groupMaterial) !!}</td>
            <td class="text-end currency">{!! rupiah_or_blank($groupJasa) !!}</td>
          </tr>
        </tfoot>
      </table>
    </div>
  @endforeach

  @php
    $ahspList = collect($headers)
      ->flatMap(fn($h) => $h->rabDetails ?? [])
      ->filter(fn($d) => !empty($d->ahsp_id) && !empty($d->ahsp))
      ->map(fn($d) => $d->ahsp)
      ->unique('id')
      ->values();

    $ahspRows = $ahspList->mapWithKeys(function($ahsp) use ($kontFactor){
      $kode = $ahsp->kode_pekerjaan ?? (string)$ahsp->id;
      $mat = (float)($ahsp->total_material ?? 0) * $kontFactor;
      $jasa = (float)($ahsp->total_upah ?? 0) * $kontFactor;
      return [$kode => [
        'nama' => (string)($ahsp->nama_pekerjaan ?? ''),
        'material' => $mat,
        'jasa' => $jasa,
        'total' => $mat + $jasa,
      ]];
    });

    $allDetails = $ahspList->flatMap(fn($a) => $a->details ?? []);
    $matIds = $allDetails->where('tipe', 'material')->pluck('referensi_id')->filter()->unique()->values();
    $upahIds = $allDetails->where('tipe', 'upah')->pluck('referensi_id')->filter()->unique()->values();
    $matMap = $matIds->isNotEmpty()
      ? \App\Models\HsdMaterial::whereIn('id', $matIds)->get()->keyBy('id')
      : collect();
    $upahMap = $upahIds->isNotEmpty()
      ? \App\Models\HsdUpah::whereIn('id', $upahIds)->get()->keyBy('id')
      : collect();
  @endphp

  @if($ahspRows->isNotEmpty())
    <div style="page-break-before: always;"></div>
    <h3>Daftar AHSP yang Digunakan</h3>
    <table>
      <thead>
        <tr>
          <th style="width:16%;">Kode AHSP</th>
          <th>Nama Pekerjaan</th>
          <th style="width:16%;">Total Material</th>
          <th style="width:16%;">Total Jasa</th>
          <th style="width:16%;">Total</th>
        </tr>
      </thead>
      <tbody>
        @foreach($ahspRows as $kode => $row)
          <tr>
            <td>{{ $kode }}</td>
            <td>{{ $row['nama'] }}</td>
            <td class="text-end currency">{!! rupiah_or_blank($row['material']) !!}</td>
            <td class="text-end currency">{!! rupiah_or_blank($row['jasa']) !!}</td>
            <td class="text-end currency">{!! rupiah_or_blank($row['total']) !!}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @endif

  @if($ahspList->isNotEmpty())
    <div style="page-break-before: always;"></div>
    <h3>Breakdown AHSP (Material & Jasa)</h3>

    @foreach($ahspList as $ahsp)
      <div class="section-wrapper">
        <div class="section-header">
          <div>{{ $ahsp->kode_pekerjaan ?? $ahsp->id }} - {{ $ahsp->nama_pekerjaan ?? '' }}</div>
        </div>

        <table class="tbl-detail">
          <colgroup>
            <col style="width:13%">
            <col style="width:33%">
            <col style="width:9%">
            <col style="width:9%">
            <col style="width:12%">
            <col style="width:8%">
            <col style="width:8%">
            <col style="width:8%">
          </colgroup>
          <thead>
            <tr>
              <th>Kode</th>
              <th>Uraian</th>
              <th>Koefisien</th>
              <th>Satuan</th>
              <th>Harga Satuan</th>
              <th>Material</th>
              <th>Jasa</th>
              <th>Total</th>
            </tr>
          </thead>
          <tbody>
            @php $subMat = 0.0; $subJasa = 0.0; @endphp
            @foreach(($ahsp->details ?? []) as $det)
              @php
                $isMat = $det->tipe === 'material';
                $ref = $isMat ? ($matMap[$det->referensi_id] ?? null) : ($upahMap[$det->referensi_id] ?? null);
                $kodeItem = $isMat
                  ? (string)($ref->kode ?? $det->referensi_id ?? '')
                  : (string)($ref->kode ?? $det->referensi_id ?? '');
                $namaItem = $isMat
                  ? (string)($ref->nama ?? $ref->nama_item ?? '')
                  : (string)($ref->jenis_pekerja ?? $ref->nama_item ?? '');
                $satuanItem = (string)($ref->satuan ?? '');
                $subtotal = (float)($det->subtotal_final ?? $det->subtotal ?? 0) * $kontFactor;
                if ($isMat) $subMat += $subtotal; else $subJasa += $subtotal;
              @endphp
              <tr>
                <td>{{ $kodeItem }}</td>
                <td>{{ $namaItem }}</td>
                <td class="text-end">{{ number_format((float)($det->koefisien ?? 0), 4, ',', '.') }}</td>
                <td>{{ $satuanItem }}</td>
                <td class="text-end currency">{!! rupiah_or_blank((float)($det->harga_satuan ?? 0) * $kontFactor) !!}</td>
                <td class="text-end currency">{!! $isMat ? rupiah_or_blank($subtotal) : '' !!}</td>
                <td class="text-end currency">{!! !$isMat ? rupiah_or_blank($subtotal) : '' !!}</td>
                <td class="text-end currency">{!! rupiah_or_blank($subtotal) !!}</td>
              </tr>
            @endforeach
          </tbody>
          <tfoot>
            <tr class="totals-row">
              <td colspan="5" class="text-end">SUBTOTAL</td>
              <td class="text-end currency">{!! rupiah_or_blank($subMat) !!}</td>
              <td class="text-end currency">{!! rupiah_or_blank($subJasa) !!}</td>
              <td class="text-end currency">{!! rupiah_or_blank($subMat + $subJasa) !!}</td>
            </tr>
          </tfoot>
        </table>
      </div>
    @endforeach
  @endif

</body>
</html>

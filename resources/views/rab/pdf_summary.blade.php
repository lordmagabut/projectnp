<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    @page { size: A4 portrait; margin: 50mm 12mm 15mm; }

    body { font-family:'DejaVu Sans', sans-serif; font-size:10px; line-height:1.4; color:#333; }
    table{ width:100%; border-collapse:collapse; }
    th,td{ border:1px solid #ddd; padding:6px; }
    th{ background:#f2f2f2; }
    .text-end{ text-align:right; }
    .currency{ white-space:nowrap; }

    .pdf-header{
      position: fixed;
      top: -45mm;
      left: 0; right: 0;
    }
    .pdf-header table{ border-collapse: collapse; width: 100%; }
    .pdf-header th, .pdf-header td{ border:1px solid #ddd; padding:4px 6px; }
    .pdf-header th{ width:22%; text-align:left; background:#f2f2f2; white-space:nowrap; }
    .pdf-header td{ text-align:left; }

    thead { display: table-header-group; }
    tfoot { display: table-row-group; }
    tr { page-break-inside: avoid; }
  </style>
</head>
<body>

  @php $company = \App\Models\Perusahaan::first(); @endphp
  <div class="pdf-header">
    <table>
      <tr><th>PROYEK</th><td>: {{ $proyek->nama_proyek }} - {{ $proyek->pemberiKerja->nama_pemberi_kerja ?? '—' }}</td></tr>
      <tr><th>KONTRAKTOR</th><td>: {{ $company->nama_perusahaan ?? ($proyek->kontraktor ?? '—') }}</td></tr>
      <tr><th>PEKERJAAN</th><td>: {{ $proyek->jenis_pekerjaan ?? '—' }}</td></tr>
      <tr><th>TANGGAL CETAK</th><td>: {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</td></tr>
    </table>
  </div>

  <h2 style="text-align:center;margin:0 0 16px;">RENCANA ANGGARAN PELAKSANAAN (RAP)</h2>

  @php
    if (!function_exists('rupiah_or_blank')) {
      function rupiah_or_blank($n){ $n=(float)$n; return $n==0.0 ? '' : 'Rp&nbsp;'.number_format($n,0,',','.'); }
    }
    if (!function_exists('terbilang_id')) {
      function terbilang_id($x){
        $x=(int)floor($x);
        $abil=["","Satu","Dua","Tiga","Empat","Lima","Enam","Tujuh","Delapan","Sembilan","Sepuluh","Sebelas"];
        if($x<12) return $abil[$x];
        if($x<20) return terbilang_id($x-10)." Belas";
        if($x<100) return terbilang_id(intval($x/10))." Puluh ".terbilang_id($x%10);
        if($x<200) return "Seratus ".terbilang_id($x-100);
        if($x<1000) return terbilang_id(intval($x/100))." Ratus ".terbilang_id($x%100);
        if($x<2000) return "Seribu ".terbilang_id($x-1000);
        if($x<1000000) return terbilang_id(intval($x/1000))." Ribu ".terbilang_id($x%1000);
        if($x<1000000000) return terbilang_id(intval($x/1000000))." Juta ".terbilang_id($x%1000000);
        if($x<1000000000000) return terbilang_id(intval($x/1000000000))." Milyar ".terbilang_id($x%1000000000);
        return terbilang_id(intval($x/1000000000000))." Triliun ".terbilang_id($x%1000000000000);
      }
    }
    if (!function_exists('rab_summary_mat_jasa_totals')) {
      function rab_summary_mat_jasa_totals($d){
        $vol = (float)($d->volume ?? 0);
        $unitMat = (float)($d->harga_material ?? 0);
        $unitJasa = (float)($d->harga_upah ?? 0);
        $totMat = (float)($d->total_material ?? 0);
        $totJasa = (float)($d->total_upah ?? 0);
        if ($totMat == 0.0 && $unitMat > 0) $totMat = $unitMat * $vol;
        if ($totJasa == 0.0 && $unitJasa > 0) $totJasa = $unitJasa * $vol;
        return [$totMat, $totJasa];
      }
    }
    if (!function_exists('rab_summary_total_combined')) {
      function rab_summary_total_combined($d){
        $vol = (float)($d->volume ?? 0);
        $totalGab = (float)($d->total ?? 0);
        if ($totalGab > 0) return $totalGab;
        $unitGab = (float)($d->harga_satuan ?? 0);
        if ($unitGab > 0) return $unitGab * $vol;
        [$totMat, $totJasa] = rab_summary_mat_jasa_totals($d);
        return $totMat + $totJasa;
      }
    }

    $groups = collect($headers)->groupBy(function($h){
      $k=(string)optional($h)->kode; return explode('.',$k)[0] ?? $k;
    });
    $grandMat=0; $grandJasa=0; $grandAll=0;
  @endphp

  <table>
    <thead>
      <tr>
        <th style="width:10%;">NO</th>
        <th>DESKRIPSI</th>
        <th style="width:18%;">TOTAL MATERIAL</th>
        <th style="width:18%;">TOTAL JASA</th>
        <th style="width:18%;">TOTAL</th>
      </tr>
    </thead>
    <tbody>
    @foreach($groups as $top => $items)
      @php
        $parent = $items->first(function($h){
          $k=(string)optional($h)->kode; return $k!=='' && strpos($k,'.')===false;
        });
        $parentKode = optional($parent)->kode ?? $top;
        $parentDesc = optional($parent)->deskripsi ?? ('Bagian '.$top);

        $groupMat=0; $groupJasa=0; $groupAll=0;
        foreach ($items as $hdr) {
          foreach (($hdr->rabDetails ?? []) as $d) {
            [$tm,$tj] = rab_summary_mat_jasa_totals($d);
            $groupMat += $tm; $groupJasa += $tj;
            $rowAll = rab_summary_total_combined($d);
            $groupAll += $rowAll;
            $grandAll += $rowAll;
          }
        }
        $grandMat += $groupMat; $grandJasa += $groupJasa;
      @endphp

      <tr style="background:#f9fbff;">
        <td class="fw-bold">{{ $parentKode }}</td>
        <td colspan="4" class="fw-bold">{{ strtoupper($parentDesc) }}</td>
      </tr>

      @foreach($items->sortBy(fn($h)=> $h->kode_sort ?? $h->kode) as $hdr)
        @php
          $kode = (string)optional($hdr)->kode;
          $desc = (string)optional($hdr)->deskripsi;
          if ($kode==='' || strpos($kode,'.')===false) continue;

          $secMat=0; $secJasa=0; $secAll=0;
          foreach (($hdr->rabDetails ?? []) as $d) {
            [$tm,$tj] = rab_summary_mat_jasa_totals($d);
            $secMat += $tm; $secJasa += $tj;
            $secAll += rab_summary_total_combined($d);
          }
          $depth = is_string($kode) ? substr_count($kode, '.') : 0;
          $pad = $depth * 8;
        @endphp
        <tr>
          <td style="padding-left: {{ $pad }}px">{{ $kode }}</td>
          <td>{{ $desc }}</td>
          <td class="text-end currency">{!! rupiah_or_blank($secMat) !!}</td>
          <td class="text-end currency">{!! rupiah_or_blank($secJasa) !!}</td>
          <td class="text-end currency">{!! rupiah_or_blank($secAll) !!}</td>
        </tr>
      @endforeach

      <tr class="totals-row">
        <td colspan="2" class="text-end"><strong>TOTAL {{ strtoupper($parentDesc) }}</strong></td>
        <td class="text-end currency"><strong>{!! rupiah_or_blank($groupMat) !!}</strong></td>
        <td class="text-end currency"><strong>{!! rupiah_or_blank($groupJasa) !!}</strong></td>
        <td class="text-end currency"><strong>{!! rupiah_or_blank($groupAll) !!}</strong></td>
      </tr>
    @endforeach
    </tbody>

    @php
      $grandAll   = $grandAll ?: ($grandMat + $grandJasa);
      $ppnRate    = (isset($tax) && $tax->is_taxable) ? (float)($tax->ppn_rate ?? 11) : 0;
      $ppn        = $grandAll * ($ppnRate/100);
      $grandTotal = $grandAll + $ppn;
      $rf         = fn($n) => 'Rp '.number_format((float)$n, 0, ',', '.');
    @endphp

    <tfoot>
      <tr class="totals-row" style="background:#eef3ff;">
        <td colspan="2" class="text-end"><strong>TOTAL</strong></td>
        <td class="text-end currency"><strong>{!! rupiah_or_blank($grandMat) !!}</strong></td>
        <td class="text-end currency"><strong>{!! rupiah_or_blank($grandJasa) !!}</strong></td>
        <td class="text-end currency"><strong>{!! rupiah_or_blank($grandAll) !!}</strong></td>
      </tr>
      @if((float)$ppnRate > 0)
      <tr class="totals-row" style="background:#fff;">
        <td colspan="2" class="text-end"><strong>PPN ({{ $ppnRate }}%)</strong></td>
        <td colspan="3" class="text-end currency"><strong>{{ $rf($ppn) }}</strong></td>
      </tr>
      @endif
      <tr class="totals-row" style="background:#e6f7ff;">
        <td colspan="2" class="text-end"><strong>GRAND TOTAL</strong></td>
        <td colspan="3" class="text-end currency"><strong>{{ $rf($grandTotal) }}</strong></td>
      </tr>
    </tfoot>
  </table>

  @php
    $terbilang = trim(preg_replace('/\s+/', ' ', terbilang_id($grandTotal)));
  @endphp
  <table style="border:none; margin-top:6px;">
    <tr>
      <td style="border:none; width:15%;"><strong>Terbilang :</strong></td>
      <td style="border:none;"><em>{{ $terbilang }} Rupiah</em></td>
    </tr>
  </table>

</body>
</html>

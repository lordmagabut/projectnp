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
    .fw-bold{ font-weight:bold; }
    .pdf-header{ position: fixed; top: -45mm; left: 0; right: 0; }
    .pdf-header table{ border-collapse: collapse; width: 100%; }
    .pdf-header th, .pdf-header td{ border:1px solid #ddd; padding:4px 6px; }
    .pdf-header th{ width:22%; text-align:left; background:#f2f2f2; white-space:nowrap; }
    thead { display: table-header-group; }
    tr { page-break-inside: avoid; }
  </style>
</head>
<body>

  @php $company = \App\Models\Perusahaan::first(); @endphp
  <div class="pdf-header">
    <table>
      <tr><th>PROYEK</th><td>: {{ $proyek->nama_proyek }} - {{ $proyek->pemberiKerja->nama_pemberi_kerja ?? '—' }}</td></tr>
      <tr><th>KONTRAKTOR</th><td>: {{ $company->nama_perusahaan ?? ($proyek->kontraktor ?? '—') }}</td></tr>
      <tr><th>PEKERJAAN</th><td>: {{ $proyek->jenis_pekerjaan ?? ($penawaran->nama_penawaran ?? '—') }}</td></tr>
      <tr><th>NOMOR PENAWARAN</th><td>: {{ $penawaran->nomor_penawaran ?? '—' }}</td></tr>
      <tr><th>TANGGAL</th><td>: {{ optional(\Carbon\Carbon::parse($penawaran->tanggal_penawaran ?? now()))->translatedFormat('d F Y') }}</td></tr>
    </table>
  </div>

  <h2 style="text-align:center;margin:0 0 16px;">RENCANA ANGGARAN BIAYA (RAB)</h2>

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

    $groups = collect($penawaran->sections)->groupBy(function($s){
      $k=(string)optional($s->rabHeader)->kode; return explode('.',$k)[0] ?? $k;
    });
    $grandAllDPP = 0;
  @endphp

  <table>
    <thead>
      <tr>
        <th style="width:10%;">NO</th>
        <th>DESKRIPSI</th>
        <th style="width:30%;">TOTAL HARGA</th>
      </tr>
    </thead>
    <tbody>
    @foreach($groups as $top => $sections)
      @php
        $parent = $sections->first(function($s){
          $k=(string)optional($s->rabHeader)->kode; return $k!=='' && strpos($k,'.')===false;
        });
        $parentKode = optional(optional($parent)->rabHeader)->kode ?? $top;
        $parentDesc = optional(optional($parent)->rabHeader)->deskripsi ?? ('Bagian '.$top);

        $groupTotal = 0;
        foreach ($sections as $sec) {
          foreach ($sec->items as $it) {
            $v=(float)($it->volume ?? 0);
            $groupTotal += ((float)($it->harga_material_penawaran_item ?? 0) + (float)($it->harga_upah_penawaran_item ?? 0)) * $v;
          }
        }
        $grandAllDPP += $groupTotal;
      @endphp

      <tr style="background:#f9fbff;">
        <td class="fw-bold">{{ $parentKode }}</td>
        <td colspan="2" class="fw-bold">{{ strtoupper($parentDesc) }}</td>
      </tr>

      @foreach($sections->sortBy(fn($s)=> optional($s->rabHeader)->kode) as $sec)
        @php
          $kode = optional($sec->rabHeader)->kode ?? '';
          $desc = optional($sec->rabHeader)->deskripsi ?? '';
          if ($kode==='' || strpos($kode,'.')===false) continue;

          $secTotal = 0;
          foreach ($sec->items as $it) {
            $v = (float)($it->volume ?? 0);
            $secTotal += ((float)($it->harga_material_penawaran_item ?? 0) + (float)($it->harga_upah_penawaran_item ?? 0)) * $v;
          }
        @endphp
        <tr>
          <td>{{ $kode }}</td>
          <td>{{ $desc }}</td>
          <td class="text-end currency">{!! rupiah_or_blank($secTotal) !!}</td>
        </tr>
      @endforeach

      <tr class="totals-row">
        <td colspan="2" class="text-end"><strong>TOTAL {{ strtoupper($parentDesc) }}</strong></td>
        <td class="text-end currency"><strong>{!! rupiah_or_blank($groupTotal) !!}</strong></td>
      </tr>
    @endforeach
    </tbody>

    @php
      $ppnRate = (isset($tax) && $tax->is_taxable) ? (float)($tax->ppn_rate ?? 11) : 0;
      $ppn = $grandAllDPP * ($ppnRate/100);
      $grandTotal = $grandAllDPP + $ppn;
      $rf = fn($n) => 'Rp '.number_format((float)$n, 0, ',', '.');
    @endphp

    <tfoot>
      <tr class="totals-row" style="background:#eef3ff;">
        <td colspan="2" class="text-end"><strong>TOTAL</strong></td>
        <td class="text-end currency"><strong>{!! rupiah_or_blank($grandAllDPP) !!}</strong></td>
      </tr>
      @if((float)$ppnRate > 0)
      <tr>
        <td colspan="2" class="text-end"><strong>PPN ({{ $ppnRate }}%)</strong></td>
        <td class="text-end currency"><strong>{{ $rf($ppn) }}</strong></td>
      </tr>
      @endif
      <tr style="background:#e6f7ff;">
        <td colspan="2" class="text-end"><strong>GRAND TOTAL</strong></td>
        <td class="text-end currency"><strong>{{ $rf($grandTotal) }}</strong></td>
      </tr>
    </tfoot>
  </table>

  <table style="border:none; margin-top:6px;">
    <tr>
      <td style="border:none; width:15%;"><strong>Terbilang :</strong></td>
      <td style="border:none;"><em>{{ trim(preg_replace('/\s+/', ' ', terbilang_id($grandTotal))) }} Rupiah</em></td>
    </tr>
  </table>

  <h3 style="margin:12px 0 6px;">KETERANGAN / TERM OF PAYMENT</h3>
  @php
    $ketLines = preg_split("/\r\n|\n|\r/", (string)($penawaran->keterangan ?? ''));
    $hasKet = collect($ketLines)->contains(fn($l)=>trim($l)!=='');
  @endphp
  @if($hasKet)
    <table>
      <thead><tr><th style="width:10%;">NO</th><th>URAIAN</th></tr></thead>
      <tbody>
        @foreach($ketLines as $line)
          @continue(trim($line)==='')
          <tr><td class="text-end">{{ $loop->iteration }}</td><td>{{ $line }}</td></tr>
        @endforeach
      </tbody>
    </table>
  @else
    <table><tr><td style="padding:6px;"><em class="text-muted">Belum ada keterangan.</em></td></tr></table>
  @endif

</body>
</html>
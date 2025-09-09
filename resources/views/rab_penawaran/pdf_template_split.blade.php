<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Penawaran Proyek - {{ $penawaran->nama_penawaran }}</title>
    <style>
        body { font-family:'DejaVu Sans',sans-serif; font-size:10px; line-height:1.4; color:#333; }
        .container { width:100%; margin:0 auto; padding:20px; }

        h2 { text-align:center; margin:0 0 16px; font-weight:700; color:#000; }
        h3 { font-size:14px; color:#0056b3; margin:16px 0 8px; }

        table { width:100%; border-collapse:collapse; margin-bottom:14px; }
        th,td { border:1px solid #ddd; padding:6px; vertical-align:top; }
        th { background:#f2f2f2; font-weight:bold; color:#555; }

        .text-end { text-align:right; }
        .fw-bold { font-weight:bold; }
        .currency { white-space:nowrap; } /* cegah "Rp 340.000" pecah baris */

        /* identitas proyek: rata kiri */
        .info-table th, .info-table td { text-align:left; }
        .info-table th { width:20%; }
        .info-table td { width:30%; }

        .section-header { background:#e9f5ff; padding:8px; margin:15px 0 5px; border:1px solid #cce5ff; font-weight:bold; font-size:11px; display:flex; justify-content:space-between; }
        .row-subheader td { background:#f7fbff; font-weight:bold; }
        .row-area td { background:#eef8ff; font-weight:600; font-style:italic; }
        .totals-row td { background:#fafafa; font-weight:bold; }
        .total-label { text-align:right; padding-right:8px; }

        .page-break { page-break-after: always; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }

        .spec { display:block; margin-top:4px; font-size:9px; color:#555; }
        .preline { white-space: pre-line; }
    </style>
</head>
<body>
<div class="container">

    {{-- ===================== HALAMAN 1: RINGKASAN (Material & Jasa) ===================== --}}
    <h2>RENCANA ANGGARAN BIAYA ( RAB )</h2>

    <table class="info-table">
        <tr>
            <th>PROYEK</th>
            <td>: {{ $proyek->nama_proyek }}</td>
            <th>KONTRAKTOR</th>
            <td>: {{ $proyek->kontraktor ?? '—' }}</td>
        </tr>
        <tr>
            <th>LOKASI</th>
            <td>: {{ $proyek->lokasi ?? '—' }}</td>
            <th>PEKERJAAN</th>
            <td>: {{ $proyek->jenis_pekerjaan ?? ($penawaran->nama_penawaran ?? '—') }}</td>
        </tr>
        <tr>
            <th>TANGGAL</th>
            <td>: {{ \Carbon\Carbon::parse($penawaran->tanggal_penawaran)->translatedFormat('d F Y') }}</td>
            <th>VERSI</th>
            <td>: {{ $penawaran->versi }}</td>
        </tr>
    </table>

    @php
        // formatter: kosongkan jika 0, selain itu tampilkan Rp nn
        if (!function_exists('rupiah_or_blank')) {
            function rupiah_or_blank($n){
                $n = (float)$n;
                return $n == 0.0 ? '' : 'Rp&nbsp;'.number_format($n, 0, ',', '.');
            }
        }
        // terbilang sederhana
        if (!function_exists('terbilang_id')) {
            function terbilang_id($x){
                $x = (int)floor($x);
                $abil = ["","Satu","Dua","Tiga","Empat","Lima","Enam","Tujuh","Delapan","Sembilan","Sepuluh","Sebelas"];
                if($x < 12) return $abil[$x];
                if($x < 20) return terbilang_id($x-10)." Belas";
                if($x < 100) return terbilang_id(intval($x/10))." Puluh ".terbilang_id($x%10);
                if($x < 200) return "Seratus ".terbilang_id($x-100);
                if($x < 1000) return terbilang_id(intval($x/100))." Ratus ".terbilang_id($x%100);
                if($x < 2000) return "Seribu ".terbilang_id($x-1000);
                if($x < 1000000) return terbilang_id(intval($x/1000))." Ribu ".terbilang_id($x%1000);
                if($x < 1000000000) return terbilang_id(intval($x/1000000))." Juta ".terbilang_id($x%1000000);
                if($x < 1000000000000) return terbilang_id(intval($x/1000000000))." Milyar ".terbilang_id($x%1000000000);
                return terbilang_id(intval($x/1000000000000))." Triliun ".terbilang_id($x%1000000000000);
            }
        }

        // Grup top-level: "1", "2", dst
        $groups = collect($penawaran->sections)->groupBy(function($s){
            $k = (string)optional($s->rabHeader)->kode;
            return explode('.', $k)[0] ?? $k;
        });

        $grandMat = 0; $grandJasa = 0;
    @endphp

    <table>
        <thead>
            <tr>
                <th style="width:8%;">NO</th>
                <th>DESKRIPSI</th>
                <th style="width:17%;">SUB TOTAL MATERIAL</th>
                <th style="width:17%;">SUB TOTAL JASA</th>
                <th style="width:17%;">TOTAL MATERIAL</th>
                <th style="width:17%;">TOTAL JASA</th>
            </tr>
        </thead>
        <tbody>
        @foreach($groups as $top => $sections)
            @php
                // Header top-level (kode tanpa titik)
                $parent = $sections->first(function($s){
                    $k = (string)optional($s->rabHeader)->kode;
                    return $k !== '' && strpos($k, '.') === false;
                });
                $parentKode = optional(optional($parent)->rabHeader)->kode ?? $top;
                $parentDesc = optional(optional($parent)->rabHeader)->deskripsi ?? ('Bagian '.$top);

                // Total M/J per top-level
                $groupMat = 0; $groupJasa = 0;
                foreach ($sections as $sec) {
                    foreach ($sec->items as $it) {
                        $v = (float)($it->volume ?? 0);
                        $groupMat  += (float)($it->harga_material_penawaran_item ?? 0) * $v;
                        $groupJasa += (float)($it->harga_upah_penawaran_item ?? 0) * $v;
                    }
                }
                $grandMat += $groupMat; $grandJasa += $groupJasa;
            @endphp

            {{-- Baris Top-Level: isi kolom TOTAL --}}
            <tr style="background:#f9fbff;">
                <td class="fw-bold">{{ $parentKode }}</td>
                <td class="fw-bold">{{ strtoupper($parentDesc) }}</td>
                <td class="text-end currency"></td>
                <td class="text-end currency"></td>
                <td class="text-end fw-bold currency">{!! rupiah_or_blank($groupMat) !!}</td>
                <td class="text-end fw-bold currency">{!! rupiah_or_blank($groupJasa) !!}</td>
            </tr>

            {{-- Anak (1.1, 1.2, ...) → isi kolom SUB --}}
            @foreach($sections->sortBy(fn($s)=> optional($s->rabHeader)->kode) as $sec)
                @php
                    $kode = optional($sec->rabHeader)->kode ?? '';
                    $desc = optional($sec->rabHeader)->deskripsi ?? '';
                    if ($kode==='' || strpos($kode,'.')===false) continue;

                    $secMat = 0; $secJasa = 0;
                    foreach ($sec->items as $it) {
                        $v = (float)($it->volume ?? 0);
                        $secMat  += (float)($it->harga_material_penawaran_item ?? 0) * $v;
                        $secJasa += (float)($it->harga_upah_penawaran_item ?? 0) * $v;
                    }
                @endphp
                <tr>
                    <td>{{ $kode }}</td>
                    <td>{{ $desc }}</td>
                    <td class="text-end currency">{!! rupiah_or_blank($secMat) !!}</td>
                    <td class="text-end currency">{!! rupiah_or_blank($secJasa) !!}</td>
                    <td class="text-end currency"></td>
                    <td class="text-end currency"></td>
                </tr>
            @endforeach

            {{-- Total per top-level --}}
            <tr class="totals-row">
                {{-- merge 4 kolom pertama & ratakan kanan supaya dekat ke kolom total --}}
                <td colspan="4" class="total-label">TOTAL {{ strtoupper($parentDesc) }}</td>
                <td class="text-end currency">{!! rupiah_or_blank($groupMat) !!}</td>
                <td class="text-end currency">{!! rupiah_or_blank($groupJasa) !!}</td>
            </tr>
        @endforeach
        </tbody>

        @php $grandAll = $grandMat + $grandJasa; @endphp
        <tfoot>
            {{-- Baris 1: Grand Total Material & Jasa sejajar kolom TOTAL --}}
            <tr class="totals-row" style="background:#eef3ff;">
                <td colspan="4" class="text-end"><strong>TOTAL</strong></td>
                <td class="text-end currency"><strong>{!! rupiah_or_blank($grandMat) !!}</strong></td>
                <td class="text-end currency"><strong>{!! rupiah_or_blank($grandJasa) !!}</strong></td>
            </tr>
            {{-- Baris 2: Grand Total keseluruhan (M + J) --}}
            <tr class="totals-row" style="background:#e6f7ff;">
                <td colspan="4" class="text-end"><strong>GRAND TOTAL</strong></td>
                <td colspan="2" class="text-end currency"><strong>{!! rupiah_or_blank($grandAll) !!}</strong></td>
            </tr>
        </tfoot>

    </table>

    @php
        $terbilang = trim(preg_replace('/\s+/', ' ', terbilang_id($grandAll)));
    @endphp
    <table style="border:none; margin-top:6px;">
        <tr>
            <td style="border:none; width:15%;"><strong>Terbilang :</strong></td>
            <td style="border:none;"><em>{{ $terbilang }} Rupiah</em></td>
        </tr>
    </table>

    <div class="page-break"></div>

    {{-- ===================== HALAMAN 2+: DETAIL (split M/J, Area & Spesifikasi) ===================== --}}
    <h3>Detail Bagian Penawaran</h3>

    @php
        $groupsDetail = collect($penawaran->sections)->groupBy(function($s){
            $k = (string)optional($s->rabHeader)->kode;
            return explode('.', $k)[0] ?? $k;
        });
    @endphp

    @foreach($groupsDetail as $top => $sections)
        @php
            $parent = $sections->first(function($s){
                $k = (string)optional($s->rabHeader)->kode;
                return $k !== '' && strpos($k, '.') === false;
            });
            $parentKode = optional(optional($parent)->rabHeader)->kode ?? $top;
            $parentDesc = optional(optional($parent)->rabHeader)->deskripsi ?? 'Bagian '.$top;

            $groupMaterial = 0; $groupJasa = 0;
        @endphp

        <div class="section-wrapper">
            <div class="section-header">
                <div>{{ $parentKode }} - {{ $parentDesc }}</div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th style="width:10%;">Kode</th>
                        <th style="width:30%;">Uraian Pekerjaan</th>
                        <th style="width:8%;">Volume</th>
                        <th style="width:8%;">Satuan</th>
                        <th style="width:11%;">Harga Material</th>
                        <th style="width:11%;">Harga Jasa</th>
                        <th style="width:11%;">Total Material</th>
                        <th style="width:11%;">Total Jasa</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($sections->sortBy(fn($s)=> optional($s->rabHeader)->kode) as $section)
                    @php
                        $secKode = optional($section->rabHeader)->kode ?? '';
                        $secDesc = optional($section->rabHeader)->deskripsi ?? '';
                        $subMaterial = 0; $subJasa = 0;
                    @endphp

                    @if($secKode && strpos($secKode, '.') !== false)
                        <tr class="row-subheader">
                            <td colspan="8">{{ $secKode }} - {{ $secDesc }}</td>
                        </tr>
                    @endif

                    @php
                        $itemsByArea = $section->items->groupBy(function($it){
                            $a = is_string($it->area) ? trim($it->area) : '';
                            return $a !== '' ? $a : '__NOAREA__';
                        });
                    @endphp

                    @foreach($itemsByArea as $areaName => $items)
                        @if($areaName !== '__NOAREA__')
                            <tr class="row-area">
                                <td colspan="8">Area: {{ $areaName }}</td>
                            </tr>
                        @endif

                        @foreach($items as $item)
                            @php
                                $vol      = (float) ($item->volume ?? 0);
                                $unitMat  = (float) ($item->harga_material_penawaran_item ?? 0);
                                $unitJasa = (float) ($item->harga_upah_penawaran_item ?? 0);
                                $totMat   = $unitMat  * $vol;
                                $totJasa  = $unitJasa * $vol;
                                $subMaterial += $totMat; $subJasa += $totJasa;
                            @endphp
                            <tr>
                                <td>{{ $item->kode }}</td>
                                <td>
                                    {{ $item->deskripsi }}
                                    @if(!empty($item->spesifikasi))
                                        <span class="spec preline">{!! nl2br(e($item->spesifikasi)) !!}</span>
                                    @endif
                                </td>
                                <td class="text-end">{{ number_format($vol,2,',','.') }}</td>
                                <td>{{ $item->satuan }}</td>
                                <td class="text-end currency">{!! rupiah_or_blank($unitMat) !!}</td>
                                <td class="text-end currency">{!! rupiah_or_blank($unitJasa) !!}</td>
                                <td class="text-end currency">{!! rupiah_or_blank($totMat) !!}</td>
                                <td class="text-end currency">{!! rupiah_or_blank($totJasa) !!}</td>
                            </tr>
                        @endforeach
                    @endforeach

                    @if($section->items->count() > 0)
                        <tr class="totals-row">
                            <td colspan="6" class="text-end">Subtotal {{ $secDesc ?: '' }}</td>
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
                        <td colspan="6" class="text-end">Subtotal ({{ $parentDesc }})</td>
                        <td class="text-end currency">{!! rupiah_or_blank($groupMaterial) !!}</td>
                        <td class="text-end currency">{!! rupiah_or_blank($groupJasa) !!}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @endforeach

</div>
</body>
</html>

<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <style>
body { font-family:'DejaVu Sans',sans-serif; font-size:10px; line-height:1.4; color:#333; }
        .container { width:100%; margin:0 auto; padding:20px; }

        h2 { text-align:center; margin:0 0 16px; font-weight:700; color:#000; }
        h3 { font-size:14px; color:#0056b3; margin:16px 0 8px; }

        /* halaman landscape bernama "landscape" */
        @page landscape {
            size: A4 landscape;
            margin: 12mm;
        }

        /* elemen dengan kelas/atribut ini akan memakai page box "landscape" */
        .landscape {
            page: landscape;
        }

        /* (opsional) rapikan tabel di landscape */
        .landscape table { font-size: 9.5px; }
        .landscape th, .landscape td { padding: 4px 6px; }

        /* ringankan baris */
        .tbl-detail th, .tbl-detail td { padding: 4px 6px; line-height: 1.15; }

        /* kolom uraian 1 baris (tidak wrap) */
        .nowrap { white-space: nowrap; word-break: normal; hyphens: none; }

        /* styling uraian & spesifikasi */
        .desc { font-size: 9.5px; }
        .spec { display: block; margin-top: 3px; font-size: 9px; color: #666; white-space: normal; }

        /* header subbagian */
        .row-subheader td { background:#f7fbff; font-weight:600; }
        .row-area td      { background:#eef8ff; font-weight:600; font-style:italic; }

        table { width:100%; border-collapse:collapse; margin-bottom:14px; }
        th,td { border:1px solid #ddd; padding:6px; vertical-align:top; }
        th { background:#f2f2f2; font-weight:bold; color:#555; }

        .text-end { text-align:right; }
        .fw-bold { font-weight:bold; }
        .currency { white-space:nowrap; } /* cegah "Rp 340.000" pecah baris */

        /* identitas proyek: rata kiri */
        .info-table { width:100%; border-collapse:collapse; margin-bottom:14px; }
        .info-table th, .info-table td { border:1px solid #ddd; padding:6px; }
        .info-table th { width:22%; white-space:nowrap; text-align:left; background:#f2f2f2; }
        .info-table td { width:78%; text-align:left; }

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

            <table class="tbl-detail">
                <colgroup>
                <col style="width:10%">
                <col style="width:35%"> {{-- uraian bisa lebih lega di landscape --}}
                <col style="width:8%">
                <col style="width:8%">
                <col style="width:11%">
                <col style="width:11%">
                <col style="width:9%">
                <col style="width:8%">
                </colgroup>
                <thead>
                <tr>
                    <th>Kode</th>
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
                                <td colspan="8">{{ $areaName }}</td>
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

                                // Batas panjang agar uraian tetap 1 baris (ubah angka 100 sesuai kebutuhan)
                                $uraian1baris = mb_strimwidth($item->deskripsi ?? '', 0, 100, '…', 'UTF-8');
                            @endphp
                            <tr>
                                <td>{{ $item->kode }}</td>
                                <td class="desc nowrap">
                                    {{ $uraian1baris }}
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
                            <td colspan="6" class="text-end">SUBTOTAL {{ $secDesc ?: '' }}</td>
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
                    <td colspan="6" class="text-end">SUBTOTAL ({{ $parentDesc }})</td>
                    <td class="text-end currency">{!! rupiah_or_blank($groupMaterial) !!}</td>
                    <td class="text-end currency">{!! rupiah_or_blank($groupJasa) !!}</td>
                </tr>
                </tfoot>
            </table>
            </div>
        @endforeach

</body>
</html>

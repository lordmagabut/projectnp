@php
    // Export sebagai nilai mentah agar Excel tidak salah baca pemisah ribuan/desimal.
    $num = fn($n, $dec = 0) => round((float)$n, $dec);
@endphp

<table>
    <tr><th colspan="8">Penawaran</th></tr>
    <tr><td>Nomor</td><td>{{ $penawaran->nomor_penawaran ?? '-' }}</td></tr>
    <tr><td>Nama</td><td>{{ $penawaran->nama_penawaran }}</td></tr>
    <tr><td>Tanggal</td><td>{{ \Carbon\Carbon::parse($penawaran->tanggal_penawaran)->format('d-m-Y') }}</td></tr>
    <tr><td>Proyek</td><td>{{ $proyek->nama_proyek }} - {{ $proyek->pemberiKerja->nama_pemberi_kerja ?? '' }}</td></tr>
    <tr><td>Mode Harga</td><td>{{ $mode === 'gab' ? 'Gabungan (Single Price)' : 'Pisah Material+Jasa' }}</td></tr>
    <tr><td>Diskon</td><td>{{ $num($calc['discPct'],2) }}%</td></tr>
    <tr><td>Subtotal setelah Diskon</td><td>{{ $num($calc['subtotal'],0) }}</td></tr>
    <tr><td>Total + PPN</td><td>{{ $num($calc['totalPlusPpn'],0) }}</td></tr>
    <tr><td>Total Dibayar (Nett)</td><td>{{ $num($calc['totalDibayar'],0) }}</td></tr>
</table>

<table>
    <tr><th colspan="3">Ringkasan Pajak</th></tr>
    <tr>
        <td>Mode PPN</td>
        <td>{{ $calc['isTaxable'] ? strtoupper($calc['ppnMode']) : 'Tidak Kena PPN' }} (Tarif {{ rtrim(rtrim(number_format($calc['ppnRate'],3,',','.'),'0'),',') }}%)</td>
        <td></td>
    </tr>
    <tr><td>DPP</td><td>Gabungan</td><td>{{ $num($calc['dpp'],0) }}</td></tr>
    <tr><td>PPN</td><td>{{ $calc['isTaxable'] ? ($calc['ppnMode']==='include' ? 'Tersirat di subtotal' : 'Ditambahkan ke DPP') : '—' }}</td><td>{{ $num($calc['ppn'],0) }}</td></tr>
    <tr><td>PPh</td><td>{{ $calc['applyPph'] ? ('Dipungut atas '.($calc['pphDppSource']==='material_jasa'?'Material + Jasa':'Jasa saja').' — Basis '.strtoupper($calc['pphBaseKind']).', Tarif '.rtrim(rtrim(number_format($calc['pphRate'],3,',','.'),'0'),',').'%') : 'Tidak dipotong' }}</td><td>- {{ $num($calc['pph'],0) }}</td></tr>
</table>

<table>
    @if($mode === 'gab')
        <tr>
            <th>Kode</th><th>Uraian</th><th>Spesifikasi</th><th>Area</th><th>Volume</th><th>Satuan</th><th>Harga Satuan (Gabung)</th><th>Total Item</th>
        </tr>
        @foreach($penawaran->sections as $sec)
            <tr><td colspan="8"><strong>{{ $sec->rabHeader->kode ?? 'N/A' }} - {{ $sec->rabHeader->deskripsi ?? 'Bagian RAB' }}</strong></td></tr>
            @php
                $itemsByArea = ($sec->items ?? collect())->groupBy(function($it){
                    $a = is_string($it->area) ? trim($it->area) : '';
                    return $a !== '' ? $a : '__NOAREA__';
                });
            @endphp
            @foreach($itemsByArea as $areaName => $items)
                @if($areaName !== '__NOAREA__')
                    <tr><td colspan="8">Area: {{ $areaName }}</td></tr>
                @endif
                @foreach($items as $it)
                    @php
                        $vol  = (float)($it->volume ?? 0);
                        $unit = (float)($it->harga_material_penawaran_item ?? 0) * $calc['discCoef'];
                        $tot  = $unit * $vol;
                    @endphp
                    <tr>
                        <td>{{ $it->kode }}</td>
                        <td>{{ $it->deskripsi }}</td>
                        <td>{{ $it->spesifikasi }}</td>
                        <td>{{ $it->area }}</td>
                        <td>{{ $num($vol,2) }}</td>
                        <td>{{ $it->satuan }}</td>
                        <td>{{ $num($unit,0) }}</td>
                        <td>{{ $num($tot,0) }}</td>
                    </tr>
                @endforeach
            @endforeach
        @endforeach
    @else
        <tr>
            <th>Kode</th><th>Uraian</th><th>Spesifikasi</th><th>Area</th><th>Volume</th><th>Satuan</th><th>Hrg Sat Material (disc)</th><th>Hrg Sat Jasa (disc)</th><th>Total Material</th><th>Total Jasa</th><th>Total Item</th>
        </tr>
        @foreach($penawaran->sections as $sec)
            <tr><td colspan="11"><strong>{{ $sec->rabHeader->kode ?? 'N/A' }} - {{ $sec->rabHeader->deskripsi ?? 'Bagian RAB' }}</strong></td></tr>
            @php
                $itemsByArea = ($sec->items ?? collect())->groupBy(function($it){
                    $a = is_string($it->area) ? trim($it->area) : '';
                    return $a !== '' ? $a : '__NOAREA__';
                });
            @endphp
            @foreach($itemsByArea as $areaName => $items)
                @if($areaName !== '__NOAREA__')
                    <tr><td colspan="11">Area: {{ $areaName }}</td></tr>
                @endif
                @foreach($items as $it)
                    @php
                        $vol      = (float)($it->volume ?? 0);
                        $unitMat  = (float)($it->harga_material_penawaran_item ?? 0) * $calc['discCoef'];
                        $unitJasa = (float)($it->harga_upah_penawaran_item ?? 0) * $calc['discCoef'];
                        $totMat   = $unitMat * $vol;
                        $totJasa  = $unitJasa * $vol;
                        $totAll   = $totMat + $totJasa;
                    @endphp
                    <tr>
                        <td>{{ $it->kode }}</td>
                        <td>{{ $it->deskripsi }}</td>
                        <td>{{ $it->spesifikasi }}</td>
                        <td>{{ $it->area }}</td>
                        <td>{{ $num($vol,2) }}</td>
                        <td>{{ $it->satuan }}</td>
                        <td>{{ $num($unitMat,0) }}</td>
                        <td>{{ $num($unitJasa,0) }}</td>
                        <td>{{ $num($totMat,0) }}</td>
                        <td>{{ $num($totJasa,0) }}</td>
                        <td>{{ $num($totAll,0) }}</td>
                    </tr>
                @endforeach
            @endforeach
        @endforeach
    @endif
</table>

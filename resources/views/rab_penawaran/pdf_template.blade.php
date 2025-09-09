<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Penawaran Proyek - {{ $penawaran->nama_penawaran }}</title>
    <style>
        /* Gaya CSS untuk PDF */
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            line-height: 1.4;
            color: #333;
        }
        .container { width: 100%; margin: 0 auto; padding: 20px; }
        h1, h2, h3, h4, h5, h6 { margin: 0 0 10px; color: #0056b3; }
        h3 { font-size: 14px; }
        h5 { font-size: 12px; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th, td { border: 1px solid #ddd; padding: 6px; vertical-align: top; }
        th { background-color: #f2f2f2; font-weight: bold; color: #555; }

        .text-end { text-align: right; }
        .fw-bold { font-weight: bold; }
        .text-success { color: #28a745; }
        .text-danger { color: #dc3545; }
        .text-info { color: #17a2b8; }

        .badge {
            display: inline-block; padding: .25em .4em; font-size: 75%; font-weight: 700;
            line-height: 1; text-align: center; white-space: nowrap; vertical-align: baseline;
            border-radius: .25rem; color: #fff; background-color: #6c757d;
        }
        .badge.bg-warning { background-color: #ffc107; color: #333; }
        .badge.bg-success { background-color: #28a745; }
        .badge.bg-info { background-color: #17a2b8; }

        .section-header {
            background-color: #e9f5ff;
            padding: 8px; margin-top: 15px; margin-bottom: 5px;
            border: 1px solid #cce5ff; font-weight: bold; font-size: 11px;
            display: flex; justify-content: space-between; align-items: center;
        }

        /* Mirip split: header Area & spesifikasi */
        .row-area td { background:#eef8ff; font-weight:600; font-style:italic; }
        .spec { display:block; margin-top:4px; font-size:9px; color:#555; white-space: pre-line; }

        /* DomPDF helpers */
        thead { display: table-header-group; } /* ulangi thead tiap halaman */
        tr { page-break-inside: avoid; }

        .section-header .kode-deskripsi { display:inline-block; }
    </style>
</head>
<body>
    <div class="container">
        <h2 style="text-align:center; margin-bottom:20px;">RINGKASAN PENAWARAN PROYEK</h2>

        <h3>Informasi Penawaran</h3>
        <table>
            <tr>
                <th style="width:25%;">Nama Penawaran</th>
                <td>{{ $penawaran->nama_penawaran }}</td>
            </tr>
            <tr>
                <th>Tanggal Penawaran</th>
                <td>{{ \Carbon\Carbon::parse($penawaran->tanggal_penawaran)->format('d-m-Y') }}</td>
            </tr>
            <tr>
                <th>Versi</th>
                <td>{{ $penawaran->versi }}</td>
            </tr>
            <tr>
                <th>Proyek</th>
                <td>
                    {{ $proyek->nama_proyek }}
                    @if(!empty($proyek->pemberiKerja?->nama_pemberi_kerja))
                        - {{ $proyek->pemberiKerja->nama_pemberi_kerja }}
                    @endif
                </td>
            </tr>
            <tr>
                <th>Total Bruto</th>
                <td class="fw-bold text-info">Rp {{ number_format($penawaran->total_penawaran_bruto, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <th>Diskon (%)</th>
                <td>{{ number_format($penawaran->discount_percentage, 2, ',', '.') }}%</td>
            </tr>
            <tr>
                <th>Jumlah Diskon</th>
                <td class="fw-bold text-danger">Rp {{ number_format($penawaran->discount_amount, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <th>Total Akhir Penawaran</th>
                <td class="fw-bold text-success" style="font-size:14px;">Rp {{ number_format($penawaran->final_total_penawaran, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <th>Status</th>
                <td>
                    @if($penawaran->status == 'draft')
                        <span class="badge bg-warning">Draft</span>
                    @elseif($penawaran->status == 'final')
                        <span class="badge bg-success">Final</span>
                    @else
                        <span class="badge">{{ ucfirst($penawaran->status) }}</span>
                    @endif
                </td>
            </tr>
        </table>

        <div style="margin-top:30px;">
            <h3>Detail Bagian Penawaran</h3>

            @php $grandTotal = 0; @endphp

            @forelse($penawaran->sections as $section)
                @php $hasItems = $section->items->isNotEmpty(); @endphp

                <div class="section-header">
                    <div class="kode-deskripsi">
                        {{ $section->rabHeader->kode ?? 'N/A' }} - {{ $section->rabHeader->deskripsi ?? 'Bagian RAB Tidak Ditemukan' }}
                    </div>
                    {{-- Subtotal dipindah ke bawah tabel --}}
                </div>

                @if($hasItems)
                    <table class="item-table">
                        <thead>
                            <tr>
                                <th style="width:10%;">Kode</th>
                                <th style="width:32%;">Uraian / Spesifikasi</th>
                                <th style="width:10%;" class="text-end">Volume</th>
                                <th style="width:10%;">Satuan</th>
                                <th style="width:18%;" class="text-end">Harga Satuan Penawaran</th>
                                <th style="width:20%;" class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                // Group by Area dari rab_penawaran_items.area
                                $itemsByArea = $section->items->groupBy(function($it){
                                    $area = is_string($it->area) ? trim($it->area) : '';
                                    return $area !== '' ? $area : '__NOAREA__';
                                });
                                $subTotal = 0;
                            @endphp

                            @foreach($itemsByArea as $areaName => $items)
                                {{-- Header Area (jika ada nama area) --}}
                                @if($areaName !== '__NOAREA__')
                                    <tr class="row-area">
                                        <td colspan="6">Area: {{ $areaName }}</td>
                                    </tr>
                                @endif

                                @foreach($items as $item)
                                    @php
                                        $vol   = (float) ($item->volume ?? 0);
                                        $unit  = (float) ($item->harga_satuan_penawaran ?? 0);
                                        $total = (float) ($item->total_penawaran_item ?? ($unit * $vol));
                                        $subTotal += $total;

                                        $spes = $item->spesifikasi; // spesifikasi dari item
                                    @endphp
                                    <tr>
                                        <td>{{ $item->kode }}</td>
                                        <td>
                                            {{ $item->deskripsi }}
                                            @if(!empty($spes))
                                                <span class="spec">{!! nl2br(e($spes)) !!}</span>
                                            @endif
                                        </td>
                                        <td class="text-end">{{ number_format($vol, 2, ',', '.') }}</td>
                                        <td>{{ $item->satuan }}</td>
                                        <td class="text-end">Rp {{ number_format($unit, 0, ',', '.') }}</td>
                                        <td class="text-end fw-bold">Rp {{ number_format($total, 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>

                        {{-- Subtotal Bagian di BAWAH tabel --}}
                        <tfoot>
                            <tr>
                                <td colspan="5" class="text-end"><strong>Subtotal Bagian (Total)</strong></td>
                                <td class="text-end"><strong>Rp {{ number_format($subTotal, 0, ',', '.') }}</strong></td>
                            </tr>
                        </tfoot>
                    </table>

                    @php $grandTotal += $subTotal; @endphp
                @endif

                @if(!$loop->last)
                    {{-- <div class="page-break"></div> --}}
                @endif
            @empty
                <p style="text-align:center; font-style:italic; color:#777;">Belum ada bagian penawaran yang dibuat untuk penawaran ini.</p>
            @endforelse
        </div>

        {{-- Ringkasan Total & Diskon (akhir dokumen, mirip split) --}}
        @php
            // Utamakan angka dari header agar konsisten,
            // fallback ke hasil penjumlahan tabel jika header kosong.
            $grandBruto = $penawaran->total_penawaran_bruto ?? $grandTotal;
            $discPct    = (float) ($penawaran->discount_percentage ?? 0);
            $discAmt    = $penawaran->discount_amount ?? ($grandBruto * $discPct / 100);
            $final      = $penawaran->final_total_penawaran ?? ($grandBruto - $discAmt);
        @endphp

        <h3 style="margin-top:25px;">Ringkasan Total & Diskon</h3>
        <table>
            <tr>
                <th style="width:40%;">Total Bruto</th>
                <td class="text-end fw-bold">Rp {{ number_format($grandBruto, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <th>Diskon ({{ number_format($discPct, 2, ',', '.') }}%)</th>
                <td class="text-end fw-bold text-danger">Rp {{ number_format($discAmt, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <th>Total Akhir</th>
                <td class="text-end fw-bold text-success" style="font-size:12px;">Rp {{ number_format($final, 0, ',', '.') }}</td>
            </tr>
        </table>

    </div>
</body>
</html>

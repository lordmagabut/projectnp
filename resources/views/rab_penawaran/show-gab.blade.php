@extends('layout.master')

@push('plugin-styles')
{{-- Font Awesome untuk ikon --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
{{-- Animate.css untuk animasi (opsional) --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
<style>
    /* Customisasi umum untuk card dan tabel */
    .card {
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        border: none;
    }
    .card-header {
        border-top-left-radius: 12px;
        border-top-right-radius: 12px;
        padding: 1.25rem 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .table-borderless th, .table-borderless td {
        padding-top: 0.75rem;
        padding-bottom: 0.75rem;
        vertical-align: top;
        border-top: none;
    }
    .table-borderless th {
        color: #495057;
        font-weight: 600;
        width: 200px; /* Lebar tetap untuk label */
    }
    .table-bordered thead th {
        background-color: #e9ecef;
        color: #495057;
        font-weight: 600;
        border-bottom: 2px solid #dee2e6;
    }
    .table-bordered tbody tr:hover {
        background-color: #f2f2f2;
    }
    .badge {
        font-size: 0.85em;
        padding: 0.5em 0.75em;
        border-radius: 1rem;
    }
    .section-header-row {
        background-color: #e9f5ff; /* Light blue for section headers */
        color: #0056b3;
        font-weight: bold;
        cursor: pointer;
        transition: background-color 0.2s ease;
    }
    .section-header-row:hover {
        background-color: #d1e7ff;
    }
    .section-header-row .collapse-icon {
        transition: transform 0.2s ease;
    }
    .section-header-row[aria-expanded="true"] .collapse-icon {
        transform: rotate(180deg);
    }
    .item-table-container {
        padding: 10px;
        background-color: #f8fafd; /* Very light gray for item tables */
        border-left: 1px solid #e0e0e0;
        border-right: 1px solid #e0e0e0;
        border-bottom: 1px solid #e0e0e0;
        border-bottom-left-radius: 8px;
        border-bottom-right-radius: 8px;
    }
    .item-table th, .item-table td {
        white-space: nowrap;
        font-size: 0.875rem;
    }
    .item-table thead th {
        background-color: #f0f8ff; /* Even lighter blue for item table headers */
        color: #495057;
    }
    .item-table tbody tr:nth-child(even) {
        background-color: #ffffff;
    }
    .item-table tbody tr:nth-child(odd) {
        background-color: #f8fafd;
    }
</style>
@endpush

@php
    $flatItems = $flatItems ?? [];
@endphp

@section('content')
<div class="card shadow-sm animate__animated animate__fadeIn">
    <div class="card-header bg-primary text-white">
        <h4 class="card-title mb-0 d-flex align-items-center">
            <i class="fas fa-file-invoice-dollar me-2"></i> Detail Penawaran
        </h4>
        <a href="{{ route('proyek.show', $proyek->id) }}#rabContent" class="btn btn-light btn-sm d-inline-flex align-items-center">
            <i class="fas fa-arrow-left me-1"></i> Kembali ke RAB Proyek
        </a>
    </div>
    <div class="card-body p-3 p-md-4">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show animate__animated animate__fadeIn mb-4" role="alert">
                <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show animate__animated animate__fadeIn mb-4" role="alert">
                <i class="fas fa-times-circle me-2"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

                @php
                    $sumMaterial = 0.0; $sumJasa = 0.0;
                    foreach ($penawaran->sections as $sec) {
                            foreach (($sec->items ?? []) as $it) {
                                    $vol = (float) ($it->volume ?? 0);
                                    $sumMaterial += (float)($it->harga_material_penawaran_item ?? 0) * $vol;
                                    $sumJasa     += (float)($it->harga_upah_penawaran_item     ?? 0) * $vol;
                            }
                    }

                    $discPct   = (float)($penawaran->discount_percentage ?? 0);
                    $discCoef  = max(0, 1 - ($discPct / 100));
                    $matNet    = $sumMaterial * $discCoef;
                    $jasaNet   = $sumJasa     * $discCoef;
                    $subtotal  = $matNet + $jasaNet;

                    $tax       = optional($proyek->taxProfileAktif);
                    $isTaxable = (int)($tax->is_taxable ?? 1) === 1;
                    $ppnMode   = $tax->ppn_mode ?? 'exclude';
                    $ppnRate   = (float)($tax->ppn_rate ?? 11.0);
                    $applyPph  = (int)($tax->apply_pph ?? 0) === 1;
                    $pphRate   = (float)($tax->pph_rate ?? 2.0);
                    $pphBaseKind = (string)($tax->pph_base ?? 'dpp');
                    $extraOpts = is_array($tax->extra_options ?? null) ? $tax->extra_options : [];
                    $pphDppSource = (string)($extraOpts['pph_dpp_source'] ?? 'jasa');

                    if (!$isTaxable) {
                            $dpp = $subtotal; $ppn = 0.0; $totalPlusPpn = $subtotal;
                    } elseif ($ppnMode === 'include') {
                            $dpp = $subtotal / (1 + $ppnRate/100);
                            $ppn = $subtotal - $dpp;
                            $totalPlusPpn = $subtotal;
                    } else {
                            $dpp = $subtotal;
                            $ppn = $dpp * ($ppnRate/100);
                            $totalPlusPpn = $dpp + $ppn;
                    }

                    $jasaDpp = ($isTaxable && $ppnMode === 'include') ? ($jasaNet / (1 + $ppnRate/100)) : $jasaNet;

                    if ($applyPph) {
                        if ($pphDppSource === 'material_jasa') {
                            $pphBaseAmount = ($pphBaseKind === 'dpp') ? $dpp : $subtotal;
                        } else {
                            $pphBaseAmount = ($pphBaseKind === 'dpp') ? $jasaDpp : $jasaNet;
                        }
                        $pph = $pphBaseAmount * ($pphRate/100);
                    } else {
                        $pph = 0.0;
                    }

                    $totalDibayar = $totalPlusPpn - $pph;
                    $rf = fn($n) => 'Rp ' . number_format((float)$n, 0, ',', '.');
                @endphp

                <h5 class="mb-3 text-primary"><i class="fas fa-info-circle me-2"></i> Informasi Penawaran</h5>
                <div class="table-responsive mb-4">
                        <table class="table table-borderless table-sm detail-table">
                                <tbody>
                                    <tr><th>Nomor Penawaran</th><td>: {{ $penawaran->nomor_penawaran ?? '—' }}</td></tr>
                                    <tr><th>Nama Penawaran</th><td>: {{ $penawaran->nama_penawaran }}</td></tr>
                                    <tr><th>Tanggal Penawaran</th><td>: {{ \Carbon\Carbon::parse($penawaran->tanggal_penawaran)->format('d-m-Y') }}</td></tr>
                                    <tr><th>Proyek</th><td>: {{ $proyek->nama_proyek }} - {{ $proyek->pemberiKerja->nama_pemberi_kerja ?? '' }}</td></tr>
                                    <tr><th>Total (sebelum diskon)</th><td class="fw-bold text-info">: {{ $rf($sumMaterial + $sumJasa) }}</td></tr>
                                    <tr><th>Diskon (%)</th><td>: {{ number_format($discPct, 2, ',', '.') }}%</td></tr>
                                    <tr><th>Subtotal setelah Diskon</th><td class="fw-bold text-info">: {{ $rf($subtotal) }}</td></tr>
                                    <tr><th>Status</th><td>:
                                        @if($penawaran->status == 'draft')
                                            <span class="badge bg-warning text-dark"><i class="fas fa-hourglass-half me-1"></i> Draft</span>
                                        @elseif($penawaran->status == 'final')
                                            <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i> Final</span>
                                        @else
                                            <span class="badge bg-secondary">{{ ucfirst($penawaran->status) }}</span>
                                        @endif
                                    </td></tr>
                                    @if($penawaran->status === 'final')
                                        @php
                                            $docs = collect($penawaran->approval_doc_paths ?? [])
                                                ->when(empty($penawaran->approval_doc_paths ?? null) && !empty($penawaran->approval_doc_path ?? null),
                                                    fn($c)=>$c->push($penawaran->approval_doc_path));
                                        @endphp
                                        @if($docs->isNotEmpty())
                                            <tr><th>Dokumen SPK/PO</th><td>:
                                                <ul class="mb-0 ps-3">
                                                    @foreach($docs as $idx => $p)
                                                        <li>
                                                            <a href="{{ route('proyek.penawaran.approval.download', [$proyek->id, $penawaran->id, base64_encode($p)]) }}" target="_blank" class="text-primary">
                                                                <i class="fas fa-file-pdf me-1"></i> Dokumen {{ $idx + 1 }}
                                                            </a>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </td></tr>
                                        @endif
                                    @endif
                                </tbody>
                        </table>
                </div>

                @if($penawaran->status === 'final')
                    <h5 class="mb-3 text-primary"><i class="fas fa-file-contract me-2"></i> Informasi Proyek</h5>
                    <div class="table-responsive mb-4">
                        <table class="table table-borderless table-sm">
                            <tr>
                                <th style="width: 200px">Nomor SPK</th>
                                <td>: <strong>{{ $proyek->no_spk ?? '-' }}</strong></td>
                            </tr>
                            <tr>
                                <th>Periode Proyek</th>
                                <td>: <strong>{{ $proyek->tanggal_mulai ? \Carbon\Carbon::parse($proyek->tanggal_mulai)->format('d-m-Y') : '-' }}</strong> s/d <strong>{{ $proyek->tanggal_selesai ? \Carbon\Carbon::parse($proyek->tanggal_selesai)->format('d-m-Y') : '-' }}</strong></td>
                            </tr>
                            <tr>
                                <th>Uang Muka (DP)</th>
                                <td>:
                                    @if($proyek->gunakan_uang_muka)
                                        <span class="badge bg-info">Digunakan</span> &nbsp; <strong>{{ $proyek->persen_dp ?? 0 }}%</strong>
                                    @else
                                        <span class="badge bg-light text-dark">Tidak Digunakan</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Retensi</th>
                                <td>:
                                    @if($proyek->gunakan_retensi)
                                        <span class="badge bg-info">Digunakan</span> &nbsp; <strong>{{ $proyek->persen_retensi ?? 0 }}%</strong> — Durasi <strong>{{ $proyek->durasi_retensi ?? 0 }} hari</strong>
                                    @else
                                        <span class="badge bg-light text-dark">Tidak Digunakan</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>PPh Pemotongan</th>
                                <td>:
                                    @if(($proyek->pph_dipungut ?? 'ya') === 'ya')
                                        <span class="badge bg-warning text-dark">Dipungut</span> &nbsp; <small class="text-muted">Dipotong dari tagihan</small>
                                    @else
                                        <span class="badge bg-success">Bayar Sendiri</span> &nbsp; <small class="text-muted">Tidak dipotong dari tagihan</small>
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </div>
                @endif

                <h5 class="mb-3 text-primary"><i class="fas fa-calculator me-2"></i> Ringkasan Pajak</h5>
                <div class="table-responsive mb-4">
                    <table class="table table-bordered table-sm">
                        <thead>
                            <tr><th>Komponen</th><th>Keterangan</th><th class="text-end">Nilai</th></tr>
                        </thead>
                        <tbody>
                            <tr><td>Mode PPN</td><td>{{ $isTaxable ? strtoupper($ppnMode) : 'Tidak Kena PPN' }} (Tarif {{ rtrim(rtrim(number_format($ppnRate,3,',','.'),'0'),',') }}%)</td><td class="text-end">—</td></tr>
                            <tr><td>DPP (setelah diskon)</td><td>Gabungan</td><td class="text-end">{{ $rf($dpp) }}</td></tr>
                            <tr><td>PPN</td><td>{{ $isTaxable ? ($ppnMode==='include' ? 'Tersirat di subtotal' : 'Ditambahkan ke DPP') : '—' }}</td><td class="text-end">{{ $rf($ppn) }}</td></tr>
                            <tr><td>PPh</td><td>@if($applyPph) Dipungut atas {{ $pphDppSource === 'material_jasa' ? 'Material + Jasa' : 'Jasa saja' }} — Basis {{ strtoupper($pphBaseKind) }}, Tarif {{ rtrim(rtrim(number_format($pphRate,3,',','.'),'0'),',') }}% @else Tidak dipotong @endif</td><td class="text-end">- {{ $rf($pph) }}</td></tr>
                        </tbody>
                        <tfoot>
                            <tr><td colspan="2" class="text-end">Total + PPN</td><td class="text-end">{{ $rf($totalPlusPpn) }}</td></tr>
                            <tr><td colspan="2" class="text-end fw-bold">Total Dibayar (Nett) = (Total + PPN) − PPh</td><td class="text-end fw-bold text-success fs-5">{{ $rf($totalDibayar) }}</td></tr>
                        </tfoot>
                    </table>
                </div>

                {{-- ================================
                  Keterangan / Term of Payment (pakai modal)
                ================================= --}}
                <h5 class="mb-3 text-primary">
                    <i class="fas fa-clipboard-list me-2"></i> Keterangan / Term of Payment
                </h5>

                <div class="d-flex align-items-center gap-2 mb-3">
                    <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#keteranganModal">
                        <i class="fas fa-pen-to-square me-1"></i> Kelola Keterangan
                    </button>
                    @if(!empty($penawaran->keterangan))
                        <span class="text-muted">Terakhir disimpan.</span>
                    @endif
                </div>

                {{-- Pratinjau dari server (isi yang tersimpan) --}}
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <strong><i class="fas fa-eye me-2"></i>Pratinjau Keterangan</strong>
                    </div>
                    <div class="card-body pt-2">
                        @php
                            $lines = preg_split("/\r\n|\n|\r/", (string)($penawaran->keterangan ?? ''));
                            $hasAny = collect($lines)->contains(fn($l)=>trim($l)!=='');
                        @endphp
                        @if($hasAny)
                            <ul class="mb-0" id="keteranganPreviewServer">
                                @foreach($lines as $line)
                                    @if(trim($line)!=='')
                                        <li>{{ $line }}</li>
                                    @endif
                                @endforeach
                            </ul>
                        @else
                            <em class="text-muted">Belum ada keterangan. Klik “Kelola Keterangan”.</em>
                        @endif
                    </div>
                </div>

                <h5 class="mb-3 text-primary"><i class="fas fa-list-alt me-2"></i> Detail Bagian Penawaran</h5>
                @forelse($penawaran->sections as $section)
                    @php
                        $hasItems = $section->items && $section->items->isNotEmpty();
                        $sectionTotal = $hasItems
                            ? $section->items->sum(fn($it)=> ((float)($it->harga_satuan_penawaran ?? 0) !== 0.0
                                    ? (float)($it->harga_satuan_penawaran ?? 0)
                                    : (float)($it->harga_material_penawaran_item ?? 0) + (float)($it->harga_upah_penawaran_item ?? 0)
                                ) * (float)($it->volume ?? 0))
                            : 0;
                    @endphp
                    <div class="card mb-3 animate__animated animate__fadeInUp animate__faster">
                        <div class="card-header section-header-row d-flex justify-content-between align-items-center"
                                 @if($hasItems)
                                     data-bs-toggle="collapse"
                                     data-bs-target="#sectionCollapse{{ $section->id }}"
                                     aria-expanded="false"
                                     aria-controls="sectionCollapse{{ $section->id }}"
                                 @endif>
                            <div class="d-flex align-items-center gap-2">
                                <i class="fas fa-folder me-2"></i>
                                {{ $section->rabHeader->kode ?? 'N/A' }} - {{ $section->rabHeader->deskripsi ?? 'Bagian RAB Tidak Ditemukan' }}
                                @if($hasItems)
                                    <span class="ms-3 badge bg-dark">Profit: {{ number_format($section->profit_percentage, 2, ',', '.') }}%</span>
                                    <span class="ms-2 badge bg-dark">Overhead: {{ number_format($section->overhead_percentage, 2, ',', '.') }}%</span>
                                @endif
                            </div>
                            @if($hasItems)
                                <div class="text-end">
                                    Total Bagian:
                                    <span class="fw-bold text-success">Rp {{ number_format($sectionTotal, 0, ',', '.') }}</span>
                                    <i class="fas fa-chevron-down ms-2 collapse-icon"></i>
                                </div>
                            @endif
                        </div>

                        @if($hasItems)
                            <div class="collapse" id="sectionCollapse{{ $section->id }}">
                                <div class="card-body item-table-container">
                                    <div class="table-responsive">
                                        <table class="table table-hover table-bordered table-sm item-table">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Kode</th>
                                                    <th>Uraian / Spesifikasi</th>
                                                    <th class="text-end">Volume</th>
                                                    <th>Satuan</th>
                                                    <th class="text-end">Harga Satuan (Gabung)</th>
                                                    <th class="text-end">Total Item</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php
                                                    $itemsByArea = $section->items->groupBy(function($it){
                                                        $a = is_string($it->area) ? trim($it->area) : '';
                                                        return $a !== '' ? $a : '__NOAREA__';
                                                    });
                                                    $subTotal = 0;
                                                @endphp

                                                @foreach($itemsByArea as $areaName => $items)
                                                    @if($areaName !== '__NOAREA__')
                                                        <tr class="row-area"><td colspan="6">Area: {{ $areaName }}</td></tr>
                                                    @endif

                                                    @foreach($items as $item)
                                                        @php
                                                            $vol  = (float) ($item->volume ?? 0);
                                                            $unit = (float)($item->harga_satuan_penawaran ?? 0);
                                                            if ($unit == 0.0) {
                                                                $unit = (float) ($item->harga_material_penawaran_item ?? 0) + (float) ($item->harga_upah_penawaran_item ?? 0);
                                                            }
                                                            $tot  = $unit * $vol;
                                                            $subTotal += $tot;
                                                        @endphp
                                                        <tr>
                                                            <td>{{ $item->kode }}</td>
                                                            <td>
                                                                {{ $item->deskripsi }}
                                                                @if(!empty($item->spesifikasi))
                                                                    <span class="spec">{!! nl2br(e($item->spesifikasi)) !!}</span>
                                                                @endif
                                                            </td>
                                                            <td class="text-end">{{ number_format($vol, 2, ',', '.') }}</td>
                                                            <td>{{ $item->satuan }}</td>
                                                            <td class="text-end">Rp {{ number_format($unit, 0, ',', '.') }}</td>
                                                            <td class="text-end">Rp {{ number_format($tot, 0, ',', '.') }}</td>
                                                        </tr>
                                                    @endforeach
                                                @endforeach
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <td colspan="5" class="text-end">Subtotal Bagian</td>
                                                    <td class="text-end">Rp {{ number_format($subTotal, 0, ',', '.') }}</td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="alert alert-warning text-center animate__animated animate__fadeIn" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i> Belum ada bagian penawaran yang dibuat untuk penawaran ini.
                    </div>
                @endforelse

                                @php
                                    $flatItems = [];
                                    foreach ($penawaran->sections as $sec) {
                                        $hdrKode = $sec->rabHeader->kode ?? '';
                                        $hdrNama = $sec->rabHeader->deskripsi ?? '';
                                        foreach (($sec->items ?? []) as $it) {
                                            $vol   = (float)($it->volume ?? 0);
                                            $unit  = (float)($it->harga_satuan_penawaran ?? 0);
                                            if ($unit == 0.0) {
                                                $unit = (float)($it->harga_material_penawaran_item ?? 0) + (float)($it->harga_upah_penawaran_item ?? 0);
                                            }
                                            $flatItems[] = [
                                                'kode'        => (string)$it->kode,
                                                'uraian'      => (string)($it->deskripsi ?? ''),
                                                'spesifikasi' => (string)($it->spesifikasi ?? ''),
                                                'area'        => (string)($it->area ?? ''),
                                                'header_kode' => (string)$hdrKode,
                                                'header_nama' => (string)$hdrNama,
                                                'volume'      => $vol,
                                                'satuan'      => (string)($it->satuan ?? ''),
                                                'unit_gab'    => $unit,
                                                'total_gab'   => $unit * $vol,
                                            ];
                                        }
                                    }
                                @endphp

                                <div class="card mb-4 animate__animated animate__fadeInUp">
                                    <div class="card-header bg-light d-flex align-items-center justify-content-between">
                                        <h5 class="mb-0 text-primary">
                                            <i class="fas fa-filter me-2"></i> Pekerjaan Per Item
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-2 align-items-end mb-3">
                                            <div class="col-md-6">
                                                <label class="form-label">Ketik kata kunci (contoh: <code>plafond</code>)</label>
                                                <input id="excelLikeQuery" type="text" class="form-control" placeholder="Cari di kode, uraian, spesifikasi, atau area…">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Mode Pencarian</label>
                                                <select id="excelLikeMode" class="form-select">
                                                    <option value="any">Mengandung salah satu kata</option>
                                                    <option value="all" selected>Harus mengandung semua kata</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-check mt-4">
                                                    <input class="form-check-input" type="checkbox" value="1" id="excelLikeUseDiscount" checked>
                                                    <label class="form-check-label" for="excelLikeUseDiscount">
                                                        Gunakan diskon global ({{ number_format($discPct,2,',','.') }}%)
                                                    </label>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="table-responsive">
                                            <table class="table table-hover table-bordered table-sm align-middle" id="tbl-excel-like">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th style="width:10%">Kode</th>
                                                        <th>Uraian / Spesifikasi</th>
                                                        <th style="width:10%">Area</th>
                                                        <th class="text-end" style="width:10%">Volume</th>
                                                        <th style="width:8%">Sat</th>
                                                        <th class="text-end" style="width:12%">Hrg Sat (Gabung)</th>
                                                        <th class="text-end" style="width:12%">Total Item</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr><td colspan="7" class="text-center text-muted py-3">Ketik kata kunci untuk menampilkan hasil…</td></tr>
                                                </tbody>
                                                <tfoot class="table-light">
                                                    <tr>
                                                        <td colspan="5" class="text-end fw-semibold">Rata-rata Hrg Sat</td>
                                                        <td class="text-end fw-semibold" id="excelLikeAvgUnit">Rp 0</td>
                                                        <td class="text-end fw-bold"     id="excelLikeTotAll">Rp 0</td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <div class="small text-muted mt-2">
                                    Tips: pisahkan banyak kata kunci dengan spasi (mis. <code>plafon gypsum</code>). Mode “Semua kata” akan mencari item yang mengandung <em>kedua</em> kata.
                                </div>

                <div class="mt-4 text-center text-md-start">
                    <a href="{{ route('proyek.penawaran.edit', ['proyek' => $proyek->id, 'penawaran' => $penawaran->id]) }}" class="btn btn-warning me-2">
                        <i class="fas fa-edit me-1"></i> Edit Penawaran
                    </a>
                    <a class="btn btn-info me-2" target="_blank" href="{{ route('proyek.penawaran.generatePdf', [$proyek->id, $penawaran->id]) }}">
                        <i class="fas fa-file-pdf me-1"></i> PDF
                    </a>
                    <a class="btn btn-outline-success" href="{{ route('proyek.penawaran.exportExcel', [$proyek->id, $penawaran->id, 'mode' => 'gab']) }}">
                        <i class="fas fa-file-excel me-1"></i> Export Excel
                    </a>
                    @php $isFinal = ($penawaran->status === 'final'); @endphp
                    
                    @if($isFinal)
                        {{-- Tombol untuk kembalikan ke Draft --}}
                        <form method="POST" action="{{ route('proyek.penawaran.unapprove', [$proyek->id, $penawaran->id]) }}" class="d-inline" onsubmit="return confirm('Yakin mengembalikan penawaran ini ke status Draft?');">
                            @csrf
                            <button type="submit" class="btn btn-warning btn-sm">
                                <i class="fas fa-undo me-1"></i> Batalkan Persetujuan
                            </button>
                        </form>
                    @else
                        {{-- SETUJUI → buka modal upload PDF --}}
                        <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#approveModal">
                            <i class="fas fa-check-circle me-1"></i> Setujui
                        </button>
                    @endif
                </div>
        </div>
</div>

                {{-- Modal Keterangan --}}
                <div class="modal fade" id="keteranganModal" tabindex="-1" aria-labelledby="keteranganModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-scrollable">
                        <div class="modal-content">
                            <form action="{{ route('proyek.penawaran.updateKeterangan', [$proyek->id, $penawaran->id]) }}" method="POST">
                                @csrf
                                @method('PUT')

                                <div class="modal-header">
                                    <h5 class="modal-title" id="keteranganModalLabel">
                                        <i class="fas fa-clipboard-list me-2"></i> Kelola Keterangan / Term of Payment
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                                </div>

                                <div class="modal-body">
                                    <div class="mb-2">
                                        <label class="form-label">Isi keterangan (satu poin per baris)</label>
                                        <textarea id="keteranganTextarea" name="keterangan" rows="8"
                                            class="form-control @error('keterangan') is-invalid @enderror"
                                            placeholder="Contoh:
                Termin 1: DP 30% setelah SPK
                Termin 2: 40% saat progres 50%
                Termin 3: 30% saat serah terima">{{ old('keterangan', $penawaran->keterangan ?? '') }}</textarea>
                                        @error('keterangan')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="input-group mb-3">
                                        <input type="text" id="quickLineModal" class="form-control" placeholder="Tambah baris cepat (mis. 'Termin 1: DP 30%')">
                                        <button type="button" id="addLineBtnModal" class="btn btn-outline-secondary">
                                            <i class="fas fa-plus me-1"></i> Tambah Baris
                                        </button>
                                    </div>

                                    <div class="border rounded p-2">
                                        <div class="small text-muted mb-2"><i class="fas fa-eye me-1"></i>Pratinjau</div>
                                        <ul class="mb-0" id="keteranganPreviewLiveModal"></ul>
                                    </div>
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i> Simpan Keterangan
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                    {{-- Modal Setujui (Multi PDF) --}}
                    <div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <form action="{{ route('proyek.penawaran.approve', [$proyek->id, $penawaran->id]) }}" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="approveModalLabel">
                                            <i class="fas fa-file-upload me-2"></i> Finalisasi Penawaran
                                        </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                                    </div>

                                    <div class="modal-body">
                                        <p class="mb-3">
                                            Unggah <strong>minimal 1 dokumen</strong> <strong>PO/WO/SPK</strong> berformat <strong>PDF</strong> dan lengkapi data proyek.
                                        </p>

                                        <div class="mb-3">
                                            <label class="form-label">Dokumen PO/WO/SPK (PDF) <span class="text-danger">*</span></label>
                                            <input type="file"
                                                         name="approval_files[]"
                                                         class="form-control @error('approval_files') is-invalid @enderror @error('approval_files.*') is-invalid @enderror"
                                                         accept="application/pdf"
                                                         multiple
                                                         required>
                                            @error('approval_files')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            @error('approval_files.*')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <div class="form-text">Pilih satu atau beberapa file (PDF, maks 5 MB per file).</div>
                                        </div>

                                        <hr>

                                        <h6 class="mb-3"><i class="fas fa-calendar-alt me-2"></i>Data Proyek</h6>
                                        
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Nomor SPK <span class="text-danger">*</span></label>
                                                <input type="text" name="no_spk" class="form-control @error('no_spk') is-invalid @enderror"
                                                       value="{{ old('no_spk', $proyek->no_spk) }}" placeholder="Nomor Surat Perintah Kerja" required>
                                                @error('no_spk')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label class="form-label\">&nbsp;</label>
                                                <div class=\"alert alert-info mb-0\">
                                                    <small><i class=\"fas fa-info-circle me-1\"></i>Masukkan nomor SPK/WO/PO dari klien</small>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Tanggal Mulai Proyek <span class="text-danger">*</span></label>
                                                <input type="date" name="tanggal_mulai" id="tanggal_mulai_gab" class="form-control @error('tanggal_mulai') is-invalid @enderror" 
                                                       value="{{ old('tanggal_mulai', $proyek->tanggal_mulai) }}" required>
                                                @error('tanggal_mulai')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Durasi Proyek (Minggu) <span class="text-danger">*</span></label>
                                                <input type="number" name="durasi_minggu" id="durasi_minggu_gab" class="form-control @error('durasi_minggu') is-invalid @enderror"
                                                       value="{{ old('durasi_minggu') }}" min="1" step="1" placeholder="Contoh: 8" required>
                                                @error('durasi_minggu')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                                <div class="form-text">Masukkan estimasi durasi pengerjaan dalam minggu</div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-12 mb-3">
                                                <div class="alert alert-info mb-0" id="estimasi-selesai-gab" style="display: none;">
                                                    <i class="fas fa-calendar-check me-1"></i>
                                                    <strong>Estimasi Selesai:</strong> <span id="tanggal-selesai-display-gab">—</span>
                                                </div>
                                                <input type="hidden" name="tanggal_selesai" id="tanggal_selesai_gab" value="">
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-12 mb-3">
                                                <div class="form-check">
                                                    <input type="checkbox" class="form-check-input" id="gunakan_uang_muka" name="gunakan_uang_muka" value="1"
                                                           {{ old('gunakan_uang_muka', $proyek->gunakan_uang_muka ?? false) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="gunakan_uang_muka">
                                                        <strong>Gunakan Uang Muka (DP)</strong>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row" id="dp-fields">
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Persentase DP (%)</label>
                                                <input type="number" step="0.01" name="persen_dp" class="form-control @error('persen_dp') is-invalid @enderror"
                                                       value="{{ old('persen_dp', $proyek->persen_dp ?? 0) }}" min="0" max="100">
                                                @error('persen_dp')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-12 mb-3">
                                                <div class="form-check">
                                                    <input type="checkbox" class="form-check-input" id="gunakan_retensi" name="gunakan_retensi" value="1"
                                                           {{ old('gunakan_retensi', $proyek->gunakan_retensi ?? false) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="gunakan_retensi">
                                                        <strong>Gunakan Retensi</strong>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row" id="retensi-fields">
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Persentase Retensi (%)</label>
                                                <input type="number" step="0.01" name="persen_retensi" class="form-control @error('persen_retensi') is-invalid @enderror"
                                                       value="{{ old('persen_retensi', $proyek->persen_retensi ?? 0) }}" min="0" max="100">
                                                @error('persen_retensi')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Durasi Retensi (Hari)</label>
                                                <input type="number" name="durasi_retensi" class="form-control @error('durasi_retensi') is-invalid @enderror"
                                                       value="{{ old('durasi_retensi', $proyek->durasi_retensi ?? 0) }}" min="0">
                                                @error('durasi_retensi')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-12 mb-3">
                                                <label class="form-label">PPh Pemotongan</label>
                                                <div class="form-check">
                                                    <input type="radio" class="form-check-input" id="pph_dipungut_ya" name="pph_dipungut" value="ya"
                                                           {{ old('pph_dipungut', $proyek->pph_dipungut ?? 'ya') === 'ya' ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="pph_dipungut_ya">
                                                        <strong>Dipungut</strong> - PPh dipotong dari tagihan
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input type="radio" class="form-check-input" id="pph_dipungut_tidak" name="pph_dipungut" value="tidak"
                                                           {{ old('pph_dipungut', $proyek->pph_dipungut ?? 'ya') === 'tidak' ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="pph_dipungut_tidak">
                                                        <strong>Bayar Sendiri</strong> - PPh tidak dipotong dari tagihan
                                                    </label>
                                                </div>
                                            </div>
                                        </div>

                                        @php
                                            $docs = collect($penawaran->approval_doc_paths ?? [])
                                                ->when(empty($penawaran->approval_doc_paths ?? null) && !empty($penawaran->approval_doc_path ?? null),
                                                    fn($c)=>$c->push($penawaran->approval_doc_path));
                                        @endphp

                                        @if($docs->isNotEmpty())
                                            <hr>
                                            <div class="border rounded p-2">
                                                <div class="small text-muted mb-1"><i class="fas fa-paperclip me-1"></i>Dokumen yang tersimpan:</div>
                                                <ul class="mb-0">
                                                    @foreach($docs as $p)
                                                        <li><a target="_blank" href="{{ route('proyek.penawaran.approval.download', [$proyek->id, $penawaran->id, base64_encode($p)]) }}">Lihat PDF</a></li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-check me-1"></i> Unggah & Finalkan
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                @endsection

@push('custom-scripts')
<script>
(function(){
    const ALL_ITEMS = @json($flatItems);
    const DISC_COEF = {{ json_encode($discCoef) }};

    const qEl    = document.getElementById('excelLikeQuery');
    const modeEl = document.getElementById('excelLikeMode');
    const discEl = document.getElementById('excelLikeUseDiscount');
    const tbody  = document.querySelector('#tbl-excel-like tbody');
    const avgEl  = document.getElementById('excelLikeAvgUnit');
    const totEl  = document.getElementById('excelLikeTotAll');

    const fmtRp = n => 'Rp ' + (Number(n||0)).toLocaleString('id-ID', {maximumFractionDigits:0});
    const esc   = s => (s||'').replace(/[&<>"/]/g, m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','/':'&#47;' }[m]));
    const mark  = (text, tokens) => {
        if (!tokens.length) return esc(text||'');
        let out = esc(text||'');
        tokens.forEach(t=>{
            if(!t) return;
            const re = new RegExp('(' + t.replace(/[.*+?^${}()|[\]\\]/g,'\\$&') + ')','ig');
            out = out.replace(re, '<mark>$1</mark>');
        });
        return out;
    };

    function filterAndRender(){
        if(!tbody) return;

        const raw    = (qEl?.value || '').trim();
        const tokens = raw.split(/\s+/).filter(Boolean);
        const mode   = modeEl?.value || 'all';
        const useDisc= !!discEl?.checked;

        if (!tokens.length) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-3">Ketik kata kunci untuk menampilkan hasil…</td></tr>';
            avgEl.textContent = fmtRp(0);
            totEl.textContent = fmtRp(0);
            return;
        }

        let sumAll=0, sumVol=0, rows=[];

        ALL_ITEMS.forEach(it=>{
            const bucket = [it.kode, it.uraian, it.spesifikasi, it.area].join(' ').toLowerCase();
            const ok = (mode==='any')
                ? tokens.some(t=>bucket.includes(t.toLowerCase()))
                : tokens.every(t=>bucket.includes(t.toLowerCase()));
            if (!ok) return;

            const coef = useDisc ? DISC_COEF : 1.0;
            const vol  = Number(it.volume || 0);
            const unit = Number(it.unit_gab || 0) * coef;
            const tot  = unit * vol;

            sumAll += tot;
            sumVol += vol;

            rows.push(`
                <tr>
                    <td>${mark(it.kode, tokens)}</td>
                    <td>
                        <div class="fw-semibold">${mark(it.uraian, tokens)}</div>
                        ${it.spesifikasi ? `<div class="text-muted small">${mark(it.spesifikasi, tokens)}</div>` : ``}
                    </td>
                    <td>${mark(it.area, tokens)}</td>
                    <td class="text-end">${vol.toLocaleString('id-ID', {minimumFractionDigits:2, maximumFractionDigits:2})}</td>
                    <td>${esc(it.satuan)}</td>
                    <td class="text-end">${fmtRp(unit)}</td>
                    <td class="text-end fw-semibold">${fmtRp(tot)}</td>
                </tr>
            `);
        });

        const avgUnit = sumVol > 0 ? (sumAll / sumVol) : 0;

        tbody.innerHTML = rows.length ? rows.join('') : '<tr><td colspan="7" class="text-center text-muted py-3">Tidak ada item yang cocok.</td></tr>';
        avgEl.textContent = fmtRp(avgUnit);
        totEl.textContent = fmtRp(sumAll);
    }

    qEl?.addEventListener('input',  filterAndRender);
    modeEl?.addEventListener('change', filterAndRender);
    discEl?.addEventListener('change', filterAndRender);
})();
</script>

<script>
// Toggle show/hide fields for DP and Retensi
(function(){
    const dpCheckbox = document.getElementById('gunakan_uang_muka');
    const dpFields = document.getElementById('dp-fields');
    const retensiCheckbox = document.getElementById('gunakan_retensi');
    const retensiFields = document.getElementById('retensi-fields');

    function toggleDpFields() {
        if (dpCheckbox && dpFields) {
            dpFields.style.display = dpCheckbox.checked ? 'flex' : 'none';
        }
    }

    function toggleRetensiFields() {
        if (retensiCheckbox && retensiFields) {
            retensiFields.style.display = retensiCheckbox.checked ? 'flex' : 'none';
        }
    }

    if (dpCheckbox) {
        dpCheckbox.addEventListener('change', toggleDpFields);
        toggleDpFields(); // Initial state
    }

    if (retensiCheckbox) {
        retensiCheckbox.addEventListener('change', toggleRetensiFields);
        toggleRetensiFields(); // Initial state
    }
})();

// Calculate end date from start date + duration weeks
(function(){
    const tanggalMulai = document.getElementById('tanggal_mulai_gab');
    const durasiMinggu = document.getElementById('durasi_minggu_gab');
    const tanggalSelesai = document.getElementById('tanggal_selesai_gab');
    const tanggalSelesaiDisplay = document.getElementById('tanggal-selesai-display-gab');
    const estimasiSelesai = document.getElementById('estimasi-selesai-gab');

    function calculateEndDate() {
        if (!tanggalMulai || !durasiMinggu || !tanggalSelesai || !tanggalSelesaiDisplay) return;

        const startDate = tanggalMulai.value;
        const weeks = parseInt(durasiMinggu.value);

        if (!startDate || !weeks || weeks < 1) {
            tanggalSelesai.value = '';
            tanggalSelesaiDisplay.textContent = '—';
            if (estimasiSelesai) estimasiSelesai.style.display = 'none';
            return;
        }

        // Calculate end date: start date + (weeks * 7 days) - 1 day
        const start = new Date(startDate);
        const end = new Date(start);
        end.setDate(end.getDate() + (weeks * 7) - 1);

        // Format for hidden input (YYYY-MM-DD)
        const yyyy = end.getFullYear();
        const mm = String(end.getMonth() + 1).padStart(2, '0');
        const dd = String(end.getDate()).padStart(2, '0');
        tanggalSelesai.value = `${yyyy}-${mm}-${dd}`;

        // Format for display (DD-MM-YYYY)
        tanggalSelesaiDisplay.textContent = `${dd}-${mm}-${yyyy}`;
        if (estimasiSelesai) estimasiSelesai.style.display = 'block';
    }

    if (tanggalMulai) {
        tanggalMulai.addEventListener('change', calculateEndDate);
    }

    if (durasiMinggu) {
        durasiMinggu.addEventListener('input', calculateEndDate);
    }

    // Calculate on page load if values exist
    calculateEndDate();
})();
</script>

<script>
(function(){
    const modalEl   = document.getElementById('keteranganModal');
    const ta        = document.getElementById('keteranganTextarea');
    const quick     = document.getElementById('quickLineModal');
    const addBtn    = document.getElementById('addLineBtnModal');
    const previewUl = document.getElementById('keteranganPreviewLiveModal');

    function renderPreview() {
        if(!previewUl || !ta) return;
        previewUl.innerHTML = '';
        (ta.value || '').split(/\r?\n/).forEach(line=>{
            if(line.trim()){
                const li = document.createElement('li');
                li.textContent = line.trim();
                previewUl.appendChild(li);
            }
        });
    }

    if(addBtn && quick && ta){
        addBtn.addEventListener('click', function(){
            const val = (quick.value || '').trim();
            if(!val) return;
            ta.value = (ta.value.trim() ? (ta.value.replace(/\s+$/,'') + '\n') : '') + val;
            quick.value = '';
            ta.focus();
            renderPreview();
        });
    }

    if (modalEl) {
        modalEl.addEventListener('shown.bs.modal', renderPreview);
        if (ta) ta.addEventListener('input', renderPreview);
    }
})();

if (typeof feather !== 'undefined') { feather.replace(); }
[].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    .map(function (el) { return new bootstrap.Tooltip(el); });
</script>
@endpush

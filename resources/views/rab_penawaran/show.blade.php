@extends('layout.master')

@push('plugin-styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
<style>
    /* General customizations for card and table */
    .card {
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        border: none;
    }
    .card-header {
        border-top-left-radius: 12px;
        border-top-right-radius: 12px;
        padding: 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background-color: #007bff; /* Primary color */
        color: white;
    }
    .table-borderless th, .table-borderless td {
        padding: 1rem;
        vertical-align: top;
        border-top: none;
    }
    .table-borderless th {
        color: #495057;
        font-weight: 600;
        width: 200px; /* Fixed width for labels */
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
    .item-table-container {
        padding: 10px;
        background-color: #f8fafd; /* Very light gray for item tables */
        border: 1px solid #e0e0e0;
        border-radius: 8px;
    }
    .item-table th, .item-table td {
        white-space: nowrap;
        font-size: 0.875rem;
    }
    .item-table thead th {
        background-color: #f0f8ff; /* Even lighter blue for item table headers */
        color: #495057;
    }
    .item-table tfoot td {
        background-color: #f8fbff;
        font-weight: 600;
    }

    /* Tambahan agar mirip PDF */
    .row-area td { background:#eef8ff; font-weight:600; font-style:italic; }
    .spec { display:block; margin-top:4px; font-size:0.8rem; color:#6c757d; white-space:pre-line; }
</style>
@endpush

@section('content')
<div class="card shadow-sm animate__animated animate__fadeIn">
    <div class="card-header">
        <h4 class="card-title mb-0 d-flex align-items-center">
            <i class="fas fa-file-invoice-dollar me-2"></i> Detail Penawaran
        </h4>
        <a href="{{ route('proyek.show', $proyek->id) }}#rabContent" class="btn btn-light btn-sm d-inline-flex align-items-center" data-bs-toggle="tooltip" title="Kembali ke RAB Proyek">
            <i class="fas fa-arrow-left me-1"></i> Kembali
        </a>
    </div>
    <div class="card-body p-4">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <i class="fas fa-times-circle me-2"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <h5 class="mb-3 text-primary"><i class="fas fa-info-circle me-2"></i> Informasi Penawaran</h5>
        <div class="table-responsive mb-4">
            <table class="table table-borderless table-sm detail-table">
                <tbody>
                    <tr>
                        <th>Nama Penawaran</th>
                        <td>: {{ $penawaran->nama_penawaran }}</td>
                    </tr>
                    <tr>
                        <th>Tanggal Penawaran</th>
                        <td>: {{ \Carbon\Carbon::parse($penawaran->tanggal_penawaran)->format('d-m-Y') }}</td>
                    </tr>
                    <tr>
                        <th>Proyek</th>
                        <td>: {{ $proyek->nama_proyek }} - {{ $proyek->pemberiKerja->nama_pemberi_kerja ?? '' }}</td>
                    </tr>
                    <tr>
                        <th>Total Bruto</th>
                        <td class="fw-bold text-info">: Rp {{ number_format($penawaran->total_penawaran_bruto, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <th>Diskon (%)</th>
                        <td>: {{ number_format($penawaran->discount_percentage, 2, ',', '.') }}%</td>
                    </tr>
                    <tr>
                        <th>Jumlah Diskon</th>
                        <td class="fw-bold text-danger">: Rp {{ number_format($penawaran->discount_amount, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <th>Total Akhir Penawaran</th>
                        <td class="fw-bold text-success fs-5">: Rp {{ number_format($penawaran->final_total_penawaran, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>:
                            @if($penawaran->status == 'draft')
                                <span class="badge bg-warning text-dark"><i class="fas fa-hourglass-half me-1"></i> Draft</span>
                            @elseif($penawaran->status == 'final')
                                <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i> Final</span>
                            @else
                                <span class="badge bg-secondary">{{ ucfirst($penawaran->status) }}</span>
                            @endif
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <h5 class="mb-3 text-primary"><i class="fas fa-list-alt me-2"></i> Detail Bagian Penawaran</h5>

        @foreach($penawaran->sections as $section)
            @php
                $hasItems = $section->items && $section->items->isNotEmpty();

                // Hitung subtotal Material/Jasa per section (untuk header breakdown)
                $sectionMat  = $hasItems ? $section->items->sum(function($it){
                    return (float) ($it->harga_material_penawaran_item ?? 0) * (float) ($it->volume ?? 0);
                }) : 0;
                $sectionJasa = $hasItems ? $section->items->sum(function($it){
                    return (float) ($it->harga_upah_penawaran_item ?? 0) * (float) ($it->volume ?? 0);
                }) : 0;
                $sectionTotal = $sectionMat + $sectionJasa;
            @endphp

            {{-- Selalu tampilkan header section --}}
            <div class="card mb-3 animate__animated animate__fadeInUp animate__faster">
                <div
                    class="card-header section-header-row d-flex justify-content-between align-items-center"
                    @if($hasItems)
                        data-bs-toggle="collapse"
                        data-bs-target="#sectionCollapse{{ $section->id }}"
                        aria-expanded="false"
                        aria-controls="sectionCollapse{{ $section->id }}"
                    @endif
                >
                    <div class="d-flex align-items-center gap-2">
                        <i class="fas fa-folder me-2"></i>
                        {{ $section->rabHeader->kode ?? 'N/A' }} - {{ $section->rabHeader->deskripsi ?? 'Bagian RAB Tidak Ditemukan' }}

                        {{-- Profit/Overhead hanya jika ada item --}}
                        @if($hasItems)
                            <span class="ms-3 badge bg-dark">Profit: {{ number_format($section->profit_percentage, 2, ',', '.') }}%</span>
                            <span class="ms-2 badge bg-dark">Overhead: {{ number_format($section->overhead_percentage, 2, ',', '.') }}%</span>
                        @endif
                    </div>

                    {{-- Breakdown Total Bagian hanya jika ada item --}}
                    @if($hasItems)
                        <div class="text-end">
                            <div class="small mb-1">
                                <span class="me-2">Material:
                                    <span class="fw-bold">Rp {{ number_format($sectionMat, 0, ',', '.') }}</span>
                                </span>
                                <span>Jasa:
                                    <span class="fw-bold">Rp {{ number_format($sectionJasa, 0, ',', '.') }}</span>
                                </span>
                            </div>
                            <div>
                                Total Bagian:
                                <span class="fw-bold text-success">
                                    Rp {{ number_format($sectionTotal, 0, ',', '.') }}
                                </span>
                                <i class="fas fa-chevron-down ms-2 collapse-icon"></i>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Tabel item hanya dirender jika ada item --}}
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
                                        <th class="text-end">Harga Satuan Material</th>
                                        <th class="text-end">Harga Satuan Jasa</th>
                                        <th class="text-end">Total Material</th>
                                        <th class="text-end">Total Jasa</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        // Group by Area hanya dari rab_penawaran_items.area
                                        $itemsByArea = $section->items->groupBy(function($it){
                                            $area = is_string($it->area) ? trim($it->area) : '';
                                            return $area !== '' ? $area : '__NOAREA__';
                                        });

                                        $subMaterial = 0; $subJasa = 0;
                                    @endphp

                                    @foreach($itemsByArea as $areaName => $items)
                                        {{-- Header Area (jika ada nama area) --}}
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

                                                // total setelah dikali volume
                                                $totMat   = $unitMat  * $vol;
                                                $totJasa  = $unitJasa * $vol;

                                                $subMaterial += $totMat;
                                                $subJasa     += $totJasa;

                                                // spesifikasi hanya dari item
                                                $spes = $item->spesifikasi;
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

                                                {{-- Harga satuan --}}
                                                <td class="text-end">Rp {{ number_format($unitMat, 0, ',', '.') }}</td>
                                                <td class="text-end">Rp {{ number_format($unitJasa, 0, ',', '.') }}</td>

                                                {{-- Total --}}
                                                <td class="text-end">Rp {{ number_format($totMat, 0, ',', '.') }}</td>
                                                <td class="text-end">Rp {{ number_format($totJasa, 0, ',', '.') }}</td>
                                            </tr>
                                        @endforeach
                                    @endforeach
                                </tbody>

                                {{-- Subtotal per section (tanpa subtotal area) --}}
                                <tfoot>
                                    <tr>
                                        <td colspan="6" class="text-end">Subtotal Bagian</td>
                                        <td class="text-end">Rp {{ number_format($subMaterial, 0, ',', '.') }}</td>
                                        <td class="text-end">Rp {{ number_format($subJasa, 0, ',', '.') }}</td>
                                    </tr>
                                </tfoot>
                            </table>

                            </div>
                        </div>
                    </div>
                @endif
            </div>
        @endforeach

        <div class="mt-4 text-center text-md-start">
            <a href="{{ route('proyek.penawaran.edit', ['proyek' => $proyek->id, 'penawaran' => $penawaran->id]) }}" class="btn btn-warning me-2" data-bs-toggle="tooltip" title="Edit Penawaran">
                <i class="fas fa-edit me-1"></i> Edit
            </a>

            <a target="_blank"
                href="{{ route('proyek.penawaran.generatePdf', ['proyek' => $proyek->id, 'penawaran' => $penawaran->id]) }}"
                class="btn btn-outline-secondary btn-sm">
                <i data-feather="printer" class="me-1"></i> PDF (Standar)
            </a>

            <a target="_blank"
                href="{{ route('proyek.penawaran.generatePdfSplit', ['proyek' => $proyek->id, 'penawaran' => $penawaran->id]) }}"
                class="btn btn-primary btn-sm">
                <i data-feather="printer" class="me-1"></i> PDF (Split Material/Jasa)
            </a>
            {{-- Setujui --}}
            <form method="POST" action="{{ route('proyek.penawaran.approve', [$proyek->id, $penawaran->id]) }}">
                @csrf
                <button class="btn btn-success btn-sm" type="submit">
                <i class="fas fa-check-circle me-1"></i> Setujui 
                </button>
            </form>

            {{-- Buat bobot (snapshot) --}}
            <form method="POST" action="{{ route('proyek.penawaran.snapshot', [$proyek->id, $penawaran->id]) }}">
                @csrf
                <button class="btn btn-dark btn-sm" type="submit">
                <i class="fas fa-balance-scale me-1"></i> Bobot
                </button>
            </form>

            {{-- Link ke Tab RAB Schedule --}}
            <a class="btn btn-outline-primary btn-sm" href="{{ route('rabSchedule.index', $proyek->id) }}">
                <i class="fas fa-calendar-alt me-1"></i> RAB Schedule
            </a>
        </div>
    </div>
</div>
@endsection

@push('custom-scripts')
<script>
    // Initialize Feather Icons if not already initialized in master layout
    if (typeof feather !== 'undefined') {
        feather.replace();
    }

    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
</script>
@endpush

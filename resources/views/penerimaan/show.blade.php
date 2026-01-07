@extends('layout.master')

@section('content')
<nav class="page-breadcrumb d-print-none">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('penerimaan.index') }}">Goods Receipt</a></li>
        <li class="breadcrumb-item active" aria-current="page">Details & Preview</li>
    </ol>
</nav>

<div class="row justify-content-center">
    <div class="col-md-10 col-lg-9">
        <div class="d-flex justify-content-between align-items-center mb-3 d-print-none">
            <a href="{{ route('penerimaan.index') }}" class="btn btn-link text-muted p-0">
                <i data-feather="arrow-left" class="icon-sm"></i> Back to List
            </a>
            <div class="d-flex gap-2">
                @if(strtolower($penerimaan->status ?? '') === 'approved')
                <button id="btnPrint" class="btn btn-primary px-3">
                    <i data-feather="printer" class="icon-sm me-1"></i> Print Document
                </button>
                @endif
                @if($penerimaan->file_surat_jalan)
                <a href="{{ route('penerimaan.viewSuratJalan', $penerimaan->id) }}" target="_blank" class="btn btn-outline-danger">
                    <i data-feather="file-text" class="icon-sm me-1"></i> Delivery Note
                </a>
                @endif
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4 document-paper">
            <div class="card-body p-4 p-md-5">
                
                <div class="status-ribbon d-print-none">
                    <span class="badge {{ $penerimaan->status === 'draft' ? 'bg-warning' : 'bg-success' }} text-uppercase ls-1">
                        {{ $penerimaan->status ?? 'Draft' }}
                    </span>
                </div>

                <div class="print-container">
                    <div class="row align-items-start mb-4">
                        <div class="col-7">
                            <h2 class="fw-bolder text-primary mb-1" style="letter-spacing: -1px;">GOODS RECEIPT</h2>
                            <div class="text-dark small">
                                <span class="text-muted">Receipt No:</span> <span class="fw-bold">{{ $penerimaan->no_penerimaan }}</span><br>
                                <span class="text-muted">Date:</span> <span class="fw-bold">{{ \Carbon\Carbon::parse($penerimaan->tanggal)->format('d F Y') }}</span><br>
                                <span class="text-muted">Delivery Note:</span> <span class="fw-bold">{{ $penerimaan->no_surat_jalan ?? '-' }}</span>
                            </div>
                        </div>
                        <div class="col-5 text-end">
                            <img src="{{ company_logo_url($penerimaan->perusahaan) }}" alt="Logo" class="mb-2 company-logo-print">
                            <h5 class="fw-bold mb-0 text-dark">{{ $penerimaan->perusahaan->nama_perusahaan ?? 'COMPANY NAME' }}</h5>
                            <p class="company-address text-muted mb-0">
                                {{ $penerimaan->perusahaan->alamat ?? 'Address not set.' }}
                            </p>
                        </div>
                    </div>

                    <div class="divider-line mb-4"></div>

                    <div class="row mb-4">
                        <div class="col-6">
                            <h6 class="text-uppercase fw-bold small text-muted mb-2">Supplier:</h6>
                            <div class="ps-2 border-start border-3 border-primary">
                                <p class="fw-bolder text-dark mb-0" style="font-size: 1.1rem;">{{ $penerimaan->nama_supplier }}</p>
                                <p class="text-muted small mb-0">
                                    <i data-feather="user" class="icon-xs"></i> {{ $penerimaan->supplier->pic ?? '-' }} 
                                    @if($penerimaan->supplier && $penerimaan->supplier->no_kontak)
                                        <span class="ms-1">({{ $penerimaan->supplier->no_kontak }})</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="col-6">
                            <h6 class="text-uppercase fw-bold small text-muted mb-2 text-end">Reference:</h6>
                            <div class="pe-2 border-end border-3 border-info text-end">
                                <p class="fw-bolder text-dark mb-0">PO: {{ $penerimaan->po->no_po ?? '-' }}</p>
                                <p class="text-muted small mb-0">Project: {{ $penerimaan->proyek->nama_proyek ?? '-' }}</p>
                                <p class="text-muted small mb-0">Transaction ID: #{{ $penerimaan->id }}</p>
                            </div>
                        </div>
                    </div>

                    @php
                        $totalDiterima = 0;
                        $totalTerfaktur = 0;
                    @endphp

                    <div class="table-responsive mb-4">
                        <table class="table table-bordered border-dark custom-table-document">
                            <thead>
                                <tr class="table-light">
                                    <th class="text-center py-2" width="5%">NO</th>
                                    <th class="py-2">ITEM DESCRIPTION</th>
                                    <th class="text-center py-2" width="12%">QTY PO</th>
                                    <th class="text-center py-2" width="12%">QTY RECEIVED</th>
                                    <th class="text-center py-2" width="12%">REMAINING</th>
                                    <th class="text-center py-2" width="8%">UOM</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($penerimaan->details as $index => $detail)
                                @php
                                    $qtyTerfaktur = $detail->qty_terfaktur ?? 0;
                                    $qtyReturApproved = \App\Models\ReturPembelianDetail::where('penerimaan_detail_id', $detail->id)
                                        ->whereHas('retur', function($q){ $q->where('status','approved'); })
                                        ->sum('qty_retur');
                                    $netDiterima = max(0, ($detail->qty_diterima - $qtyReturApproved));
                                    $sisaBelumDifaktur = max(0, ($netDiterima - $qtyTerfaktur));
                                    $totalDiterima += $netDiterima;
                                    $totalTerfaktur += $qtyTerfaktur;
                                @endphp
                                <tr>
                                    <td class="text-center">{{ $index + 1 }}</td>
                                    <td>
                                        <div class="fw-bold text-dark">{{ $detail->uraian }}</div>
                                        <div class="small text-muted">Code: {{ $detail->kode_item }}</div>
                                        @if($detail->keterangan)
                                        <div class="small text-info fst-italic">Note: {{ $detail->keterangan }}</div>
                                        @endif
                                    </td>
                                    <td class="text-center">{{ number_format($detail->qty_po, 2, ',', '.') }}</td>
                                    <td class="text-center fw-bold text-success">
                                        {{ number_format($detail->qty_diterima, 2, ',', '.') }}
                                        @if($qtyReturApproved > 0)
                                            <div><small class="text-danger">Returned: -{{ number_format($qtyReturApproved, 2, ',', '.') }}</small></div>
                                            <div><small class="text-muted">Net: {{ number_format($netDiterima, 2, ',', '.') }}</small></div>
                                        @endif
                                    </td>
                                    <td class="text-center {{ $sisaBelumDifaktur > 0 ? 'text-danger' : 'text-muted' }}">
                                        {{ number_format($sisaBelumDifaktur, 2, ',', '.') }}
                                    </td>
                                    <td class="text-center">{{ $detail->uom }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="total-row shadow-sm">
                                    <td colspan="3" class="text-end fw-bolder text-primary">TOTAL RECEIVED (NET)</td>
                                    <td class="text-center fw-bolder text-success bg-soft-primary">
                                        {{ number_format($totalDiterima, 2, ',', '.') }}
                                    </td>
                                    <td class="text-center fw-bolder text-danger">
                                        {{ number_format(max(0, $totalDiterima - $totalTerfaktur), 2, ',', '.') }}
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="row mb-4 d-print-none">
                        <div class="col-12">
                            <div class="alert alert-info border-0 bg-soft-info py-2">
                                <div class="row align-items-center">
                                    <div class="col-md-6">
                                        <strong>Invoice Status:</strong>
                                        @switch($penerimaan->status_penagihan)
                                            @case('lunas')
                                                <span class="badge bg-success">Fully Invoiced</span>
                                                @break
                                            @case('sebagian')
                                                <span class="badge bg-warning text-dark">Partially Invoiced</span>
                                                @break
                                            @default
                                                <span class="badge bg-secondary">Not Invoiced</span>
                                        @endswitch
                                    </div>
                                    <div class="col-md-6 text-end">
                                        @if($penerimaan->status == 'draft')
                                            <span class="text-muted">Status: Awaiting Approval</span>
                                        @else
                                            <span class="text-success"><i data-feather="check-circle" class="icon-xs"></i> Approved</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row align-items-end mt-2">
                        <div class="col-7 small">
                            @if($penerimaan->keterangan)
                            <div class="p-2 bg-light rounded border mb-3">
                                <strong class="d-block small text-muted text-uppercase mb-1">Additional Notes:</strong>
                                <span class="text-dark italic">"{{ $penerimaan->keterangan }}"</span>
                            </div>
                            @endif
                        </div>
                        <div class="col-5 d-print-none">
                             <div class="alert alert-info py-2 small border-0 bg-soft-info">
                                <i data-feather="info" class="icon-xs me-1"></i> Use the print button for perfect A4 layout results.
                             </div>
                        </div>
                    </div>

                    <!-- Approval Section -->
                    <div class="mt-5">
                        <div class="dashed-sep mb-3"></div>
                        <div class="border rounded small">
                            <div class="px-3 py-2 border-bottom bg-light">
                                <strong class="text-uppercase">Document Approval Trail</strong>
                            </div>
                            <div class="px-3 py-2">
                                <div class="row">
                                    <div class="col-md-12 mb-2">
                                        <strong>Status:</strong>
                                        @if($penerimaan->status == 'draft')
                                            <span class="badge bg-warning">DRAFT - Pending Approval</span>
                                        @else
                                            <span class="badge bg-success">APPROVED</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-md-6">
                                        <strong>Created by:</strong><br>
                                        <span class="text-muted small">
                                            {{ optional($penerimaan->dibuatOleh)->name ?? '-' }}
                                            @if($penerimaan->dibuat_at)
                                                <br>[{{ $penerimaan->dibuat_at->format('d/m/Y H:i') }}]
                                            @endif
                                        </span>
                                    </div>
                                    <div class="col-md-6">
                                        <strong>Approved by:</strong><br>
                                        <span class="text-muted small">
                                            {{ optional($penerimaan->disetujuiOleh)->name ?? '-' }}
                                            @if($penerimaan->disetujui_at)
                                                <br>[{{ $penerimaan->disetujui_at->format('d/m/Y H:i') }}]
                                            @endif
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="dashed-sep mt-3 mb-4"></div>

                        <div class="mt-4 pt-2">
                            <div class="fw-bold small mb-2">Signature Section,</div>
                            <div class="d-flex justify-content-between small mb-4">
                                <div>
                                    <span class="me-2">Signature :</span>
                                    <span class="underline-space"></span>
                                </div>
                                <div>
                                    <span class="me-2">Receipt # :</span>
                                    <span class="fw-bold">{{ $penerimaan->no_penerimaan }}</span>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between small">
                                <div>
                                    <span class="me-2">Name :</span>
                                    <span class="underline-space" style="width:220px;"></span>
                                </div>
                                <div>
                                    <span class="me-2">Date :</span>
                                    <span class="underline-space" style="width:180px;"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div> 

                <!-- Action Buttons (Print Hidden) -->
                <div class="mt-5 d-print-none">
                    <div class="d-flex gap-2 flex-wrap">
                        @if($penerimaan->status == 'draft')
                        <form action="{{ route('penerimaan.approve', $penerimaan->id) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-success" onclick="return confirm('Approve this goods receipt?')">
                                <i data-feather="check"></i> Approve Receipt
                            </button>
                        </form>
                        @else
                        <form action="{{ route('penerimaan.revisi', $penerimaan->id) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-warning" onclick="return confirm('Revise this receipt? It will return to draft status.')">
                                <i data-feather="edit-3"></i> Revise
                            </button>
                        </form>
                        @endif
                        
                        @php
                            $totalSisaBelumDifaktur = 0;
                            foreach($penerimaan->details as $detail) {
                                $qtyReturApproved = \App\Models\ReturPembelianDetail::where('penerimaan_detail_id', $detail->id)
                                    ->whereHas('retur', function($q){ $q->where('status','approved'); })
                                    ->sum('qty_retur');
                                $netDiterima = max(0, ($detail->qty_diterima - $qtyReturApproved));
                                $sisaBelumDifaktur = max(0, ($netDiterima - ($detail->qty_terfaktur ?? 0)));
                                $totalSisaBelumDifaktur += $sisaBelumDifaktur;
                            }
                        @endphp
                        
                        @if($penerimaan->status == 'approved' && $totalSisaBelumDifaktur > 0)
                        <a href="{{ route('faktur.createFromPenerimaan', $penerimaan->id) }}" class="btn btn-success">
                            <i data-feather="file-text"></i> Create Invoice
                        </a>
                        @endif
                        
                        @if($penerimaan->status == 'approved')
                        <a href="{{ route('retur.create', $penerimaan->id) }}" class="btn btn-outline-warning">
                            <i data-feather="rotate-ccw"></i> Create Return
                        </a>
                        @endif
                        
                        @if($penerimaan->status == 'draft')
                        <form action="{{ route('penerimaan.destroy', $penerimaan->id) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger" 
                                onclick="return confirm('Delete this receipt? This action cannot be undone.')">
                                <i data-feather="trash-2"></i> Delete
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* --- UI Enhancement --- */
.document-paper {
    background: #fff;
    border-radius: 4px;
    position: relative;
    overflow: hidden;
}

/* Decorative divider line */
.divider-line {
    height: 3px;
    background: linear-gradient(to right, #28a745, #20c997);
    border-radius: 2px;
}

.status-ribbon {
    position: absolute;
    top: 20px;
    right: -35px;
    transform: rotate(45deg);
    width: 150px;
    text-align: center;
    z-index: 10;
    display: none;
}

.company-logo-print {
    max-height: 70px;
    max-width: 180px;
    object-fit: contain;
    filter: grayscale(20%);
}

.company-address {
    font-size: 10px;
    line-height: 1.3;
    max-width: 250px;
    margin-left: auto;
}

.custom-table-document {
    font-size: 12px;
}

.custom-table-document th {
    background-color: #f8f9fb !important;
    text-transform: uppercase;
    font-weight: 700;
    letter-spacing: 0.5px;
    border-color: #333 !important;
}

.custom-table-document td {
    border-color: #dee2e6 !important;
    color: #333;
    padding: 10px 8px !important;
}

.bg-soft-primary { background-color: rgba(40, 167, 69, 0.08) !important; }
.bg-soft-info { background-color: rgba(0, 210, 255, 0.1) !important; color: #007fa0; }
.ls-1 { letter-spacing: 1px; }
.dashed-sep { border-top: 4px dashed #557; opacity: .7; }
.underline-space { display: inline-block; border-bottom: 1px solid #000; width: 160px; height: 14px; vertical-align: middle; }

/* --- Print Logic --- */
@media print {
    /* Block printing unless explicitly allowed via JS */
    body:not(.print-allowed) {
        display: block !important;
    }
    body:not(.print-allowed) * {
        display: none !important;
        visibility: hidden !important;
    }
    body:not(.print-allowed)::before {
        content: 'Printing disabled. Use "Print Document" button.';
        display: block !important;
        margin: 2cm 0 0 0;
        font-size: 14px;
        color: #000;
        visibility: visible !important;
    }

    @page {
        size: A4 portrait;
        margin: 1.5cm;
    }

    body {
        background: #fff !important;
    }

    .document-paper {
        box-shadow: none !important;
        border: none !important;
        padding: 0 !important;
    }

    .card-body {
        padding: 0 !important;
    }

    .divider-line {
        background: #000 !important;
        height: 1px;
    }

    .custom-table-document th {
        background-color: #eee !important;
        -webkit-print-color-adjust: exact;
    }

    .total-row td {
        border-top: 2px solid #000 !important;
    }

    .sidebar, .navbar, .footer, .d-print-none, .page-breadcrumb {
        display: none !important;
    }

    .main-wrapper, .page-content {
        margin: 0 !important;
        padding: 0 !important;
    }
    
    .text-primary, .text-success, .text-danger { color: #000 !important; }
}
</style>

<script>
// Print protection: block Ctrl+P/menu print except via official button
(function() {
    const canPrint = {{ strtolower($penerimaan->status ?? '') === 'approved' ? 'true' : 'false' }};

    document.addEventListener('keydown', function(e) {
        const isPrintShortcut = (e.ctrlKey || e.metaKey) && (e.key === 'p' || e.key === 'P');
        if (isPrintShortcut) {
            e.preventDefault();
            e.stopPropagation();
            alert('Use the "Print Document" button on this page to print.');
        }
    }, true);

    const btn = document.getElementById('btnPrint');
    if (btn) {
        btn.addEventListener('click', function() {
            if (!canPrint) {
                alert('Receipt not approved yet, printing not allowed.');
                return;
            }
            document.body.classList.add('print-allowed');
            setTimeout(function() { window.print(); }, 0);
        });
    }

    window.addEventListener('afterprint', function() {
        document.body.classList.remove('print-allowed');
    });
})();

// Initialize feather icons
if (typeof feather !== 'undefined') { feather.replace(); }
</script>
@endsection

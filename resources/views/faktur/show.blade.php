@extends('layout.master')

@section('content')
<nav class="page-breadcrumb d-print-none">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('faktur.index') }}">Invoice</a></li>
        <li class="breadcrumb-item active" aria-current="page">Details & Preview</li>
    </ol>
</nav>

<div class="row justify-content-center">
    <div class="col-md-10 col-lg-9">
        <div class="d-flex justify-content-between align-items-center mb-3 d-print-none">
            <a href="{{ route('faktur.index') }}" class="btn btn-link text-muted p-0">
                <i data-feather="arrow-left" class="icon-sm"></i> Back to List
            </a>
            <div class="d-flex gap-2">
                @if(strtolower($faktur->status ?? '') === 'sedang diproses')
                <button id="btnPrint" class="btn btn-primary px-3">
                    <i data-feather="printer" class="icon-sm me-1"></i> Print Document
                </button>
                @endif
                @if($faktur->file_path)
                <a href="{{ asset('storage/' . $faktur->file_path) }}" target="_blank" class="btn btn-outline-danger">
                    <i data-feather="file-text" class="icon-sm me-1"></i> Official PDF
                </a>
                @endif
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4 document-paper">
            <div class="card-body p-4 p-md-5">
                
                <div class="status-ribbon d-print-none">
                    <span class="badge {{ strtolower($faktur->status) === 'draft' ? 'bg-warning' : 'bg-success' }} text-uppercase ls-1">
                        {{ $faktur->status ?? 'Draft' }}
                    </span>
                </div>

                <div class="print-container">
                    <div class="row align-items-start mb-4">
                        <div class="col-7">
                            <h2 class="fw-bolder text-primary mb-1" style="letter-spacing: -1px;">PURCHASE INVOICE</h2>
                            <div class="text-dark small">
                                <span class="text-muted">Invoice Number:</span> <span class="fw-bold">{{ $faktur->no_faktur }}</span><br>
                                <span class="text-muted">Date:</span> <span class="fw-bold">{{ \Carbon\Carbon::parse($faktur->tanggal)->format('d F Y') }}</span>
                            </div>
                        </div>
                        <div class="col-5 text-end">
                            <img src="{{ company_logo_url($faktur->perusahaan) }}" alt="Logo" class="mb-2 company-logo-print">
                            <h5 class="fw-bold mb-0 text-dark">{{ $faktur->perusahaan->nama_perusahaan ?? 'COMPANY NAME' }}</h5>
                            <p class="company-address text-muted mb-0">
                                {{ $faktur->perusahaan->alamat ?? 'Address not set.' }}
                            </p>
                        </div>
                    </div>
                    <div class="divider-line mb-4"></div>

                    <div class="row mb-4">
                        <div class="col-6">
                            <h6 class="text-uppercase fw-bold small text-muted mb-2">Supplier:</h6>
                            <div class="ps-2 border-start border-3 border-primary">
                                <p class="fw-bolder text-dark mb-0" style="font-size: 1.1rem;">{{ $faktur->nama_supplier }}</p>
                                <p class="text-muted small mb-0">
                                    @if($faktur->supplier)
                                    <i data-feather="user" class="icon-xs"></i> {{ $faktur->supplier->pic ?? '-' }}
                                    @if($faktur->supplier->no_kontak)
                                        <span class="ms-1">({{ $faktur->supplier->no_kontak }})</span>
                                    @endif
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="col-6">
                            <h6 class="text-uppercase fw-bold small text-muted mb-2 text-end">Project:</h6>
                            <div class="pe-2 border-end border-3 border-info text-end">
                                <p class="fw-bolder text-dark mb-0">{{ $faktur->proyek->nama_proyek ?? '-' }}</p>
                                <p class="text-muted small mb-0">
                                    @if($faktur->po)
                                    PO Ref: {{ $faktur->po->no_po }}
                                    @endif
                                </p>
                                <p class="text-muted small mb-0">Transaction ID: #{{ $faktur->id }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive mb-4">
                        <table class="table table-bordered border-dark custom-table-document">
                            <thead>
                                <tr class="table-light">
                                    <th class="text-center py-2" width="5%">NO</th>
                                    <th class="py-2">ITEM DESCRIPTION</th>
                                    <th class="text-center py-2" width="10%">QTY</th>
                                    <th class="text-center py-2" width="8%">UOM</th>
                                    <th class="text-end py-2" width="15%">UNIT PRICE</th>
                                    <th class="text-end py-2" width="18%">TOTAL</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($faktur->details as $index => $detail)
                                <tr>
                                    <td class="text-center">{{ $index + 1 }}</td>
                                    <td>
                                        <div class="fw-bold text-dark">{{ $detail->uraian }}</div>
                                        <div class="small text-muted">Code: {{ $detail->kode_item }}</div>
                                    </td>
                                    <td class="text-center">{{ number_format($detail->qty, 2, ',', '.') }}</td>
                                    <td class="text-center">{{ $detail->uom }}</td>
                                    <td class="text-end">Rp {{ number_format($detail->harga, 0, ',', '.') }}</td>
                                    <td class="text-end fw-bold">Rp {{ number_format($detail->total, 0, ',', '.') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @php
                        $dppTotal = max(0, $faktur->subtotal - $faktur->total_diskon);
                        $ppnRate = $dppTotal > 0 ? ($faktur->total_ppn / $dppTotal) : 0;
                        $dppUm = $faktur->uang_muka_dipakai > 0 ? ($faktur->uang_muka_dipakai / (1 + $ppnRate)) : 0;
                        $ppnUm = $faktur->uang_muka_dipakai > 0 ? ($faktur->uang_muka_dipakai - $dppUm) : 0;
                        $ppnSetelahUm = max(0, $faktur->total_ppn - $ppnUm);
                        $grandTotalSetelahUm = max(0, $faktur->total - ($faktur->uang_muka_dipakai ?? 0));
                        $sisaBayar = max(0, ($faktur->total - ($faktur->total_kredit_retur ?? 0) - ($faktur->uang_muka_dipakai ?? 0)) - $faktur->sudah_dibayar);
                    @endphp

                    <div class="row">
                        <div class="col-7">
                            @if($faktur->uang_muka_dipakai > 0 || $faktur->total_kredit_retur > 0 || $faktur->sudah_dibayar > 0)
                            <div class="card bg-soft-info border-info mb-3">
                                <div class="card-body p-3">
                                    <h6 class="card-title small mb-2">Payment Information</h6>
                                    @if($faktur->uang_muka_dipakai > 0)
                                    <div class="small mb-1">
                                        <span class="text-muted">Down Payment Used:</span> 
                                        <strong class="text-info">Rp {{ number_format($faktur->uang_muka_dipakai, 0, ',', '.') }}</strong>
                                    </div>
                                    @endif
                                    @if($faktur->total_kredit_retur > 0)
                                    <div class="small mb-1">
                                        <span class="text-muted">Credit Note (Return):</span> 
                                        <strong class="text-danger">Rp {{ number_format($faktur->total_kredit_retur, 0, ',', '.') }}</strong>
                                    </div>
                                    @endif
                                    @if($faktur->sudah_dibayar > 0)
                                    <div class="small mb-1">
                                        <span class="text-muted">Paid:</span> 
                                        <strong class="text-success">Rp {{ number_format($faktur->sudah_dibayar, 0, ',', '.') }}</strong>
                                    </div>
                                    @endif
                                    <div class="small mt-2 pt-2 border-top">
                                        <span class="text-muted">Balance Due:</span> 
                                        <strong class="{{ $sisaBayar > 0 ? 'text-danger' : 'text-success' }}">
                                            Rp {{ number_format($sisaBayar, 0, ',', '.') }}
                                        </strong>
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                        <div class="col-5">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td class="text-muted">Subtotal:</td>
                                    <td class="text-end fw-bold">Rp {{ number_format($faktur->subtotal, 0, ',', '.') }}</td>
                                </tr>
                                @if($faktur->total_diskon > 0)
                                <tr>
                                    <td class="text-muted">Discount:</td>
                                    <td class="text-end text-danger">-Rp {{ number_format($faktur->total_diskon, 0, ',', '.') }}</td>
                                </tr>
                                @endif
                                @if($faktur->uang_muka_dipakai > 0)
                                <tr>
                                    <td class="text-muted">Down Payment:</td>
                                    <td class="text-end text-info">-Rp {{ number_format($faktur->uang_muka_dipakai, 0, ',', '.') }}</td>
                                </tr>
                                @endif
                                <tr>
                                    <td class="text-muted">VAT (PPN):</td>
                                    <td class="text-end">Rp {{ number_format($ppnSetelahUm, 0, ',', '.') }}</td>
                                </tr>
                                <tr class="total-row">
                                    <td class="fw-bolder text-primary">TOTAL:</td>
                                    <td class="text-end fw-bolder text-primary">Rp {{ number_format($grandTotalSetelahUm, 0, ',', '.') }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="row mt-3 d-print-none">
                        <div class="col-12">
                             <div class="alert alert-info py-2 small border-0 bg-soft-info">
                                <i data-feather="info" class="icon-xs me-1"></i> Use the print button for perfect A4 layout results.
                             </div>
                        </div>
                    </div>

                    <!-- Approval Trail -->
                    @php
                        $preparedBy = optional($faktur->dibuatOleh)->name ?? '-';
                        $preparedAt = optional($faktur->dibuat_at)->format('d/m/Y H:i');
                        $approver = optional($faktur->disetujuiOleh)->name ?? '-';
                        $approvedAt = optional($faktur->disetujui_at)->format('d/m/Y H:i');
                    @endphp
                    <div class="mt-5">
                        <div class="dashed-sep mb-3"></div>
                        <div class="border rounded small">
                            <div class="px-3 py-2">
                                <strong>Prepared by:</strong>
                                <span class="ms-2">{{ $preparedBy }}</span>
                                @if($preparedAt)
                                <span class="text-muted ms-2">[{{ $preparedAt }}]</span>
                                @endif
                                <span class="ms-4">|</span>
                                <strong class="ms-4">Approved By:</strong>
                                <span class="ms-2">{{ $approver }}</span>
                                @if($approvedAt)
                                <span class="text-muted ms-2">[{{ $approvedAt }}]</span>
                                @endif
                            </div>
                        </div>
                        <div class="dashed-sep mt-3 mb-4"></div>

                        <div class="mt-4 pt-2">
                            <div class="fw-bold small mb-2">Received & Verified by,</div>
                            <div class="d-flex justify-content-between small mb-4">
                                <div>
                                    <span class="me-2">Signature :</span>
                                    <span class="underline-space"></span>
                                </div>
                                <div>
                                    <span class="me-2">Invoice# :</span>
                                    <span class="fw-bold">{{ $faktur->no_faktur }}</span>
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
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="card border-0 shadow-sm d-print-none">
            <div class="card-body">
                <div class="d-flex gap-2 flex-wrap justify-content-between align-items-center">
                    <div>
                        @if(strtolower($faktur->status) === 'draft')
                        <form action="{{ route('faktur.approve', $faktur->id) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-success" onclick="return confirm('Approve this invoice and create journal entry?')">
                                <i data-feather="check-circle"></i> Approve Invoice
                            </button>
                        </form>
                        @else
                        <span class="badge bg-success py-2 px-3">
                            <i data-feather="check-circle" class="icon-sm me-1"></i> Invoice Approved
                        </span>
                        @endif
                    </div>
                    <div class="text-muted small">
                        <i data-feather="clock" class="icon-xs"></i> Created: {{ $faktur->created_at->format('d/m/Y H:i') }}
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
    background: linear-gradient(to right, #6571ff, #00d2ff);
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

.bg-soft-primary { background-color: rgba(101, 113, 255, 0.08) !important; }
.bg-soft-info { background-color: rgba(0, 210, 255, 0.1) !important; color: #007fa0; }
.ls-1 { letter-spacing: 1px; }
.dashed-sep { border-top: 4px dashed #557; opacity: .7; }
.underline-space { display: inline-block; border-bottom: 1px solid #000; width: 160px; height: 14px; vertical-align: middle; }

.total-row {
    border-top: 2px solid #6571ff;
    font-size: 14px;
}

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

    .total-row {
        border-top: 2px solid #000 !important;
    }

    .sidebar, .navbar, .footer, .d-print-none, .page-breadcrumb {
        display: none !important;
    }

    .main-wrapper, .page-content {
        margin: 0 !important;
        padding: 0 !important;
    }
    
    .text-primary { color: #000 !important; }
}
</style>

<script>
// Print protection: block Ctrl+P/menu print except via official button
(function() {
    const canPrint = {{ strtolower($faktur->status ?? '') === 'sedang diproses' ? 'true' : 'false' }};

    document.addEventListener('keydown', function(e) {
        const isPrintShortcut = (e.ctrlKey || e.metaKey) && (e.key === 'p' || e.key === 'P');
        if (isPrintShortcut) {
            e.preventDefault();
            e.stopPropagation();
            alert('Please use the "Print Document" button on this page to print.');
        }
    }, true);

    const btn = document.getElementById('btnPrint');
    if (btn) {
        btn.addEventListener('click', function() {
            if (!canPrint) {
                alert('Invoice not approved yet, printing not allowed.');
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
if (typeof feather !== 'undefined') {
    feather.replace();
}
</script>
@endsection

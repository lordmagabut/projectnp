@extends('layout.master')

@section('content')
<nav class="page-breadcrumb d-print-none">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('po.index') }}">Purchase Order</a></li>
        <li class="breadcrumb-item active" aria-current="page">Details & Preview</li>
    </ol>
</nav>

<div class="row justify-content-center">
    <div class="col-md-10 col-lg-9">
        <div class="d-flex justify-content-between align-items-center mb-3 d-print-none">
            <a href="{{ route('po.index') }}" class="btn btn-link text-muted p-0">
                <i data-feather="arrow-left" class="icon-sm"></i> Back to List
            </a>
            <div class="d-flex gap-2">
                @if(strtolower($po->status ?? '') === 'sedang diproses')
                <button id="btnPrint" class="btn btn-primary px-3">
                    <i data-feather="printer" class="icon-sm me-1"></i> Print Document
                </button>
                @endif
                @if($po->file_path)
                <a href="{{ asset('storage/' . $po->file_path) }}" target="_blank" class="btn btn-outline-danger">
                    <i data-feather="file-text" class="icon-sm me-1"></i> Official PDF
                </a>
                @endif
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4 document-paper">
            <div class="card-body p-4 p-md-5">
                
                <div class="status-ribbon d-print-none">
                    <span class="badge {{ in_array(strtolower($po->status), ['draft', 'pending']) ? 'bg-warning' : 'bg-success' }} text-uppercase ls-1">
                        {{ $po->status ?? 'Draft' }}
                    </span>
                </div>

                <div class="print-container">
                    <div class="row align-items-start mb-4">
                        <div class="col-7">
                            <h2 class="fw-bolder text-primary mb-1" style="letter-spacing: -1px;">PURCHASE ORDER</h2>
                            <div class="text-dark small">
                                <span class="text-muted">PO Number:</span> <span class="fw-bold">{{ $po->no_po }}</span><br>
                                <span class="text-muted">Date:</span> <span class="fw-bold">{{ \Carbon\Carbon::parse($po->tanggal)->format('d F Y') }}</span>
                            </div>
                        </div>
                        <div class="col-5 text-end">
                            <img src="{{ company_logo_url($po->perusahaan) }}" alt="Logo" class="mb-2 company-logo-print">
                            <h5 class="fw-bold mb-0 text-dark">{{ $po->perusahaan->nama_perusahaan ?? 'COMPANY NAME' }}</h5>
                            <p class="company-address text-muted mb-0">
                                {{ $po->perusahaan->alamat ?? 'Address not set.' }}
                            </p>
                        </div>
                    </div>
                    <div class="divider-line mb-4"></div>

                    <div class="row mb-4">
                        <div class="col-6">
                            <h6 class="text-uppercase fw-bold small text-muted mb-2">Supplier:</h6>
                            <div class="ps-2 border-start border-3 border-primary">
                                <p class="fw-bolder text-dark mb-0" style="font-size: 1.1rem;">{{ $po->nama_supplier }}</p>
                                <p class="text-muted small mb-0">
                                    <i data-feather="user" class="icon-xs"></i> {{ $po->supplier->pic ?? '-' }} 
                                    @if($po->supplier && $po->supplier->no_kontak)
                                        <span class="ms-1">({{ $po->supplier->no_kontak }})</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="col-6">
                            <h6 class="text-uppercase fw-bold small text-muted mb-2 text-end">Project:</h6>
                            <div class="pe-2 border-end border-3 border-info text-end">
                                <p class="fw-bolder text-dark mb-0">{{ $po->proyek->nama_proyek ?? '-' }}</p>
                                <p class="text-muted small mb-0">Transaction ID: #{{ $po->id }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive mb-4">
                        <table class="table table-bordered border-dark custom-table-document">
                            <thead>
                                <tr class="table-light">
                                    <th class="text-center py-2" width="5%">NO</th>
                                    <th class="py-2">ITEM / SERVICE DESCRIPTION</th>
                                    <th class="text-center py-2" width="15%">QTY</th>
                                    <th class="text-end py-2" width="18%">PRICE (IDR)</th>
                                    <th class="text-end py-2" width="20%">SUBTOTAL (IDR)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($po->details as $index => $detail)
                                <tr>
                                    <td class="text-center">{{ $index + 1 }}</td>
                                    <td>
                                        <div class="fw-bold text-dark">{{ $detail->uraian }}</div>
                                    </td>
                                    <td class="text-center fw-bold">
                                        {{ number_format($detail->qty, 0, ',', '.') }} 
                                        <span class="small fw-normal text-muted">{{ $detail->uom }}</span>
                                    </td>
                                    <td class="text-end">{{ number_format($detail->harga, 0, ',', '.') }}</td>
                                    <td class="text-end fw-bolder">{{ number_format($detail->total, 0, ',', '.') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4" class="text-end fw-bold text-muted small">SUBTOTAL BEFORE TAX</td>
                                    <td class="text-end fw-bold bg-light">{{ number_format($subtotal, 0, ',', '.') }}</td>
                                </tr>
                                @if($diskonPersen > 0)
                                <tr>
                                    <td colspan="4" class="text-end fw-bold text-danger small">DISCOUNT ({{ $diskonPersen }}%)</td>
                                    <td class="text-end fw-bold text-danger">- {{ number_format($diskonRupiah, 0, ',', '.') }}</td>
                                </tr>
                                @endif
                                @if($ppnPersen > 0)
                                <tr>
                                    <td colspan="4" class="text-end fw-bold text-muted small">VAT ({{ $ppnPersen }}%)</td>
                                    <td class="text-end fw-bold">{{ number_format($ppnRupiah, 0, ',', '.') }}</td>
                                </tr>
                                @endif
                                <tr class="total-row shadow-sm">
                                    <td colspan="4" class="text-end fw-bolder text-primary">GRAND TOTAL</td>
                                    <td class="text-end fw-bolder text-primary bg-soft-primary" style="font-size: 1.1rem;">
                                        Rp {{ number_format($grandTotal, 0, ',', '.') }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="row align-items-end mt-2">
                        <div class="col-7 small">
                            @if($po->keterangan)
                            <div class="p-2 bg-light rounded border mb-3">
                                <strong class="d-block small text-muted text-uppercase mb-1">Additional Notes:</strong>
                                <span class="text-dark italic">"{{ $po->keterangan }}"</span>
                            </div>
                            @endif
                        </div>
                        <div class="col-5 d-print-none">
                             <div class="alert alert-info py-2 small border-0 bg-soft-info">
                                <i data-feather="info" class="icon-xs me-1"></i> Use the print button for perfect A4 layout results.
                             </div>
                        </div>
                    </div>

                    <!-- Completion & Delivery Section -->
                    @php
                        $preparedBy = optional($po->dibuatOleh)->name ?? '-';
                        $preparedAt = optional($po->dibuat_at)->format('d/m/Y H:i');
                        $reviewer = optional($po->direviewOleh)->name ?? '-';
                        $reviewTime = optional($po->direview_at)->format('d/m/Y H:i');
                        $approver = optional($po->disetujuiOleh)->name ?? '-';
                        $approvedAt = optional($po->disetujui_at)->format('d/m/Y H:i');
                    @endphp
                    <div class="mt-5">
                        <div class="dashed-sep mb-3"></div>
                        <div class="border rounded small">
                            <div class="px-3 py-2">
                                        <strong>Prepared by :</strong>
                                        <span class="ms-2">{{ $preparedBy }}</span>
                                        @if($preparedAt)
                                        <span class="text-muted ms-2">[{{ $preparedAt }}]</span>
                                        @endif
                                        <span class="ms-4">|</span>
                                        <strong class="ms-4">Reviewed &amp; Verified By:</strong>
                                        <span class="ms-2">{{ $reviewer }}</span>
                                        @if($reviewTime)
                                        <span class="text-muted ms-2">[{{ $reviewTime }}]</span>
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
                            <div class="fw-bold small mb-2">Received by,</div>
                            <div class="d-flex justify-content-between small mb-4">
                                <div>
                                    <span class="me-2">Signature :</span>
                                    <span class="underline-space"></span>
                                </div>
                                <div>
                                    <span class="me-2">P.O.# :</span>
                                    <span class="fw-bold">{{ $po->no_po }}</span>
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
                </div> </div>
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
    display: none; /* Change to block if you want ribbon style, or leave as badge */
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
        content: 'Pencetakan dinonaktifkan. Gunakan tombol "Cetak Dokumen".';
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
    
    .text-primary { color: #000 !important; }
}
</style>

<script>
// Proteksi cetak: blok Ctrl+P/menu print kecuali melalui tombol resmi
(function() {
    const canPrint = {{ strtolower($po->status ?? '') === 'sedang diproses' ? 'true' : 'false' }};

    document.addEventListener('keydown', function(e) {
        const isPrintShortcut = (e.ctrlKey || e.metaKey) && (e.key === 'p' || e.key === 'P');
        if (isPrintShortcut) {
            e.preventDefault();
            e.stopPropagation();
            alert('Gunakan tombol "Cetak Dokumen" pada halaman ini untuk mencetak.');
        }
    }, true);

    const btn = document.getElementById('btnPrint');
    if (btn) {
        btn.addEventListener('click', function() {
            if (!canPrint) {
                alert('PO belum disetujui, pencetakan belum diizinkan.');
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
</script>
@endsection
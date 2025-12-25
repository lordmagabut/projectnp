@extends('layout.master')

@section('content')
<nav class="page-breadcrumb d-print-none">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('po.index') }}">Purchase Order</a></li>
        <li class="breadcrumb-item active" aria-current="page">Detail & Cetak Ringan</li>
    </ol>
</nav>

<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="card shadow-none border-0">
            <div class="card-header d-print-none bg-transparent border-bottom d-flex justify-content-between align-items-center p-3">
                <a href="{{ route('po.index') }}" class="btn btn-outline-secondary">
                    <i data-feather="arrow-left" class="icon-sm"></i> Kembali
                </a>
                <div class="d-flex align-items-center">
                    <button onclick="window.print()" class="btn btn-info text-white me-2">
                        <i data-feather="printer" class="icon-sm"></i> Cetak Ringan
                    </button>
                    <span class="badge {{ strtolower($po->status ?? 'draft') === 'draft' ? 'bg-warning text-dark' : 'bg-success' }} py-2 px-3 text-uppercase">
                        <i data-feather="clipboard" class="icon-sm me-1"></i> {{ $po->status ?? 'draft' }}
                    </span>
                </div>
            </div>

            <div class="print-container">
                <div class="po-print-box">

                    <div class="row align-items-center mb-2">
                        <div class="col-7">
                            <h4 class="text-primary fw-bolder mb-0">PURCHASE ORDER</h4>
                            <p class="text-muted small mb-0">No: <strong>{{ $po->no_po }}</strong></p>
                            <p class="text-muted small mb-0">Tanggal: {{ \Carbon\Carbon::parse($po->tanggal)->format('d/m/Y') }}</p>
                        </div>
                        <div class="col-5 text-end">
                            <div class="mb-2">
                                <img src="{{ company_logo_url($po->perusahaan) }}" alt="Logo {{ $po->perusahaan->nama_perusahaan ?? '' }}" style="max-height:80px; max-width:220px; object-fit:contain;">
                            </div>
                            <h6 class="fw-bold mb-0 text-dark">{{ $po->perusahaan->nama_perusahaan ?? 'NAMA PERUSAHAAN' }}</h6>
                            <p style="font-size: 10px; line-height: 1.2;" class="text-muted mb-0">
                                {{ $po->perusahaan->alamat ?? 'Alamat lengkap perusahaan belum diatur.' }}
                            </p>
                        </div>
                    </div>

                    <div style="border-top: 2px solid #6571ff; margin-bottom: 12px;"></div>

                    <div class="row mb-3" style="font-size: 11px;">
                        <div class="col-6">
                            <table class="table table-sm table-borderless mb-0">
                                <tr>
                                    <td class="p-0 text-muted" width="35%">Supplier</td>
                                    <td class="p-0 text-dark">: <strong>{{ $po->nama_supplier }}</strong></td>
                                </tr>
                                <tr>
                                    <td class="p-0 text-muted">PIC / Kontak</td>
                                    <td class="p-0 text-dark">: {{ $po->supplier->pic ?? '-' }} {{ $po->supplier && $po->supplier->no_kontak ? '('.$po->supplier->no_kontak.')' : '' }}</td>
                                </tr>
                                <tr>
                                    <td class="p-0 text-muted">Proyek</td>
                                    <td class="p-0 text-dark">: {{ $po->proyek->nama_proyek ?? '-' }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-6">
                            <table class="table table-sm table-borderless mb-0">
                                <tr>
                                    <td class="p-0 text-muted text-end" width="60%">ID Transaksi</td>
                                    <td class="p-0 text-end text-dark">: #{{ $po->id }}</td>
                                </tr>
                                <tr>
                                    <td class="p-0 text-muted text-end">Status</td>
                                    <td class="p-0 text-end text-dark">: {{ ucfirst($po->status ?? 'draft') }}</td>
                                </tr>
                                <tr>
                                    <td class="p-0 text-muted text-end">Keterangan</td>
                                    <td class="p-0 text-end text-dark">: {{ $po->keterangan ?? '-' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered custom-table-print">
                            <thead>
                                <tr>
                                    <th class="text-center" width="5%">No</th>
                                    <th width="40%">Deskripsi / Uraian</th>
                                    <th class="text-center" width="12%">Qty</th>
                                    <th class="text-end" width="18%">Harga</th>
                                    <th class="text-end" width="20%">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($po->details as $index => $detail)
                                <tr>
                                    <td class="text-center">{{ $index + 1 }}</td>
                                    <td style="line-height: 1.2;">
                                        <div class="fw-bold">{{ $detail->kode_item }}</div>
                                        <div class="text-muted small">{{ $detail->uraian }}</div>
                                    </td>
                                    <td class="text-center">{{ number_format($detail->qty, 2, ',', '.') }} {{ $detail->uom }}</td>
                                    <td class="text-end">{{ number_format($detail->harga, 0, ',', '.') }}</td>
                                    <td class="text-end fw-bold">{{ number_format($detail->total, 0, ',', '.') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="row mt-3">
                        <div class="col-7">
                            <div class="row text-center mt-3 d-none d-print-flex">
                                <div class="col-5">
                                    <p class="mb-4 small">Diterima Oleh,</p>
                                    <div class="mx-auto border-bottom border-dark" style="width: 80%;"></div>
                                    <p class="small mt-1">( .................... )</p>
                                </div>
                                <div class="col-2"></div>
                                <div class="col-5">
                                    <p class="mb-4 small">Disetujui Oleh,</p>
                                    <div class="mx-auto border-bottom border-dark" style="width: 80%;"></div>
                                    <p class="small mt-1">Adm. Keuangan</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-5">
                            <table class="table table-sm table-borderless text-end fw-bold" style="font-size: 11px;">
                                <tr>
                                    <td class="text-muted fw-normal">Subtotal:</td>
                                    <td class="text-dark">Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
                                </tr>
                                @if($diskonPersen > 0)
                                <tr>
                                    <td class="text-muted fw-normal">Diskon ({{ $diskonPersen }}%):</td>
                                    <td class="text-danger">- {{ number_format($diskonRupiah, 0, ',', '.') }}</td>
                                </tr>
                                @endif
                                @if($ppnPersen > 0)
                                <tr>
                                    <td class="text-muted fw-normal">PPN ({{ $ppnPersen }}%):</td>
                                    <td class="text-dark">{{ number_format($ppnRupiah, 0, ',', '.') }}</td>
                                </tr>
                                @endif
                                <tr style="font-size: 14px; border-top: 2px solid #6571ff;">
                                    <td class="text-primary pt-1">GRAND TOTAL:</td>
                                    <td class="text-primary pt-1">Rp {{ number_format($grandTotal, 0, ',', '.') }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    @if($po->file_path)
                    <div class="mt-3 d-print-none">
                        <hr>
                        <a href="{{ asset('storage/' . $po->file_path) }}" target="_blank" class="btn btn-sm btn-outline-danger">
                            <i data-feather="file-text" class="icon-sm"></i> Lihat File PO (PDF dari Template)
                        </a>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* --- Tampilan Web --- */
.po-print-box {
    background: #fff;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 0 10px rgba(0,0,0,0.05);
    width: 100%;
    margin-top: 10px;
}

.custom-table-print {
    border: 1px solid #000 !important;
}

.custom-table-print th {
    background-color: #f4f7f6 !important;
    color: #000;
    font-size: 10px;
    text-transform: uppercase;
    border: 1px solid #000 !important;
}

.custom-table-print td {
    border: 1px solid #000 !important;
    vertical-align: middle;
}

/* --- Tampilan Cetak --- */
@media print {
    @page {
        size: A4 portrait;
        margin: 0;
    }

    .sidebar, .navbar, .footer, .d-print-none, .page-breadcrumb, .btn {
        display: none !important;
    }

    .main-wrapper, .page-content {
        margin: 0 !important;
        padding: 0 !important;
        background: white !important;
    }

    .print-container {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        padding: 1.5cm;
        box-sizing: border-box;
    }

    .po-print-box {
        border: none !important;
        padding: 0 !important;
        box-shadow: none !important;
        width: 100% !important;
    }

    body, table, td, th {
        color: #000 !important;
        font-family: 'Arial', sans-serif !important;
    }

    .text-primary { color: #6571ff !important; }
    * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
}
</style>
@endsection

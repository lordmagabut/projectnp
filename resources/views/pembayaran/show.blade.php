@extends('layout.master')

@section('content')
<nav class="page-breadcrumb d-print-none">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('pembayaran.index') }}">Riwayat Pembayaran</a></li>
        <li class="breadcrumb-item active" aria-current="page">Bukti Pembayaran</li>
    </ol>
</nav>

<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="card shadow-none border-0">
            <div class="card-header d-print-none bg-transparent border-bottom d-flex justify-content-between align-items-center p-3">
                <a href="{{ route('pembayaran.index') }}" class="btn btn-outline-secondary">
                    <i data-feather="arrow-left" class="icon-sm"></i> Kembali
                </a>
                <div class="d-flex align-items-center">
                    <button onclick="window.print()" class="btn btn-info text-white me-2">
                        <i data-feather="printer" class="icon-sm"></i> Cetak 
                    </button>
                    <span class="badge bg-success py-2 px-3 text-uppercase">
                        <i data-feather="credit-card" class="icon-sm me-1"></i> Pembayaran
                    </span>
                </div>
            </div>

            <div class="print-container">
                <div class="pembayaran-print-box">

                    <div class="row align-items-center mb-2">
                        <div class="col-7">
                            <h4 class="text-primary fw-bolder mb-0">BUKTI PEMBAYARAN (BKK)</h4>
                            <p class="text-muted small mb-0">No: <strong>{{ $pembayaran->no_pembayaran }}</strong></p>
                        </div>
                        <div class="col-5 text-end">
                            <h6 class="fw-bold mb-0 text-dark">{{ $pembayaran->faktur->perusahaan->nama_perusahaan ?? 'NAMA PERUSAHAAN' }}</h6>
                            <p style="font-size: 10px; line-height: 1.2;" class="text-muted mb-0">
                                {{ $pembayaran->faktur->perusahaan->alamat ?? '' }}
                            </p>
                        </div>
                    </div>

                    <div style="border-top: 2px solid #6571ff; margin-bottom: 12px;"></div>

                    <div class="row mb-3" style="font-size: 11px;">
                        <div class="col-6">
                            <table class="table table-sm table-borderless mb-0">
                                <tr>
                                    <td class="p-0 text-muted" width="35%">Supplier</td>
                                    <td class="p-0 text-dark">: <strong>{{ $pembayaran->faktur->nama_supplier ?? '-' }}</strong></td>
                                </tr>
                                <tr>
                                    <td class="p-0 text-muted">Proyek</td>
                                    <td class="p-0 text-dark">: {{ $pembayaran->faktur->proyek->nama_proyek ?? '-' }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-6">
                            <table class="table table-sm table-borderless mb-0">
                                <tr>
                                    <td class="p-0 text-muted text-end" width="60%">Tanggal Pembayaran</td>
                                    <td class="p-0 text-end text-dark">: {{ \Carbon\Carbon::parse($pembayaran->tanggal)->format('d/m/Y') }}</td>
                                </tr>
                                <tr>
                                    <td class="p-0 text-muted text-end">ID Transaksi</td>
                                    <td class="p-0 text-end text-dark">: #{{ $pembayaran->id }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered custom-table-print">
                            <thead>
                                <tr>
                                    <th class="text-center" width="5%">No</th>
                                    <th width="45%">Deskripsi / Uraian</th>
                                    <th class="text-center" width="12%">Qty</th>
                                    <th class="text-end" width="18%">Harga</th>
                                    <th class="text-end" width="20%">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($pembayaran->faktur->details ?? [] as $index => $detail)
                                <tr>
                                    <td class="text-center">{{ $index + 1 }}</td>
                                    <td style="line-height: 1.2;">
                                        <div class="fw-bold">{{ $detail->kode_item ?? '' }}</div>
                                        <div class="text-muted small">{{ $detail->uraian ?? '' }}</div>
                                    </td>
                                    <td class="text-center">{{ $detail->qty ?? '-' }} {{ $detail->uom ?? '' }}</td>
                                    <td class="text-end">{{ number_format($detail->harga ?? 0, 0, ',', '.') }}</td>
                                    <td class="text-end fw-bold">{{ number_format($detail->total ?? 0, 0, ',', '.') }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">Detail faktur tidak tersedia.</td>
                                </tr>
                                @endforelse
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
                                    <td class="text-muted fw-normal">Total Faktur:</td>
                                    <td class="text-dark">Rp {{ number_format($pembayaran->faktur->total ?? 0, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted fw-normal">Sudah Dibayar (sebelum ini):</td>
                                    <td class="text-dark">Rp {{ number_format(max(0, ($pembayaran->faktur->sudah_dibayar ?? 0) - ($pembayaran->nominal ?? 0)), 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted fw-normal">Nominal Pembayaran:</td>
                                    <td class="text-success">Rp {{ number_format($pembayaran->nominal ?? 0, 0, ',', '.') }}</td>
                                </tr>
                                <tr style="font-size: 14px; border-top: 2px solid #6571ff;">
                                    <td class="text-primary pt-1">Sisa Hutang:</td>
                                    <td class="text-primary pt-1">Rp {{ number_format(max(0, ($pembayaran->faktur->total ?? 0) - ($pembayaran->faktur->sudah_dibayar ?? 0)), 0, ',', '.') }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    @if(!empty($pembayaran->faktur->file_path))
                    <div class="mt-3 d-print-none">
                        <hr>
                        <a href="{{ asset('storage/' . $pembayaran->faktur->file_path) }}" target="_blank" class="btn btn-sm btn-outline-danger">
                            <i data-feather="file-text" class="icon-sm"></i> Lihat File Faktur Asli
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
.pembayaran-print-box {
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

@media print {
    @page { size: A4 portrait; margin: 0; }
    .sidebar, .navbar, .footer, .d-print-none, .page-breadcrumb, .btn { display: none !important; }
    .main-wrapper, .page-content { margin: 0 !important; padding: 0 !important; background: white !important; }
    .print-container { position: absolute; top: 0; left: 0; width: 100%; height: 50%; padding: 1.5cm; box-sizing: border-box; }
    .pembayaran-print-box { border: none !important; padding: 0 !important; box-shadow: none !important; width: 100% !important; }
    body, table, td, th { color: #000 !important; font-family: 'Arial', sans-serif !important; }
    .text-primary { color: #6571ff !important; }
    * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
}
</style>
@endsection

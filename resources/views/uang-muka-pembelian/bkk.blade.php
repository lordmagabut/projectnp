@extends('layout.master')

@section('content')
<nav class="page-breadcrumb d-print-none">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('uang-muka-pembelian.index') }}">Uang Muka Pembelian</a></li>
        <li class="breadcrumb-item active" aria-current="page">Bukti Pembayaran (BKK)</li>
    </ol>
</nav>

<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="card shadow-none border-0">
            <div class="card-header d-print-none bg-transparent border-bottom d-flex justify-content-between align-items-center p-3">
                <a href="{{ route('uang-muka-pembelian.show', $uangMuka->id) }}" class="btn btn-outline-secondary">
                    <i data-feather="arrow-left" class="icon-sm"></i> Kembali
                </a>
                <div class="d-flex align-items-center">
                    <button onclick="window.print()" class="btn btn-info text-white me-2">
                        <i data-feather="printer" class="icon-sm"></i> Cetak BKK
                    </button>
                    <span class="badge bg-success py-2 px-3 text-uppercase">
                        <i data-feather="credit-card" class="icon-sm me-1"></i> Approved
                    </span>
                </div>
            </div>

            <div class="print-container">
                <div class="bkk-print-box">

                    <div class="row align-items-center mb-2">
                        <div class="col-7">
                            <h4 class="text-primary fw-bolder mb-0">BUKTI PEMBAYARAN (BKK)</h4>
                            <p class="text-muted small mb-0">No: <strong>{{ $uangMuka->no_uang_muka }}</strong></p>
                        </div>
                        <div class="col-5 text-end">
                            <div class="mb-2">
                                <img src="{{ company_logo_url($uangMuka->perusahaan) }}" alt="Logo {{ $uangMuka->perusahaan->nama_perusahaan ?? '' }}" style="max-height:80px; max-width:220px; object-fit:contain;">
                            </div>
                            <h6 class="fw-bold mb-0 text-dark">{{ $uangMuka->perusahaan->nama_perusahaan ?? 'NAMA PERUSAHAAN' }}</h6>
                            <p style="font-size: 10px; line-height: 1.2;" class="text-muted mb-0">
                                {{ $uangMuka->perusahaan->alamat ?? '' }}
                            </p>
                        </div>
                    </div>

                    <div style="border-top: 2px solid #6571ff; margin-bottom: 12px;"></div>

                    <div class="row mb-3" style="font-size: 11px;">
                        <div class="col-6">
                            <table class="table table-sm table-borderless mb-0">
                                <tr>
                                    <td class="p-0 text-muted" width="35%">Supplier</td>
                                    <td class="p-0 text-dark">: <strong>{{ $uangMuka->nama_supplier ?? '-' }}</strong></td>
                                </tr>
                                <tr>
                                    <td class="p-0 text-muted">Proyek</td>
                                    <td class="p-0 text-dark">: {{ $uangMuka->proyek->nama_proyek ?? '-' }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-6">
                            <table class="table table-sm table-borderless mb-0">
                                <tr>
                                    <td class="p-0 text-muted text-end" width="60%">Tanggal Pembayaran</td>
                                    <td class="p-0 text-end text-dark">: {{ \Carbon\Carbon::parse($uangMuka->tanggal)->format('d/m/Y') }}</td>
                                </tr>
                                <tr>
                                    <td class="p-0 text-muted text-end">ID Transaksi</td>
                                    <td class="p-0 text-end text-dark">: #{{ $uangMuka->id }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered custom-table-print">
                            <thead>
                                <tr>
                                    <th class="text-center" width="5%">No</th>
                                    <th width="55%">Deskripsi</th>
                                    <th class="text-end" width="20%">Nominal</th>
                                    <th class="text-end" width="20%">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="text-center">1</td>
                                    <td style="line-height: 1.2;">
                                        <div class="fw-bold">Uang Muka Pembelian</div>
                                        <div class="text-muted small">PO: {{ $uangMuka->po?->no_po ?? '-' }}</div>
                                        <div class="text-muted small">Metode: {{ strtoupper($uangMuka->metode_pembayaran) }}</div>
                                        @if($uangMuka->nama_bank)
                                        <div class="text-muted small">Bank: {{ $uangMuka->nama_bank }} / Rek: {{ $uangMuka->no_rekening_bank }}</div>
                                        @endif
                                    </td>
                                    <td class="text-end">{{ number_format($uangMuka->nominal, 0, ',', '.') }}</td>
                                    <td class="text-end fw-bold">{{ number_format($uangMuka->nominal, 0, ',', '.') }}</td>
                                </tr>
                                @if(isset($umDpp) && isset($umPpn))
                                <tr>
                                    <td></td>
                                    <td class="text-muted">
                                        <div>Rincian:</div>
                                        <div class="small">DPP</div>
                                    </td>
                                    <td class="text-end">{{ number_format($umDpp, 0, ',', '.') }}</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td></td>
                                    <td class="text-muted">
                                        <div class="small">PPN ({{ number_format(($ppnRate ?? 0)*100, 2) }}%)</div>
                                    </td>
                                    <td class="text-end">{{ number_format($umPpn, 0, ',', '.') }}</td>
                                    <td></td>
                                </tr>
                                @endif
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
                                    <td class="text-muted fw-normal">Total Pembayaran:</td>
                                    <td class="text-primary">Rp {{ number_format($uangMuka->nominal, 0, ',', '.') }}</td>
                                </tr>
                                @if(isset($umDpp) && isset($umPpn))
                                <tr>
                                    <td class="text-muted fw-normal">DPP:</td>
                                    <td class="text-dark">Rp {{ number_format($umDpp, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted fw-normal">PPN Masukan:</td>
                                    <td class="text-dark">Rp {{ number_format($umPpn, 0, ',', '.') }}</td>
                                </tr>
                                @endif
                            </table>
                        </div>
                    </div>

                    @if(!empty($uangMuka->file_path))
                    <div class="mt-3 d-print-none">
                        <hr>
                        <a href="{{ asset('storage/' . $uangMuka->file_path) }}" target="_blank" class="btn btn-sm btn-outline-danger">
                            <i data-feather="file-text" class="icon-sm"></i> Lihat Bukti
                        </a>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.bkk-print-box { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.05); width: 100%; margin-top: 10px; }
.custom-table-print { border: 1px solid #000 !important; }
.custom-table-print th { background-color: #f4f7f6 !important; color: #000; font-size: 10px; text-transform: uppercase; border: 1px solid #000 !important; }
.custom-table-print td { border: 1px solid #000 !important; vertical-align: middle; }
@media print { @page { size: A4 portrait; margin: 0; } .sidebar, .navbar, .footer, .d-print-none, .page-breadcrumb, .btn { display: none !important; } .main-wrapper, .page-content { margin: 0 !important; padding: 0 !important; background: white !important; } .print-container { position: absolute; top: 0; left: 0; width: 100%; height: 100%; padding: 1.5cm; box-sizing: border-box; } .bkk-print-box { border: none !important; padding: 0 !important; box-shadow: none !important; width: 100% !important; } body, table, td, th { color: #000 !important; font-family: 'Arial', sans-serif !important; } .text-primary { color: #6571ff !important; } * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; } }
</style>
@endsection
@extends('layout.master')

@section('content')
<nav class="page-breadcrumb d-print-none">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('uang-muka-penjualan.index') }}">Uang Muka Penjualan</a></li>
        <li class="breadcrumb-item active" aria-current="page">Detail & Cetak</li>
    </ol>
</nav>

<div class="row justify-content-center">
  <div class="col-md-10">
    <div class="card shadow-none border-0">
      <div class="card-header d-print-none bg-transparent border-bottom d-flex justify-content-between align-items-center p-3">
        <a href="{{ route('uang-muka-penjualan.index') }}" class="btn btn-outline-secondary">
          <i data-feather="arrow-left" class="icon-sm"></i> Kembali
        </a>
        <div class="d-flex align-items-center gap-2">
          <button onclick="window.print()" class="btn btn-info text-white">
            <i data-feather="printer" class="icon-sm"></i> Cetak
          </button>
          
          @if($um->payment_status === 'belum_dibayar')
            <a href="{{ route('uang-muka-penjualan.pay', $um->id) }}" class="btn btn-success btn-sm">
              <i class="fas fa-money-bill-wave me-1"></i> Bayar
            </a>
          @elseif($um->payment_status === 'dibayar' && $um->nominal_digunakan == 0)
            <form action="{{ route('uang-muka-penjualan.unpay', $um->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Batalkan pembayaran UM ini?');">
              @csrf
              <button type="submit" class="btn btn-warning btn-sm">
                <i class="fas fa-undo me-1"></i> Batalkan Pembayaran
              </button>
            </form>
          @endif
          
          @if($um->payment_status === 'dibayar' && $um->nominal_digunakan == 0)
            <a href="{{ route('uang-muka-penjualan.edit', $um->id) }}" class="btn btn-primary btn-sm">Edit</a>
          @endif
          
          <form action="{{ route('uang-muka-penjualan.destroy', $um->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus UM ini?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger btn-sm" {{ $um->nominal_digunakan > 0 ? 'disabled' : '' }}>Hapus</button>
          </form>
        </div>
          </div>

      <!-- INVOICE UNTUK PRINT -->
      <div class="card-body p-0">
        <div class="alert alert-info m-3 mb-2 d-print-none">
          <i class="fas fa-info-circle me-2"></i>
          <strong>Invoice untuk Pemberi Kerja</strong> - Klik tombol "Cetak" untuk mencetak invoice ini dan menyerahkannya ke pemberi kerja/customer.
        </div>
      </div>

      <div class="print-container">
        <div class="um-print-box">
          
          <div class="row align-items-center mb-2">
            <div class="col-7">
              <h4 class="text-primary fw-bolder mb-0">INVOICE UANG MUKA</h4>
              <p class="text-muted small mb-0">No: <strong>{{ $um->nomor_bukti ?? '-' }}</strong></p>
            </div>
            <div class="col-5 text-end">
              <div class="mb-2">
                @if($um->proyek && $um->proyek->perusahaan)
                  <img src="{{ company_logo_url($um->proyek->perusahaan) }}" alt="Logo" style="max-height:80px; max-width:220px; object-fit:contain;">
                @endif
              </div>
              <h6 class="fw-bold mb-0 text-dark">{{ $um->proyek->perusahaan->nama_perusahaan ?? 'NAMA PERUSAHAAN' }}</h6>
              <p style="font-size: 10px; line-height: 1.2;" class="text-muted mb-0">
                {{ $um->proyek->perusahaan->alamat ?? 'Alamat lengkap perusahaan belum diatur.' }}
              </p>
            </div>
          </div>

          <div style="border-top: 2px solid #6571ff; margin-bottom: 12px;"></div>

          <div class="row mb-3" style="font-size: 11px;">
            <div class="col-6">
              <p class="text-muted mb-1 small"><strong>KEPADA:</strong></p>
              <div class="ps-2">
                <p class="mb-0 fw-bold text-dark">{{ optional($um->proyek->pemberiKerja)->nama_pemberi_kerja ?? '-' }}</p>
                <p class="mb-0 text-muted" style="font-size: 10px; line-height: 1.3;">
                  {{ optional($um->proyek->pemberiKerja)->alamat ?? '-' }}
                </p>
                @if(optional($um->proyek->pemberiKerja)->pic)
                <p class="mb-0 text-muted" style="font-size: 10px;">Attn: {{ $um->proyek->pemberiKerja->pic }}</p>
                @endif
              </div>
            </div>
            <div class="col-6">
              <table class="table table-sm table-borderless mb-0">
                <tr>
                  <td class="p-0 text-muted text-end" width="50%">Tanggal</td>
                  <td class="p-0 text-end text-dark">: {{ optional($um->tanggal)->format('d/m/Y') }}</td>
                </tr>
                <tr>
                  <td class="p-0 text-muted text-end">Proyek</td>
                  <td class="p-0 text-end text-dark">: {{ $um->proyek->nama_proyek ?? '-' }}</td>
                </tr>
                @if($um->salesOrder)
                <tr>
                  <td class="p-0 text-muted text-end">Penawaran</td>
                  <td class="p-0 text-end text-dark">: {{ $um->salesOrder->penawaran->nomor_penawaran ?? $um->salesOrder->nomor ?? '-' }}</td>
                </tr>
                @endif
                <tr>
                  <td class="p-0 text-muted text-end">No. SPK</td>
                  <td class="p-0 text-end text-dark">: {{ $um->proyek->no_spk ?? '-' }}</td>
                </tr>
              </table>
            </div>
          </div>

          <div class="table-responsive mb-3">
            <table class="table table-bordered custom-table-print">
              <thead>
                <tr>
                  <th class="text-center" width="5%">No</th>
                  <th width="60%">Deskripsi / Uraian</th>
                  <th class="text-end" width="35%">Jumlah (Rp)</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td class="text-center">1</td>
                  <td>
                    <div class="fw-bold">Uang Muka Pekerjaan</div>
                    <div class="text-muted small">Proyek: {{ $um->proyek->nama_proyek ?? '-' }}</div>
                    @if($um->salesOrder && $um->salesOrder->penawaran)
                    <div class="text-muted small">Penawaran: {{ $um->salesOrder->penawaran->nomor_penawaran }} - {{ $um->salesOrder->penawaran->nama_penawaran }}</div>
                    @elseif($um->salesOrder)
                    <div class="text-muted small">SO: {{ $um->salesOrder->nomor }}</div>
                    @endif
                    @if($um->proyek->no_spk)
                    <div class="text-muted small">No. SPK: {{ $um->proyek->no_spk }}</div>
                    @endif
                    @if($um->keterangan)
                    <div class="text-muted small mt-1">Ket: {{ $um->keterangan }}</div>
                    @endif
                  </td>
                  <td class="text-end fw-bold">{{ number_format($um->nominal, 0, ',', '.') }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="row mt-3">
            <div class="col-7">
              <div class="mb-3">
                <p class="small mb-1"><strong>Metode Pembayaran:</strong> {{ $um->metode_pembayaran ?? '-' }}</p>
                @if($um->payment_status == 'dibayar')
                <p class="small mb-1"><strong>Tanggal Pembayaran:</strong> {{ optional($um->tanggal_bayar)->format('d/m/Y') }}</p>
                @endif
              </div>
              
              <div class="row text-center mt-5 d-none d-print-flex">
                <div class="col-6">
                  <p class="mb-4 small">Hormat Kami,</p>
                  <div class="mx-auto border-bottom border-dark" style="width: 80%; height: 50px;"></div>
                  <p class="small mt-1 fw-bold">{{ $um->proyek->perusahaan->nama_perusahaan ?? 'Perusahaan' }}</p>
                </div>
                <div class="col-6">
                  <p class="mb-4 small">Diterima Oleh,</p>
                  <div class="mx-auto border-bottom border-dark" style="width: 80%; height: 50px;"></div>
                  <p class="small mt-1 fw-bold">{{ optional($um->proyek->pemberiKerja)->nama_pemberi_kerja ?? 'Pemberi Kerja' }}</p>
                </div>
              </div>
            </div>
            <div class="col-5">
              <table class="table table-sm table-borderless text-end fw-bold" style="font-size: 11px;">
                <tr style="font-size: 14px; border-top: 2px solid #6571ff;">
                  <td class="text-primary pt-1">TOTAL UANG MUKA:</td>
                  <td class="text-primary pt-1">Rp {{ number_format($um->nominal, 0, ',', '.') }}</td>
                </tr>
              </table>
              
              <div class="alert alert-light border mt-3" style="font-size: 10px;">
                <p class="mb-1"><strong>Informasi Pembayaran:</strong></p>
                <p class="mb-0 text-muted">Mohon transfer ke rekening perusahaan yang tertera pada SPK/Kontrak.</p>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</div>

<!-- DETAIL LENGKAP (TIDAK DI-PRINT) -->
<div class="d-print-none mt-4">
  <div class="row justify-content-center">
    <div class="col-md-10">
      <div class="card">
        <div class="card-header bg-light">
          <h5 class="mb-0">
            <i class="fas fa-list-alt me-2"></i>
            Detail Lengkap Uang Muka
          </h5>
          <small class="text-muted">Informasi detail untuk keperluan internal (tidak tercetak di invoice)</small>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-6">
              <table class="table table-sm table-borderless">
                <tr>
                  <th style="width:150px">Nomor Bukti</th>
                  <td>: {{ $um->nomor_bukti ?? '-' }}</td>
                </tr>
                <tr>
                  <th>Tanggal</th>
                  <td>: {{ optional($um->tanggal)->format('d/m/Y') }}</td>
                </tr>
                <tr>
                  <th>Proyek</th>
                  <td>: {{ $um->proyek->nama_proyek ?? '-' }}</td>
                </tr>
                <tr>
                  <th>No. SPK</th>
                  <td>: {{ $um->proyek->no_spk ?? '-' }}</td>
                </tr>
                @if($um->salesOrder)
                <tr>
                  <th>Penawaran</th>
                  <td>: 
                    @if($um->salesOrder->penawaran)
                      {{ $um->salesOrder->penawaran->nomor_penawaran }} - {{ $um->salesOrder->penawaran->nama_penawaran }}
                    @else
                      {{ $um->salesOrder->nomor ?? '-' }}
                    @endif
                  </td>
                </tr>
                @endif
                <tr>
                  <th>Metode Pembayaran</th>
                  <td>: {{ $um->metode_pembayaran ?? '-' }}</td>
                </tr>
                @if($um->payment_status == 'dibayar')
                <tr>
                  <th>Tanggal Pembayaran</th>
                  <td>: {{ optional($um->tanggal_bayar)->format('d/m/Y') }}</td>
                </tr>
                @endif
              </table>
            </div>
            <div class="col-md-6">
              <table class="table table-sm table-borderless">
                <tr>
                  <th style="width:150px">Nominal Awal</th>
                  <td>: Rp {{ number_format($um->nominal, 2, ',', '.') }}</td>
                </tr>
                <tr>
                  <th>Nominal Digunakan</th>
                  <td>: Rp {{ number_format($um->nominal_digunakan, 2, ',', '.') }}</td>
                </tr>
                <tr>
                  <th>Sisa Uang Muka</th>
                  <td>: <strong class="text-success">Rp {{ number_format($um->getSisaUangMuka(), 2, ',', '.') }}</strong></td>
                </tr>
                <tr>
                  <th>Status</th>
                  <td>: 
                    @if($um->status == 'diterima')
                      <span class="badge bg-success">Diterima</span>
                    @elseif($um->status == 'sebagian')
                      <span class="badge bg-warning">Sebagian</span>
                    @else
                      <span class="badge bg-info">Lunas</span>
                    @endif
                  </td>
                </tr>
                <tr>
                  <th>Status Pembayaran</th>
                  <td>: 
                    @if($um->payment_status == 'dibayar')
                      <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i> Sudah Dibayar</span>
                    @else
                      <span class="badge bg-warning"><i class="fas fa-clock me-1"></i> Belum Dibayar</span>
                    @endif
                  </td>
                </tr>
              </table>
            </div>
          </div>

          @if($um->keterangan)
          <div class="mt-3">
            <h6 class="fw-bold">Keterangan:</h6>
            <p class="text-muted">{{ $um->keterangan }}</p>
          </div>
          @endif

          <hr class="my-4">

          <div class="row">
            <div class="col-md-6">
              <h6 class="fw-bold mb-3">
                <i class="fas fa-history me-2"></i>
                Riwayat Penggunaan
              </h6>
              <div class="alert alert-info">
                <div class="row">
                  <div class="col-6">
                    <small class="text-muted">Nominal Awal:</small>
                    <p class="mb-2 fw-bold">Rp {{ number_format($um->nominal, 0, ',', '.') }}</p>
                  </div>
                  <div class="col-6">
                    <small class="text-muted">Telah Digunakan:</small>
                    <p class="mb-2 fw-bold text-warning">Rp {{ number_format($um->nominal_digunakan, 0, ',', '.') }}</p>
                  </div>
                  <div class="col-12">
                    <hr class="my-2">
                    <small class="text-muted">Sisa Tersedia:</small>
                    <p class="mb-0 fw-bold text-success fs-5">Rp {{ number_format($um->getSisaUangMuka(), 0, ',', '.') }}</p>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <h6 class="fw-bold mb-3">
                <i class="fas fa-info-circle me-2"></i>
                Informasi Sistem
              </h6>
              <table class="table table-sm table-borderless">
                <tr>
                  <th style="width:120px">Dibuat Oleh</th>
                  <td>: {{ optional($um->creator)->name ?? '-' }}</td>
                </tr>
                <tr>
                  <th>Dibuat Pada</th>
                  <td>: {{ optional($um->created_at)->format('d/m/Y H:i') }}</td>
                </tr>
                <tr>
                  <th>Terakhir Diubah</th>
                  <td>: {{ optional($um->updated_at)->format('d/m/Y H:i') }}</td>
                </tr>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
/* --- Tampilan Web --- */
.print-container {
    display: block;
    visibility: visible;
}

.um-print-box {
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
    font-size: 11px;
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
        height: 50%;
        padding: 1.5cm;
        box-sizing: border-box;
    }

    .um-print-box {
        border: none !important;
        padding: 0 !important;
        box-shadow: none !important;
        width: 100% !important;
    }

    body, table, td, th {
        color: #000 !important;
        font-family: 'Arial', sans-serif !important;
    }

    .text-primary {
        color: #6571ff !important;
    }

    * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
}
</style>
@endsection

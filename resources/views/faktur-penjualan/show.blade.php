@extends('layout.master')

@section('content')
<nav class="page-breadcrumb d-print-none">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('faktur-penjualan.index') }}">Faktur Penjualan</a></li>
        <li class="breadcrumb-item active" aria-current="page">Detail & Cetak</li>
    </ol>
</nav>

<div class="row justify-content-center">
  <div class="col-md-10">
    <div class="card shadow-none border-0">
      <div class="card-header d-print-none bg-transparent border-bottom d-flex justify-content-between align-items-center p-3">
        <a href="{{ route('faktur-penjualan.index') }}" class="btn btn-outline-secondary">
          <i data-feather="arrow-left" class="icon-sm"></i> Kembali
        </a>
        <div class="d-flex align-items-center gap-2">
          <button onclick="window.print()" class="btn btn-info text-white">
            <i data-feather="printer" class="icon-sm"></i> Cetak
          </button>
          
          @if($faktur->status_pembayaran !== 'lunas')
            <a href="{{ route('penerimaan-penjualan.create') }}?faktur_penjualan_id={{ $faktur->id }}" class="btn btn-success btn-sm">
              <i class="fas fa-coins"></i> Terima Pembayaran
            </a>
          @endif
          @if($faktur->status === 'draft')
            <a href="{{ route('faktur-penjualan.edit', $faktur->id) }}" class="btn btn-warning btn-sm">
              <i class="fas fa-edit"></i> Edit
            </a>
          @endif
          @if($faktur->status === 'approved')
            <form action="{{ route('faktur-penjualan.revisi', $faktur->id) }}" method="POST" style="display:inline;">
              @csrf
              <button type="submit" class="btn btn-info btn-sm" onclick="return confirm('Revisi faktur ke draft?')">
                <i class="fas fa-redo"></i> Revisi
              </button>
            </form>
          @endif
          @if($faktur->status === 'draft')
            <form action="{{ route('faktur-penjualan.destroy', $faktur->id) }}" method="POST" style="display:inline;">
              @csrf
              @method('DELETE')
              <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Hapus faktur ini?')">
                <i class="fas fa-trash"></i> Hapus
              </button>
            </form>
          @endif
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
        <div class="faktur-print-box">
          @php
            $perusahaan = $faktur->perusahaan ?? \App\Models\Perusahaan::first();
          @endphp
          
          <div class="row align-items-center mb-2">
            <div class="col-7">
              <h4 class="text-primary fw-bolder mb-0">INVOICE TAGIHAN</h4>
              <p class="text-muted small mb-0">No: <strong>{{ $faktur->no_faktur }}</strong></p>
            </div>
            <div class="col-5 text-end">
              @if($perusahaan && $perusahaan->logo_path)
              <div class="mb-2">
                <img src="{{ company_logo_url($perusahaan) }}" alt="Logo" style="max-height:80px; max-width:220px; object-fit:contain;">
              </div>
              @endif
              <h6 class="fw-bold mb-0 text-dark">{{ $perusahaan->nama_perusahaan ?? 'NAMA PERUSAHAAN' }}</h6>
              <p style="font-size: 10px; line-height: 1.2;" class="text-muted mb-0">
                {{ $perusahaan->alamat ?? 'Alamat lengkap perusahaan belum diatur.' }}
              </p>
            </div>
          </div>

          <div style="border-top: 2px solid #6571ff; margin-bottom: 12px;"></div>

          <div class="row mb-3" style="font-size: 11px;">
            <div class="col-6">
              <p class="text-muted mb-1 small"><strong>KEPADA:</strong></p>
              <div class="ps-2">
                <p class="mb-0 fw-bold text-dark">{{ optional($faktur->proyek->pemberiKerja)->nama_pemberi_kerja ?? '-' }}</p>
                <p class="mb-0 text-muted" style="font-size: 10px; line-height: 1.3;">
                  {{ optional($faktur->proyek->pemberiKerja)->alamat ?? '-' }}
                </p>
                @if(optional($faktur->proyek->pemberiKerja)->pic)
                <p class="mb-0 text-muted" style="font-size: 10px;">Attn: {{ $faktur->proyek->pemberiKerja->pic }}</p>
                @endif
              </div>
            </div>
            <div class="col-6">
              <table class="table table-sm table-borderless mb-0">
                <tr>
                  <td class="p-0 text-muted text-end" width="50%">Tanggal</td>
                  <td class="p-0 text-end text-dark">: {{ optional($faktur->tanggal)->format('d/m/Y') }}</td>
                </tr>
                <tr>
                  <td class="p-0 text-muted text-end">Proyek</td>
                  <td class="p-0 text-end text-dark">: {{ optional($faktur->proyek)->nama_proyek ?? '-' }}</td>
                </tr>
                <tr>
                  <td class="p-0 text-muted text-end">Ref. Sertifikat</td>
                  <td class="p-0 text-end text-dark">: 
                    @if($faktur->sertifikat_pembayaran_id)
                      SP-{{ str_pad($faktur->sertifikat_pembayaran_id, 4, '0', STR_PAD_LEFT) }}
                    @else
                      -
                    @endif
                  </td>
                </tr>
              </table>
            </div>
          </div>

          <div class="table-responsive mb-3">
            <table class="table table-bordered custom-table-print">
              <thead>
                <tr>
                  <th class="text-center" width="5%">No</th>
                  <th width="55%">Deskripsi / Uraian Pekerjaan</th>
                  <th class="text-end" width="40%">Jumlah (Rp)</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td class="text-center">1</td>
                  <td>
                    <div class="fw-bold">Tagihan Pekerjaan - {{ optional($faktur->proyek)->nama_proyek ?? '-' }}</div>
                    @if($faktur->sertifikatPembayaran)
                    <div class="text-muted small">Berdasarkan Sertifikat Pembayaran</div>
                    <div class="text-muted small">Periode: {{ optional($faktur->sertifikatPembayaran->bapp)->tanggal_opname ? optional($faktur->sertifikatPembayaran->bapp->tanggal_opname)->format('M Y') : '-' }}</div>
                    @else
                    <div class="text-muted small">Tagihan Pekerjaan Proyek</div>
                    @endif
                  </td>
                  <td class="text-end fw-bold">{{ number_format($faktur->subtotal, 0, ',', '.') }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="row mt-3">
            <div class="col-7">
              <div class="mb-3" style="font-size: 10px;">
                <p class="mb-1"><strong>Keterangan:</strong></p>
                <p class="mb-0 text-muted">Mohon transfer pembayaran ke rekening perusahaan yang tertera pada SPK/Kontrak.</p>
                <p class="mb-0 text-muted">Harap konfirmasi setelah melakukan pembayaran.</p>
              </div>
              
              <div class="row text-center mt-5 d-none d-print-flex">
                <div class="col-6">
                  <p class="mb-4 small">Hormat Kami,</p>
                  <div class="mx-auto border-bottom border-dark" style="width: 80%; height: 50px;"></div>
                  <p class="small mt-1 fw-bold">{{ $perusahaan->nama_perusahaan ?? 'Perusahaan' }}</p>
                </div>
                <div class="col-6">
                  <p class="mb-4 small">Diterima Oleh,</p>
                  <div class="mx-auto border-bottom border-dark" style="width: 80%; height: 50px;"></div>
                  <p class="small mt-1 fw-bold">{{ optional($faktur->proyek->pemberiKerja)->nama_pemberi_kerja ?? 'Pemberi Kerja' }}</p>
                </div>
              </div>
            </div>
            <div class="col-5">
              <table class="table table-sm table-borderless text-end fw-bold" style="font-size: 11px;">
                <tr>
                  <td class="text-muted fw-normal">Subtotal (DPP):</td>
                  <td class="text-dark">Rp {{ number_format($faktur->subtotal, 0, ',', '.') }}</td>
                </tr>
                <tr>
                  <td class="text-muted fw-normal">PPN 
                    @if($faktur->ppn_persen > 0)
                      ({{ number_format($faktur->ppn_persen, 0) }}%)
                    @endif
                    :
                  </td>
                  <td class="text-dark">{{ number_format($faktur->total_ppn, 0, ',', '.') }}</td>
                </tr>
                @if($faktur->pph_nilai > 0)
                <tr>
                  <td class="text-muted fw-normal">PPh 
                    @if($faktur->pph_persen > 0)
                      ({{ number_format($faktur->pph_persen, 2) }}%)
                    @endif
                    :
                  </td>
                  <td class="text-danger">- {{ number_format($faktur->pph_nilai, 0, ',', '.') }}</td>
                </tr>
                @endif
                <tr style="font-size: 14px; border-top: 2px solid #6571ff;">
                  <td class="text-primary pt-1">TOTAL TAGIHAN:</td>
                  <td class="text-primary pt-1">Rp {{ number_format($faktur->total, 0, ',', '.') }}</td>
                </tr>
              </table>
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
            Detail Lengkap Faktur Penjualan
          </h5>
          <small class="text-muted">Informasi detail untuk keperluan internal (tidak tercetak di invoice)</small>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-6">
              <table class="table table-sm table-borderless">
                <tr><th style="width:150px">Nomor</th><td>: {{ $faktur->no_faktur }}</td></tr>
                <tr><th>Tanggal</th><td>: {{ optional($faktur->tanggal)->format('d/m/Y') }}</td></tr>
                <tr><th>Proyek</th><td>: {{ optional($faktur->proyek)->nama_proyek ?? '-' }}</td></tr>
                <tr><th>Sertifikat</th><td>: 
                  @if($faktur->sertifikat_pembayaran_id)
                    <a class="text-decoration-underline" href="{{ route('sertifikat.show', $faktur->sertifikat_pembayaran_id) }}">Sertifikat #{{ $faktur->sertifikat_pembayaran_id }}</a>
                  @else
                    <span class="text-muted">-</span>
                  @endif
                </td></tr>
              </table>
            </div>
            <div class="col-md-6">
              <table class="table table-sm table-borderless">
                <tr><th style="width:150px">Subtotal (DPP)</th><td>: Rp {{ number_format($faktur->subtotal, 2, ',', '.') }}</td></tr>
                <tr><th>PPN</th><td>: Rp {{ number_format($faktur->total_ppn, 2, ',', '.') }}
                  @if($faktur->ppn_persen > 0)
                    <small class="text-muted">({{ number_format($faktur->ppn_persen, 2, ',', '.') }}%)</small>
                  @endif
                </td></tr>
                @if($faktur->retensi_nilai > 0 || $faktur->retensi_persen > 0)
                  <tr><th>Retensi</th><td>: Rp {{ number_format($faktur->retensi_nilai, 2, ',', '.') }}
                    @if($faktur->retensi_persen > 0)
                      <small class="text-muted">({{ number_format($faktur->retensi_persen, 2, ',', '.') }}%)</small>
                    @endif
                  </td></tr>
                @endif
                @if($faktur->pph_nilai > 0 || $faktur->pph_persen > 0)
                  <tr><th>PPh</th><td>: Rp {{ number_format($faktur->pph_nilai, 2, ',', '.') }}
                    @if($faktur->pph_persen > 0)
                      <small class="text-muted">({{ number_format($faktur->pph_persen, 2, ',', '.') }}%)</small>
                    @endif
                  </td></tr>
                @endif
                <tr><th>Uang Muka Dipakai</th><td>: Rp {{ number_format($faktur->uang_muka_dipakai ?? 0, 2, ',', '.') }}</td></tr>
                <tr><th>Total Tagihan</th><td>: <strong class="text-success">Rp {{ number_format($faktur->total, 2, ',', '.') }}</strong></td></tr>
              </table>
            </div>
          </div>
          <hr class="my-3">
          <div class="row">
            <div class="col-md-6">
              <h6 class="fw-bold mb-3">
                <i class="fas fa-info-circle me-2"></i>
                Status
              </h6>
              <table class="table table-sm table-borderless">
                <tr><th style="width:150px">Status Pembayaran</th><td>: <span class="badge bg-secondary text-uppercase">{{ $faktur->status_pembayaran ?? 'belum' }}</span></td></tr>
                <tr><th>Status</th><td>: <span class="badge bg-info text-uppercase">{{ $faktur->status ?? 'draft' }}</span></td></tr>
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
    display: none;
}

.faktur-print-box {
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
        display: block !important;
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 50%;
        padding: 1.5cm;
        box-sizing: border-box;
    }

    .faktur-print-box {
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

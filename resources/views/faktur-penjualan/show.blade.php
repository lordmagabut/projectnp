@extends('layout.master')

@section('content')
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <div>
      <h5 class="mb-0">Faktur Penjualan</h5>
      <small class="text-muted">Auto-dibuat dari Sertifikat Pembayaran</small>
    </div>
    <div class="gap-2 d-flex">
      <button type="button" class="btn btn-info btn-sm text-white" onclick="window.print()">
        <i class="fas fa-print"></i> Cetak
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
      <a href="{{ route('faktur-penjualan.index') }}" class="btn btn-secondary btn-sm">Kembali</a>
    </div>
  </div>
  <div class="card-body">
    <table class="table table-borderless">
      <tr><th style="width:200px">Nomor</th><td>{{ $faktur->no_faktur }}</td></tr>
      <tr><th>Tanggal</th><td>{{ optional($faktur->tanggal)->format('d/m/Y') }}</td></tr>
      <tr><th>Proyek</th><td>{{ optional($faktur->proyek)->nama_proyek ?? '-' }}</td></tr>
      <tr><th>Sertifikat</th><td>
        @if($faktur->sertifikat_pembayaran_id)
          <a class="text-decoration-underline" href="{{ route('sertifikat.show', $faktur->sertifikat_pembayaran_id) }}">Sertifikat #{{ $faktur->sertifikat_pembayaran_id }}</a>
        @else
          <span class="text-muted">-</span>
        @endif
      </td></tr>
      <tr><th>Subtotal (DPP)</th><td>Rp {{ number_format($faktur->subtotal, 2, ',', '.') }}</td></tr>
      <tr><th>PPN</th><td>
        <div>Rp {{ number_format($faktur->total_ppn, 2, ',', '.') }}</div>
        @if($faktur->ppn_persen > 0)
          <small class="text-muted">({{ number_format($faktur->ppn_persen, 2, ',', '.') }}%)</small>
        @endif
      </td></tr>
      @if($faktur->retensi_nilai > 0 || $faktur->retensi_persen > 0)
        <tr><th>Retensi</th><td>
          <div>Rp {{ number_format($faktur->retensi_nilai, 2, ',', '.') }}</div>
          @if($faktur->retensi_persen > 0)
            <small class="text-muted">({{ number_format($faktur->retensi_persen, 2, ',', '.') }}%)</small>
          @endif
        </td></tr>
      @endif
      @if($faktur->pph_nilai > 0 || $faktur->pph_persen > 0)
        <tr><th>PPh</th><td>
          <div>Rp {{ number_format($faktur->pph_nilai, 2, ',', '.') }}</div>
          @if($faktur->pph_persen > 0)
            <small class="text-muted">({{ number_format($faktur->pph_persen, 2, ',', '.') }}%)</small>
          @endif
        </td></tr>
      @endif
      <tr><th>Total</th><td><strong>Rp {{ number_format($faktur->total, 2, ',', '.') }}</strong></td></tr>
      <tr><th>Uang Muka Dipakai</th><td>Rp {{ number_format($faktur->uang_muka_dipakai ?? 0, 2, ',', '.') }}</td></tr>
      <tr><th>Status Pembayaran</th><td><span class="badge bg-secondary text-uppercase">{{ $faktur->status_pembayaran ?? 'belum' }}</span></td></tr>
      <tr><th>Status</th><td><span class="badge bg-info text-uppercase">{{ $faktur->status ?? 'draft' }}</span></td></tr>
    </table>
  </div>
</div>

<div class="print-container mt-4 d-print-block">
  <div class="faktur-print-box p-4 border rounded">
    <div class="d-flex justify-content-between align-items-start mb-3">
      <div>
        <h4 class="text-primary fw-bolder mb-1">FAKTUR PENJUALAN</h4>
        <div class="text-muted" style="font-size:12px;">No: {{ $faktur->no_faktur }}</div>
        <div class="text-muted" style="font-size:12px;">Tanggal: {{ optional($faktur->tanggal)->format('d/m/Y') }}</div>
        <div class="text-muted" style="font-size:12px;">Proyek: {{ optional($faktur->proyek)->nama_proyek ?? '-' }}</div>
      </div>
      <div class="text-end">
        <div class="mb-2">
          <img src="{{ company_logo_url($faktur->perusahaan) }}" alt="Logo" style="max-height:70px; max-width:200px; object-fit:contain;">
        </div>
        <div class="fw-bold">{{ $faktur->perusahaan->nama_perusahaan ?? 'Perusahaan' }}</div>
        <div class="text-muted" style="font-size:11px; line-height:1.2; max-width:220px;">{{ $faktur->perusahaan->alamat ?? '' }}</div>
      </div>
    </div>

    <table class="table table-sm table-borderless" style="font-size:12px;">
      <tr><td width="45%" class="text-muted">Subtotal (DPP)</td><td class="fw-bold">Rp {{ number_format($faktur->subtotal, 2, ',', '.') }}</td></tr>
      <tr><td class="text-muted">PPN</td><td class="fw-bold">Rp {{ number_format($faktur->total_ppn, 2, ',', '.') }}</td></tr>
      @if($faktur->retensi_nilai > 0)
      <tr><td class="text-muted">Retensi</td><td class="fw-bold">Rp {{ number_format($faktur->retensi_nilai, 2, ',', '.') }}</td></tr>
      @endif
      @if($faktur->pph_nilai > 0)
      <tr><td class="text-muted">PPh</td><td class="fw-bold">Rp {{ number_format($faktur->pph_nilai, 2, ',', '.') }}</td></tr>
      @endif
      <tr><td class="text-muted">Total Tagihan</td><td class="fw-bold fs-5">Rp {{ number_format($faktur->total, 2, ',', '.') }}</td></tr>
      @if($faktur->uang_muka_dipakai > 0)
      <tr><td class="text-muted">Uang Muka Dipakai</td><td class="fw-bold">(Rp {{ number_format($faktur->uang_muka_dipakai, 2, ',', '.') }})</td></tr>
      @endif
    </table>

    <div class="row text-center mt-5" style="font-size:12px;">
      <div class="col-6">
        <p class="mb-4">Diterima Oleh,</p>
        <div class="mx-auto border-bottom border-dark" style="width:80%; height:24px;"></div>
      </div>
      <div class="col-6">
        <p class="mb-4">Disetujui Oleh,</p>
        <div class="mx-auto border-bottom border-dark" style="width:80%; height:24px;"></div>
      </div>
    </div>
  </div>
</div>

<style>
@media print {
  body { background: #fff; }
  .card, .page-breadcrumb, .navbar, .sidebar { display: none !important; }
  .print-container { display: block !important; }
}
</style>
@endsection

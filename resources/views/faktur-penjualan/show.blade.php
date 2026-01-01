@extends('layout.master')

@section('content')
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <div>
      <h5 class="mb-0">Faktur Penjualan</h5>
      <small class="text-muted">Auto-dibuat dari Sertifikat Pembayaran</small>
    </div>
    <div class="gap-2 d-flex">
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
@endsection

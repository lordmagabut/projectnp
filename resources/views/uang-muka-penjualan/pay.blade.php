@extends('layout.master')

@section('content')
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0"><i class="fas fa-money-bill-wave me-2"></i>Catat Pembayaran Uang Muka Penjualan</h5>
  </div>
  <div class="card-body">
    <div class="alert alert-info">
      <strong>Informasi Uang Muka:</strong><br>
      Nomor Bukti: <strong>{{ $um->nomor_bukti }}</strong><br>
      Sales Order: <strong>{{ $um->salesOrder->nomor ?? 'SO #' . $um->sales_order_id }}</strong><br>
      Proyek: <strong>{{ $um->proyek->nama_proyek ?? '-' }}</strong><br>
      Nominal: <strong>Rp {{ number_format($um->nominal, 0, ',', '.') }}</strong>
    </div>

    <form method="POST" action="{{ route('uang-muka-penjualan.processPay', $um->id) }}">
      @csrf
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Tanggal Pembayaran <span class="text-danger">*</span></label>
          <input type="date" name="tanggal_bayar" class="form-control @error('tanggal_bayar') is-invalid @enderror" 
                 value="{{ old('tanggal_bayar', now()->toDateString()) }}" required>
          @error('tanggal_bayar')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>

        <div class="col-md-6">
          <label class="form-label">Metode Pembayaran <span class="text-danger">*</span></label>
          <input type="text" name="metode_pembayaran" class="form-control @error('metode_pembayaran') is-invalid @enderror" 
                 value="{{ old('metode_pembayaran', $um->metode_pembayaran) }}" 
                 placeholder="Transfer, Tunai, Cek, dll" required>
          @error('metode_pembayaran')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>

        <div class="col-md-12">
          <label class="form-label">Keterangan Pembayaran</label>
          <textarea name="keterangan_bayar" class="form-control @error('keterangan_bayar') is-invalid @enderror" rows="3" 
                    placeholder="Catatan tambahan pembayaran">{{ old('keterangan_bayar') }}</textarea>
          @error('keterangan_bayar')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>
      </div>

      <div class="mt-4 d-flex gap-2">
        <a href="{{ route('uang-muka-penjualan.show', $um->id) }}" class="btn btn-secondary">Batal</a>
        <button type="submit" class="btn btn-success">
          <i class="fas fa-check me-1"></i> Catat Pembayaran
        </button>
      </div>
    </form>
  </div>
</div>
@endsection

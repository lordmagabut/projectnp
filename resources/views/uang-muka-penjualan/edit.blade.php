@extends('layout.master')

@section('content')
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Edit Uang Muka Penjualan</h5>
  </div>
  <div class="card-body">
    <form method="POST" action="{{ route('uang-muka-penjualan.update', $um->id) }}" id="umForm">
      @csrf
      @method('PUT')
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Sales Order</label>
          <div class="form-control-plaintext">
            <strong>{{ optional($um->salesOrder)->nomor ?? '-' }}</strong>
            <small class="text-muted d-block">{{ optional($um->salesOrder->penawaran)->proyek->nama_proyek ?? '-' }}</small>
          </div>
          <input type="hidden" name="sales_order_id" value="{{ $um->sales_order_id }}">
        </div>

        <div class="col-md-6">
          <label class="form-label">Proyek</label>
          <div class="form-control-plaintext">
            <strong>{{ $um->proyek->nama_proyek ?? '-' }}</strong>
          </div>
          <input type="hidden" name="proyek_id" value="{{ $um->proyek_id }}">
        </div>

        <div class="col-md-6">
          <label class="form-label">Nomor Bukti Uang Muka</label>
          <input type="text" class="form-control @error('nomor_bukti') is-invalid @enderror" name="nomor_bukti" value="{{ old('nomor_bukti', $um->nomor_bukti) }}" required>
          @error('nomor_bukti')
            <span class="invalid-feedback">{{ $message }}</span>
          @enderror
        </div>

        <div class="col-md-6">
          <label class="form-label">Tanggal</label>
          <input type="date" class="form-control @error('tanggal') is-invalid @enderror" name="tanggal" value="{{ old('tanggal', optional($um->tanggal)->format('Y-m-d')) }}" required>
          @error('tanggal')
            <span class="invalid-feedback">{{ $message }}</span>
          @enderror
        </div>

        <div class="col-md-6">
          <label class="form-label">Nominal Uang Muka</label>
          <div class="input-group">
            <input type="number" step="0.01" class="form-control @error('nominal') is-invalid @enderror" name="nominal" value="{{ old('nominal', $um->nominal) }}" required>
            <span class="input-group-text text-muted">
              @if($um->nominal_digunakan > 0)
                <small>Min: {{ number_format($um->nominal_digunakan, 2, ',', '.') }}</small>
              @endif
            </span>
          </div>
          @error('nominal')
            <span class="invalid-feedback d-block">{{ $message }}</span>
          @enderror
        </div>

        <div class="col-md-6">
          <label class="form-label">Metode Pembayaran</label>
          <input type="text" class="form-control @error('metode_pembayaran') is-invalid @enderror" name="metode_pembayaran" value="{{ old('metode_pembayaran', $um->metode_pembayaran) }}" placeholder="Transfer, Tunai, Cek, dll">
          @error('metode_pembayaran')
            <span class="invalid-feedback">{{ $message }}</span>
          @enderror
        </div>

        <div class="col-md-12">
          <label class="form-label">Keterangan</label>
          <textarea class="form-control @error('keterangan') is-invalid @enderror" name="keterangan" rows="3">{{ old('keterangan', $um->keterangan) }}</textarea>
          @error('keterangan')
            <span class="invalid-feedback">{{ $message }}</span>
          @enderror
        </div>

        <div class="col-md-12">
          <div class="alert alert-info">
            <strong>Status Penggunaan:</strong><br>
            Nominal: Rp {{ number_format($um->nominal, 2, ',', '.') }}<br>
            Digunakan: Rp {{ number_format($um->nominal_digunakan, 2, ',', '.') }}<br>
            Sisa: Rp {{ number_format($um->getSisaUangMuka(), 2, ',', '.') }}
          </div>
        </div>
      </div>

      <div class="mt-4 d-flex gap-2">
        <a href="{{ route('uang-muka-penjualan.show', $um->id) }}" class="btn btn-secondary">Batal</a>
        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
      </div>
    </form>
  </div>
</div>
@endsection

@extends('layout.master')

@section('content')
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Buat Uang Muka Penjualan</h5>
  </div>
  <div class="card-body">
    <form method="POST" action="{{ route('uang-muka-penjualan.store') }}" id="umForm">
      @csrf
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Sales Order</label>
          <select class="form-select @error('sales_order_id') is-invalid @enderror" name="sales_order_id" id="sales_order_id" required onchange="updateProyekFromSO()">
            <option value="">-- Pilih Sales Order --</option>
            @foreach($salesOrders as $so)
              <option value="{{ $so->id }}" data-proyek-id="{{ optional($so->penawaran)->proyek_id }}" @if($prefillSoId == $so->id) selected @endif>
                {{ $so->nomor ?? 'SO #' . $so->id }} - {{ optional($so->penawaran)->proyek->nama_proyek ?? 'Proyek' }}
              </option>
            @endforeach
          </select>
          @error('sales_order_id')
            <span class="invalid-feedback">{{ $message }}</span>
          @enderror
        </div>

        <div class="col-md-6">
          <label class="form-label">Proyek</label>
          <select class="form-select @error('proyek_id') is-invalid @enderror" name="proyek_id" id="proyek_id" required>
            <option value="">-- Pilih Proyek --</option>
            @foreach($proyeks as $p)
              <option value="{{ $p->id }}">{{ $p->nama_proyek }}</option>
            @endforeach
          </select>
          @error('proyek_id')
            <span class="invalid-feedback">{{ $message }}</span>
          @enderror
        </div>

        <div class="col-md-6">
          <label class="form-label">Nomor Bukti Uang Muka</label>
          <input type="text" class="form-control @error('nomor_bukti') is-invalid @enderror" name="nomor_bukti" value="{{ old('nomor_bukti') }}" required>
          @error('nomor_bukti')
            <span class="invalid-feedback">{{ $message }}</span>
          @enderror
        </div>

        <div class="col-md-6">
          <label class="form-label">Tanggal</label>
          <input type="date" class="form-control @error('tanggal') is-invalid @enderror" name="tanggal" value="{{ old('tanggal', now()->toDateString()) }}" required>
          @error('tanggal')
            <span class="invalid-feedback">{{ $message }}</span>
          @enderror
        </div>

        <div class="col-md-6">
          <label class="form-label">Nominal Uang Muka</label>
          <input type="number" step="0.01" class="form-control @error('nominal') is-invalid @enderror" name="nominal" value="{{ old('nominal') }}" required>
          @error('nominal')
            <span class="invalid-feedback">{{ $message }}</span>
          @enderror
        </div>

        <div class="col-md-6">
          <label class="form-label">Metode Pembayaran</label>
          <input type="text" class="form-control @error('metode_pembayaran') is-invalid @enderror" name="metode_pembayaran" value="{{ old('metode_pembayaran') }}" placeholder="Transfer, Tunai, Cek, dll">
          @error('metode_pembayaran')
            <span class="invalid-feedback">{{ $message }}</span>
          @enderror
        </div>

        <div class="col-md-12">
          <label class="form-label">Keterangan</label>
          <textarea class="form-control @error('keterangan') is-invalid @enderror" name="keterangan" rows="3">{{ old('keterangan') }}</textarea>
          @error('keterangan')
            <span class="invalid-feedback">{{ $message }}</span>
          @enderror
        </div>
      </div>

      <div class="mt-4 d-flex gap-2">
        <a href="{{ route('uang-muka-penjualan.index') }}" class="btn btn-secondary">Batal</a>
        <button type="submit" class="btn btn-primary">Simpan</button>
      </div>
    </form>
  </div>
</div>
@endsection

@push('custom-scripts')
<script>
  function updateProyekFromSO() {
    const select = document.getElementById('sales_order_id');
    const proyekSelect = document.getElementById('proyek_id');
    const selectedOption = select.options[select.selectedIndex];
    const proyekId = selectedOption.getAttribute('data-proyek-id');
    
    if (proyekId) {
      proyekSelect.value = proyekId;
    }
  }
</script>
@endpush

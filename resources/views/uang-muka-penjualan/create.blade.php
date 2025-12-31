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
          <select class="form-select @error('sales_order_id') is-invalid @enderror" name="sales_order_id" id="sales_order_id" required onchange="updateProyekAndNominal()">
            <option value="">-- Pilih Sales Order --</option>
            @foreach($salesOrders as $so)
              <option value="{{ $so->id }}" 
                      data-proyek-id="{{ optional($so->penawaran)->proyek_id }}" 
                      data-total="{{ $so->total }}"
                      data-persen-dp="{{ $so->persen_dp }}"
                      data-nominal-dp="{{ $so->nominal_dp }}"
                      @if($prefillSoId == $so->id) selected @endif>
                {{ $so->nomor ?? 'SO #' . $so->id }} - {{ optional($so->penawaran)->proyek->nama_proyek ?? 'Proyek' }} (Total: Rp {{ number_format($so->total, 0, ',', '.') }})
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
          <label class="form-label">Tanggal</label>
          <input type="date" class="form-control @error('tanggal') is-invalid @enderror" name="tanggal" value="{{ old('tanggal', now()->toDateString()) }}" required>
          @error('tanggal')
            <span class="invalid-feedback">{{ $message }}</span>
          @enderror
        </div>

        <div class="col-md-6">
          <label class="form-label">Persentase DP (%)</label>
          <input type="number" step="0.01" class="form-control bg-light" id="persen_dp" value="0" readonly>
          <small class="text-muted">Diambil dari persentase DP proyek</small>
        </div>

        <div class="col-md-6">
          <label class="form-label">Nominal Uang Muka (Otomatis)</label>
          <input type="text" class="form-control bg-light" id="nominal_display" value="Rp 0" readonly>
          <input type="number" step="0.01" class="form-control bg-light @error('nominal') is-invalid @enderror" name="nominal" id="nominal" value="{{ old('nominal') }}" hidden required>
          @error('nominal')
            <span class="invalid-feedback">{{ $message }}</span>
          @enderror
          <small class="text-muted">Dihitung otomatis: Total SO × Persentase DP</small>
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
  function updateProyekAndNominal() {
    const select = document.getElementById('sales_order_id');
    const proyekSelect = document.getElementById('proyek_id');
    const nominalInput = document.getElementById('nominal');
    const nominalDisplay = document.getElementById('nominal_display');
    const persenDpDisplay = document.getElementById('persen_dp');
    const selectedOption = select.options[select.selectedIndex];
    
    const proyekId = selectedOption.getAttribute('data-proyek-id');
    const nominalDp = parseFloat(selectedOption.getAttribute('data-nominal-dp')) || 0;
    const persenDp = parseFloat(selectedOption.getAttribute('data-persen-dp')) || 0;
    
    if (proyekId) {
      proyekSelect.value = proyekId;
    }
    
    if (nominalDp) {
      nominalInput.value = nominalDp.toFixed(2);
      nominalDisplay.value = 'Rp ' + nominalDp.toLocaleString('id-ID', {maximumFractionDigits: 0});
    } else {
      nominalInput.value = '';
      nominalDisplay.value = 'Rp 0';
    }
    
    if (persenDp) {
      persenDpDisplay.value = persenDp.toFixed(2);
    } else {
      persenDpDisplay.value = '0.00';
    }
  }
  
  // Trigger on page load if SO is pre-selected
  document.addEventListener('DOMContentLoaded', function() {
    const soSelect = document.getElementById('sales_order_id');
    if (soSelect.value) {
      updateProyekAndNominal();
    }
  });
</script>
@endpush

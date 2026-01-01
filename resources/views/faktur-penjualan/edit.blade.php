@extends('layout.master')

@section('content')
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Edit Faktur Penjualan</h5>
    <a href="{{ route('faktur-penjualan.show', $faktur->id) }}" class="btn btn-secondary btn-sm">Batal</a>
  </div>
  <div class="card-body">
    <form action="{{ route('faktur-penjualan.update', $faktur->id) }}" method="POST">
      @csrf
      @method('PUT')

      <div class="row mb-3">
        <div class="col-md-6">
          <label for="tanggal" class="form-label">Tanggal</label>
          <input type="date" name="tanggal" id="tanggal" 
                 class="form-control @error('tanggal') is-invalid @enderror"
                 value="{{ old('tanggal', optional($faktur->tanggal)->format('Y-m-d')) }}"
                 required>
          @error('tanggal')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>
        <div class="col-md-6">
          <label for="status" class="form-label">Status</label>
          <input type="text" class="form-control" value="{{ $faktur->status }}" disabled>
        </div>
      </div>

      <div class="row mb-3">
        <div class="col-md-6">
          <label for="subtotal" class="form-label">Subtotal (DPP)</label>
          <input type="number" name="subtotal" id="subtotal" step="0.01"
                 class="form-control text-right @error('subtotal') is-invalid @enderror"
                 value="{{ old('subtotal', $faktur->subtotal) }}"
                 required>
          @error('subtotal')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>
        <div class="col-md-6">
          <label for="total_diskon" class="form-label">Total Diskon</label>
          <input type="number" name="total_diskon" id="total_diskon" step="0.01"
                 class="form-control text-right @error('total_diskon') is-invalid @enderror"
                 value="{{ old('total_diskon', $faktur->total_diskon ?? 0) }}">
          @error('total_diskon')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>
      </div>

      <div class="card card-light mb-3">
        <div class="card-header">
          <h6 class="mb-0">Pajak & Pemotongan</h6>
        </div>
        <div class="card-body">
          <div class="row mb-3">
            <div class="col-md-6">
              <label for="ppn_persen" class="form-label">PPN %</label>
              <input type="number" name="ppn_persen" id="ppn_persen" step="0.0001"
                     class="form-control text-right @error('ppn_persen') is-invalid @enderror"
                     value="{{ old('ppn_persen', $faktur->ppn_persen ?? 0) }}">
              @error('ppn_persen')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
            <div class="col-md-6">
              <label for="total_ppn" class="form-label">PPN (Rp)</label>
              <input type="number" name="total_ppn" id="total_ppn" step="0.01"
                     class="form-control text-right @error('total_ppn') is-invalid @enderror"
                     value="{{ old('total_ppn', $faktur->total_ppn) }}"
                     required>
              @error('total_ppn')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-6">
              <label for="retensi_persen" class="form-label">Retensi %</label>
              <input type="number" name="retensi_persen" id="retensi_persen" step="0.0001"
                     class="form-control text-right @error('retensi_persen') is-invalid @enderror"
                     value="{{ old('retensi_persen', $faktur->retensi_persen ?? 0) }}">
              @error('retensi_persen')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
            <div class="col-md-6">
              <label for="retensi_nilai" class="form-label">Retensi (Rp)</label>
              <input type="number" name="retensi_nilai" id="retensi_nilai" step="0.01"
                     class="form-control text-right @error('retensi_nilai') is-invalid @enderror"
                     value="{{ old('retensi_nilai', $faktur->retensi_nilai ?? 0) }}">
              @error('retensi_nilai')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-6">
              <label for="pph_persen" class="form-label">PPh %</label>
              <input type="number" name="pph_persen" id="pph_persen" step="0.0001"
                     class="form-control text-right @error('pph_persen') is-invalid @enderror"
                     value="{{ old('pph_persen', $faktur->pph_persen ?? 0) }}">
              @error('pph_persen')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
            <div class="col-md-6">
              <label for="pph_nilai" class="form-label">PPh (Rp)</label>
              <input type="number" name="pph_nilai" id="pph_nilai" step="0.01"
                     class="form-control text-right @error('pph_nilai') is-invalid @enderror"
                     value="{{ old('pph_nilai', $faktur->pph_nilai ?? 0) }}">
              @error('pph_nilai')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-6">
              <label for="uang_muka_dipakai" class="form-label">Uang Muka Dipakai</label>
              <input type="number" name="uang_muka_dipakai" id="uang_muka_dipakai" step="0.01"
                     class="form-control text-right @error('uang_muka_dipakai') is-invalid @enderror"
                     value="{{ old('uang_muka_dipakai', $faktur->uang_muka_dipakai ?? 0) }}">
              @error('uang_muka_dipakai')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>
        </div>
      </div>

      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
          <i class="fas fa-save"></i> Simpan Perubahan
        </button>
        <a href="{{ route('faktur-penjualan.show', $faktur->id) }}" class="btn btn-secondary">
          <i class="fas fa-times"></i> Batal
        </a>
      </div>
    </form>
  </div>
</div>
@endsection

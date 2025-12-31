@extends('layout.master')

@section('content')
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Edit Sertifikat Pembayaran</h5>
    <a href="{{ route('sertifikat.index') }}" class="btn btn-secondary btn-sm">Kembali</a>
  </div>
  <div class="card-body">
    <form method="POST" action="{{ route('sertifikat.update', $sp->id) }}">
      @csrf
      @method('PUT')

      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Nomor Sertifikat</label>
          <input type="text" name="nomor" class="form-control" value="{{ old('nomor', $sp->nomor) }}" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Tanggal</label>
          <input type="date" name="tanggal" class="form-control" value="{{ old('tanggal', optional($sp->tanggal)->format('Y-m-d')) }}" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Termin Ke</label>
          <input type="number" name="termin_ke" min="1" class="form-control" value="{{ old('termin_ke', $sp->termin_ke) }}" required>
        </div>
      </div>

      <div class="mt-4">
        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
        <a href="{{ route('sertifikat.show', $sp->id) }}" class="btn btn-link">Batal</a>
      </div>
    </form>

    <div class="alert alert-light border mt-4 mb-0">
      <div class="small text-muted">Catatan:</div>
      <ul class="mb-0 small">
        <li>Perubahan ini hanya mengubah metadata (nomor, tanggal, termin). Angka perhitungan di sertifikat tidak diubah.</li>
        <li>Untuk revisi perhitungan, gunakan tombol Revisi pada daftar sertifikat untuk menerbitkan dokumen baru.</li>
      </ul>
    </div>
  </div>
</div>
@endsection

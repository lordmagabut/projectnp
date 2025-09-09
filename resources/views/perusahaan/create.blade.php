@extends('layout.master')

@section('content')
<div class="row">
  <div class="col grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
<h4 class="card-title mb-4">Form Input Perusahaan</h4>
    <form action="{{ route('perusahaan.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="mb-3">
            <label for="nama_perusahaan" class="form-label">Nama Perusahaan</label>
            <input type="text" name="nama_perusahaan" class="form-control" required>
        </div>

        <div class="mb-3">
            <label for="alamat" class="form-label">Alamat</label>
            <textarea name="alamat" class="form-control" required></textarea>
        </div>

        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" name="email" class="form-control">
        </div>

        <div class="mb-3">
            <label for="no_telp" class="form-label">No. Telp</label>
            <input type="text" name="no_telp" class="form-control">
        </div>

        <div class="mb-3">
            <label for="npwp" class="form-label">NPWP</label>
            <input type="text" name="npwp" class="form-control">
        </div>

        <div class="mb-3">
            <label for="tipe_perusahaan" class="form-label">Tipe Perusahaan</label>
            <select name="tipe_perusahaan" class="form-control" required>
                <option value="UMKM">UMKM</option>
                <option value="Kontraktor">Kontraktor</option>
                <option value="Perorangan">Perorangan</option>
            </select>
        </div>

        <div class="mb-3">
            <label for="template_po" class="form-label">Template PO (Opsional)</label>
            <input type="file" name="template_po" class="form-control">
        </div>

        <button type="submit" class="btn btn-primary">Simpan</button>
        <a href="{{ route('perusahaan.index') }}" class="btn btn-secondary">Batal</a>
    </form>
</div>
</div>
</div>
</div>
@endsection
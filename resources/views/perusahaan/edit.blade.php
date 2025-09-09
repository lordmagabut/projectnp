@extends('layout.master')

@section('content')
<div class="row">
<div class="col-lg-12 grid-margin stretch-card">
<div class="card">
      <div class="card-body">
    <h4 class="card-title mb-4">Edit Perusahaan</h4>
    <form action="{{ route('perusahaan.update', $perusahaan->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label for="nama_perusahaan" class="form-label">Nama Perusahaan</label>
            <input type="text" name="nama_perusahaan" class="form-control" value="{{ $perusahaan->nama_perusahaan }}" required>
        </div>

        <div class="mb-3">
            <label for="alamat" class="form-label">Alamat</label>
            <textarea name="alamat" class="form-control" required>{{ $perusahaan->alamat }}</textarea>
        </div>

        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" name="email" class="form-control" value="{{ $perusahaan->email }}">
        </div>

        <div class="mb-3">
            <label for="no_telp" class="form-label">No. Telp</label>
            <input type="text" name="no_telp" class="form-control" value="{{ $perusahaan->no_telp }}">
        </div>

        <div class="mb-3">
            <label for="npwp" class="form-label">NPWP</label>
            <input type="text" name="npwp" class="form-control" value="{{ $perusahaan->npwp }}">
        </div>

        <div class="mb-3">
            <label for="tipe_perusahaan" class="form-label">Tipe Perusahaan</label>
            <select name="tipe_perusahaan" class="form-control" required>
                <option value="UMKM" {{ $perusahaan->tipe_perusahaan == 'UMKM' ? 'selected' : '' }}>UMKM</option>
                <option value="Kontraktor" {{ $perusahaan->tipe_perusahaan == 'Kontraktor' ? 'selected' : '' }}>Kontraktor</option>
                <option value="Perorangan" {{ $perusahaan->tipe_perusahaan == 'Perorangan' ? 'selected' : '' }}>Perorangan</option>
            </select>
        </div>

        <div class="mb-3">
            <label for="template_po" class="form-label">Template PO (Opsional)</label>
            <input type="file" name="template_po" class="form-control">
            @if($perusahaan->template_po)
                <small>File saat ini: <a href="{{ asset('storage/' . $perusahaan->template_po) }}" target="_blank">Lihat File</a></small>
            @endif
        </div>

        <button type="submit" class="btn btn-primary">Update</button>
        <a href="{{ route('perusahaan.index') }}" class="btn btn-secondary">Batal</a>
    </form>
</div>
</div>
</div>
</div>
@endsection
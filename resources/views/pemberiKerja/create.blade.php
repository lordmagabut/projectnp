@extends('layout.master')

@section('content')
<div class="row">
  <div class="col-12 grid-margin stretch-card">
    <div class="card shadow-sm">
      <div class="card-header d-flex align-items-center justify-content-between flex-wrap">
        <h5 class="mb-0">Form Input Pemberi Kerja</h5>
        <a href="{{ route('pemberiKerja.index') }}" class="btn btn-light btn-sm">Kembali</a>
      </div>

      <div class="card-body">
        {{-- Alert validasi --}}
        @if ($errors->any())
          <div class="alert alert-danger">
            <div class="fw-semibold mb-1">Gagal menyimpan:</div>
            <ul class="mb-0">
              @foreach ($errors->all() as $e)
                <li>{{ $e }}</li>
              @endforeach
            </ul>
          </div>
        @endif

        <form action="{{ route('pemberiKerja.store') }}" method="POST" novalidate>
          @csrf

          <div class="row g-3">
            <div class="col-md-6">
              <label for="nama_pemberi_kerja" class="form-label">Nama Pemberi Kerja <span class="text-danger">*</span></label>
              <input type="text" id="nama_pemberi_kerja" name="nama_pemberi_kerja"
                     class="form-control @error('nama_pemberi_kerja') is-invalid @enderror"
                     value="{{ old('nama_pemberi_kerja') }}" required>
              @error('nama_pemberi_kerja')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
              <label for="pic" class="form-label">PIC</label>
              <input type="text" id="pic" name="pic"
                     class="form-control @error('pic') is-invalid @enderror"
                     value="{{ old('pic') }}">
              @error('pic')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
              <label for="jabatan_pic" class="form-label">Jabatan PIC</label>
              <input type="text" id="jabatan_pic" name="jabatan_pic"
                     class="form-control @error('jabatan_pic') is-invalid @enderror"
                     value="{{ old('jabatan_pic') }}">
              @error('jabatan_pic')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
              <label for="no_kontak" class="form-label">No. Kontak</label>
              <input type="text" id="no_kontak" name="no_kontak"
                     class="form-control @error('no_kontak') is-invalid @enderror"
                     value="{{ old('no_kontak') }}">
              @error('no_kontak')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
              <label for="nama_direktur" class="form-label">Nama Direktur</label>
              <input type="text" id="nama_direktur" name="nama_direktur"
                     class="form-control @error('nama_direktur') is-invalid @enderror"
                     value="{{ old('nama_direktur') }}">
              @error('nama_direktur')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
              <label for="jabatan_direktur" class="form-label">Jabatan Direktur</label>
              <input type="text" id="jabatan_direktur" name="jabatan_direktur"
                     class="form-control @error('jabatan_direktur') is-invalid @enderror"
                     value="{{ old('jabatan_direktur') }}">
              @error('jabatan_direktur')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-12">
              <label for="alamat" class="form-label">Alamat</label>
              <textarea id="alamat" name="alamat" rows="3"
                        class="form-control @error('alamat') is-invalid @enderror">{{ old('alamat') }}</textarea>
              @error('alamat')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
          </div>

          <div class="mt-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary">Simpan</button>
            <a href="{{ route('pemberiKerja.index') }}" class="btn btn-secondary">Batal</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

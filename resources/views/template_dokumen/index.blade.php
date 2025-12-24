@extends('layout.master')

@section('content')
<div class="row">
  <div class="col-lg-8 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <h4 class="card-title">Template Dokumen</h4>
        <p class="mb-4">Upload atau ganti Template PO untuk perusahaan.</p>

        @if(session('success'))
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        @endif

        @if($errors->any())
          <div class="alert alert-danger">
            <ul class="mb-0">
              @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
          </div>
        @endif

        <form action="{{ route('template-dokumen.store') }}" method="POST" enctype="multipart/form-data">
          @csrf

          <div class="mb-3">
            <label for="perusahaan_id" class="form-label">Pilih Perusahaan</label>
            <select name="perusahaan_id" id="perusahaan_id" class="form-control" required>
              @foreach($perusahaans as $p)
                <option value="{{ $p->id }}">{{ $p->nama_perusahaan }}</option>
              @endforeach
            </select>
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label for="template_po" class="form-label">Template PO (doc, docx)</label>
                <input type="file" name="template_po" id="template_po" class="form-control" accept=".doc,.docx">
                <small class="form-text text-muted">Maks 20MB. Format: .doc, .docx</small>
              </div>
            </div>

            <div class="col-md-6">
              <div class="mb-3">
                <label for="logo" class="form-label">Logo Perusahaan (jpg, png, svg)</label>
                <input type="file" name="logo" id="logo" class="form-control" accept="image/*">
                <small class="form-text text-muted">Maks 5MB. File akan digunakan di faktur dan dokumen lain.</small>
              </div>
            </div>
          </div>

          <button type="submit" class="btn btn-primary">Simpan</button>
          <a href="{{ route('perusahaan.index') }}" class="btn btn-secondary">Kembali</a>
        </form>

        <hr>
        <h6>Template & Logo Saat Ini</h6>
        <ul class="list-group mt-2">
          @foreach($perusahaans as $p)
            <li class="list-group-item d-flex justify-content-between align-items-center">
              <div>
                <strong>{{ $p->nama_perusahaan }}</strong>
                <div class="mt-1">
                  @if($p->template_po)
                    <a href="{{ asset('storage/' . $p->template_po) }}" target="_blank">Lihat / Unduh Template PO</a>
                  @else
                    <span class="text-muted">Belum ada template PO</span>
                  @endif
                </div>
                <div class="mt-1">
                  <img src="{{ company_logo_url($p) }}" alt="Logo {{ $p->nama_perusahaan }}" style="max-height:48px;">
                </div>
              </div>
            </li>
          @endforeach
        </ul>

      </div>
    </div>
  </div>
</div>
@endsection

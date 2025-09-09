@extends('layout.master')

@section('content')
<div class="row">
  <div class="col-lg-12 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <h4 class="card-title mb-4">Edit Supplier</h4>
        <form action="{{ route('supplier.update', $supplier->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
          <div class="mb-3">
            <label class="form-label">Nama Supplier</label>
            <input type="text" name="nama_supplier" class="form-control" value="{{ $supplier->nama_supplier }}" required>
          </div>

          <div class="mb-3">
            <label class="form-label">PIC</label>
            <input type="text" name="pic" class="form-control" value="{{ $supplier->pic }}" required>
          </div>

          <div class="mb-3">
            <label class="form-label">No Kontak</label>
            <input type="text" name="no_kontak" class="form-control" value="{{ $supplier->no_kontak }}">
          </div>

          <div class="mb-3">
            <label class="form-label">Keterangan</label>
            <textarea name="keterangan" class="form-control" rows="3">{{ $supplier->keterangan }}</textarea>
          </div>

          <button type="submit" class="btn btn-primary">Update</button>
          <a href="{{ route('supplier.index') }}" class="btn btn-secondary">Kembali</a>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

@extends('layout.master')

@section('content')
<div class="card">
    <div class="card-header">
        <h4 class="card-title">Tambah Upah / Tukang</h4>
    </div>
    <div class="card-body">
        <form action="{{ route('hsd-upah.store') }}" method="POST">
            @csrf

            <div class="mb-3">
                <label for="kode" class="form-label">Kode</label>
                <input type="text" name="kode" id="kode" class="form-control" required value="{{ old('kode') }}">
            </div>

            <div class="mb-3">
                <label for="jenis_pekerja" class="form-label">Jenis Pekerja</label>
                <input type="text" name="jenis_pekerja" id="jenis_pekerja" class="form-control" required value="{{ old('jenis_pekerja') }}">
            </div>

            <div class="mb-3">
                <label for="satuan" class="form-label">Satuan</label>
                <input type="text" name="satuan" id="satuan" class="form-control" value="{{ old('satuan', 'OH') }}">
            </div>

            <div class="mb-3">
                <label for="harga_satuan" class="form-label">Harga Satuan</label>
                <input type="number" name="harga_satuan" id="harga_satuan" class="form-control" step="0.01" required value="{{ old('harga_satuan') }}">
            </div>

            <div class="mb-3">
                <label for="keterangan" class="form-label">Keterangan</label>
                <textarea name="keterangan" id="keterangan" class="form-control">{{ old('keterangan') }}</textarea>
            </div>

            <button type="submit" class="btn btn-primary">Simpan</button>
            <a href="{{ route('hsd-upah.index') }}" class="btn btn-secondary">Kembali</a>
        </form>
    </div>
</div>
@endsection

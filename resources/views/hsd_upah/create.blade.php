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
                @php
                    $kodeValue = $nextKode ?? 'U-00001';
                @endphp
                <input type="text" class="form-control" id="kode" value="{{ $kodeValue }}" readonly style="background-color: #e9ecef; font-weight: 600; color: #0d6efd;">
                <small class="text-muted d-block mt-1">Kode akan di-assign otomatis</small>
            </div>

            <div class="mb-3">
                <label for="jenis_pekerja" class="form-label">Jenis Pekerja</label>
                @php
                    $jenisPekerja = old('jenis_pekerja');
                @endphp
                <input type="text" name="jenis_pekerja" id="jenis_pekerja" class="form-control" required value="{{ $jenisPekerja }}">
            </div>

            <div class="mb-3">
                <label for="satuan" class="form-label">Satuan</label>
                @php
                    $satuan = old('satuan', 'OH');
                @endphp
                <input type="text" name="satuan" id="satuan" class="form-control" value="{{ $satuan }}">
            </div>

            <div class="mb-3">
                <label for="harga_satuan" class="form-label">Harga Satuan</label>
                @php
                    $harga = old('harga_satuan');
                @endphp
                <input type="number" name="harga_satuan" id="harga_satuan" class="form-control" step="0.01" required value="{{ $harga }}">
            </div>

            <div class="mb-3">
                <label for="keterangan" class="form-label">Keterangan</label>
                @php
                    $keterangan = old('keterangan');
                @endphp
                <textarea name="keterangan" id="keterangan" class="form-control">{{ $keterangan }}</textarea>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> Simpan
                </button>
                <a href="{{ route('ahsp.index') }}" class="btn btn-secondary">
                    <i class="fas fa-times-circle me-1"></i> Batal
                </a>
            </div>
        </form>
    </div>
</div>
@endsection

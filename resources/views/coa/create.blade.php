@extends('layout.master')

@section('content')
<div class="row">
    <div class="col-lg-8 grid-margin stretch-card mx-auto">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">Form Input COA</h4>

                <form action="{{ route('coa.store') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">No Akun</label>
                        <input type="text" name="no_akun" class="form-control @error('no_akun') is-invalid @enderror" required value="{{ old('no_akun') }}">
                        @error('no_akun')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nama Akun</label>
                        <input type="text" name="nama_akun" class="form-control @error('nama_akun') is-invalid @enderror" required value="{{ old('nama_akun') }}">
                        @error('nama_akun')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tipe</label>
                        <select name="tipe" class="form-select @error('tipe') is-invalid @enderror" required>
                            <option value="">-- Pilih Tipe Akun --</option>
                            <option value="aset" {{ old('tipe') == 'aset' ? 'selected' : '' }}>Aset</option>
                            <option value="kewajiban" {{ old('tipe') == 'kewajiban' ? 'selected' : '' }}>Kewajiban</option>
                            <option value="ekuitas" {{ old('tipe') == 'ekuitas' ? 'selected' : '' }}>Ekuitas</option>
                            <option value="pendapatan" {{ old('tipe') == 'pendapatan' ? 'selected' : '' }}>Pendapatan</option>
                            <option value="beban" {{ old('tipe') == 'beban' ? 'selected' : '' }}>Beban</option>
                        </select>
                        @error('tipe')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Akun Induk (Opsional)</label>
                        <select name="parent_id" class="form-select">
                            <option value="">-- Pilih Akun Induk --</option>
                            @foreach($parentAkun as $akun)
                                <option value="{{ $akun->id }}">
                                    {{ str_repeat('— ', $akun->depth) . $akun->no_akun . ' - ' . $akun->nama_akun }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary">Simpan</button>
                    <a href="{{ route('coa.index') }}" class="btn btn-secondary">Kembali</a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

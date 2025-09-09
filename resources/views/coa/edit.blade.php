@extends('layout.master')

@section('content')
<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">Form Edit COA</h4>

                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('coa.update', $coa->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label">No Akun</label>
                        <input type="text" name="no_akun" class="form-control" value="{{ $coa->no_akun }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nama Akun</label>
                        <input type="text" name="nama_akun" class="form-control" value="{{ $coa->nama_akun }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tipe</label>
                        <input type="text" name="tipe" class="form-control" value="{{ $coa->tipe }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Akun Induk (Opsional)</label>
                        <select name="parent_id" class="form-select">
                            <option value="">-- Pilih Akun Induk --</option>
                            @foreach($parentAkun as $akun)
                                @if($akun->id != $coa->id && !$akun->isDescendantOf($coa))
                                    <option value="{{ $akun->id }}" {{ $coa->parent_id == $akun->id ? 'selected' : '' }}>
                                        {{ str_repeat('— ', $akun->depth) . $akun->no_akun . ' - ' . $akun->nama_akun }}
                                    </option>
                                @endif
                            @endforeach
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary">Update</button>
                    <a href="{{ route('coa.index') }}" class="btn btn-secondary">Kembali</a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@extends('layout.master')

@section('content')
<div class="row">
    <div class="col-lg-6 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Buat Izin Baru</h4>
                <p class="card-description">
                    Isi nama izin yang unik untuk ditambahkan ke sistem.
                </p>

                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>Terjadi Kesalahan!</strong> Mohon periksa kembali input Anda.
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <form action="{{ route('permissions.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="name" class="form-label">Nama Izin <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" placeholder="Contoh: create articles, view dashboard" required>
                    </div>

                    <button type="submit" class="btn btn-primary me-2">Simpan Izin</button>
                    <a href="{{ route('permissions.index') }}" class="btn btn-secondary">Batal</a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('plugin-scripts')
    {{-- Optional: Jika ada plugin khusus untuk form --}}
@endpush

@push('custom-scripts')
    <script>
        feather.replace();
    </script>
@endpush
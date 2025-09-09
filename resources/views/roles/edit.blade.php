@extends('layout.master')

@section('content')
<div class="row">
    <div class="col-lg-8 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Edit Peran: {{ $role->name }}</h4>
                <p class="card-description">
                    Ubah detail peran dan izin yang terkait.
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

                <form action="{{ route('roles.update', $role->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label for="name" class="form-label">Nama Peran <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $role->name) }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Pilih Izin untuk Peran Ini:</label>
                        <div class="row">
                            @forelse($permissions as $permission)
                                <div class="col-md-6 col-lg-4 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $permission->name }}" id="permission-{{ $permission->id }}"
                                            {{ in_array($permission->name, old('permissions', $rolePermissions)) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="permission-{{ $permission->id }}">
                                            {{ $permission->name }}
                                        </label>
                                    </div>
                                </div>
                            @empty
                                <div class="col-12">
                                    <p class="text-muted">Tidak ada izin yang tersedia. Silakan tambahkan izin terlebih dahulu.</p>
                                    @can('manage permissions')
                                        <a href="{{ route('permissions.create') }}" class="btn btn-sm btn-outline-primary">Tambah Izin Baru</a>
                                    @endcan
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary me-2">Perbarui Peran</button>
                    <a href="{{ route('roles.index') }}" class="btn btn-secondary">Batal</a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('plugin-scripts')
    {{-- Optional: Jika ada plugin khusus untuk form (misal: select2) --}}
@endpush

@push('custom-scripts')
    <script>
        feather.replace();
    </script>
@endpush
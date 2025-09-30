@extends('layout.master')

@section('content')
<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Kelola Peran: {{ $user->name ?? $user->username }}</h5>
                <a href="{{ route('user.index') }}" class="btn btn-light btn-sm">Kembali</a>
            </div>
            <div class="card-body">

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>Terjadi Kesalahan!</strong> Mohon periksa kembali input Anda.
                        <ul class="mb-0 mt-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <form action="{{ route('user.updateRoles', $user->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label">Pilih Peran:</label>
                        <div class="row">
                            @forelse($roles as $role)
                                <div class="col-md-6 col-lg-4 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input"
                                               type="checkbox"
                                               name="roles[]"
                                               value="{{ $role->name }}"
                                               id="role-{{ $role->id }}"
                                               {{ in_array($role->name, old('roles', $userRoles ?? [])) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="role-{{ $role->id }}">
                                            {{ $role->name }}
                                        </label>
                                    </div>
                                </div>
                            @empty
                                <div class="col-12">
                                    <p class="text-muted">Tidak ada peran yang tersedia. Tambahkan peran terlebih dahulu.</p>
                                    @can('manage roles')
                                        <a href="{{ route('roles.create') }}" class="btn btn-sm btn-outline-primary">Tambah Peran Baru</a>
                                    @endcan
                                </div>
                            @endforelse
                        </div>
                        @error('roles')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary me-2">Simpan Peran</button>
                        <a href="{{ route('user.index') }}" class="btn btn-secondary">Batal</a>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>
@endsection

@push('custom-scripts')
<script>
    if (typeof feather !== 'undefined') { feather.replace(); }
</script>
@endpush

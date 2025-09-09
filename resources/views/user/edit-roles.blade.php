@extends('layout.master')

@section('content')
<div class="row">
    <div class="col-lg-8 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Kelola Peran untuk Pengguna: {{ $user->name }}</h4>
                <p class="card-description">
                    Pilih peran yang akan diberikan kepada pengguna ini.
                </p>

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
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

                <form action="{{ route('users.updateRoles', $user->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label class="form-label">Pilih Peran:</label>
                        <div class="row">
                            @forelse($roles as $role)
                                <div class="col-md-6 col-lg-4 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="roles[]" value="{{ $role->name }}" id="role-{{ $role->id }}"
                                            {{ in_array($role->name, old('roles', $userRoles)) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="role-{{ $role->id }}">
                                            {{ $role->name }}
                                        </label>
                                    </div>
                                </div>
                            @empty
                                <div class="col-12">
                                    <p class="text-muted">Tidak ada peran yang tersedia. Silakan tambahkan peran terlebih dahulu.</p>
                                    @can('manage roles')
                                        <a href="{{ route('roles.create') }}" class="btn btn-sm btn-outline-primary">Tambah Peran Baru</a>
                                    @endcan
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary me-2">Perbarui Peran Pengguna</button>
                    <a href="{{ url()->previous() }}" class="btn btn-secondary">Kembali</a> {{-- Sesuaikan rute kembali --}}
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
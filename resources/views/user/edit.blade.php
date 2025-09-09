@extends('layout.master') {{-- Sesuaikan dengan layout utama Anda --}}

@section('content')
<div class="row">
    <div class="col-lg-8 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Edit Pengguna: {{ $user->username }}</h4>
                <p class="card-description">
                    Ubah detail pengguna dan peran yang terkait.
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

                <form action="{{ route('user.update', $user->id) }}" method="POST">
                    @csrf
                    @method('PUT') {{-- Penting untuk memberitahu Laravel bahwa ini adalah request PUT --}}

                    <div class="mb-3">
                        <label for="username" class="form-label">Nama <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="username" name="username" value="{{ old('name', $user->username) }}" placeholder="Nama Lengkap" required>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password Baru</label>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Biarkan kosong jika tidak ingin mengubah">
                        <small class="form-text text-muted">Isi hanya jika Anda ingin mengubah password.</small>
                    </div>

                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label">Konfirmasi Password Baru</label>
                        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" placeholder="Ketik ulang password baru">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Pilih Peran untuk Pengguna Ini:</label>
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

                    <button type="submit" class="btn btn-primary me-2">Perbarui Pengguna</button>
                    <a href="{{ route('user.index') }}" class="btn btn-secondary">Batal</a>
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
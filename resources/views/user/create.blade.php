@extends('layout.master') {{-- Sesuaikan dengan layout utama Anda --}}

@section('content')
<div class="row">
    <div class="col-lg-8 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Buat Pengguna Baru</h4>
                <p class="card-description">
                    Isi formulir di bawah untuk menambahkan pengguna baru ke sistem.
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

                <form action="{{ route('user.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="name" class="form-label">Nama <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" placeholder="Nama Lengkap" required>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" placeholder="contoh@domain.com" required>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Minimal 8 karakter" required>
                    </div>

                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label">Konfirmasi Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" placeholder="Ketik ulang password" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Pilih Peran untuk Pengguna Ini:</label>
                        <div class="row">
                            @forelse($roles as $role)
                                <div class="col-md-6 col-lg-4 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="roles[]" value="{{ $role->name }}" id="role-{{ $role->id }}"
                                               {{ in_array($role->name, old('roles', [])) ? 'checked' : '' }}>
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

                    <button type="submit" class="btn btn-primary me-2">Simpan Pengguna</button>
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
        // Inisialisasi Feather Icons jika digunakan
        feather.replace();
    </script>
@endpush
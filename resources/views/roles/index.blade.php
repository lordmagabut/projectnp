@extends('layout.master') {{-- Sesuaikan dengan layout Anda --}}

@section('content')
<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Manajemen Peran</h4>
                <p class="mb-4">Daftar semua peran dalam sistem.</p>

                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                @can('manage roles') {{-- Hanya tampilkan tombol jika user memiliki izin --}}
                    <a href="{{ route('roles.create') }}" class="btn btn-primary mb-3">Tambah Peran Baru</a>
                @endcan

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Nama Peran</th>
                                <th>Izin Terkait</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($roles as $role)
                                <tr>
                                    <td>{{ $role->name }}</td>
                                    <td>
                                        @forelse($role->permissions as $permission)
                                            <span class="badge bg-info">{{ $permission->name }}</span>
                                        @empty
                                            <span class="text-muted">Tidak ada izin</span>
                                        @endforelse
                                    </td>
                                    <td>
                                        @can('manage roles')
                                            <a href="{{ route('roles.edit', $role->id) }}" class="btn btn-sm btn-warning">Edit</a>
                                            <form action="{{ route('roles.destroy', $role->id) }}" method="POST" style="display:inline-block;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menghapus peran ini?')">Hapus</button>
                                            </form>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3">Tidak ada peran yang ditemukan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
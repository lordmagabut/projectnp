@extends('layout.master') {{-- Sesuaikan dengan layout utama Anda --}}

@push('plugin-styles')
    {{-- DataTables CSS --}}
    <link href="{{ asset('assets/plugins/datatables-net-bs5/dataTables.bootstrap5.css') }}" rel="stylesheet" />
    <link href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap5.min.css" rel="stylesheet" />
    {{-- Anda mungkin juga ingin menambahkan CSS kustom untuk tampilan Metronic yang lebih spesifik jika ada --}}
@endpush

@section('content')
<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Manajemen Pengguna</h4>
                <p class="card-description">
                    Daftar semua pengguna dalam sistem dan peran yang mereka miliki.
                </p>

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                {{-- Tombol Tambah Pengguna Baru, hanya jika user punya izin 'manage users' --}}
                @can('manage users')
                    <a href="{{ route('user.create') }}" class="btn btn-primary mb-3">
                        <i class="btn-icon-prepend" data-feather="plus"></i> Tambah Pengguna Baru
                    </a>
                @endcan

                <div class="table-responsive">
                    {{-- Tambahkan ID unik untuk DataTables --}}
                    <table id="usersDataTable" class="table table-hover align-middle display nowrap" style="width:100%">
                        <thead>
                            <tr>
                                <th>Username</th> {{-- Dikembalikan ke Username --}}
                                <th>Peran (Roles)</th>
                                <th class="text-end">Aksi</th> {{-- Tambahkan text-end di sini juga untuk header --}}
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($users as $user)
                                <tr>
                                    <td>{{ $user->username }}</td> {{-- Dikembalikan ke $user->username --}}
                                    <td>
                                        @forelse($user->getRoleNames() as $roleName)
                                            <span class="badge bg-primary text-light me-1">{{ $roleName }}</span>
                                        @empty
                                            <span class="text-muted">Tidak ada peran</span>
                                        @endforelse
                                    </td>
                                    <td class="text-end"> {{-- PASTIKAN INI ADA untuk meratakan konten TD ke kanan --}}
                                        @can('manage users')
                                        <div class="dropdown">
                                            <button class="btn btn-primary dropdown-toggle" type="button" id="dropdownUserActions-{{ $user->id }}" data-bs-toggle="dropdown" aria-expanded="false">
                                                Aksi
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownUserActions-{{ $user->id }}"> {{-- PASTIKAN INI ADA untuk meratakan dropdown menu ke kanan --}}
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('users.editRoles', $user->id) }}">
                                                        <i class="me-2" data-feather="tool"></i> Kelola Peran
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('user.edit', $user->id) }}">
                                                        <i class="me-2" data-feather="edit"></i> Edit Pengguna
                                                    </a>
                                                </li>
                                                <li>
                                                    <form action="{{ route('user.destroy', $user->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pengguna ini?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="dropdown-item text-danger">
                                                            <i class="me-2" data-feather="trash"></i> Hapus
                                                        </button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center">Tidak ada pengguna yang ditemukan.</td>
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

@push('plugin-scripts')
    {{-- jQuery (pastikan sudah dimuat di master layout sebelum DataTables) --}}
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    {{-- DataTables JS --}}
    <script src="{{ asset('assets/plugins/datatables-net/jquery.dataTables.js') }}"></script>
    <script src="{{ asset('assets/plugins/datatables-net-bs5/dataTables.bootstrap5.js') }}"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap5.min.js"></script>
@endpush

@push('custom-scripts')
    <script>
        $(document).ready(function() {
            // Inisialisasi DataTables
            $('#usersDataTable').DataTable({
                "aLengthMenu": [
                    [10, 25, 50, 100, -1],
                    [10, 25, 50, 100, "All"]
                ],
                "iDisplayLength": 10,
                "language": {
                    "search": "",
                    "zeroRecords": "Tidak ada data pengguna yang ditemukan",
                    "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ pengguna",
                    "infoEmpty": "Menampilkan 0 sampai 0 dari 0 pengguna",
                    "infoFiltered": "(difilter dari _MAX_ total pengguna)",
                    "lengthMenu": "Tampilkan _MENU_ pengguna",
                    "paginate": {
                        "first": "Pertama",
                        "last": "Terakhir",
                        "next": "Berikutnya",
                        "previous": "Sebelumnya"
                    }
                },
                "responsive": true, // Aktifkan responsif
                "autoWidth": false // Nonaktifkan autoWidth agar DataTables tidak mengganggu responsif
            });

            // Placeholder untuk input pencarian DataTables
            $('#usersDataTable_filter input').attr('placeholder', 'Cari Pengguna...');

            // Inisialisasi Feather Icons
            feather.replace();
        });
    </script>
@endpush
@extends('layout.master')

@push('plugin-styles')
    {{-- DataTables CSS --}}
    <link href="{{ asset('assets/plugins/datatables-net-bs5/dataTables.bootstrap5.css') }}" rel="stylesheet" />
    <link href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap5.min.css" rel="stylesheet" />
    {{-- Jika Anda memiliki CSS kustom Metronic lainnya --}}
@endpush

@section('content')
<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Manajemen Izin</h4>
                <p class="card-description">
                    Daftar semua izin yang terdaftar dalam sistem.
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

                @can('manage permissions')
                    <a href="{{ route('permissions.create') }}" class="btn btn-primary mb-3">
                        <i class="btn-icon-prepend" data-feather="plus"></i> Tambah Izin Baru
                    </a>
                @endcan

                <div class="table-responsive">
                    <table id="permissionsDataTable" class="table table-hover align-middle display nowrap" style="width:100%">
                        <thead>
                            <tr>
                                <th>Nama Izin</th>
                                <th class="text-end">Aksi</th> {{-- Tambahkan text-end di sini juga untuk header --}}
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($permissions as $permission)
                                <tr>
                                    <td>{{ $permission->name }}</td>
                                    <td class="text-end"> {{-- TAMBAHKAN KELAS INI --}}
                                        @can('manage permissions')
                                        <div class="dropdown">
                                            <button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuButton-{{ $permission->id }}" data-bs-toggle="dropdown" aria-expanded="false">
                                                Aksi
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton-{{ $permission->id }}"> {{-- Tambahkan dropdown-menu-end --}}
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('permissions.edit', $permission->id) }}">
                                                        <i class="me-2" data-feather="edit"></i> Edit
                                                    </a>
                                                </li>
                                                <li>
                                                    <form action="{{ route('permissions.destroy', $permission->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus izin ini? Ini akan memengaruhi peran dan pengguna yang menggunakan izin ini.')">
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
                                    <td colspan="2" class="text-center">Tidak ada izin yang ditemukan.</td>
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
            $('#permissionsDataTable').DataTable({
                "aLengthMenu": [
                    [10, 25, 50, 100, -1],
                    [10, 25, 50, 100, "All"]
                ],
                "iDisplayLength": 10,
                "language": {
                    "search": "",
                    "zeroRecords": "Tidak ada data izin yang ditemukan",
                    "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ izin",
                    "infoEmpty": "Menampilkan 0 sampai 0 dari 0 izin",
                    "infoFiltered": "(difilter dari _MAX_ total izin)",
                    "lengthMenu": "Tampilkan _MENU_ izin",
                    "paginate": {
                        "first": "Pertama",
                        "last": "Terakhir",
                        "next": "Berikutnya",
                        "previous": "Sebelumnya"
                    }
                },
                "responsive": true,
                "autoWidth": false
            });

            // Placeholder untuk input pencarian DataTables
            $('#permissionsDataTable_filter input').attr('placeholder', 'Cari Izin...');

            // Inisialisasi Feather Icons
            feather.replace();
        });
    </script>
@endpush
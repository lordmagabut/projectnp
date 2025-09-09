@extends('layout.master')

@push('plugin-styles')
<link href="{{ asset('assets/plugins/datatables-net-bs5/dataTables.bootstrap5.css') }}" rel="stylesheet" />
<link href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap5.min.css" rel="stylesheet" />
{{-- Asumsi Anda sudah memuat Font Awesome atau Lucide Icons di layout.master --}}
{{-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"> --}}
{{-- Atau jika menggunakan Feather Icons secara langsung: --}}
{{-- <script src="https://unpkg.com/feather-icons"></script> --}}
@endpush

@section('content')
<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card shadow-sm animate__animated animate__fadeIn">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center flex-wrap rounded-top">
                <h4 class="card-title mb-0 d-flex align-items-center">
                    <i data-feather="folder-plus" class="me-2"></i> Daftar Proyek
                </h4>
                @if(auth()->user()->buat_proyek == 1)
                    <a href="{{ route('proyek.create') }}" class="btn btn-light btn-sm d-inline-flex align-items-center animate__animated animate__pulse animate__infinite">
                        <i data-feather="plus-circle" class="me-1"></i> Tambah Proyek
                    </a>
                @endif
            </div>
            <div class="card-body p-3 p-md-4">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show animate__animated animate__fadeIn mb-4" role="alert">
                        <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show animate__animated animate__fadeIn mb-4" role="alert">
                        <i class="fas fa-times-circle me-2"></i> {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <div class="table-responsive">
                    <table id="dataTableExample" class="table table-hover table-bordered table-striped align-middle display nowrap" style="width:100%">
                        <thead class="table-secondary">
                            <tr>
                                <th><i data-feather="tag" class="me-1"></i> Nama Proyek</th>
                                <th><i data-feather="users" class="me-1"></i> Pemberi Kerja</th>
                                <th><i data-feather="file-text" class="me-1"></i> No SPK</th>
                                <th class="text-end"><i data-feather="dollar-sign" class="me-1"></i> Nilai SPK</th>
                                <th><i data-feather="compass" class="me-1"></i> Jenis Proyek</th>
                                <th class="text-center"><i data-feather="settings" class="me-1"></i> Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($proyeks as $proyek)
                            <tr class="animate__animated animate__fadeInUp animate__faster">
                                <td>{{ $proyek->nama_proyek }}</td>
                                <td>{{ $proyek->pemberiKerja->nama_pemberi_kerja ?? '-' }}</td>
                                <td>
                                    @if($proyek->file_spk)
                                        <a href="{{ asset('storage/' . $proyek->file_spk) }}" target="_blank" class="text-decoration-none text-primary d-flex align-items-center">
                                            <i data-feather="link" class="me-1"></i> {{ $proyek->no_spk }}
                                        </a>
                                    @else
                                        {{ $proyek->no_spk ?? '-' }}
                                    @endif
                                </td>
                                <td class="text-end fw-bold text-success">Rp. {{ number_format($proyek->nilai_spk, 0, ',', '.') }}</td>
                                <td>{{ ucfirst($proyek->jenis_proyek) }}</td>
                                <td class="text-center">
                                    <div class="btn-group" role="group">
                                        <button id="aksiDropdown{{ $proyek->id }}" type="button" class="btn btn-sm btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                            Aksi
                                        </button>
                                        <ul class="dropdown-menu shadow" aria-labelledby="aksiDropdown{{ $proyek->id }}">
                                            @if(auth()->user()->akses_proyek == 1)
                                                <li>
                                                    <a href="{{ route('proyek.show', $proyek->id) }}" class="dropdown-item d-flex align-items-center">
                                                        <i data-feather="eye" class="me-2 text-info"></i> Detail
                                                    </a>
                                                </li>
                                            @endif

                                            @if(auth()->user()->hapus_proyek == 1)
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <form action="{{ route('proyek.destroy', $proyek->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus proyek ini? Tindakan ini tidak dapat dibatalkan.')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="dropdown-item text-danger d-flex align-items-center">
                                                            <i data-feather="trash-2" class="me-2"></i> Hapus
                                                        </button>
                                                    </form>
                                                </li>
                                            @endif
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    <i class="fas fa-box-open fa-2x mb-2"></i><br>
                                    Belum ada data proyek.
                                </td>
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
<script src="{{ asset('assets/plugins/datatables-net/jquery.dataTables.js') }}"></script>
<script src="{{ asset('assets/plugins/datatables-net-bs5/dataTables.bootstrap5.js') }}"></script>
<script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap5.min.js"></script>
{{-- Memuat Feather Icons untuk memastikan ikon berfungsi --}}
<script src="https://unpkg.com/feather-icons"></script>
@endpush

@push('custom-scripts')
<script>
    // Inisialisasi Feather Icons
    feather.replace();

    $(document).ready(function () {
        $('#dataTableExample').DataTable({
            responsive: true,
            autoWidth: false,
            // Menambahkan beberapa opsi DataTables untuk tampilan yang lebih baik
            "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Indonesian.json" // Opsional: Untuk bahasa Indonesia
            },
            "columnDefs": [
                { "orderable": false, "targets": [5] } // Nonaktifkan sorting untuk kolom Aksi
            ]
        });
    });
</script>
@endpush

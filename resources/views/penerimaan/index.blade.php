@extends('layout.master')

@push('plugin-styles')
<link href="{{ asset('assets/plugins/datatables-net-bs5/dataTables.bootstrap5.css') }}" rel="stylesheet" />
<style>
    /* Mencegah Scrollbar muncul saat Dropdown diklik */
    .table-responsive {
        overflow: visible !important;
    }

    /* Styling Badge Modern */
    .badge-soft {
        padding: 5px 10px;
        border-radius: 4px;
        font-weight: 600;
        font-size: 11px;
    }
    .badge-soft-success { background-color: #e1f5ed; color: #198754; border: 1px solid #c3e6cb; }
    .badge-soft-secondary { background-color: #f3f4f7; color: #6c757d; border: 1px solid #dee2e6; }
    .badge-soft-warning { background-color: #fff8e6; color: #856404; border: 1px solid #ffeeba; }
    .badge-soft-info { background-color: #e0f2ff; color: #004a99; border: 1px solid #b3d7ff; }

    /* Custom Table Style */
    .table > :not(caption) > * > * {
        padding: 12px 10px;
        vertical-align: middle;
    }
    .text-mono { font-family: 'Courier New', Courier, monospace; font-weight: 600; }
</style>
@endpush

@section('content')
<nav class="page-breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="#">Logistik</a></li>
        <li class="breadcrumb-item active" aria-current="page">Penerimaan Pembelian</li>
    </ol>
</nav>

<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h6 class="card-title mb-0">Daftar Penerimaan Barang</h6>
                </div>
                
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i data-feather="check-circle" class="icon-sm me-2"></i> {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <div class="table-responsive">
                    <table id="dataTableExample" class="table table-hover">
                        <thead>
                            <tr class="bg-light">
                                <th>Tanggal</th>
                                <th>No. Penerimaan</th>
                                <th>No. PO</th>
                                <th>Supplier</th>
                                <th>Proyek</th>
                                <th>No. Surat Jalan</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Penagihan</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($penerimaans as $item)
                            <tr>
                                <td class="small">{{ \Carbon\Carbon::parse($item->tanggal)->format('d/m/Y') }}</td>
                                <td>
                                    <a href="{{ route('penerimaan.show', $item->id) }}" class="fw-bold text-primary">
                                        {{ $item->no_penerimaan }}
                                    </a>
                                </td>
                                <td class="text-mono small">
                                    @if($item->po?->file_path)
                                        <a href="{{ asset('storage/' . $item->po->file_path) }}" target="_blank" class="text-info">
                                            {{ $item->po->no_po }} <i data-feather="external-link" class="icon-xs"></i>
                                        </a>
                                    @else
                                        {{ $item->po->no_po ?? '-' }}
                                    @endif
                                </td>
                                <td>{{ Str::limit($item->nama_supplier, 20) }}</td>
                                <td class="small text-muted">{{ $item->proyek->nama_proyek ?? '-' }}</td>
                                <td class="small">
                                    {{ $item->no_surat_jalan ?? '-' }}
                                    @if($item->file_surat_jalan)
                                        <a href="{{ route('penerimaan.viewSuratJalan', $item->id) }}" 
                                           class="btn btn-sm btn-outline-info ms-1" 
                                           target="_blank"
                                           title="Lihat Surat Jalan">
                                            <i data-feather="file-text" class="icon-xs"></i>
                                        </a>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($item->status == 'draft')
                                        <span class="badge-soft badge-soft-secondary text-uppercase">Draft</span>
                                    @else
                                        <span class="badge-soft badge-soft-success text-uppercase">Approved</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @switch($item->status_penagihan)
                                        @case('lunas')
                                            <span class="badge-soft badge-soft-success">Lunas</span>
                                            @break
                                        @case('sebagian')
                                            <span class="badge-soft badge-soft-warning">Sebagian</span>
                                            @break
                                        @default
                                            <span class="badge-soft badge-soft-secondary">Belum</span>
                                    @endswitch
                                </td>
                                <td class="text-center">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" 
                                                data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false">
                                            Aksi
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow">
                                            <li>
                                                <a class="dropdown-item py-2" href="{{ route('penerimaan.show', $item->id) }}">
                                                    <i data-feather="eye" class="icon-sm me-2 text-primary"></i> Detail Penerimaan
                                                </a>
                                            </li>

                                            @if($item->status == 'draft')
                                            @can('approve penerimaan')
                                            <li>
                                                <form action="{{ route('penerimaan.approve', $item->id) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="dropdown-item py-2" onclick="return confirm('Approve penerimaan ini?')">
                                                        <i data-feather="check-square" class="icon-sm me-2 text-success"></i> Approve
                                                    </button>
                                                </form>
                                            </li>
                                            @endcan
                                            @else
                                            @can('edit penerimaan')
                                            <li>
                                                <form action="{{ route('penerimaan.revisi', $item->id) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="dropdown-item py-2" onclick="return confirm('Revisi penerimaan ini?')">
                                                        <i data-feather="refresh-cw" class="icon-sm me-2 text-warning"></i> Revisi
                                                    </button>
                                                </form>
                                            </li>
                                            @endcan
                                            @endif

                                            <li>
                                                <a class="dropdown-item py-2" href="{{ route('retur.create', $item->id) }}">
                                                    <i data-feather="corner-up-left" class="icon-sm me-2 text-info"></i> Retur Barang
                                                </a>
                                            </li>
                                            
                                            @if($item->status == 'draft')
                                            @can('delete penerimaan')
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <form action="{{ route('penerimaan.destroy', $item->id) }}" method="POST">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="dropdown-item py-2 text-danger" onclick="return confirm('Yakin hapus penerimaan ini?')">
                                                        <i data-feather="trash-2" class="icon-sm me-2"></i> Hapus
                                                    </button>
                                                </form>
                                            </li>
                                            @endcan
                                            @endif
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
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
@endpush

@push('custom-scripts')
<script>
$(document).ready(function() {
    if ($('#dataTableExample').length) {
        $('#dataTableExample').DataTable({
            "order": [[0, "desc"]],
            "language": {
                search: "",
                searchPlaceholder: "Cari data...",
            },
            "drawCallback": function() {
                feather.replace(); // Memastikan icon muncul setelah ganti halaman data
            }
        });
    }
});
</script>
@endpush
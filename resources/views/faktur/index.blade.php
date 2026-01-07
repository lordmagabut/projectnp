@extends('layout.master')

@push('plugin-styles')
<link href="{{ asset('assets/plugins/datatables-net-bs5/dataTables.bootstrap5.css') }}" rel="stylesheet" />
<link href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap5.min.css" rel="stylesheet" />
<style>
    /* 1. Perbaikan utama: Menghilangkan scrollbar akibat dropdown */
    .table-responsive {
        overflow: visible !important; /* Memaksa elemen keluar tidak memicu scroll */
    }

    /* Agar tabel tetap bisa scroll di layar HP yang sangat kecil, 
       tapi tetap visible di desktop */
    @media (max-width: 767px) {
        .table-responsive {
            overflow-x: auto !important;
            -webkit-overflow-scrolling: touch;
        }
    }

    /* 2. Styling Konsistensi */
    .table td { vertical-align: middle !important; }
    .badge-status { font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; padding: 0.5em 0.8em; font-size: 10px; }
    .bg-soft-info { background-color: rgba(0, 210, 255, 0.1); color: #007fa0; }
    .bg-soft-warning { background-color: rgba(255, 159, 67, 0.1); color: #ff9f43; }
    .bg-soft-success { background-color: rgba(40, 199, 111, 0.1); color: #28c76f; }
    
    /* 3. Dropdown Styling */
    .dropdown-menu { 
        box-shadow: 0 5px 15px rgba(0,0,0,0.1); 
        border: 1px solid #eee; 
        z-index: 1050 !important; /* Pastikan di atas elemen lain */
    }
    .dropdown-item { padding: 0.6rem 1rem; transition: all 0.2s; }
    .dropdown-item:hover { background-color: #f8f9fa; }
</style>
@endpush

@section('content')
<nav class="page-breadcrumb d-print-none">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="#">Transaksi</a></li>
        <li class="breadcrumb-item active" aria-current="page">Faktur Pembelian</li>
    </ol>
</nav>

<div class="row">
    <div class="col-md-12 grid-margin stretch-card">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="card-title mb-0 text-primary">Daftar Faktur Pembelian</h4>
                        <p class="text-muted small">Kelola penagihan masuk dan status pembayaran supplier</p>
                    </div>
                    @if(auth()->user()->buat_faktur == 1)
                    <a href="{{ route('faktur.create') }}" class="btn btn-primary btn-icon-text shadow-sm">
                        <i class="btn-icon-prepend" data-feather="plus-circle"></i> Tambah Faktur
                    </a>
                    @endif
                </div>

                @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <div class="d-flex align-items-center">
                        <i data-feather="check-circle" class="icon-md me-2"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                @endif

                <div class="table-responsive">
                    <table id="dataTableFaktur" class="table table-hover display nowrap" style="width:100%">
                        <thead>
                            <tr class="bg-light">
                                <th class="py-3" width="10%">Tanggal</th>
                                <th class="py-3">No. Faktur</th>
                                <th class="py-3">Supplier</th>
                                <th class="py-3 text-end">Tagihan (Net)</th>
                                <th class="py-3">Proyek</th>
                                <th class="py-3 text-center">Status</th>
                                <th class="py-3 text-center" width="5%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($fakturs as $faktur)
                            <tr>
                                <td class="text-muted small">
                                    {{ \Carbon\Carbon::parse($faktur->tanggal)->format('d/m/Y') }}
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <span class="fw-bold text-dark me-2">{{ $faktur->no_faktur }}</span>
                                        @if($faktur->file_path)
                                            <a href="{{ asset('storage/' . $faktur->file_path) }}" target="_blank" class="text-primary" title="Lihat Lampiran PDF">
                                                <i data-feather="file-text" style="width: 14px; height: 14px;"></i>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                                <td>{{ $faktur->nama_supplier }}</td>
                                <td class="text-end fw-bold text-dark">
                                    Rp {{ number_format(max(0, $faktur->total - ($faktur->uang_muka_dipakai ?? 0)), 0, ',', '.') }}
                                </td>
                                <td>
                                    <span class="badge bg-light text-muted border small">{{ $faktur->proyek->nama_proyek ?? '-' }}</span>
                                </td>
                                <td class="text-center">
                                    @if($faktur->status == 'draft')
                                        <span class="badge badge-status bg-soft-warning border border-warning">Draft</span>
                                    @elseif($faktur->status == 'sedang diproses')
                                        <span class="badge badge-status bg-soft-info border border-info">Approved</span>
                                    @elseif($faktur->status == 'lunas')
                                        <span class="badge badge-status bg-soft-success border border-success">Lunas</span>
                                    @else
                                        <span class="badge badge-status bg-light text-dark border">{{ ucfirst($faktur->status) }}</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="dropdown">
                                        <button class="btn btn-link p-0" type="button" id="drop{{ $faktur->id }}" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false">
                                            <i data-feather="more-vertical" class="icon-sm text-muted"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="drop{{ $faktur->id }}">
                                            <li>
                                                <a class="dropdown-item d-flex align-items-center" href="{{ route('faktur.show', $faktur->id) }}">
                                                    <i data-feather="eye" class="icon-sm me-2"></i> Detail Faktur
                                                </a>
                                            </li>

                                            @if($faktur->status == 'draft')
                                                @if(auth()->user()->hapus_faktur == 1)
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <form action="{{ route('faktur.destroy', $faktur->id) }}" method="POST" id="del-{{ $faktur->id }}">
                                                        @csrf @method('DELETE')
                                                        <button type="button" class="dropdown-item d-flex align-items-center text-danger" onclick="if(confirm('Hapus faktur ini?')) document.getElementById('del-{{ $faktur->id }}').submit();">
                                                            <i data-feather="trash-2" class="icon-sm me-2"></i> Hapus
                                                        </button>
                                                    </form>
                                                </li>
                                                @endif

                                            @elseif($faktur->status == 'sedang diproses')
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <a class="dropdown-item d-flex align-items-center text-success fw-bold" href="{{ route('pembayaran.create', $faktur->id) }}">
                                                        <i data-feather="dollar-sign" class="icon-sm me-2"></i> Bayar Sekarang
                                                    </a>
                                                </li>
                                                <li>
                                                    <form action="{{ route('faktur.revisi', $faktur->id) }}" method="POST" id="rev-{{ $faktur->id }}">
                                                        @csrf
                                                        <button type="button" class="dropdown-item d-flex align-items-center text-warning" onclick="if(confirm('Revisi akan membatalkan jurnal terkait. Lanjutkan?')) document.getElementById('rev-{{ $faktur->id }}').submit();">
                                                            <i data-feather="edit-3" class="icon-sm me-2"></i> Ajukan Revisi
                                                        </button>
                                                    </form>
                                                </li>
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
<script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap5.min.js"></script>
@endpush

@push('custom-scripts')
<script>
  $(document).ready(function () {
    if ($('#dataTableFaktur').length) {
      $('#dataTableFaktur').DataTable({
        responsive: true,
        autoWidth: false,
        order: [[0, 'desc']],
        language: {
          search: "",
          searchPlaceholder: "Cari Faktur...",
          lengthMenu: "_MENU_ data",
          info: "Menampilkan _START_ - _END_ dari _TOTAL_",
          paginate: {
            previous: "<i class='feather icon-chevron-left'></i>",
            next: "<i class='feather icon-chevron-right'></i>"
          }
        },
        drawCallback: function() {
            feather.replace();
        }
      });
    }
  });
</script>
@endpush
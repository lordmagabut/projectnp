@extends('layout.master')

@push('plugin-styles')
<link href="{{ asset('assets/plugins/datatables-net-bs5/dataTables.bootstrap5.css') }}" rel="stylesheet" />
<link href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap5.min.css" rel="stylesheet" />
@endpush

@section('content')
<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Daftar Faktur Pembelian</h4>

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if(auth()->user()->buat_faktur == 1)
                <a href="{{ route('faktur.create') }}" class="btn btn-primary mb-3">
                    <i class="btn-icon-prepend" data-feather="plus-circle"></i> Tambah Faktur
                </a>
                @endif

                <div class="table-responsive">
                    <table id="dataTableExample" class="table table-hover align-middle display nowrap" style="width:100%">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>No. Faktur</th>
                                <th>Supplier</th>
                                <th>Total Tagihan</th>
                                <th>Proyek</th>
                                <th>Status Faktur</th>
                                <th>File</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($fakturs as $faktur)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($faktur->tanggal)->format('d/m/Y') }}</td>
                                <td class="fw-bold">{{ $faktur->no_faktur }}</td>
                                <td>{{ $faktur->nama_supplier }}</td>
                                <td>Rp {{ number_format($faktur->total, 0, ',', '.') }}</td>
                                <td>{{ $faktur->proyek->nama_proyek ?? '-' }}</td>
                                <td>
                                    @if($faktur->status == 'draft')
                                        <span class="badge bg-warning">Draft</span>
                                    @elseif($faktur->status == 'sedang diproses')
                                        <span class="badge bg-info text-white">Approved</span>
                                    @elseif($faktur->status == 'lunas')
                                        <span class="badge bg-success">Lunas</span>
                                    @else
                                        <span class="badge bg-secondary">{{ ucfirst($faktur->status) }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($faktur->file_path)
                                        <a href="{{ asset('storage/' . $faktur->file_path) }}" target="_blank" class="text-primary">
                                            <i data-feather="file-text" class="icon-sm"></i> PDF
                                        </a>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center">
                                        <a href="{{ route('faktur.show', $faktur->id) }}" class="btn btn-xs btn-outline-info me-1" title="Lihat Detail">
                                            <i data-feather="eye" class="icon-sm"></i>
                                        </a>

                                        @if($faktur->status == 'draft' && auth()->user()->hapus_faktur == 1)
                                            <form action="{{ route('faktur.destroy', $faktur->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus faktur ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-xs btn-outline-danger" title="Hapus">
                                                    <i data-feather="trash-2" class="icon-sm"></i>
                                                </button>
                                            </form>
                                            
                                        @elseif($faktur->status == 'sedang diproses')
                                            <a href="{{ route('pembayaran.create', $faktur->id) }}" class="btn btn-xs btn-success me-1 text-white" title="Bayar Sekarang">
                                                <i data-feather="dollar-sign" class="icon-sm"></i> Bayar
                                            </a>

                                            <form action="{{ route('faktur.revisi', $faktur->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Revisi faktur akan menghapus jurnal yang sudah tercatat. Lanjutkan?')">
                                                @csrf
                                                <button type="submit" class="btn btn-xs btn-warning text-white" title="Revisi">
                                                    <i data-feather="edit-3" class="icon-sm"></i>
                                                </button>
                                            </form>
                                        @endif
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
    if ($('#dataTableExample').length) {
      $('#dataTableExample').DataTable({
        responsive: true,
        autoWidth: false,
        order: [[0, 'desc']], // Urutkan berdasarkan tanggal terbaru
        language: {
          search: "_INPUT_",
          searchPlaceholder: "Cari Faktur...",
        }
      });
    }
  });
</script>
@endpush
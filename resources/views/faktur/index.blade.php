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
                <h4 class="card-title">Daftar Faktur</h4>

                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                @if(auth()->user()->buat_faktur == 1)
                <a href="{{ route('faktur.create') }}" class="btn btn-primary mb-3">
                    Tambah Faktur
                </a>

                @endif

                <table id="dataTableExample" class="table table-hover align-middle display nowrap" style="width:100%">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>No. Faktur</th>
                            <th>Supplier</th>
                            <th>Total</th>
                            <th>Proyek</th>
                            <th>Status</th>
                            <th>File Faktur</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($fakturs as $faktur)
                        <tr>
                            <td>{{ $faktur->tanggal }}</td>
                            <td>{{ $faktur->no_faktur }}</td>
                            <td>{{ $faktur->nama_supplier }}</td>
                            <td>Rp {{ number_format($faktur->total, 0, ',', '.') }}</td>
                            <td>{{ $faktur->proyek->nama_proyek ?? '-' }}</td>
                            <td>{{ ucfirst($faktur->status) }}</td>
                            <td>@if($faktur->file_path)
                                <a href="{{ asset('storage/' . $faktur->file_path) }}" target="_blank">Lihat PDF</a>
                            @endif</td>
                            <td>
                                <a href="{{ route('faktur.show', $faktur->id) }}" class="btn btn-sm btn-info me-2">
                                    <i class="btn-icon-prepend" data-feather="eye"></i> Preview
                                </a>

                                @if($faktur->status == 'draft' && auth()->user()->hapus_faktur == 1)
                                    <form action="{{ route('faktur.destroy', $faktur->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus faktur ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger btn-icon-text">
                                            <i class="btn-icon-prepend" data-feather="trash-2"></i> Hapus
                                        </button>
                                    </form>
                                @elseif($faktur->status == 'sedang diproses')
                                    <form action="{{ route('faktur.revisi', $faktur->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Revisi faktur akan menghapus jurnal yang sudah tercatat. Lanjutkan?')">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-warning btn-icon-text">
                                            <i class="btn-icon-prepend" data-feather="edit-3"></i> Revisi
                                        </button>
                                    </form>
                                @endif
                            </td>

                        </tr>
                        @endforeach
                    </tbody>
                </table>

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
    $('#dataTableExample').DataTable({
      responsive: true,
      autoWidth: false,
      order: [[0, 'desc']]
    });
  });
</script>
@endpush

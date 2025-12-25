@extends('layout.master')

@push('plugin-styles')
<link href="{{ asset('assets/plugins/datatables-net-bs5/dataTables.bootstrap5.css') }}" rel="stylesheet" />
@endpush

@section('content')
<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Daftar Penerimaan Pembelian</h4>
                
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif
                @if(session('warning'))
                    <div class="alert alert-warning">{{ session('warning') }}</div>
                @endif

                <table id="dataTableExample" class="table table-hover">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>No. Penerimaan</th>
                            <th>No. PO</th>
                            <th>Supplier</th>
                            <th>Proyek</th>
                            <th>No. Surat Jalan</th>
                            <th>Status</th>
                            <th>Status Penagihan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($penerimaans as $item)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($item->tanggal)->format('d/m/Y') }}</td>
                            <td>
                                <a href="{{ route('penerimaan.show', $item->id) }}">{{ $item->no_penerimaan }}</a>
                            </td>
                            <td>
                                @if($item->po?->file_path)
                                    <a href="{{ asset('storage/' . $item->po->file_path) }}" target="_blank">{{ $item->po->no_po }}</a>
                                @else
                                    {{ $item->po->no_po }}
                                @endif
                            </td>
                            <td>{{ $item->nama_supplier }}</td>
                            <td>{{ $item->proyek->nama_proyek ?? '-' }}</td>
                            <td>{{ $item->no_surat_jalan ?? '-' }}</td>
                            <td>
                                @if($item->status == 'draft')
                                    <span class="badge bg-secondary">Draft</span>
                                @else
                                    <span class="badge bg-success">Approved</span>
                                @endif
                            </td>
                            <td>
                                @switch($item->status_penagihan)
                                    @case('lunas')
                                        <span class="badge bg-success">Lunas</span>
                                        @break
                                    @case('sebagian')
                                        <span class="badge bg-warning text-dark">Sebagian</span>
                                        @break
                                    @default
                                        <span class="badge bg-secondary">Belum</span>
                                @endswitch
                            </td>
                            <td>
                                <a href="{{ route('penerimaan.show', $item->id) }}" class="btn btn-sm btn-info">Detail</a>
                                @if($item->status == 'draft')
                                    <form action="{{ route('penerimaan.approve', $item->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Approve penerimaan ini?')">Approve</button>
                                    </form>
                                @else
                                    <form action="{{ route('penerimaan.revisi', $item->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-warning" onclick="return confirm('Revisi penerimaan ini?')">Revisi</button>
                                    </form>
                                @endif
                                <a href="{{ route('retur.create', $item->id) }}" class="btn btn-sm btn-warning">Retur</a>
                                
                                @if($item->status == 'draft')
                                <form action="{{ route('penerimaan.destroy', $item->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" 
                                        onclick="return confirm('Yakin hapus penerimaan ini?')">Hapus</button>
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
@endpush

@push('custom-scripts')
<script>
$(document).ready(function() {
    $('#dataTableExample').DataTable({
        "order": [[0, "desc"]]
    });
});
</script>
@endpush

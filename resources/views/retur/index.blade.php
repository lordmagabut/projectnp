@extends('layout.master')

@push('plugin-styles')
<link href="{{ asset('assets/plugins/datatables-net-bs5/dataTables.bootstrap5.css') }}" rel="stylesheet" />
@endpush

@section('content')
<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Daftar Retur Pembelian</h4>
                
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif

                <table id="dataTableExample" class="table table-hover">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>No. Retur</th>
                            <th>No. Penerimaan</th>
                            <th>Supplier</th>
                            <th>Proyek</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($returs as $item)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($item->tanggal)->format('d/m/Y') }}</td>
                            <td>{{ $item->no_retur }}</td>
                            <td>{{ $item->penerimaan->no_penerimaan }}</td>
                            <td>{{ $item->nama_supplier }}</td>
                            <td>{{ $item->proyek->nama_proyek ?? '-' }}</td>
                            <td>
                                @if($item->status == 'draft')
                                    <span class="badge bg-secondary">Draft</span>
                                @else
                                    <span class="badge bg-success">Approved</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('retur.show', $item->id) }}" class="btn btn-sm btn-info">Detail</a>
                                
                                @if($item->status == 'draft')
                                    <form action="{{ route('retur.approve', $item->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success" 
                                            onclick="return confirm('Approve retur ini?')">Approve</button>
                                    </form>
                                @else
                                    <form action="{{ route('retur.revisi', $item->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-warning" 
                                            onclick="return confirm('Revisi retur ini? Jurnal & kredit akan dibalik.')">Revisi</button>
                                    </form>
                                @endif

                                <form action="{{ route('retur.destroy', $item->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" 
                                        onclick="return confirm('Yakin hapus retur ini? Jurnal & kredit akan dibalik.')">Hapus</button>
                                </form>
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

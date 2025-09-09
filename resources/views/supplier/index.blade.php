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
        <h4 class="card-title">Daftar Supplier</h4>
        @if(session('success'))
          <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(auth()->user()->buat_supplier == 1)
          <a href="{{ route('supplier.create') }}" class="btn btn-primary mb-3">Tambah Supplier</a>
        @endif
        <div class="table-responsive">
        <table id="dataTableExample" class="table">
            <thead>
              <tr>
                <th>No</th>
                <th>Nama Supplier</th>
                <th>PIC</th>
                <th>No Kontak</th>
                <th>Keterangan</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              @foreach($suppliers as $index => $supplier)
              <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $supplier->nama_supplier }}</td>
                <td>{{ $supplier->pic }}</td>
                <td>{{ $supplier->no_kontak }}</td>
                <td>{{ $supplier->keterangan }}</td>
                <td>
                @if(auth()->user()->edit_supplier == 1)
                  <a href="{{ route('supplier.edit', $supplier->id) }}" class="btn btn-sm btn-primary btn-icon-text me-2">
                            <i class="btn-icon-prepend" data-feather="edit"></i> Edit</a>
                @endif
                @if(auth()->user()->hapus_supplier == 1)
                <form action="{{ route('supplier.destroy', $supplier->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Yakin mau dihapus?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger btn-icon-text">
                                <i class="btn-icon-prepend" data-feather="delete"></i> Hapus
                            </button>
                </form>
                @endif
                </td>
              </tr>
              @endforeach
              @if($suppliers->isEmpty())
              <tr>
                <td colspan="6" class="text-center">Data tidak ditemukan</td>
              </tr>
              @endif
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
    $('#dataTableExample').DataTable({
      responsive: true,
      autoWidth: false
    });
  });
</script>
@endpush

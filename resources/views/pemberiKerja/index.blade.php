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
        <h4 class="card-title">Daftar Pemberi Kerja</h4>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if(auth()->user()->buat_pemberikerja == 1)
        <a href="{{ route('pemberiKerja.create') }}" class="btn btn-primary mb-3">Tambah Pemberi Kerja</a>
    @endif

    <table id="dataTableExample" class="table table-hover align-middle display nowrap" style="width:100%">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama</th>
                    <th>PIC</th>
                    <th>No Kontak</th>
                    <th>Alamat</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pemberiKerjas as $index => $pemberiKerja)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $pemberiKerja->nama_pemberi_kerja }}</td>
                        <td>{{ $pemberiKerja->pic }}</td>
                        <td>{{ $pemberiKerja->no_kontak }}</td>
                        <td>{{ $pemberiKerja->alamat }}</td>
                        <td class="text-nowrap">
                            @if(auth()->user()->edit_pemberikerja == 1)
                                <a href="{{ route('pemberiKerja.edit', $pemberiKerja->id) }}" class="btn btn-sm btn-primary btn-icon-text me-2">
                      <i class="btn-icon-prepend" data-feather="edit"></i> Edit</a>
                            @endif

                            @if(auth()->user()->hapus_pemberikerja == 1)
                                <form action="{{ route('pemberiKerja.destroy', $pemberiKerja->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Yakin mau dihapus?')">
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
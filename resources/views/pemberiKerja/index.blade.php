@extends('layout.master')

@push('plugin-styles')
<link href="{{ asset('assets/plugins/datatables-net-bs5/dataTables.bootstrap5.css') }}" rel="stylesheet" />
<link href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap5.min.css" rel="stylesheet" />
@endpush

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card shadow-sm">
      <div class="card-header d-flex align-items-center justify-content-between flex-wrap">
        <h5 class="mb-2 mb-md-0">Daftar Pemberi Kerja</h5>

        @if(auth()->user()->buat_pemberikerja == 1)
          <a href="{{ route('pemberiKerja.create') }}" class="btn btn-primary btn-sm">
            <i data-feather="plus" class="me-1"></i> Tambah Pemberi Kerja
          </a>
        @endif
      </div>

      <div class="card-body">
        @if(session('success'))
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        @endif

        <div class="table-responsive">
          <table id="pemberiKerjaTable" class="table table-hover align-middle nowrap w-100">
            <thead class="table-light">
              <tr>
                <th style="width: 60px;">No</th>
                <th>Nama</th>
                <th>PIC</th>
                <th>No Kontak</th>
                <th>Alamat</th>
                <th style="width: 180px;">Aksi</th>
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
                    <div class="d-flex gap-2">
                      @if(auth()->user()->edit_pemberikerja == 1)
                        <a href="{{ route('pemberiKerja.edit', $pemberiKerja->id) }}" class="btn btn-sm btn-outline-primary">
                          <i data-feather="edit" class="me-1"></i> Edit
                        </a>
                      @endif

                      @if(auth()->user()->hapus_pemberikerja == 1)
                        <form action="{{ route('pemberiKerja.destroy', $pemberiKerja->id) }}" method="POST" onsubmit="return confirm('Yakin mau dihapus?')">
                          @csrf
                          @method('DELETE')
                          <button type="submit" class="btn btn-sm btn-outline-danger">
                            <i data-feather="trash-2" class="me-1"></i> Hapus
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

      </div> {{-- card-body --}}
    </div> {{-- card --}}
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
  $(function () {
    const table = $('#pemberiKerjaTable').DataTable({
      responsive: true,
      autoWidth: false,
      pageLength: 10,
      lengthChange: true,
      ordering: true,
      columnDefs: [
        { targets: 0, orderable: false },           // No
        { targets: -1, orderable: false }           // Aksi
      ],
      language: {
        search: "Cari:",
        lengthMenu: "Tampil _MENU_ data",
        info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
        infoEmpty: "Tidak ada data",
        infoFiltered: "(disaring dari _MAX_ total data)",
        zeroRecords: "Data tidak ditemukan",
        paginate: {
          first: "Pertama", last: "Terakhir", next: "Berikutnya", previous: "Sebelumnya"
        }
      }
    });

    // Render ulang feather icons setiap draw/redraw
    table.on('draw.dt responsive-display', function () {
      if (window.feather) { feather.replace(); }
    });

    // Render awal feather icons
    if (window.feather) { feather.replace(); }
  });
</script>
@endpush

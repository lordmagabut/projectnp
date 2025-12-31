@extends('layout.master')

@push('plugin-styles')
<link href="{{ asset('assets/plugins/datatables-net-bs5/dataTables.bootstrap5.css') }}" rel="stylesheet" />
<link href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap5.min.css" rel="stylesheet" />
@endpush

@section('content')
<nav class="page-breadcrumb d-flex justify-content-between align-items-center flex-wrap">
  <ol class="breadcrumb mb-2 mb-md-0">
    <li class="breadcrumb-item"><a href="#">Sertifikat</a></li>
    <li class="breadcrumb-item active" aria-current="page">Daftar Sertifikat Pembayaran</li>
  </ol>
  <div class="d-flex gap-2">
    <a href="{{ route('sertifikat.create') }}" class="btn btn-primary btn-sm">Buat Sertifikat</a>
  </div>
</nav>

<div class="card">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
      <h5 class="mb-0">Daftar Sertifikat Pembayaran</h5>
      @if(session('success'))
        <span class="badge bg-success">{{ session('success') }}</span>
      @endif
    </div>

    <div class="table-responsive">
      <table class="table table-bordered table-striped" id="tbl-sertifikat-index">
        <thead class="table-light">
          <tr>
            <th class="text-center" style="width:60px">#</th>
            <th>No. Sertifikat</th>
            <th>Proyek</th>
            <th>Tanggal</th>
            <th>Termin</th>
            <th class="text-end">Tagihan</th>
            <th class="text-center" style="width:220px">Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse($list as $i => $row)
            <tr>
              <td class="text-center">{{ $list->firstItem() + $i }}</td>
              <td class="text-nowrap">{{ $row->nomor ?? '-' }}</td>
              <td>{{ $row->bapp->proyek->nama_proyek ?? '-' }}</td>
              <td>{{ optional($row->tanggal)->format('d/m/Y') }}</td>
              <td class="text-nowrap">Ke-{{ $row->termin_ke }}</td>
              <td class="text-end">Rp {{ number_format($row->total_tagihan, 0, ',', '.') }}</td>
              <td class="text-center">
                <a href="{{ route('sertifikat.show', $row->id) }}" class="btn btn-sm btn-outline-secondary me-1">Detail</a>
                <a href="{{ route('sertifikat.edit', $row->id) }}" class="btn btn-sm btn-outline-primary me-1">Edit</a>
                <a href="{{ route('sertifikat.create', ['bapp_id' => $row->bapp_id]) }}" class="btn btn-sm btn-outline-warning me-1">Revisi</a>
                <form action="{{ route('sertifikat.destroy', $row->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus sertifikat ini?');">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                </form>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center text-muted py-3">Belum ada sertifikat.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="mt-3">
      {{ $list->links() }}
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
  document.addEventListener('DOMContentLoaded', function(){
    const tbl = document.getElementById('tbl-sertifikat-index');
    if (tbl) {
      $(tbl).DataTable({
        responsive: true,
        order: [[0,'asc']],
        paging: false,
        info: false,
        searching: false
      });
    }
  });
</script>
@endpush

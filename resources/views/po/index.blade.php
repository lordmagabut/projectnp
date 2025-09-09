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
                                <h4 class="card-title">Daftar Pesanan Pembelian</h4>
                                @if(session('success'))
                                    <div class="alert alert-success">{{ session('success') }}</div>
                                @endif

                                @if(session('error'))
                                    <div class="alert alert-danger">{{ session('error') }}</div>
                                @endif
                                @if(auth()->user()->buat_po == 1)
                                <a href="{{ route('po.create') }}" class="btn btn-primary mb-3">Tambah PO</a>
                                @endif
                                <form method="GET" class="row g-3 mb-4">
                                    <div class="col-md-4">
                                        <label>Tahun</label>
                                        <select name="tahun" class="form-select"> 
                                            @foreach($tahunList as $thn)
                                                <option value="{{ $thn }}" {{ $tahun == $thn ? 'selected' : '' }}>{{ $thn }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2 d-grid">
                                        <label class="invisible">_</label>
                                        <button type="submit" class="btn btn-primary">Filter</button>
                                    </div> 
                                </form> 
                                <table id="dataTableExample" class="table table-hover align-middle display nowrap" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>Tanggal</th>
                                            <th>No. PO</th>
                                            <th>Supplier</th>
                                            <th>Total</th>
                                            <th>Proyek</th>
                                            <th>Keterangan</th>
                                            <th>Status</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($po as $item)
                                    <tr>
                                        <td>{{ $item->tanggal }}</td>
                                        <td>
                                            @if($item->file_path)
                                                <a href="{{ asset('storage/' . $item->file_path) }}" target="_blank">{{ $item->no_po }}</a>
                                            @else
                                                {{ $item->no_po }}
                                            @endif
                                        </td>
                                        <td>{{ $item->nama_supplier }}</td>
                                        <td>Rp {{ number_format($item->total, 0, ',', '.') }}</td>
                                        <td>{{ $item->proyek->nama_proyek ?? '-' }}</td>
                                        <td>{{ $item->keterangan }}</td>
                                        <td>{{ ucfirst($item->status) }}</td>
                                        <td>
                                            @if(auth()->user()->edit_po == 1)
                                            @if($item->status == 'draft')
                                            <a href="{{ route('po.edit', $item->id) }}" class="btn btn-sm btn-primary btn-icon-text me-2">
                                            <i class="btn-icon-prepend" data-feather="edit"></i>Edit</a>
                                            @endif
                                            @endif
                                            
                                            @if(auth()->user()->edit_po == 1 && $item->status == 'sedang diproses')
                                            <form action="{{ route('po.revisi', $item->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin merevisi PO ini?')">
                                                @csrf
                                                @method('PUT')
                                                <button type="submit" class="btn btn-sm btn-warning btn-icon-text me-2">
                                                    <i class="btn-icon-prepend" data-feather="refresh-ccw"></i>Revisi
                                                </button>
                                            </form>
                                            @endif
                                            {{-- Tombol Print jika draft --}}
                                            @if(auth()->user()->print_po == 1 && $item->status == 'draft')
                                                <a href="{{ route('po.print', $item->id) }}" class="btn btn-sm btn-primary btn-icon-text me-2">
                                                    <i class="btn-icon-prepend" data-feather="check-circle"></i>Setuju</a>
                                            @endif

                                            {{-- Tombol Buat Faktur jika sedang diproses --}}
                                            @if(auth()->user()->buat_faktur == 1 && $item->status == 'sedang diproses')
                                                <a href="{{ route('faktur.createFromPo', $item->id) }}" class="btn btn-sm btn-success btn-icon-text me-2">
                                                    <i class="btn-icon-prepend" data-feather="file-text"></i>Buat Faktur</a>
                                            @endif
                                            @if(auth()->user()->hapus_po == 1 && $item->status == 'draft')
                                                <form action="{{ route('po.destroy', $item->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger btn-icon-text">
                                                        <i class="btn-icon-prepend" data-feather="delete"></i>Hapus
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
    const table = $('#dataTableExample').DataTable({
      responsive: true,
      autoWidth: true,
      order: [[0, 'desc']],
      drawCallback: function(settings) {
        setTimeout(() => {
          feather.replace(); // Paksa feather update setelah draw selesai
        }, 2); // Delay agar DOM benar-benar dirender
      }
    });
    // Render icon saat pertama kali
    feather.replace();
  });
</script>
@endpush



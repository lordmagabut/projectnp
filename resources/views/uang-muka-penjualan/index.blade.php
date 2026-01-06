@extends('layout.master')

@push('plugin-styles')
<link href="{{ asset('assets/plugins/datatables-net-bs5/dataTables.bootstrap5.css') }}" rel="stylesheet" />
<link href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap5.min.css" rel="stylesheet" />
@endpush

@section('content')
<nav class="page-breadcrumb d-flex justify-content-between align-items-center flex-wrap">
  <ol class="breadcrumb mb-2 mb-md-0">
    <li class="breadcrumb-item"><a href="#">Penjualan</a></li>
    <li class="breadcrumb-item active" aria-current="page">Uang Muka Penjualan</li>
  </ol>
  <div class="d-flex gap-2">
    <a href="{{ route('uang-muka-penjualan.create') }}" class="btn btn-primary btn-sm">Buat Uang Muka</a>
  </div>
</nav>

<div class="card">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
      <h5 class="mb-0">Daftar Uang Muka Penjualan</h5>
      @if(session('success'))
        <span class="badge bg-success">{{ session('success') }}</span>
      @endif
    </div>

    <div class="row mb-3">
      <div class="col-md-6">
        <form action="{{ route('uang-muka-penjualan.index') }}" method="GET" class="d-flex gap-2">
          <select class="form-select form-select-sm" name="proyek_id" style="max-width: 200px;">
            <option value="">-- Semua Proyek --</option>
            @foreach($proyeks as $p)
              <option value="{{ $p->id }}" {{ request('proyek_id') == $p->id ? 'selected' : '' }}>
                {{ $p->nama_proyek }}
              </option>
            @endforeach
          </select>
          <select class="form-select form-select-sm" name="status" style="max-width: 150px;">
            <option value="">-- Semua Status --</option>
            <option value="diterima" {{ request('status') == 'diterima' ? 'selected' : '' }}>Diterima</option>
            <option value="sebagian" {{ request('status') == 'sebagian' ? 'selected' : '' }}>Sebagian</option>
            <option value="lunas" {{ request('status') == 'lunas' ? 'selected' : '' }}>Lunas</option>
          </select>
          <button type="submit" class="btn btn-outline-primary btn-sm">Filter</button>
          <a href="{{ route('uang-muka-penjualan.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
        </form>
      </div>
    </div>

    <div class="table-responsive">
      <table class="table table-bordered table-striped" id="tbl-um-penjualan-index">
        <thead class="table-light">
          <tr>
            <th class="text-center" style="width:60px">#</th>
            <th>Nomor Bukti</th>
            <th>Proyek</th>
            <th>Tanggal</th>
            <th class="text-end">Nominal</th>
            <th class="text-end">Digunakan</th>
            <th class="text-end">Sisa</th>
            <th class="text-center">Status</th>
            <th class="text-center">Pembayaran</th>
            <th class="text-center" style="width:220px">Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse($list as $i => $row)
            <tr>
              <td class="text-center">{{ $list->firstItem() + $i }}</td>
              <td class="text-nowrap">{{ $row->nomor_bukti ?? '-' }}</td>
              <td>{{ $row->proyek->nama_proyek ?? '-' }}</td>
              <td>{{ optional($row->tanggal)->format('d/m/Y') }}</td>
              <td class="text-end">Rp {{ number_format($row->nominal, 0, ',', '.') }}</td>
              <td class="text-end">Rp {{ number_format($row->nominal_digunakan, 0, ',', '.') }}</td>
              <td class="text-end">Rp {{ number_format($row->getSisaUangMuka(), 0, ',', '.') }}</td>
              <td class="text-center">
                @if($row->status == 'diterima')
                  <span class="badge bg-success">Diterima</span>
                @elseif($row->status == 'sebagian')
                  <span class="badge bg-warning">Sebagian</span>
                @else
                  <span class="badge bg-info">Lunas</span>
                @endif
              </td>
              <td class="text-center">
                @if($row->payment_status == 'dibayar')
                  <span class="badge bg-success"><i class="fas fa-check me-1"></i> Dibayar</span>
                @else
                  <span class="badge bg-warning"><i class="fas fa-clock me-1"></i> Belum Bayar</span>
                @endif
              </td>
              <td class="text-center">
                <a href="{{ route('uang-muka-penjualan.show', $row->id) }}" class="btn btn-sm btn-outline-secondary me-1">Detail</a>
                @if($row->payment_status === 'belum_dibayar')
                  <a href="{{ route('uang-muka-penjualan.pay', $row->id) }}" class="btn btn-sm btn-outline-success">Bayar</a>
                @endif
                <form action="{{ route('uang-muka-penjualan.destroy', $row->id) }}" method="POST" style="display:inline;">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="btn btn-sm btn-outline-danger" {{ $row->nominal_digunakan > 0 ? 'disabled' : '' }} onclick="return confirm('Hapus UM ini?')">Hapus</button>
                </form>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="9" class="text-center text-muted py-3">Belum ada uang muka penjualan.</td>
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
@endpush

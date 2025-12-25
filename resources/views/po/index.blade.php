@extends('layout.master')

@push('plugin-styles')
<link href="{{ asset('assets/plugins/datatables-net-bs5/dataTables.bootstrap5.css') }}" rel="stylesheet" />
<link href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap5.min.css" rel="stylesheet" />
@endpush

@section('content')
<nav class="page-breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ url('/') }}">Pembelian</a></li>
    <li class="breadcrumb-item active" aria-current="page">Pesanan Pembelian</li>
  </ol>
</nav>

<div class="row">
  <div class="col-lg-12 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h6 class="card-title mb-0">Daftar Pesanan Pembelian</h6>
          @if(auth()->user()->buat_po == 1)
          <a href="{{ route('po.create') }}" class="btn btn-primary btn-sm">
            <i class="link-icon" data-feather="plus"></i> Tambah PO
          </a>
          @endif
        </div>

        @if(session('success'))
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="link-icon" data-feather="check-circle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        @endif

        @if(session('error'))
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="link-icon" data-feather="alert-circle"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        @endif

        <!-- Filter Form -->
        <form method="GET" class="row g-3 mb-4">
          <div class="col-md-4">
            <label class="form-label">Tahun</label>
            <select name="tahun" class="form-select" onchange="this.form.submit()">
              @foreach($tahunList as $thn)
                <option value="{{ $thn }}" {{ $tahun == $thn ? 'selected' : '' }}>{{ $thn }}</option>
              @endforeach
            </select>
          </div>
        </form>

        <!-- Table -->
        <div class="table-responsive">
          <table id="poTable" class="table table-bordered table-hover">
            <thead class="table-light">
              <tr>
                <th>Tanggal</th>
                <th>No. PO</th>
                <th>Supplier</th>
                <th class="text-end">Total</th>
                <th>Proyek</th>
                <th>Status</th>
                <th width="300">Aksi</th>
              </tr>
            </thead>
            <tbody>
              @forelse($po as $item)
              <tr>
                <td>{{ \Carbon\Carbon::parse($item->tanggal)->format('d/m/Y') }}</td>
                <td>
                  @if($item->file_path)
                    <a href="{{ asset('storage/' . $item->file_path) }}" target="_blank" class="text-primary fw-bold">
                      {{ $item->no_po }}
                    </a>
                  @else
                    <span class="fw-bold">{{ $item->no_po }}</span>
                  @endif
                </td>
                <td>{{ $item->nama_supplier }}</td>
                <td class="text-end">
                  <strong>Rp {{ number_format($item->total, 0, ',', '.') }}</strong>
                </td>
                <td>{{ $item->proyek->nama_proyek ?? '-' }}</td>
                <td>
                  @if($item->status == 'draft')
                    <span class="badge bg-secondary">Draft</span>
                  @elseif($item->status == 'sedang diproses')
                    <span class="badge bg-warning">Sedang Diproses</span>
                  @elseif($item->status == 'selesai')
                    <span class="badge bg-success">Selesai</span>
                  @else
                    <span class="badge bg-light text-dark">{{ ucfirst($item->status) }}</span>
                  @endif
                </td>
                <td>
                  <div class="btn-group btn-group-sm" role="group">
                    {{-- Edit PO (hanya draft & belum ada penerimaan) --}}
                    @if(auth()->user()->edit_po == 1 && $item->status == 'draft')
                      @if(!$item->penerimaans()->exists())
                        <a href="{{ route('po.edit', $item->id) }}" class="btn btn-outline-primary" title="Edit">
                          <i class="link-icon" data-feather="edit"></i>
                        </a>
                      @else
                        <button class="btn btn-outline-secondary" disabled title="Tidak bisa edit: sudah ada penerimaan">
                          <i class="link-icon" data-feather="edit"></i>
                        </button>
                      @endif
                    @endif

                    {{-- Revisi PO (sedang diproses & belum ada penerimaan) --}}
                    @if(auth()->user()->edit_po == 1 && $item->status == 'sedang diproses')
                      @if(!$item->penerimaans()->exists())
                        <form action="{{ route('po.revisi', $item->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin merevisi PO ini?')">
                          @csrf
                          @method('PUT')
                          <button type="submit" class="btn btn-outline-warning" title="Revisi">
                            <i class="link-icon" data-feather="refresh-ccw"></i>
                          </button>
                        </form>
                      @else
                        <button class="btn btn-outline-secondary" disabled title="Tidak bisa revisi: sudah ada penerimaan">
                          <i class="link-icon" data-feather="refresh-ccw"></i>
                        </button>
                      @endif
                    @endif

                    {{-- Print/Setuju PO (draft) --}}
                    @if(auth()->user()->print_po == 1 && $item->status == 'draft')
                      <a href="{{ route('po.print', $item->id) }}" class="btn btn-outline-success" title="Setuju / Print">
                        <i class="link-icon" data-feather="check-circle"></i>
                      </a>
                    @endif

                    {{-- Terima Barang (sedang diproses) --}}
                    @if($item->status == 'sedang diproses')
                      <a href="{{ route('penerimaan.create', $item->id) }}" class="btn btn-outline-info" title="Terima Barang">
                        <i class="link-icon" data-feather="package"></i>
                      </a>
                    @endif

                    {{-- Buat Faktur (sedang diproses & ada penerimaan approved) --}}
                    @if(auth()->user()->buat_faktur == 1 && $item->status == 'sedang diproses')
                      @php
                        $adaYangBisaDifaktur = $item->poDetails->some(function($detail) {
                          $qtyApproved = \App\Models\PenerimaanPembelianDetail::where('po_detail_id', $detail->id)
                            ->whereHas('penerimaan', function($q){ $q->where('status','approved'); })
                            ->sum('qty_diterima');
                          $qtyReturApproved = \App\Models\ReturPembelianDetail::whereHas('retur', function($q){ $q->where('status','approved'); })
                            ->whereHas('penerimaanDetail', function($q) use ($detail){ $q->where('po_detail_id', $detail->id); })
                            ->sum('qty_retur');
                          $netApproved = max(0, $qtyApproved - $qtyReturApproved);
                          return ($netApproved - $detail->qty_terfaktur) > 0;
                        });
                      @endphp
                      @if($adaYangBisaDifaktur)
                        <a href="{{ route('faktur.createFromPo', $item->id) }}" class="btn btn-outline-success" title="Buat Faktur">
                          <i class="link-icon" data-feather="file-text"></i>
                        </a>
                      @else
                        <button class="btn btn-outline-secondary" disabled title="Belum ada penerimaan approved atau semua sudah difakturkan">
                          <i class="link-icon" data-feather="file-text"></i>
                        </button>
                      @endif
                    @endif

                    {{-- Hapus PO (draft & belum ada penerimaan) --}}
                    @if(auth()->user()->hapus_po == 1 && $item->status == 'draft')
                      @if(!$item->penerimaans()->exists())
                        <form action="{{ route('po.destroy', $item->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus?')">
                          @csrf
                          @method('DELETE')
                          <button type="submit" class="btn btn-outline-danger" title="Hapus">
                            <i class="link-icon" data-feather="trash-2"></i>
                          </button>
                        </form>
                      @else
                        <button class="btn btn-outline-secondary" disabled title="Tidak bisa hapus: sudah ada penerimaan">
                          <i class="link-icon" data-feather="trash-2"></i>
                        </button>
                      @endif
                    @endif
                  </div>
                </td>
              </tr>
              @empty
              <tr>
                <td colspan="7" class="text-center text-muted">Belum ada Pesanan Pembelian</td>
              </tr>
              @endforelse
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
  $(function() {
    'use strict';

    if ($('#poTable').length) {
      $('#poTable').DataTable({
        "aLengthMenu": [[10, 30, 50, -1], [10, 30, 50, "All"]],
        "iDisplayLength": 20,
        "order": [[0, "desc"]],
        "language": { search: "Cari:" },
        "dom": 'Bfrtip',
        "responsive": true,
        "drawCallback": function() {
          if (feather) {
            feather.replace();
          }
        }
      });
    }

    if (feather) {
      feather.replace();
    }
  });
</script>
@endpush



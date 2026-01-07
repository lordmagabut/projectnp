@extends('layout.master')

@push('plugin-styles')
<link href="{{ asset('assets/plugins/datatables-net-bs5/dataTables.bootstrap5.css') }}" rel="stylesheet" />
<link href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap5.min.css" rel="stylesheet" />
<style>
    /* Mencegah Scrollbar muncul saat Dropdown diklik */
    .table-responsive {
        overflow: visible !important;
    }
    
    /* Mempercantik Baris Tabel */
    .table > :not(caption) > * > * {
        padding: 15px 10px;
        vertical-align: middle;
    }

    /* Styling Badge Modern (Soft Colors) */
    .badge-soft {
        padding: 6px 12px;
        border-radius: 6px;
        font-weight: 600;
        font-size: 11px;
        text-transform: uppercase;
    }
    .badge-soft-secondary { background-color: #f1f2f4; color: #6c757d; border: 1px solid #dee2e6; }
    .badge-soft-warning { background-color: #fff8e6; color: #856404; border: 1px solid #ffeeba; }
    .badge-soft-success { background-color: #e6fffa; color: #087a5b; border: 1px solid #b2f5ea; }
    .badge-soft-info { background-color: #e0f2ff; color: #004a99; border: 1px solid #b3d7ff; }

    /* Custom Dropdown Style */
    .dropdown-menu {
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        border: 1px solid #edf2f9;
        padding: 8px;
        min-width: 200px;
    }
    .dropdown-item {
        border-radius: 4px;
        padding: 8px 12px;
        font-size: 13px;
        display: flex;
        align-items: center;
        transition: all 0.2s;
    }
    .dropdown-item:hover {
        background-color: #f8f9fa;
        transform: translateX(3px);
    }
    .dropdown-item i {
        margin-right: 10px;
        width: 16px;
        height: 16px;
    }
    .text-po { letter-spacing: 0.5px; }
</style>
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
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h6 class="card-title mb-0">Daftar Pesanan Pembelian</h6>
          @can('create po')
          <a href="{{ route('po.create') }}" class="btn btn-primary">
            <i class="link-icon me-1" data-feather="plus-circle"></i> Tambah PO
          </a>
          @endcan
        </div>

        @if(session('success'))
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="me-2" data-feather="check-circle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        @endif

        <div class="row mb-4">
            <div class="col-md-3">
                <form method="GET">
                  <label class="form-label fw-bold small">Pilih Tahun</label>
                  <select name="tahun" class="form-select form-select-sm" onchange="this.form.submit()">
                    @foreach($tahunList as $thn)
                      <option value="{{ $thn }}" {{ $tahun == $thn ? 'selected' : '' }}>{{ $thn }}</option>
                    @endforeach
                  </select>
                </form>
            </div>
        </div>

        <div class="table-responsive">
          <table id="poTable" class="table table-hover">
            <thead>
              <tr class="bg-light">
                <th>Tanggal</th>
                <th>No. PO</th>
                <th>Supplier</th>
                <th class="text-end">Total</th>
                <th>Proyek</th>
                <th class="text-center">Status</th>
                <th class="text-center">Aksi</th>
              </tr>
            </thead>
            <tbody>
              @forelse($po as $item)
              <tr>
                <td class="small">{{ \Carbon\Carbon::parse($item->tanggal)->format('d/m/Y') }}</td>
                <td>
                  @if($item->file_path)
                    <a href="{{ asset('storage/' . $item->file_path) }}" target="_blank" class="text-primary fw-bold text-po">
                      {{ $item->no_po }} <i data-feather="external-link" class="icon-sm ms-1"></i>
                    </a>
                  @else
                    <span class="fw-bold text-dark text-po">{{ $item->no_po }}</span>
                  @endif
                </td>
                <td>{{ $item->nama_supplier }}</td>
                <td class="text-end fw-bold">
                  Rp {{ number_format($item->total, 0, ',', '.') }}
                </td>
                <td><span class="text-muted small">{{ $item->proyek->nama_proyek ?? '-' }}</span></td>
                <td class="text-center">
                  @if($item->status == 'draft')
                    <span class="badge-soft badge-soft-secondary">Draft</span>
                  @elseif($item->status == 'reviewed')
                    <span class="badge-soft badge-soft-info">Reviewed</span>
                  @elseif($item->status == 'sedang diproses')
                    <span class="badge-soft badge-soft-warning">Proses</span>
                  @elseif($item->status == 'selesai')
                    <span class="badge-soft badge-soft-success">Selesai</span>
                  @else
                    <span class="badge-soft badge-soft-info">{{ $item->status }}</span>
                  @endif
                </td>
                <td class="text-center">
                  <div class="dropdown">
                    <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" 
                            data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false">
                      Aksi
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow">
                      {{-- Lihat Detail --}}
                      <li>
                        <a class="dropdown-item" href="{{ route('po.show', $item->id) }}">
                          <i data-feather="eye" class="text-primary"></i> Lihat Detail
                        </a>
                      </li>

                      {{-- Edit (Hanya Draft) --}}
                      @can('edit po')
                      @if($item->status == 'draft' && !$item->penerimaans()->exists())
                      <li>
                        <a class="dropdown-item" href="{{ route('po.edit', $item->id) }}">
                          <i data-feather="edit" class="text-info"></i> Edit Pesanan
                        </a>
                      </li>
                      @endif
                      @endcan

                      {{-- Revisi (Sedang Diproses) --}}
                      @can('edit po')
                      @if($item->status == 'sedang diproses' && !$item->penerimaans()->exists())
                      <li>
                        <form action="{{ route('po.revisi', $item->id) }}" method="POST" id="revisi-{{ $item->id }}">
                          @csrf @method('PUT')
                          <button type="button" class="dropdown-item" onclick="if(confirm('Revisi PO ini?')) document.getElementById('revisi-{{ $item->id }}').submit()">
                            <i data-feather="refresh-ccw" class="text-warning"></i> Revisi Pesanan
                          </button>
                        </form>
                      </li>
                      @endif
                      @endcan

                      {{-- Review (Hanya Draft) --}}
                      @can('review po')
                      @if($item->status == 'draft')
                      <li>
                        <form action="{{ route('po.review', $item->id) }}" method="POST" id="review-{{ $item->id }}">
                          @csrf
                          <button type="button" class="dropdown-item" onclick="if(confirm('Review PO ini?')) document.getElementById('review-{{ $item->id }}').submit()">
                            <i data-feather="eye"></i> Review OK
                          </button>
                        </form>
                      </li>
                      @endif
                      @endcan

                      {{-- Setuju / Print (Setelah Review) --}}
                      @can('approve po')
                      @if($item->status == 'reviewed')
                      <li>
                        <a class="dropdown-item text-success" href="{{ route('po.print', $item->id) }}">
                          <i data-feather="check-square"></i> Setujui & Cetak
                        </a>
                      </li>
                      @endif
                      @endcan

                      <div class="dropdown-divider"></div>

                      {{-- Uang Muka --}}
                      @if($item->status == 'sedang diproses' && !$item->penerimaans()->exists())
                      <li>
                        <a class="dropdown-item" href="{{ route('uang-muka-pembelian.create', ['po_id' => $item->id]) }}">
                          <i data-feather="credit-card" class="text-muted"></i> Buat Uang Muka
                        </a>
                      </li>
                      @endif

                      {{-- Terima Barang --}}
                      @if($item->status == 'sedang diproses')
                      <li>
                        <a class="dropdown-item" href="{{ route('penerimaan.create', $item->id) }}">
                          <i data-feather="package" class="text-info"></i> Terima Barang
                        </a>
                      </li>
                      @endif

                      {{-- Buat Faktur --}}
                      @if(auth()->user()->buat_faktur == 1 && $item->status == 'sedang diproses')
                        @php
                          $adaYangBisaDifaktur = $item->poDetails->some(function($detail) {
                            $qtyApproved = \App\Models\PenerimaanPembelianDetail::where('po_detail_id', $detail->id)
                              ->whereHas('penerimaan', function($q){ $q->where('status','approved'); })->sum('qty_diterima');
                            $qtyReturApproved = \App\Models\ReturPembelianDetail::whereHas('retur', function($q){ $q->where('status','approved'); })
                              ->whereHas('penerimaanDetail', function($q) use ($detail){ $q->where('po_detail_id', $detail->id); })->sum('qty_retur');
                            return (max(0, $qtyApproved - $qtyReturApproved) - $detail->qty_terfaktur) > 0;
                          });
                        @endphp
                        @if($adaYangBisaDifaktur)
                        <li>
                          <a class="dropdown-item" href="{{ route('faktur.createFromPo', $item->id) }}">
                            <i data-feather="file-text" class="text-success"></i> Buat Faktur
                          </a>
                        </li>
                        @endif
                      @endif

                      {{-- Hapus (Hanya Draft) --}}
                      @can('delete po')
                      @if($item->status == 'draft' && !$item->penerimaans()->exists())
                      <li><hr class="dropdown-divider"></li>
                      <li>
                        <form action="{{ route('po.destroy', $item->id) }}" method="POST" id="delete-{{ $item->id }}">
                          @csrf @method('DELETE')
                          <button type="button" class="dropdown-item text-danger" onclick="if(confirm('Hapus PO ini?')) document.getElementById('delete-{{ $item->id }}').submit()">
                            <i data-feather="trash-2"></i> Hapus Pesanan
                          </button>
                        </form>
                      </li>
                      @endif
                      @endcan
                    </ul>
                  </div>
                </td>
              </tr>
              @empty
              <tr>
                <td colspan="7" class="text-center py-5 text-muted">Belum ada data Pesanan Pembelian</td>
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
        "aLengthMenu": [[10, 30, 50, -1], [10, 30, 50, "Semua"]],
        "iDisplayLength": 20,
        "order": [[0, "desc"]],
        "language": { 
            search: "",
            searchPlaceholder: "Cari nomor PO atau supplier...",
        },
        "drawCallback": function() {
          if (typeof feather !== 'undefined') {
            feather.replace();
          }
        }
      });
    }
  });
</script>
@endpush
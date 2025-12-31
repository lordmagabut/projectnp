@extends('layout.master')

@section('content')
<nav class="page-breadcrumb d-print-none">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="#">Penjualan</a></li>
    <li class="breadcrumb-item active" aria-current="page">Daftar SO</li>
  </ol>
</nav>

<div class="row">
  <div class="col-md-12 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h4 class="card-title">Daftar Sales Order (Berbasis Penawaran yang Disetujui)</h4>
          <form class="d-flex" method="GET">
            <input name="q" class="form-control form-control-sm me-2" placeholder="Cari penawaran atau proyek" value="{{ request('q') }}">
            <button class="btn btn-primary btn-sm" type="submit">Cari</button>
          </form>
        </div>

        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead>
              <tr>
                <th>No</th>
                <th>Nama Penawaran / SO</th>
                <th>Proyek</th>
                <th>Tanggal Disetujui</th>
                <th>Total</th>
                <th class="text-center">Aksi</th>
              </tr>
            </thead>
            <tbody>
              @forelse($penawarans as $i => $p)
              <tr>
                <td>{{ $penawarans->firstItem() + $i }}</td>
                <td>{{ $p->nama_penawaran }} <div class="text-muted small">Versi: {{ $p->versi ?? '-' }}</div></td>
                <td>{{ $p->proyek->nama_proyek ?? '-' }}</td>
                <td>{{ optional($p->approved_at)->format('d/m/Y H:i') ?? '-' }}</td>
                <td>Rp {{ number_format($p->final_total_penawaran ?? 0,0,',','.') }}</td>
                <td class="text-center">
                  @if(!empty($p->salesOrder))
                    <a href="{{ route('so.show', $p->salesOrder->id) }}" class="btn btn-xs btn-outline-info me-1" title="Detail SO">
                      <i data-feather="eye" class="icon-sm"></i>
                    </a>
                    @if(!$p->salesOrder->uangMuka)
                      <a href="{{ route('uang-muka-penjualan.create', ['sales_order_id' => $p->salesOrder->id]) }}" class="btn btn-xs btn-outline-success me-1" title="Buat UM Penjualan">
                        <i data-feather="plus" class="icon-sm"></i> UM
                      </a>
                    @else
                      <a href="{{ route('uang-muka-penjualan.show', $p->salesOrder->uangMuka->id) }}" class="btn btn-xs btn-outline-warning me-1" title="Lihat UM Penjualan">
                        <i data-feather="eye" class="icon-sm"></i> UM
                      </a>
                    @endif
                  @else
                    <a href="{{ route('proyek.penawaran.show', ['proyek' => $p->proyek_id, 'penawaran' => $p->id]) }}" class="btn btn-xs btn-outline-secondary me-1" title="Detail Penawaran">
                      <i data-feather="file-text" class="icon-sm"></i>
                    </a>
                  @endif

                  {{-- Link langsung ke Penawaran (selalu tersedia) --}}
                  <a href="{{ route('proyek.penawaran.show', ['proyek' => $p->proyek_id, 'penawaran' => $p->id]) }}" class="btn btn-xs btn-outline-primary" title="Buka Penawaran">
                    <i data-feather="book-open" class="icon-sm"></i>
                  </a>
                </td>
              </tr>
              @empty
              <tr><td colspan="6" class="text-center text-muted">Belum ada Sales Order (penawaran disetujui).</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>

        <div class="mt-3">{{ $penawarans->links() }}</div>
      </div>
    </div>
  </div>
</div>
@endsection

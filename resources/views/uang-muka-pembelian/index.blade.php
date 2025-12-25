@extends('layout.master')

@section('content')
<nav class="page-breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ url('/') }}">Pembelian</a></li>
    <li class="breadcrumb-item active" aria-current="page">Uang Muka Pembelian</li>
  </ol>
</nav>

<div class="row">
  <div class="col-lg-12 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h4 class="card-title mb-0">Data Uang Muka Pembelian</h4>
          <a href="{{ route('uang-muka-pembelian.create') }}" class="btn btn-success">
            <i data-feather="plus"></i> Buat Uang Muka
          </a>
        </div>

        <!-- Filter -->
        <form method="GET" class="mb-4">
          <div class="row g-3">
            <div class="col-md-3">
              <select name="status" class="form-select">
                <option value="">-- Semua Status --</option>
                <option value="draft" @selected(request('status') == 'draft')>Draft</option>
                <option value="approved" @selected(request('status') == 'approved')>Approved</option>
              </select>
            </div>
            <div class="col-md-3">
              <select name="payment_status" class="form-select">
                <option value="">-- Status Pembayaran --</option>
                <option value="unpaid" @selected(request('payment_status') == 'unpaid')>Belum Dibayar</option>
                <option value="paid" @selected(request('payment_status') == 'paid')>Sudah Dibayar</option>
              </select>
            </div>
            <div class="col-md-3">
              <select name="id_perusahaan" class="form-select">
                <option value="">-- Semua Perusahaan --</option>
                @foreach($perusahaans as $p)
                  <option value="{{ $p->id }}" @selected(request('id_perusahaan') == $p->id)>{{ $p->nama_perusahaan }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-2">
              <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
          </div>
        </form>

        <div class="table-responsive">
          <table class="table table-striped">
            <thead>
              <tr>
                <th>No. UM</th>
                <th>Tanggal</th>
                <th>PO</th>
                <th>Supplier</th>
                <th class="text-end">Nominal</th>
                <th class="text-end">Digunakan</th>
                <th class="text-end">Sisa</th>
                <th>Status</th>
                <th>Pembayaran</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              @forelse($uangMukas as $um)
                @php
                  $isPaid = $um->status === 'approved' && \App\Models\Jurnal::where('ref_table', 'uang_muka_pembelian')->where('ref_id', $um->id)->exists();
                @endphp
                <tr>
                  <td><strong>{{ $um->no_uang_muka }}</strong></td>
                  <td>{{ $um->tanggal->format('d/m/Y') }}</td>
                  <td>{{ $um->po->no_po }}</td>
                  <td>{{ $um->nama_supplier }}</td>
                  <td class="text-end">Rp {{ number_format($um->nominal, 0, ',', '.') }}</td>
                  <td class="text-end">Rp {{ number_format($um->nominal_digunakan, 0, ',', '.') }}</td>
                  <td class="text-end">Rp {{ number_format($um->sisa_uang_muka, 0, ',', '.') }}</td>
                  <td>
                    @if($um->status == 'draft')
                      <span class="badge bg-secondary">Draft</span>
                    @else
                      <span class="badge bg-success">Approved</span>
                    @endif
                  </td>
                  <td>
                    @if($um->status === 'draft')
                      <span class="badge bg-light text-dark">-</span>
                    @elseif($isPaid)
                      <span class="badge bg-primary">Sudah Dibayar</span>
                    @else
                      <span class="badge bg-warning text-dark">Belum Dibayar</span>
                    @endif
                  </td>
                  <td>
                    <div class="d-flex">
                      <a href="{{ route('uang-muka-pembelian.show', $um->id) }}" class="btn btn-sm btn-info me-1" title="Lihat">
                        <i data-feather="eye"></i>
                      </a>
                      @if($um->status == 'draft')
                        <a href="{{ route('uang-muka-pembelian.edit', $um->id) }}" class="btn btn-sm btn-warning me-1" title="Edit">
                          <i data-feather="edit"></i>
                        </a>
                        <form action="{{ route('uang-muka-pembelian.destroy', $um->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus Uang Muka ini?')">
                          @csrf
                          @method('DELETE')
                          <button type="submit" class="btn btn-sm btn-danger" title="Hapus">
                            <i data-feather="trash-2"></i>
                          </button>
                        </form>
                      @elseif($um->status === 'approved' && !$isPaid)
                        <a href="{{ route('uang-muka-pembelian.bkk.create', $um->id) }}" class="btn btn-sm btn-success text-white me-1" title="Bayar via BKK">
                          <i data-feather="credit-card"></i>
                        </a>
                        @if($um->nominal_digunakan == 0)
                        <form action="{{ route('uang-muka-pembelian.revisi', $um->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Revisi akan mengubah status UM kembali ke Draft. Lanjutkan?')">
                          @csrf
                          <button type="submit" class="btn btn-sm btn-warning" title="Revisi">
                            <i data-feather="edit-3"></i>
                          </button>
                        </form>
                        @endif
                      @elseif($isPaid)
                        <a href="{{ route('uang-muka-pembelian.bkk', $um->id) }}" class="btn btn-sm btn-secondary me-1" title="Cetak BKK">
                          <i data-feather="printer"></i>
                        </a>
                        <a href="{{ route('uang-muka-pembelian.edit-paid', $um->id) }}" class="btn btn-sm btn-info me-1" title="Edit Detail">
                          <i data-feather="edit"></i>
                        </a>
                        @if($um->nominal_digunakan == 0)
                        <form action="{{ route('uang-muka-pembelian.cancel-payment', $um->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Batalkan pembayaran akan menghapus jurnal BKK. Lanjutkan?')">
                          @csrf
                          <button type="submit" class="btn btn-sm btn-danger" title="Batalkan Pembayaran">
                            <i data-feather="x-circle"></i>
                          </button>
                        </form>
                        @endif
                      @endif
                    </div>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="10" class="text-center py-4">Data tidak ditemukan</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        @if($uangMukas->hasPages())
          <nav aria-label="Page navigation">
            {{ $uangMukas->links() }}
          </nav>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection

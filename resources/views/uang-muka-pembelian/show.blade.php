@extends('layout.master')

@section('content')
<nav class="page-breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ url('/') }}">Pembelian</a></li>
    <li class="breadcrumb-item"><a href="{{ route('uang-muka-pembelian.index') }}">Uang Muka Pembelian</a></li>
    <li class="breadcrumb-item active" aria-current="page">Detail</li>
  </ol>
</nav>

<div class="row">
  <div class="col-lg-12 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h4 class="card-title mb-0">{{ $uangMuka->no_uang_muka }}</h4>
          <div class="d-flex gap-2">
            @if($uangMuka->status === 'approved')
              @if($isPaid)
                <a href="{{ route('uang-muka-pembelian.bkk', $uangMuka->id) }}" class="btn btn-info text-white">
                  <i data-feather="printer" class="icon-sm"></i> Cetak BKK
                </a>
              @else
                <a href="{{ route('uang-muka-pembelian.bkk.create', $uangMuka->id) }}" class="btn btn-success text-white">
                  <i data-feather="credit-card" class="icon-sm"></i> Bayar via BKK
                </a>
              @endif
            @endif
            <a href="{{ route('uang-muka-pembelian.index') }}" class="btn btn-secondary">Kembali</a>
          </div>
        </div>

        <div class="row mb-4">
          <div class="col-md-6">
            <table class="table table-borderless">
              <tr>
                <td width="200"><strong>No. UM</strong></td>
                <td>: {{ $uangMuka->no_uang_muka }}</td>
              </tr>
              <tr>
                <td><strong>Tanggal</strong></td>
                <td>: {{ $uangMuka->tanggal->format('d/m/Y') }}</td>
              </tr>
              <tr>
                <td><strong>No. PO</strong></td>
                <td>: {{ $uangMuka->po->no_po }}</td>
              </tr>
              <tr>
                <td><strong>Status</strong></td>
                <td>:
                  @if($uangMuka->status == 'draft')
                    <span class="badge bg-secondary">Draft</span>
                  @else
                    <span class="badge bg-success">Approved</span>
                  @endif
                  @if($uangMuka->status === 'approved')
                    @if($isPaid)
                      <span class="badge bg-primary ms-1">Sudah Dibayar</span>
                    @else
                      <span class="badge bg-warning text-dark ms-1">Belum Dibayar</span>
                    @endif
                  @endif
                </td>
              </tr>
            </table>
          </div>
          <div class="col-md-6">
            <table class="table table-borderless">
              <tr>
                <td width="200"><strong>Supplier</strong></td>
                <td>: {{ $uangMuka->nama_supplier }}</td>
              </tr>
              <tr>
                <td><strong>Perusahaan</strong></td>
                <td>: {{ $uangMuka->perusahaan->nama_perusahaan }}</td>
              </tr>
              <tr>
                <td><strong>Proyek</strong></td>
                <td>: {{ $uangMuka->proyek?->nama_proyek ?? '-' }}</td>
              </tr>
              <tr>
                <td><strong>Metode</strong></td>
                <td>: {{ ucfirst($uangMuka->metode_pembayaran) }}</td>
              </tr>
            </table>
          </div>
        </div>

        <h6 class="card-title mb-3 mt-4">Detail Uang Muka</h6>
        <div class="row g-3 mb-4">
          <div class="col-md-3">
            <div class="card bg-light">
              <div class="card-body">
                <p class="text-muted mb-2">Nominal</p>
                <h5>Rp {{ number_format($uangMuka->nominal, 0, ',', '.') }}</h5>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card bg-light">
              <div class="card-body">
                <p class="text-muted mb-2">Sudah Digunakan</p>
                <h5>Rp {{ number_format($uangMuka->nominal_digunakan, 0, ',', '.') }}</h5>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card bg-light">
              <div class="card-body">
                <p class="text-muted mb-2">Sisa</p>
                <h5>Rp {{ number_format($uangMuka->sisa_uang_muka, 0, ',', '.') }}</h5>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card bg-light">
              <div class="card-body">
                <p class="text-muted mb-2">Persentase</p>
                <h5>{{ $uangMuka->nominal > 0 ? round(($uangMuka->nominal_digunakan / $uangMuka->nominal) * 100, 1) : 0 }}%</h5>
              </div>
            </div>
          </div>
        </div>

        <div class="alert alert-info">
          <i class="link-icon" data-feather="info"></i>
          <strong>Catatan:</strong> {{ $uangMuka->keterangan ?? 'Tidak ada catatan' }}
        </div>

        @if($isPaid && $uangMuka->metode_pembayaran != 'tunai')
        <h6 class="card-title mb-3 mt-4">Detail Pembayaran</h6>
        <table class="table table-borderless">
          <tr>
            <td width="200"><strong>Nama Bank</strong></td>
            <td>: {{ $uangMuka->nama_bank ?? '-' }}</td>
          </tr>
          <tr>
            <td><strong>No. Rekening</strong></td>
            <td>: {{ $uangMuka->no_rekening_bank ?? '-' }}</td>
          </tr>
          <tr>
            <td><strong>Tanggal Transfer</strong></td>
            <td>: {{ $uangMuka->tanggal_transfer?->format('d/m/Y') ?? '-' }}</td>
          </tr>
          <tr>
            <td><strong>No. Bukti Transfer</strong></td>
            <td>: {{ $uangMuka->no_bukti_transfer ?? '-' }}</td>
          </tr>
        </table>
        @endif

        @if($uangMuka->file_path)
        <div class="mb-4 mt-4">
          <h6 class="card-title mb-3">File Bukti</h6>
          <a href="{{ asset('storage/' . $uangMuka->file_path) }}" class="btn btn-sm btn-outline-primary" target="_blank">
            <i data-feather="download"></i> Download PDF
          </a>
        </div>
        @endif

        <!-- Aksi -->
        <div class="mt-4">
          @if($uangMuka->status == 'draft')
            <a href="{{ route('uang-muka-pembelian.edit', $uangMuka->id) }}" class="btn btn-warning">
              <i data-feather="edit"></i> Edit
            </a>
            <form action="{{ route('uang-muka-pembelian.approve', $uangMuka->id) }}" method="POST" class="d-inline">
              @csrf
              <button type="submit" class="btn btn-success" onclick="return confirm('Yakin approve uang muka ini?')">
                <i data-feather="check"></i> Approve
              </button>
            </form>
            <form action="{{ route('uang-muka-pembelian.destroy', $uangMuka->id) }}" method="POST" class="d-inline">
              @csrf
              @method('DELETE')
              <button type="submit" class="btn btn-danger" onclick="return confirm('Yakin hapus uang muka ini?')">
                <i data-feather="trash-2"></i> Hapus
              </button>
            </form>
          @else
            <p class="text-muted mt-3">Uang muka telah di-approve. Tidak dapat diubah.</p>
          @endif
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

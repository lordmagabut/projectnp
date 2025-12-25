@extends('layout.master')

@section('content')
<nav class="page-breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ url('/') }}">Pembelian</a></li>
    <li class="breadcrumb-item"><a href="{{ route('uang-muka-pembelian.index') }}">Uang Muka Pembelian</a></li>
    <li class="breadcrumb-item active" aria-current="page">Edit Detail UM</li>
  </ol>
</nav>

<div class="row">
  <div class="col-lg-8 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <h6 class="card-title mb-4">Edit Detail Uang Muka: {{ $uangMuka->no_uang_muka }}</h6>
        
        <div class="alert alert-info">
          <i data-feather="info"></i>
          <strong>Catatan:</strong> Anda hanya dapat mengubah detail seperti bukti transfer, keterangan, dll. Untuk mengubah nominal atau data critical lainnya, batalkan pembayaran terlebih dahulu.
        </div>

        <form action="{{ route('uang-muka-pembelian.update-paid', $uangMuka->id) }}" method="POST" enctype="multipart/form-data">
          @csrf
          @method('PUT')

          <!-- Read-only Summary -->
          <div class="card bg-light mb-4">
            <div class="card-body">
              <h6 class="mb-3">Informasi UM (Tidak dapat diubah)</h6>
              <table class="table table-sm table-borderless">
                <tr>
                  <td width="200"><strong>No. UM</strong></td>
                  <td>: {{ $uangMuka->no_uang_muka }}</td>
                </tr>
                <tr>
                  <td><strong>Tanggal</strong></td>
                  <td>: {{ $uangMuka->tanggal->format('d/m/Y') }}</td>
                </tr>
                <tr>
                  <td><strong>PO</strong></td>
                  <td>: {{ $uangMuka->po->no_po ?? '-' }}</td>
                </tr>
                <tr>
                  <td><strong>Supplier</strong></td>
                  <td>: {{ $uangMuka->nama_supplier }}</td>
                </tr>
                <tr>
                  <td><strong>Nominal</strong></td>
                  <td>: Rp {{ number_format($uangMuka->nominal, 0, ',', '.') }}</td>
                </tr>
                <tr>
                  <td><strong>Status</strong></td>
                  <td>: <span class="badge bg-primary">Sudah Dibayar</span></td>
                </tr>
              </table>
            </div>
          </div>

          <!-- Editable Fields -->
          <div class="row mb-3">
            <label class="col-md-3 col-form-label">Tanggal Transfer</label>
            <div class="col-md-9">
              <input type="date" name="tanggal_transfer" class="form-control" value="{{ old('tanggal_transfer', $uangMuka->tanggal_transfer?->format('Y-m-d')) }}">
              @error('tanggal_transfer')
                <div class="text-danger small">{{ $message }}</div>
              @enderror
            </div>
          </div>

          <div class="row mb-3">
            <label class="col-md-3 col-form-label">No. Bukti Transfer</label>
            <div class="col-md-9">
              <input type="text" name="no_bukti_transfer" class="form-control" value="{{ old('no_bukti_transfer', $uangMuka->no_bukti_transfer) }}" placeholder="Contoh: TRF-20250125-001">
              @error('no_bukti_transfer')
                <div class="text-danger small">{{ $message }}</div>
              @enderror
            </div>
          </div>

          <div class="row mb-3">
            <label class="col-md-3 col-form-label">Keterangan</label>
            <div class="col-md-9">
              <textarea name="keterangan" class="form-control" rows="3">{{ old('keterangan', $uangMuka->keterangan) }}</textarea>
              @error('keterangan')
                <div class="text-danger small">{{ $message }}</div>
              @enderror
            </div>
          </div>

          <div class="row mb-3">
            <label class="col-md-3 col-form-label">Bukti Transfer</label>
            <div class="col-md-9">
              @if($uangMuka->file_path)
                <div class="mb-2">
                  <a href="{{ asset('storage/' . $uangMuka->file_path) }}" target="_blank" class="btn btn-sm btn-outline-info">
                    <i data-feather="file"></i> Lihat File Saat Ini
                  </a>
                </div>
              @endif
              <input type="file" name="file_path" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
              <small class="text-muted">Format: PDF, JPG, PNG (Max 2MB). Upload file baru untuk mengganti.</small>
              @error('file_path')
                <div class="text-danger small">{{ $message }}</div>
              @enderror
            </div>
          </div>

          <div class="d-flex justify-content-between mt-4">
            <a href="{{ route('uang-muka-pembelian.show', $uangMuka->id) }}" class="btn btn-secondary">
              <i data-feather="arrow-left"></i> Batal
            </a>
            <button type="submit" class="btn btn-primary">
              <i data-feather="save"></i> Simpan Perubahan
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Info Sidebar -->
  <div class="col-lg-4">
    <div class="card">
      <div class="card-body">
        <h6 class="card-title">Bantuan</h6>
        <ul class="small text-muted">
          <li>Form ini hanya untuk update detail pembayaran seperti bukti transfer</li>
          <li>Nominal UM tidak dapat diubah setelah dibayar</li>
          <li>Jika perlu ubah nominal, gunakan "Batalkan Pembayaran" terlebih dahulu</li>
          <li>Setelah batalkan pembayaran, UM akan kembali ke status Approved - Belum Dibayar</li>
        </ul>
      </div>
    </div>
  </div>
</div>
@endsection

@extends('layout.master')

@section('content')
<nav class="page-breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ url('/') }}">Pembelian</a></li>
    <li class="breadcrumb-item"><a href="{{ route('uang-muka-pembelian.index') }}">Uang Muka Pembelian</a></li>
    <li class="breadcrumb-item active" aria-current="page">Edit Uang Muka</li>
  </ol>
</nav>

<div class="row">
  <div class="col-lg-8 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <h6 class="card-title mb-4">Edit Uang Muka: {{ $uangMuka->no_uang_muka }}</h6>

        <form action="{{ route('uang-muka-pembelian.update', $uangMuka->id) }}" method="POST" enctype="multipart/form-data">
          @csrf
          @method('PUT')

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Tanggal <span class="text-danger">*</span></label>
              <input type="date" name="tanggal" class="form-control" value="{{ $uangMuka->tanggal->format('Y-m-d') }}" required>
              @error('tanggal')
                <div class="text-danger">{{ $message }}</div>
              @enderror
            </div>
            <div class="col-md-6">
              <label class="form-label">Nominal <span class="text-danger">*</span></label>
              <input type="number" name="nominal" class="form-control" value="{{ $uangMuka->nominal }}" step="0.01" required>
              @error('nominal')
                <div class="text-danger">{{ $message }}</div>
              @enderror
            </div>
          </div>

          <div class="row g-3 mt-2">
            <div class="col-md-6">
              <label class="form-label">Metode Pembayaran <span class="text-danger">*</span></label>
              <select name="metode_pembayaran" class="form-select" required>
                <option value="transfer" @selected($uangMuka->metode_pembayaran == 'transfer')>Transfer</option>
                <option value="cek" @selected($uangMuka->metode_pembayaran == 'cek')>Cek</option>
                <option value="tunai" @selected($uangMuka->metode_pembayaran == 'tunai')>Tunai</option>
                <option value="giro" @selected($uangMuka->metode_pembayaran == 'giro')>Giro</option>
              </select>
              @error('metode_pembayaran')
                <div class="text-danger">{{ $message }}</div>
              @enderror
            </div>
            <div class="col-md-6">
              <label class="form-label">Nama Bank</label>
              <input type="text" name="nama_bank" class="form-control" value="{{ $uangMuka->nama_bank }}">
            </div>
          </div>

          <div class="row g-3 mt-2">
            <div class="col-md-6">
              <label class="form-label">No. Rekening</label>
              <input type="text" name="no_rekening_bank" class="form-control" value="{{ $uangMuka->no_rekening_bank }}">
            </div>
            <div class="col-md-6">
              <label class="form-label">Tanggal Transfer</label>
              <input type="date" name="tanggal_transfer" class="form-control" value="{{ $uangMuka->tanggal_transfer?->format('Y-m-d') }}">
            </div>
          </div>

          <div class="mb-3 mt-3">
            <label class="form-label">No. Bukti Transfer/Cek/Giro</label>
            <input type="text" name="no_bukti_transfer" class="form-control" value="{{ $uangMuka->no_bukti_transfer }}">
          </div>

          <div class="mb-3">
            <label class="form-label">Keterangan</label>
            <textarea name="keterangan" class="form-control" rows="3">{{ $uangMuka->keterangan }}</textarea>
          </div>

          <div class="mb-3">
            <label class="form-label">File Bukti (PDF)</label>
            <input type="file" name="file_path" class="form-control" accept=".pdf">
            @if($uangMuka->file_path)
              <small class="text-muted">File saat ini: <a href="{{ asset('storage/' . $uangMuka->file_path) }}" target="_blank">Download</a></small>
            @else
              <small class="text-muted">Belum ada file</small>
            @endif
          </div>

          <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-success">
              <i data-feather="save"></i> Update
            </button>
            <a href="{{ route('uang-muka-pembelian.show', $uangMuka->id) }}" class="btn btn-secondary">
              <i data-feather="x"></i> Batal
            </a>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-4 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <h6 class="card-title mb-3">Info</h6>
        <table class="table table-sm table-borderless">
          <tr>
            <td><strong>No. UM</strong></td>
            <td>: {{ $uangMuka->no_uang_muka }}</td>
          </tr>
          <tr>
            <td><strong>No. PO</strong></td>
            <td>: {{ $uangMuka->po->no_po }}</td>
          </tr>
          <tr>
            <td><strong>Supplier</strong></td>
            <td>: {{ $uangMuka->nama_supplier }}</td>
          </tr>
          <tr>
            <td><strong>Digunakan</strong></td>
            <td>: Rp {{ number_format($uangMuka->nominal_digunakan, 0, ',', '.') }}</td>
          </tr>
          <tr>
            <td><strong>Sisa</strong></td>
            <td>: Rp {{ number_format($uangMuka->sisa_uang_muka, 0, ',', '.') }}</td>
          </tr>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection

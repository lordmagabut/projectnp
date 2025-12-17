@extends('layout.master')

@section('content')
<nav class="page-breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('faktur.index') }}">Faktur</a></li>
        <li class="breadcrumb-item active" aria-current="page">Proses Pembayaran</li>
    </ol>
</nav>

<div class="row justify-content-center">
    <div class="col-md-8 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title text-primary mb-4">Konfirmasi Pembayaran Pembelian</h4>
                
                <div class="bg-light p-3 rounded mb-4">
                    <div class="row">
                        <div class="col-sm-6">
                            <p class="text-muted mb-1">No. Faktur:</p>
                            <h6 class="fw-bold">{{ $faktur->no_faktur }}</h6>
                        </div>
                        <div class="col-sm-6 text-sm-end">
                            <p class="text-muted mb-1">Total Tagihan:</p>
                            <h5 class="text-dark fw-bold">Rp {{ number_format($faktur->total, 0, ',', '.') }}</h5>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-6">
                            <p class="text-muted mb-1">Supplier:</p>
                            <p class="fw-bold">{{ $faktur->nama_supplier }}</p>
                        </div>
                        <div class="col-sm-6 text-sm-end">
                            <p class="text-muted mb-1">Sisa Hutang:</p>
                            <p class="text-danger fw-bold">Rp {{ number_format($faktur->total - $faktur->sudah_dibayar, 0, ',', '.') }}</p>
                        </div>
                    </div>
                </div>

                <form action="{{ route('pembayaran.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="faktur_id" value="{{ $faktur->id }}">

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Tanggal Bayar</label>
                            <div class="input-group">
                                <input type="date" name="tanggal" class="form-control" value="{{ date('Y-m-d') }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Sumber Dana (Kas/Bank)</label>
                            <select name="coa_id" class="form-select @error('coa_id') is-invalid @enderror" required>
                                <option value="" selected disabled>-- Pilih Akun --</option>
                                @foreach($coaKas as $coa)
                                    <option value="{{ $coa->id }}">{{ $coa->no_akun }} - {{ $coa->nama_akun }}</option>
                                @endforeach
                            </select>
                            @error('coa_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Nominal Pembayaran</label>
                        <div class="input-group">
                            <span class="input-group-text bg-primary text-white">Rp</span>
                            <input type="number" name="nominal" class="form-control fw-bold" 
                                   value="{{ $faktur->total - $faktur->sudah_dibayar }}" 
                                   max="{{ $faktur->total - $faktur->sudah_dibayar }}" required>
                        </div>
                        <small class="text-muted">*Pastikan nominal sesuai dengan bukti transfer/kas keluar.</small>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Keterangan / Catatan</label>
                        <textarea name="keterangan" class="form-control" rows="2" placeholder="Contoh: Pembayaran via Transfer Mandiri"></textarea>
                    </div>

                    <div class="d-flex justify-content-end border-top pt-3">
                        <a href="{{ route('faktur.index') }}" class="btn btn-outline-secondary me-2">Batal</a>
                        <button type="submit" class="btn btn-primary btn-icon-text" onclick="return confirm('Apakah Anda yakin data pembayaran sudah benar?')">
                            <i data-feather="save" class="icon-sm me-1"></i> Simpan & Posting Jurnal
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>
@endsection
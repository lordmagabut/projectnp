@extends('layout.master')

@section('title', 'Edit Penerimaan Penjualan')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1>Edit Penerimaan Pembayaran Penjualan</h1>
        </div>
    </div>

    @if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle"></i> <strong>Terjadi Kesalahan</strong>
        <ul class="mb-0 mt-2">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Edit Penerimaan Pembayaran: {{ $penerimaanPenjualan->no_bukti }}</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('penerimaan-penjualan.update', $penerimaanPenjualan->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="faktur_penjualan_id" class="form-label">
                                Faktur Penjualan <span class="text-danger">*</span>
                            </label>
                            <select name="faktur_penjualan_id" id="faktur_penjualan_id" 
                                    class="form-control @error('faktur_penjualan_id') is-invalid @enderror"
                                    required>
                                <option value="">-- Pilih Faktur --</option>
                                @foreach ($fakturPenjualan as $faktur)
                                    <option value="{{ $faktur->id }}"
                                            @selected(old('faktur_penjualan_id', $penerimaanPenjualan->faktur_penjualan_id) == $faktur->id)>
                                        {{ $faktur->no_faktur }} - Rp {{ number_format($faktur->total, 2, ',', '.') }}
                                        (Sisa: Rp {{ number_format($faktur->total - ($faktur->penerimaanPenjualan->where('status', '!=', null)->sum('nominal') ?? 0), 2, ',', '.') }})
                                    </option>
                                @endforeach
                            </select>
                            @error('faktur_penjualan_id')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="tanggal" class="form-label">
                                Tanggal <span class="text-danger">*</span>
                            </label>
                            <input type="date" name="tanggal" id="tanggal" 
                                   class="form-control @error('tanggal') is-invalid @enderror"
                                   value="{{ old('tanggal', $penerimaanPenjualan->tanggal->format('Y-m-d')) }}"
                                   required>
                            @error('tanggal')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="nominal" class="form-label">
                                Nominal <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" name="nominal" id="nominal" 
                                       class="form-control text-right @error('nominal') is-invalid @enderror"
                                       step="0.01"
                                       value="{{ old('nominal', $penerimaanPenjualan->nominal) }}"
                                       required>
                            </div>
                            @error('nominal')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="pph_dipotong" class="form-label">PPh Dipotong</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" name="pph_dipotong" id="pph_dipotong" 
                                               class="form-control text-right @error('pph_dipotong') is-invalid @enderror"
                                               step="0.01"
                                               value="{{ old('pph_dipotong', $penerimaanPenjualan->pph_dipotong ?? 0) }}">
                                    </div>
                                    <small class="text-muted d-block mt-1">
                                        Dari Sertifikat Pembayaran: 
                                        @if($penerimaanPenjualan->fakturPenjualan->sertifikatPembayaran)
                                            Rp {{ number_format($penerimaanPenjualan->fakturPenjualan->sertifikatPembayaran->ppn_nilai ?? 0, 2, ',', '.') }}
                                        @else
                                            -
                                        @endif
                                    </small>
                                    @error('pph_dipotong')
                                        <span class="invalid-feedback d-block">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="keterangan_pph" class="form-label">Keterangan PPh</label>
                                    <input type="text" name="keterangan_pph" id="keterangan_pph" 
                                           class="form-control @error('keterangan_pph') is-invalid @enderror"
                                           placeholder="Misal: PPh 21, PPh 23, dll"
                                           value="{{ old('keterangan_pph', $penerimaanPenjualan->keterangan_pph) }}"
                                           maxlength="100">
                                    @error('keterangan_pph')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="metode_pembayaran" class="form-label">
                                Metode Pembayaran <span class="text-danger">*</span>
                            </label>
                            <select name="metode_pembayaran" id="metode_pembayaran" 
                                    class="form-control @error('metode_pembayaran') is-invalid @enderror"
                                    required>
                                <option value="">-- Pilih Metode --</option>
                                <option value="Tunai" @selected(old('metode_pembayaran', $penerimaanPenjualan->metode_pembayaran) === 'Tunai')>Tunai</option>
                                <option value="Transfer" @selected(old('metode_pembayaran', $penerimaanPenjualan->metode_pembayaran) === 'Transfer')>Transfer Bank</option>
                                <option value="Cek" @selected(old('metode_pembayaran', $penerimaanPenjualan->metode_pembayaran) === 'Cek')>Cek</option>
                                <option value="Giro" @selected(old('metode_pembayaran', $penerimaanPenjualan->metode_pembayaran) === 'Giro')>Giro</option>
                                <option value="Kartu Kredit" @selected(old('metode_pembayaran', $penerimaanPenjualan->metode_pembayaran) === 'Kartu Kredit')>Kartu Kredit</option>
                            </select>
                            @error('metode_pembayaran')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="keterangan" class="form-label">Keterangan</label>
                            <textarea name="keterangan" id="keterangan" 
                                      class="form-control @error('keterangan') is-invalid @enderror"
                                      rows="3">{{ old('keterangan', $penerimaanPenjualan->keterangan) }}</textarea>
                            @error('keterangan')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Perbarui
                            </button>
                            <a href="{{ route('penerimaan-penjualan.show', $penerimaanPenjualan->id) }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Batal
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card bg-light">
                <div class="card-header">
                    <h5 class="card-title mb-0">Informasi</h5>
                </div>
                <div class="card-body">
                    <p><strong>Status:</strong> <span class="badge bg-warning">Draft</span></p>
                    <p><strong>No. Bukti:</strong> {{ $penerimaanPenjualan->no_bukti }}</p>
                    <p><strong>Dibuat:</strong> {{ $penerimaanPenjualan->created_at->format('d/m/Y H:i') }}</p>
                    
                    @if($penerimaanPenjualan->fakturPenjualan->sertifikatPembayaran)
                    <hr>
                    <p><strong>Data PPh dari Sertifikat:</strong></p>
                    <ul class="small mb-0">
                        <li>Persentase: {{ $penerimaanPenjualan->fakturPenjualan->sertifikatPembayaran->ppn_persen }}%</li>
                        <li>Nilai: Rp {{ number_format($penerimaanPenjualan->fakturPenjualan->sertifikatPembayaran->ppn_nilai ?? 0, 2, ',', '.') }}</li>
                    </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

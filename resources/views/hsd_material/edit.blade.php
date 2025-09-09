@extends('layout.master')

@push('plugin-styles')
{{-- Font Awesome untuk ikon (tetap diperlukan untuk riwayat harga jika menggunakan fas) --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
{{-- Animate.css untuk animasi (opsional) --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
<style>
    /* Kustomisasi tambahan untuk tampilan (sama seperti create.blade.php) */
    .card {
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        border: none;
    }
    .card-header {
        background-color: #f8f9fa;
        border-bottom: 1px solid #e9ecef;
        border-top-left-radius: 12px;
        border-top-right-radius: 12px;
        padding: 1.25rem 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .form-label {
        font-weight: 600;
        color: #495057;
    }
    .form-control, .form-select {
        border-radius: 8px;
        border: 1px solid #ced4da;
        padding: 0.75rem 1rem;
    }
    .form-control:focus, .form-select:focus {
        border-color: #80bdff;
        box-shadow: 0 0 0 0.25rem rgba(0, 123, 255, 0.25);
    }
    .btn {
        border-radius: 8px;
        padding: 0.75rem 1.25rem;
        font-weight: 600;
        transition: all 0.2s ease-in-out;
    }
    .btn-primary {
        background-color: #007bff;
        border-color: #007bff;
    }
    .btn-primary:hover {
        background-color: #0056b3;
        border-color: #0056b3;
    }
    .btn-secondary {
        background-color: #6c757d;
        border-color: #6c757d;
    }
    .btn-secondary:hover {
        background-color: #5a6268;
        border-color: #545b62;
    }
    .alert {
        border-radius: 8px;
        display: flex;
        align-items: center;
        padding: 1rem 1.25rem;
        margin-bottom: 1.5rem;
    }
    .alert .fa-solid, .alert .fas { /* Menambahkan .fas untuk Font Awesome 5 */
        margin-right: 10px;
        font-size: 1.25rem;
    }
    /* Styling untuk tabel riwayat harga */
    .history-table th, .history-table td {
        font-size: 0.875rem;
        white-space: nowrap;
    }
    .history-table thead th {
        background-color: #e9ecef; /* Light gray for header */
        color: #495057;
    }
    .history-table tbody tr:nth-of-type(odd) {
        background-color: rgba(0, 0, 0, 0.03); /* Zebra striping */
    }
</style>
@endpush

@section('content')
<div class="card animate__animated animate__fadeInDown">
    <div class="card-header">
        <h4 class="card-title mb-0 d-flex align-items-center"><i data-feather="box" class="me-2"></i> Edit Harga Satuan Material</h4>
        <a href="{{ route('ahsp.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill d-inline-flex align-items-center">
            <i data-feather="arrow-left" class="me-1"></i> Kembali ke Daftar
        </a>
    </div>
    <div class="card-body">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show animate__animated animate__fadeIn" role="alert">
                <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show animate__animated animate__fadeIn" role="alert">
                <i class="fas fa-times-circle me-2"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <form action="{{ route('hsd-material.update', $material->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label for="kode" class="form-label d-flex align-items-center"><i data-feather="hash" class="me-2 text-muted"></i> Kode Material <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('kode') is-invalid @enderror" id="kode" name="kode" value="{{ old('kode', $material->kode) }}" required>
                @error('kode')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="nama" class="form-label d-flex align-items-center"><i data-feather="tag" class="me-2 text-muted"></i> Nama Material <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('nama') is-invalid @enderror" id="nama" name="nama" value="{{ old('nama', $material->nama) }}" required>
                @error('nama')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="satuan" class="form-label d-flex align-items-center"><i data-feather="ruler" class="me-2 text-muted"></i> Satuan <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('satuan') is-invalid @enderror" id="satuan" name="satuan" value="{{ old('satuan', $material->satuan) }}" required>
                @error('satuan')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="harga_satuan" class="form-label d-flex align-items-center"><i data-feather="dollar-sign" class="me-2 text-muted"></i> Harga Satuan <span class="text-danger">*</span></label>
                <input type="number" step="1" class="form-control @error('harga_satuan') is-invalid @enderror" id="harga_satuan" name="harga_satuan" value="{{ old('harga_satuan', $material->harga_satuan) }}" required>
                @error('harga_satuan')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="sumber" class="form-label d-flex align-items-center">Sumber Perubahan Harga</label>
                <input type="text" name="sumber" id="sumber" class="form-control" placeholder="Contoh: kenaikan BBM, kenaikan harga, dsb" value="{{ old('sumber') }}">
                <div class="form-text">Opsional. Diisi jika harga berubah.</div>
            </div>

            <div class="mb-3">
                <label for="keterangan" class="form-label d-flex align-items-center"><i data-feather="info" class="me-2 text-muted"></i> Keterangan (Opsional)</label>
                <textarea class="form-control @error('keterangan') is-invalid @enderror" id="keterangan" name="keterangan" rows="3">{{ old('keterangan', $material->keterangan) }}</textarea>
                @error('keterangan')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="d-flex justify-content-end mt-4">
                <button type="submit" class="btn btn-primary me-2">
                    <i data-feather="save" class="me-1"></i> Perbarui Material
                </button>
                <a href="{{ route('ahsp.index') }}" class="btn btn-secondary">
                    <i data-feather="x-circle" class="me-1"></i> Batal
                </a>
            </div>
        </form>
    </div>
</div>

{{-- Riwayat Perubahan Harga --}}
@if($material->histories->count())
<div class="card mt-4 animate__animated animate__fadeInUp">
    <div class="card-header">
        <h5 class="card-title mb-0 d-flex align-items-center"><i data-feather="clock" class="me-2"></i> Riwayat Perubahan Harga</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered table-sm mb-0 history-table">
                <thead class="table-light">
                    <tr>
                        <th style="width: 15%">Tanggal Berlaku</th>
                        <th style="width: 25%">Harga (Lama → Baru)</th>
                        <th style="width: 35%">Sumber</th>
                        <th style="width: 25%">Diupdate Oleh</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($material->histories->sortByDesc('tanggal_berlaku') as $history)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($history->tanggal_berlaku)->format('d-m-Y') }}</td>
                        <td>
                            Rp {{ number_format($history->harga_satuan, 0, ',', '.') }}
                            @if($history->harga_baru)
                                → Rp {{ number_format($history->harga_baru, 0, ',', '.') }}
                            @endif
                        </td>
                        <td>{{ $history->sumber ?? '-' }}</td>
                        <td>{{ $history->updatedBy->username ?? '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif
@endsection

@push('custom-scripts')
{{-- Memuat Feather Icons untuk memastikan ikon berfungsi --}}
<script src="https://unpkg.com/feather-icons"></script>
<script>
    // Inisialisasi Feather Icons
    feather.replace();
</script>
@endpush

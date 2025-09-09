@extends('layout.master')

@push('plugin-styles')
{{-- Asumsi Anda sudah memuat Font Awesome atau Lucide Icons di layout.master --}}
{{-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"> --}}
{{-- Atau jika menggunakan Feather Icons secara langsung: --}}
{{-- <script src="https://unpkg.com/feather-icons"></script> --}}
@endpush

@section('content')
<div class="card shadow-sm animate__animated animate__fadeIn">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center flex-wrap rounded-top">
        <h4 class="m-0 d-flex align-items-center">
            <i data-feather="calendar" class="me-2"></i> Input Schedule Mingguan
        </h4>
        <a href="{{ route('proyek.show', $proyek->id) }}" class="btn btn-light btn-sm d-inline-flex align-items-center">
            <i data-feather="arrow-left" class="me-1"></i> Kembali ke Detail Proyek
        </a>
    </div>
    <div class="card-body p-3 p-md-4">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show animate__animated animate__fadeIn mb-4" role="alert">
                <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show animate__animated animate__fadeIn mb-4" role="alert">
                <i class="fas fa-times-circle me-2"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <h5 class="mb-3 d-flex align-items-center text-secondary">
            <i data-feather="info" class="me-2"></i> Proyek: {{ $proyek->nama_proyek }}
        </h5>

        <form action="{{ route('schedule.store', $proyek->id) }}" method="POST">
            @csrf
            <input type="hidden" name="proyek_id" value="{{ $proyek->id }}">

            <div class="table-responsive">
                <table class="table table-hover table-bordered table-striped align-middle schedule-input-table">
                    <thead class="table-secondary">
                        <tr class="text-center">
                            <th style="width: 15%;">WBS</th>
                            <th style="width: 40%;">Deskripsi</th>
                            <th style="width: 15%;">Bobot (%)</th>
                            <th style="width: 15%;">Minggu Mulai</th>
                            <th style="width: 15%;">Durasi (minggu)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($subHeaders as $header)
                            @php
                                $existing = $existingSchedules[$header->id] ?? null;
                                $isParentHeader = !Str::contains($header->kode, '.');
                            @endphp
                            <tr class="{{ $isParentHeader ? 'table-info fw-bold' : '' }}">
                                <td>{{ $header->kode }}</td>
                                <td>{{ $header->deskripsi }}</td>
                                <td class="text-end">{{ number_format($header->bobot, 2, ',', '.') }}%</td>
                                <td>
                                    <select name="jadwal[{{ $header->id }}][minggu_ke]" class="form-select form-select-sm @error("jadwal.{$header->id}.minggu_ke") is-invalid @enderror" required>
                                        <option value="">-- Pilih Minggu --</option>
                                        @foreach($mingguOptions as $minggu => $tanggal)
                                            <option value="{{ $minggu }}"
                                                {{ (old("jadwal.{$header->id}.minggu_ke", $existing->minggu_ke ?? '') == $minggu) ? 'selected' : '' }}>
                                                M-{{ $minggu }} ({{ \Carbon\Carbon::parse($tanggal)->format('d-m-Y') }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error("jadwal.{$header->id}.minggu_ke")
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </td>
                                <td>
                                    <input type="number" name="jadwal[{{ $header->id }}][durasi]" class="form-control form-control-sm text-end @error("jadwal.{$header->id}.durasi") is-invalid @enderror"
                                        value="{{ old("jadwal.{$header->id}.durasi", $existing->durasi ?? '') }}"
                                        min="1" required>
                                    @error("jadwal.{$header->id}.durasi")
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    <i class="fas fa-exclamation-circle fa-2x mb-2"></i><br>
                                    Tidak ada sub-header RAB yang ditemukan untuk proyek ini.<br>
                                    Pastikan Anda sudah membuat RAB terlebih dahulu.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4 text-end">
                <button type="submit" class="btn btn-primary btn-lg shadow-sm">
                    <i data-feather="save" class="me-1"></i>
                    {{ count($existingSchedules) ? 'Update Jadwal' : 'Generate Jadwal' }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('plugin-scripts')
{{-- Memuat Feather Icons untuk memastikan ikon berfungsi --}}
<script src="https://unpkg.com/feather-icons"></script>
@endpush

@push('custom-scripts')
<script>
    // Inisialisasi Feather Icons
    feather.replace();

    // Pastikan select dan input number memiliki lebar yang konsisten jika diperlukan
    // Anda bisa menambahkan CSS kustom di sini atau di file CSS eksternal jika ingin lebih spesifik
</script>
@endpush

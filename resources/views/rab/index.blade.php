@extends('layout.master')

@push('plugin-styles')
    {{-- DataTables (jika kamu pakai) --}}
    <link href="{{ asset('assets/plugins/datatables-net-bs5/dataTables.bootstrap5.css') }}" rel="stylesheet" />
    <link href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap5.min.css" rel="stylesheet" />
    {{-- Icons + Animations --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

    {{-- Select2 + Bootstrap 5 theme (agar kotak AHSP sama dengan .form-select) --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css"/>

    <style>
        .container-fluid{ padding-top:20px; }
        .card{ border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,.08); border:none; }
        .card-header{ background:#f8f9fa; border-bottom:1px solid #e9ecef; border-top-left-radius:12px; border-top-right-radius:12px; padding:1.25rem 1.5rem; display:flex; justify-content:space-between; align-items:center; }
        .nav-tabs .nav-link{ border-radius:8px 8px 0 0; padding:12px 20px; font-weight:600; color:#6c757d; transition:all .3s ease; }
        .nav-tabs .nav-link.active{ background:#007bff; color:#fff; border-color:#007bff #007bff #fff; }
        .nav-tabs .nav-item .nav-link:hover:not(.active){ background:#e9ecef; color:#0056b3; }
        .btn{ border-radius:8px; padding:.75rem 1.25rem; font-weight:600; transition:all .2s ease-in-out; }
        .btn-secondary{ background:#6c757d; border-color:#6c757d; }
        .btn-secondary:hover{ background:#5a6268; border-color:#545b62; }
        .alert{ border-radius:8px; display:flex; align-items:center; padding:1rem 1.25rem; margin-bottom:1.5rem; }
        .rab-header-card{ border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,.05); border:1px solid #e0e0e0; }
        .rab-header-card .card-header{ background:#e9f5ff; color:#0056b3; font-size:1.1em; border-bottom:1px solid #cce5ff; }
        .table-sm th,.table-sm td{ padding:.5rem; }
        .cursor-pointer{ cursor:pointer; }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4 animate__animated animate__fadeInDown">
            <h4 class="mb-0">
                <i class="fas fa-project-diagram me-2"></i>
                Input RAB Proyek: <span class="text-primary">{{ $proyek->nama_proyek }}</span>
            </h4>
            <a href="{{ route('proyek.show', $proyek->id) }}" class="btn btn-secondary rounded-pill">
                <i class="fas fa-arrow-left me-1"></i> Kembali ke Detail Proyek
            </a>
        </div>

        {{-- Tabs --}}
        <ul class="nav nav-tabs nav-tabs-line mb-4 animate__animated animate__fadeIn" id="rabTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="summary-tab" data-bs-toggle="tab" data-bs-target="#summary" type="button" role="tab" aria-controls="summary" aria-selected="true">
                    <i class="fas fa-chart-bar me-2"></i> Summary
                </button>
            </li>

            {{-- Tab Baru: Gambar Kerja --}}
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="gambar-kerja-tab" data-bs-toggle="tab" data-bs-target="#gambar-kerja" type="button" role="tab" aria-controls="gambar-kerja" aria-selected="false">
                    <i class="fas fa-file-image me-2"></i> Gambar Kerja
                </button>
            </li>

            @foreach($kategoris as $kategori)
                <li class="nav-item" role="presentation">
                    <button class="nav-link"
                            id="tab-{{ $kategori->id }}-tab"
                            data-bs-toggle="tab"
                            data-bs-target="#tab-{{ $kategori->id }}"
                            type="button" role="tab"
                            aria-controls="tab-{{ $kategori->id }}"
                            aria-selected="false">
                        <i class="fas fa-folder me-2"></i> {{ $kategori->nama_kategori }}
                    </button>
                </li>
            @endforeach
        </ul>

        <div class="tab-content mt-3" id="rabTabsContent">
            {{-- Summary --}}
            <div class="tab-pane fade show active animate__animated animate__fadeIn" id="summary" role="tabpanel" aria-labelledby="summary-tab">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <livewire:rab-summary :proyek_id="$proyek_id" />
                    </div>
                    <div class="col-md-6 mb-3">
                        <livewire:rab-summary-by-category :proyek_id="$proyek_id" />
                    </div>
                </div>
            </div>

            {{-- Tab Content: Gambar Kerja --}}
            <div class="tab-pane fade animate__animated animate__fadeIn" id="gambar-kerja" role="tabpanel" aria-labelledby="gambar-kerja-tab">
                <div class="card">
                    <div class="card-header rab-header-card">
                        <i class="fas fa-file-image me-2"></i> Dokumen Gambar Kerja & Analisa
                    </div>
                    <div class="card-body">
                        @if ($proyek->file_gambar_kerja)
                            @php
                                $filePath = asset('storage/' . $proyek->file_gambar_kerja);
                                $fileExtension = pathinfo($proyek->file_gambar_kerja, PATHINFO_EXTENSION);
                                $isImage = in_array(strtolower($fileExtension), ['jpg', 'jpeg', 'png']);
                            @endphp

                            <div class="alert alert-success d-flex align-items-center mb-4" role="alert">
                                <i class="fas fa-check-circle me-3 fs-4"></i>
                                <div>
                                    Dokumen Gambar Kerja sudah diunggah. Anda dapat melihatnya melalui tautan di bawah ini:
                                </div>
                            </div>

                            <a href="{{ $filePath }}" target="_blank" class="btn btn-primary btn-lg me-2">
                                <i class="fas fa-eye me-2"></i> Lihat Dokumen ({{ strtoupper($fileExtension) }})
                            </a>
                            
                            {{-- Tambahkan tampilan jika file adalah gambar (optional) --}}
                            @if ($isImage)
                                <div class="mt-4 border rounded p-3 text-center">
                                    <h6 class="mb-3">Preview Gambar:</h6>
                                    <img src="{{ $filePath }}" class="img-fluid rounded shadow-sm" style="max-height: 400px; object-fit: contain;">
                                </div>
                            @endif

                        @else
                            <div class="alert alert-warning d-flex align-items-center" role="alert">
                                <i class="fas fa-exclamation-triangle me-3 fs-4"></i>
                                <div>
                                    **Belum ada** Dokumen Gambar Kerja yang diunggah untuk proyek ini.
                                    Silakan unggah melalui halaman **Edit Proyek**.
                                </div>
                            </div>
                            <a href="{{ route('proyek.edit', $proyek->id) }}" class="btn btn-warning">
                                <i class="fas fa-upload me-2"></i> Unggah Gambar Kerja Sekarang
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Per Kategori --}}
            @foreach($kategoris as $kategori)
                <div class="tab-pane fade animate__animated animate__fadeIn"
                    id="tab-{{ $kategori->id }}"
                    role="tabpanel"
                    aria-labelledby="tab-{{ $kategori->id }}-tab">
                    <livewire:rab-input
                        :key="'rab-input-'.$proyek_id.'-'.$kategori->id"
                        :proyek_id="$proyek_id"
                        :kategori_id="$kategori->id"
                    />
                </div>
            @endforeach
        </div>
    </div>
@endsection

@push('custom-scripts')
    {{-- Select2 JS (sekali saja di layout/halaman; Livewire view akan pakai ini) --}}
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // Baca ?tab=... untuk buka tab tertentu lewat URL
            const urlParams = new URLSearchParams(window.location.search);
            const activeTab = urlParams.get('tab');
            if (activeTab) {
                const tabTrigger = document.querySelector(`#${activeTab}-tab`);
                if (tabTrigger) new bootstrap.Tab(tabTrigger).show();
            } else {
                const summaryTabTrigger = document.getElementById('summary-tab');
                if (summaryTabTrigger) new bootstrap.Tab(summaryTabTrigger).show();
            }

            // Jika ada DataTables di luar Livewire dan perlu reflow ketika tab ganti, aktifkan ini:
            // $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function () {
            //   $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust().responsive.recalc();
            // });
        });
    </script>
@endpush
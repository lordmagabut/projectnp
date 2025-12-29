@extends('layout.master')

@push('plugin-styles')
<link href="{{ asset('assets/plugins/datatables-net-bs5/dataTables.bootstrap5.css') }}" rel="stylesheet" />
<link href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap5.min.css" rel="stylesheet" />
{{-- Font Awesome untuk ikon --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
{{-- Animate.css untuk animasi (opsional) --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
<style>
    /* Kustomisasi tambahan untuk tampilan */
    .card {
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        border: none;
    }
    .card-header {
        background-color: #f8f9fa; /* Warna latar belakang header kartu */
        border-bottom: 1px solid #e9ecef;
        border-top-left-radius: 12px;
        border-top-right-radius: 12px;
        padding: 1.25rem 1.5rem;
    }
    .nav-tabs .nav-link {
        border-radius: 8px 8px 0 0;
        padding: 12px 20px;
        font-weight: 600;
        color: #6c757d;
        transition: all 0.3s ease;
    }
    .nav-tabs .nav-link.active {
        background-color: #007bff;
        color: white;
        border-color: #007bff #007bff #fff;
    }
    .nav-tabs .nav-item .nav-link:hover:not(.active) {
        background-color: #e9ecef;
        color: #0056b3;
    }
    .table-responsive {
        padding: 15px; /* Padding di sekitar tabel */
    }
    .table thead th {
        background-color: #e9ecef;
        color: #495057;
        font-weight: 600;
        border-bottom: 2px solid #dee2e6;
    }
    .table tbody tr:hover {
        background-color: #f2f2f2;
    }
    .btn-sm {
        border-radius: 6px;
    }
    .dropdown-item {
        padding: 8px 15px;
        transition: background-color 0.2s ease;
    }
    .dropdown-item:hover {
        background-color: #e9ecef;
    }
    .alert {
        border-radius: 8px;
        display: flex;
        align-items: center;
        padding: 1rem 1.25rem;
    }
    .alert .fa-solid {
        margin-right: 10px;
        font-size: 1.25rem;
    }

    /* Style khusus modal history */
    .modal .table th, .modal .table td {
        vertical-align: middle;
    }
    .spinner-wrap{
        display:flex;align-items:center;justify-content:center;min-height:160px
    }
</style>
@endpush

@section('content')
<h4 class="mb-4 animate__animated animate__fadeInDown">Daftar Harga & Analisa</h4>

<ul class="nav nav-tabs nav-tabs-line mb-4 animate__animated animate__fadeIn" id="tab-harga" role="tablist">
  <li class="nav-item" role="presentation">
    <button class="nav-link active" id="material-tab" data-bs-toggle="tab" data-bs-target="#materialContent" type="button" role="tab" aria-controls="materialContent" aria-selected="true">
        <i class="fas fa-box me-2"></i> Material
    </button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="upah-tab" data-bs-toggle="tab" data-bs-target="#upahContent" type="button" role="tab" aria-controls="upahContent" aria-selected="false">
        <i class="fas fa-hand-holding-usd me-2"></i> Upah
    </button>
  </li>
  @can('manage ahsp')
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="ahsp-tab" data-bs-toggle="tab" data-bs-target="#ahspContent" type="button" role="tab" aria-controls="ahspContent" aria-selected="false">
        <i class="fas fa-chart-pie me-2"></i> Analisa (AHSP)
    </button>
  </li>
  @endcan
</ul>

<div class="tab-content mt-3">
  {{-- Tab Material --}}
  <div class="tab-pane fade show active animate__animated animate__fadeIn" id="materialContent" role="tabpanel" aria-labelledby="material-tab">
    <div class="card animate__animated animate__fadeInUp animate__faster">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="card-title mb-0"><i class="fas fa-boxes me-2"></i> Harga Satuan Material</h4>
            <a href="{{ route('hsd-material.create') }}" class="btn btn-sm btn-primary rounded-pill">
                <i class="fas fa-plus me-1"></i> Tambah Material
            </a>
        </div>
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
        <div class="table-responsive">
          <table id="tableMaterial" class="table table-hover align-middle display nowrap" style="width:100%">
            <thead>
              <tr>
                <th class="text-nowrap">Kode</th>
                <th class="text-nowrap">Nama Material</th>
                <th class="text-nowrap">Satuan</th>
                <th class="text-end text-nowrap">Harga Satuan</th>
                <th class="text-nowrap">Keterangan</th>
                <th class="text-center text-nowrap">Aksi</th>
              </tr>
            </thead>
            <tbody>
              @foreach($materials as $material)
              <tr>
                <td>{{ $material->kode }}</td>
                <td>{{ $material->nama }}</td>
                <td>{{ $material->satuan }}</td>
                <td class="text-end">Rp {{ number_format($material->harga_satuan, 0, ',', '.') }}</td>
                <td>{{ $material->keterangan }}</td>
                <td class="text-center">
                  <a href="{{ route('hsd-material.edit', $material->id) }}" class="btn btn-sm btn-outline-primary rounded">
                      <i class="fas fa-edit"></i> Edit
                  </a>

                  {{-- Tombol History Material --}}
                  <button type="button"
                          class="btn btn-sm btn-outline-info rounded btn-history-material"
                          data-bs-toggle="modal"
                          data-bs-target="#historyMaterialModal"
                          data-id="{{ $material->id }}"
                          data-name="{{ $material->nama }}">
                      <i class="fas fa-clock-rotate-left"></i> Riwayat
                  </button>

                  {{-- Tombol Hapus --}}
                  <button type="button" class="btn btn-sm btn-outline-danger rounded"
                          data-bs-toggle="modal"
                          data-bs-target="#deleteMaterialModal"
                          data-id="{{ $material->id }}"
                          data-name="{{ $material->nama }}">
                      <i class="fas fa-trash-alt"></i> Hapus
                  </button>
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  {{-- Tab Upah --}}
  <div class="tab-pane fade animate__animated animate__fadeIn" id="upahContent" role="tabpanel" aria-labelledby="upah-tab">
    <div class="card animate__animated animate__fadeInUp animate__faster">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="card-title mb-0"><i class="fas fa-hard-hat me-2"></i> Harga Satuan Upah / Tukang</h4>
            <a href="{{ route('hsd-upah.create') }}" class="btn btn-sm btn-primary rounded-pill">
                <i class="fas fa-plus me-1"></i> Tambah Upah
            </a>
        </div>
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
        <div class="table-responsive">
          <table id="tableUpah" class="table table-hover align-middle display nowrap" style="width:100%">
            <thead>
              <tr>
                <th class="text-nowrap">Kode</th>
                <th class="text-nowrap">Jenis Pekerja</th>
                <th class="text-nowrap">Satuan</th>
                <th class="text-end text-nowrap">Harga Satuan</th>
                <th class="text-nowrap">Keterangan</th>
                <th class="text-center text-nowrap">Aksi</th>
              </tr>
            </thead>
            <tbody>
              @foreach($upahs as $upah)
              <tr>
                <td>{{ $upah->kode }}</td>
                <td>{{ $upah->jenis_pekerja }}</td>
                <td>{{ $upah->satuan }}</td>
                <td class="text-end">Rp {{ number_format($upah->harga_satuan, 0, ',', '.') }}</td>
                <td>{{ $upah->keterangan }}</td>
                <td class="text-center">
                  <a href="{{ route('hsd-upah.edit', $upah->id) }}" class="btn btn-sm btn-outline-primary rounded">
                      <i class="fas fa-edit"></i> Edit
                  </a>

                  {{-- Tombol History Upah --}}
                  <button type="button"
                          class="btn btn-sm btn-outline-info rounded btn-history-upah"
                          data-bs-toggle="modal"
                          data-bs-target="#historyUpahModal"
                          data-id="{{ $upah->id }}"
                          data-name="{{ $upah->jenis_pekerja }}">
                      <i class="fas fa-clock-rotate-left"></i> Riwayat
                  </button>

                  {{-- Tombol Hapus --}}
                  <button type="button" class="btn btn-sm btn-outline-danger rounded"
                          data-bs-toggle="modal"
                          data-bs-target="#deleteUpahModal"
                          data-id="{{ $upah->id }}"
                          data-name="{{ $upah->jenis_pekerja }}">
                      <i class="fas fa-trash-alt"></i> Hapus
                  </button>
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  {{-- Tab AHSP --}}
  @can('manage ahsp')
  <div class="tab-pane fade animate__animated animate__fadeIn" id="ahspContent" role="tabpanel" aria-labelledby="ahsp-tab">
    <div class="card animate__animated animate__fadeInUp animate__faster">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="card-title mb-0"><i class="fas fa-calculator me-2"></i> Analisa Harga Satuan Pekerjaan</h4>
            <a href="{{ route('ahsp.create') }}" class="btn btn-sm btn-primary rounded-pill">
                <i class="fas fa-plus me-1"></i> Tambah Analisa
            </a>
        </div>
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
        <div class="table-responsive">
          <table id="tableAhsp" class="table table-hover align-middle display nowrap" style="width:100%">
            <thead>
              <tr>
                <th class="text-nowrap">AHSP ID</th>
                <th class="text-nowrap">Kode</th>
                <th class="text-nowrap">Nama Pekerjaan</th>
                <th class="text-nowrap">Kategori</th>
                <th class="text-nowrap">Satuan</th>
                <th class="text-end text-nowrap">Total Harga</th>
                <th class="text-end text-nowrap">Total Pembulatan</th>
                <th class="text-center text-nowrap">Status</th>
                <th class="text-center text-nowrap">Aksi</th>
              </tr>
            </thead>
            <tbody>
              @forelse($ahsps as $a)
              <tr>
                <td>{{ $a->id }}</td>
                <td>{{ $a->kode_pekerjaan }}</td>
                <td>{{ $a->nama_pekerjaan }}</td>
                <td>{{ $a->kategori->nama ?? '-' }}</td>
                <td>{{ $a->satuan }}</td>
                <td class="text-end">Rp {{ number_format($a->total_harga, 0, ',', '.') }}</td>
                <td class="text-end">Rp {{ number_format($a->total_harga_pembulatan ?? 0, 0, ',', '.') }}</td>
                <td class="text-center">
                  @if($a->is_locked)
                    <span class="badge bg-danger rounded-pill py-2 px-3">Terkunci <i class="fas fa-lock ms-1"></i></span>
                  @else
                    <span class="badge bg-success rounded-pill py-2 px-3">Draft <i class="fas fa-pencil-alt ms-1"></i></span>
                  @endif
                </td>
                <td class="text-center">
                  <div class="dropdown">
                    <button class="btn btn-sm btn-outline-primary dropdown-toggle rounded" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                      Aksi
                    </button>
                    <ul class="dropdown-menu">
                      <li>
                        <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#duplicateAhspModal" data-id="{{ $a->id }}" data-name="{{ $a->nama_pekerjaan }}">
                            <i class="fas fa-copy me-1"></i> Duplikat
                        </button>
                      </li>
                      <li>
                        <a href="{{ route('ahsp.show', $a->id) }}" class="dropdown-item">
                          <i class="fas fa-eye me-1"></i> Lihat
                        </a>
                      </li>
                      @if(!$a->is_locked)
                        <li>
                          <a href="{{ route('ahsp.edit', $a->id) }}" class="dropdown-item">
                            <i class="fas fa-edit me-1"></i> Edit
                          </a>
                        </li>
                        <li>
                          <button type="button" class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#deleteAhspModal" data-id="{{ $a->id }}" data-name="{{ $a->nama_pekerjaan }}">
                            <i class="fas fa-trash-alt me-1"></i> Hapus
                          </button>
                        </li>
                      @else
                        <li>
                          <button class="dropdown-item text-muted" disabled>
                            <i class="fas fa-lock me-1"></i> Terkunci
                          </button>
                        </li>
                      @endif
                    </ul>
                  </div>
                </td>
              </tr>
              @empty
              <tr><td colspan="9" class="text-center py-4">Belum ada data AHSP.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  @endcan
</div>

{{-- Modal Konfirmasi Hapus Material --}}
<div class="modal fade" id="deleteMaterialModal" tabindex="-1" aria-labelledby="deleteMaterialModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-lg shadow-lg">
            <div class="modal-header bg-danger text-white rounded-top">
                <h5 class="modal-title" id="deleteMaterialModalLabel"><i class="fas fa-exclamation-triangle me-2"></i> Konfirmasi Hapus Material</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <p class="lead">Apakah Anda yakin ingin menghapus material: <br><strong><span id="materialName"></span></strong>?</p>
                <p class="text-muted">Tindakan ini tidak dapat dibatalkan.</p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary rounded" data-bs-dismiss="modal">Batal</button>
                <form id="deleteMaterialForm" method="POST" class="d-inline-block">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger rounded"><i class="fas fa-trash-alt me-1"></i> Hapus</button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Modal Konfirmasi Hapus Upah --}}
<div class="modal fade" id="deleteUpahModal" tabindex="-1" aria-labelledby="deleteUpahModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-lg shadow-lg">
            <div class="modal-header bg-danger text-white rounded-top">
                <h5 class="modal-title" id="deleteUpahModalLabel"><i class="fas fa-exclamation-triangle me-2"></i> Konfirmasi Hapus Upah</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <p class="lead">Apakah Anda yakin ingin menghapus upah: <br><strong><span id="upahName"></span></strong>?</p>
                <p class="text-muted">Tindakan ini tidak dapat dibatalkan.</p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary rounded" data-bs-dismiss="modal">Batal</button>
                <form id="deleteUpahForm" method="POST" class="d-inline-block">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger rounded"><i class="fas fa-trash-alt me-1"></i> Hapus</button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Modal Konfirmasi Hapus AHSP --}}
<div class="modal fade" id="deleteAhspModal" tabindex="-1" aria-labelledby="deleteAhspModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-lg shadow-lg">
            <div class="modal-header bg-danger text-white rounded-top">
                <h5 class="modal-title" id="deleteAhspModalLabel"><i class="fas fa-exclamation-triangle me-2"></i> Konfirmasi Hapus Analisa</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <p class="lead">Apakah Anda yakin ingin menghapus analisa: <br><strong><span id="ahspName"></span></strong>?</p>
                <p class="text-muted">Tindakan ini tidak dapat dibatalkan.</p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary rounded" data-bs-dismiss="modal">Batal</button>
                <form id="deleteAhspForm" method="POST" class="d-inline-block">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger rounded"><i class="fas fa-trash-alt me-1"></i> Hapus</button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Modal Konfirmasi Duplikat AHSP --}}
<div class="modal fade" id="duplicateAhspModal" tabindex="-1" aria-labelledby="duplicateAhspModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-lg shadow-lg">
            <div class="modal-header bg-primary text-white rounded-top">
                <h5 class="modal-title" id="duplicateAhspModalLabel"><i class="fas fa-copy me-2"></i> Konfirmasi Duplikat Analisa</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <p class="lead">Apakah Anda yakin ingin menduplikasi analisa: <br><strong><span id="duplicateAhspName"></span></strong>?</p>
                <p class="text-muted">Ini akan membuat salinan baru dari analisa ini.</p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary rounded" data-bs-dismiss="modal">Batal</button>
                <form id="duplicateAhspForm" method="POST" class="d-inline-block">
                    @csrf
                    <button type="submit" class="btn btn-primary rounded"><i class="fas fa-copy me-1"></i> Duplikat</button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- ======================== MODAL HISTORY MATERIAL ======================== --}}
<div class="modal fade" id="historyMaterialModal" tabindex="-1" aria-labelledby="historyMaterialModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content rounded-lg shadow-lg">
      <div class="modal-header bg-info text-white rounded-top">
        <h5 class="modal-title" id="historyMaterialModalLabel">
          <i class="fas fa-clock-rotate-left me-2"></i> Riwayat Perubahan Material
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0">
        <div class="p-3">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <div>
              <div class="fw-semibold">Material:</div>
              <div class="text-primary" id="historyMaterialName">-</div>
            </div>
            <div class="text-muted small">Sumber: <code>hsd_material_history</code></div>
          </div>
          <div id="matHistoryLoading" class="spinner-wrap">
            <div class="spinner-border" role="status" aria-hidden="true"></div>
            <span class="ms-2">Memuat riwayat...</span>
          </div>
          <div class="table-responsive d-none" id="matHistoryWrap">
            <table class="table table-striped table-hover align-middle">
              <thead>
                <tr>
                  <th class="text-nowrap">Tanggal</th>
                  <th class="text-nowrap text-end">Harga Lama</th>
                  <th class="text-nowrap text-end">Harga Baru</th>
                  <th class="text-nowrap">Satuan</th>
                  <th class="text-nowrap">Keterangan</th>
                  <th class="text-nowrap">Diubah Oleh</th>
                </tr>
              </thead>
              <tbody id="matHistoryBody">
                {{-- rows via JS --}}
              </tbody>
            </table>
          </div>
          <div class="p-3 d-none" id="matHistoryEmpty">
            <div class="alert alert-secondary mb-0"><i class="fas fa-info-circle me-2"></i>Tidak ada data riwayat.</div>
          </div>
          <div class="p-3 d-none" id="matHistoryError">
            <div class="alert alert-danger mb-0"><i class="fas fa-triangle-exclamation me-2"></i>Gagal memuat data.</div>
          </div>
        </div>
      </div>
      <div class="modal-footer justify-content-between">
        <small class="text-muted">Menampilkan 50 riwayat terakhir (jika tersedia)</small>
        <button type="button" class="btn btn-secondary rounded" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

{{-- ======================== MODAL HISTORY UPAH ======================== --}}
<div class="modal fade" id="historyUpahModal" tabindex="-1" aria-labelledby="historyUpahModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content rounded-lg shadow-lg">
      <div class="modal-header bg-info text-white rounded-top">
        <h5 class="modal-title" id="historyUpahModalLabel">
          <i class="fas fa-clock-rotate-left me-2"></i> Riwayat Perubahan Upah
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0">
        <div class="p-3">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <div>
              <div class="fw-semibold">Jenis Pekerja:</div>
              <div class="text-primary" id="historyUpahName">-</div>
            </div>
            <div class="text-muted small">Sumber: <code>hsd_upah_history</code></div>
          </div>
          <div id="upahHistoryLoading" class="spinner-wrap">
            <div class="spinner-border" role="status" aria-hidden="true"></div>
            <span class="ms-2">Memuat riwayat...</span>
          </div>
          <div class="table-responsive d-none" id="upahHistoryWrap">
            <table class="table table-striped table-hover align-middle">
              <thead>
                <tr>
                  <th class="text-nowrap">Tanggal</th>
                  <th class="text-nowrap text-end">Harga Lama</th>
                  <th class="text-nowrap text-end">Harga Baru</th>
                  <th class="text-nowrap">Satuan</th>
                  <th class="text-nowrap">Keterangan</th>
                  <th class="text-nowrap">Diubah Oleh</th>
                </tr>
              </thead>
              <tbody id="upahHistoryBody">
                {{-- rows via JS --}}
              </tbody>
            </table>
          </div>
          <div class="p-3 d-none" id="upahHistoryEmpty">
            <div class="alert alert-secondary mb-0"><i class="fas fa-info-circle me-2"></i>Tidak ada data riwayat.</div>
          </div>
          <div class="p-3 d-none" id="upahHistoryError">
            <div class="alert alert-danger mb-0"><i class="fas fa-triangle-exclamation me-2"></i>Gagal memuat data.</div>
          </div>
        </div>
      </div>
      <div class="modal-footer justify-content-between">
        <small class="text-muted">Menampilkan 50 riwayat terakhir (jika tersedia)</small>
        <button type="button" class="btn btn-secondary rounded" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

@endsection

@push('custom-scripts')
<!-- DataTables Core -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<!-- Bootstrap 5 Integration -->
<script src="{{ asset('assets/plugins/datatables-net-bs5/dataTables.bootstrap5.js') }}"></script>
<!-- Responsive -->
<script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap5.min.js"></script>

<script>
    // Util format rupiah
    function formatRupiah(n){
        const v = Number(n||0);
        return 'Rp ' + v.toLocaleString('id-ID');
    }
    function safeText(v, fallback='-'){
        if(v===null || v===undefined || v==='') return fallback;
        return String(v);
    }
    function fmtDate(raw) {
    if (!raw) return '-';
    // dukung "YYYY-MM-DD HH:MM:SS" atau "YYYY-MM-DDTHH:MM:SS(.ms)(Z)?"
    // 1) kalau ada spasi, anggap lokal dan split manual
    let s = String(raw).trim();

    // Normalisasi: ganti 'T' dengan spasi, buang suffix zona ('Z' / '+hh:mm')
    s = s.replace('T', ' ').replace(/([.]\d+)?([zZ]|[+\-]\d{2}:?\d{2})$/, '');

    const m = s.match(/^(\d{4})-(\d{2})-(\d{2})[ ](\d{2}):(\d{2})(?::(\d{2}))?$/);
    if (!m) {
        // fallback: tampilkan mentah kalau format tak dikenali
        return raw;
    }
    const [_, Y, MM, DD, hh, mm, ss] = m;
    const bulan = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    const blnIdx = Math.max(0, Math.min(11, parseInt(MM,10)-1));
    const jam = String(hh).padStart(2,'0');
    const menit = String(mm).padStart(2,'0');

    return `${parseInt(DD,10)} ${bulan[blnIdx]} ${Y}, ${jam}.${menit}`;
    }


    $(document).ready(function () {
        // Inisialisasi tabel Material secara langsung
        let tableMaterial = $('#tableMaterial').DataTable({ responsive: true });

        // Flag inisialisasi tabel Upah dan AHSP
        let tableUpahInitialized = false;
        let tableAhspInitialized = false;

        // Tab switching
        $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
            const target = $(e.target).attr('data-bs-target');

            if (target === '#upahContent' && !tableUpahInitialized) {
                $('#tableUpah').DataTable({ responsive: true });
                tableUpahInitialized = true;
            }

            if (target === '#ahspContent' && !tableAhspInitialized) {
                $('#tableAhsp').DataTable({ responsive: true });
                tableAhspInitialized = true;
            }

            // Pastikan semua tabel responsif saat tab berubah
            $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust().responsive.recalc();
        });

        // Handle URL tab parameter on page load
        const urlParams = new URLSearchParams(window.location.search);
        const activeTabParam = urlParams.get('tab');

        if (activeTabParam) {
            const tabTrigger = document.querySelector(`#${activeTabParam}-tab`);
            if (tabTrigger) {
                new bootstrap.Tab(tabTrigger).show();
            }
        }

        // --- Logika Modal Konfirmasi Hapus Material ---
        $('#deleteMaterialModal').on('show.bs.modal', function (event) {
            const button = $(event.relatedTarget); // Tombol yang memicu modal
            const materialId = button.data('id');
            const materialName = button.data('name');
            const modal = $(this);

            modal.find('#materialName').text(materialName);
            modal.find('#deleteMaterialForm').attr('action', `/hsd-material/${materialId}`); // Sesuaikan dengan route Anda
        });

        // --- Logika Modal Konfirmasi Hapus Upah ---
        $('#deleteUpahModal').on('show.bs.modal', function (event) {
            const button = $(event.relatedTarget);
            const upahId = button.data('id');
            const upahName = button.data('name');
            const modal = $(this);

            modal.find('#upahName').text(upahName);
            modal.find('#deleteUpahForm').attr('action', `/hsd-upah/${upahId}`); // Sesuaikan dengan route Anda
        });

        // --- Logika Modal Konfirmasi Hapus AHSP ---
        $('#deleteAhspModal').on('show.bs.modal', function (event) {
            const button = $(event.relatedTarget);
            const ahspId = button.data('id');
            const ahspName = button.data('name');
            const modal = $(this);

            modal.find('#ahspName').text(ahspName);
            modal.find('#deleteAhspForm').attr('action', `/ahsp/${ahspId}`); // Sesuaikan dengan route Anda
        });

        // --- Logika Modal Konfirmasi Duplikat AHSP ---
        $('#duplicateAhspModal').on('show.bs.modal', function (event) {
            const button = $(event.relatedTarget);
            const ahspId = button.data('id');
            const ahspName = button.data('name');
            const modal = $(this);

            modal.find('#duplicateAhspName').text(ahspName);
            modal.find('#duplicateAhspForm').attr('action', `/ahsp/${ahspId}/duplicate`); // Sesuaikan dengan route Anda
        });

        // ===================== HISTORY MATERIAL =====================
        const historyMaterialModal = document.getElementById('historyMaterialModal');
        historyMaterialModal.addEventListener('show.bs.modal', function (event) {
            const btn = event.relatedTarget;
            const id = btn.getAttribute('data-id');
            const name = btn.getAttribute('data-name') || '-';

            $('#historyMaterialName').text(name);

            // reset state
            $('#matHistoryLoading').removeClass('d-none');
            $('#matHistoryWrap').addClass('d-none');
            $('#matHistoryEmpty').addClass('d-none');
            $('#matHistoryError').addClass('d-none');
            $('#matHistoryBody').empty();

            // fetch
            $.ajax({
                url: `/hsd-material/${id}/history`,
                method: 'GET',
                dataType: 'json'
            })
            .done(function(res){
                const rows = Array.isArray(res) ? res : (res.data || []);
                if(!rows || rows.length === 0){
                    $('#matHistoryEmpty').removeClass('d-none');
                }else{
                    const html = rows.map(r=>{
                        // r contoh key yang didukung:
                        // created_at / updated_at / tanggal
                        // old_price / new_price / harga_lama / harga_baru / harga
                        // satuan
                        // note / keterangan / sumber
                        // user_name / changed_by
                        const tanggal = fmtDate(r.tanggal || r.created_at || r.updated_at);
                        const hargaLama = r.harga_lama ?? r.old_price ?? null;
                        const hargaBaru = r.harga_baru ?? r.new_price ?? r.harga ?? null;
                        const satuan   = r.satuan || '-';
                        const ket      = safeText(r.keterangan ?? r.note ?? r.sumber, '-');
                        const user     = safeText(r.user_name ?? r.changed_by, '-');

                        return `
                          <tr>
                            <td>${tanggal}</td>
                            <td class="text-end">${hargaLama !== null ? formatRupiah(hargaLama) : '-'}</td>
                            <td class="text-end">${hargaBaru !== null ? formatRupiah(hargaBaru) : '-'}</td>
                            <td>${safeText(satuan,'-')}</td>
                            <td>${ket}</td>
                            <td>${user}</td>
                          </tr>
                        `;
                    }).join('');
                    $('#matHistoryBody').html(html);
                    $('#matHistoryWrap').removeClass('d-none');
                }
            })
            .fail(function(){
                $('#matHistoryError').removeClass('d-none');
            })
            .always(function(){
                $('#matHistoryLoading').addClass('d-none');
            });
        });

        // ===================== HISTORY UPAH =====================
        const historyUpahModal = document.getElementById('historyUpahModal');
        historyUpahModal.addEventListener('show.bs.modal', function (event) {
            const btn = event.relatedTarget;
            const id = btn.getAttribute('data-id');
            const name = btn.getAttribute('data-name') || '-';

            $('#historyUpahName').text(name);

            // reset state
            $('#upahHistoryLoading').removeClass('d-none');
            $('#upahHistoryWrap').addClass('d-none');
            $('#upahHistoryEmpty').addClass('d-none');
            $('#upahHistoryError').addClass('d-none');
            $('#upahHistoryBody').empty();

            // fetch
            $.ajax({
                url: `/hsd-upah/${id}/history`,
                method: 'GET',
                dataType: 'json'
            })
            .done(function(res){
                const rows = Array.isArray(res) ? res : (res.data || []);
                if(!rows || rows.length === 0){
                    $('#upahHistoryEmpty').removeClass('d-none');
                }else{
                    const html = rows.map(r=>{
                        const tanggal = fmtDate(r.tanggal || r.created_at || r.updated_at);
                        const hargaLama = r.harga_lama ?? r.old_price ?? null;
                        const hargaBaru = r.harga_baru ?? r.new_price ?? r.harga ?? null;
                        const satuan   = r.satuan || '-';
                        const ket      = safeText(r.keterangan ?? r.note ?? r.sumber, '-');
                        const user     = safeText(r.user_name ?? r.changed_by, '-');

                        return `
                          <tr>
                            <td>${tanggal}</td>
                            <td class="text-end">${hargaLama !== null ? formatRupiah(hargaLama) : '-'}</td>
                            <td class="text-end">${hargaBaru !== null ? formatRupiah(hargaBaru) : '-'}</td>
                            <td>${safeText(satuan,'-')}</td>
                            <td>${ket}</td>
                            <td>${user}</td>
                          </tr>
                        `;
                    }).join('');
                    $('#upahHistoryBody').html(html);
                    $('#upahHistoryWrap').removeClass('d-none');
                }
            })
            .fail(function(){
                $('#upahHistoryError').removeClass('d-none');
            })
            .always(function(){
                $('#upahHistoryLoading').addClass('d-none');
            });
        });

    });
</script>
@endpush

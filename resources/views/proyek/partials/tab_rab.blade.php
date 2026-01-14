{{-- resources/views/proyek/partials/tab_rab.blade.php --}}
{{-- Pastikan file ini di-include di show.blade.php di dalam div tab-pane untuk rabContent --}}

<div class="card shadow-sm animate__animated animate__fadeInUp animate__faster">
  <div class="card-body">

    {{-- Form Import RAB --}}
    @if($headers->isEmpty())
      <div class="alert alert-info d-flex align-items-center" role="alert">
        <i class="fas fa-info-circle me-2"></i>
        <div>
          Belum ada data RAB untuk proyek ini. Anda bisa mengimpor dari file Excel atau membuat RAB secara manual.
        </div>
      </div>

      {{-- Tombol unduh template & README --}}
      <div class="d-flex flex-wrap gap-2 mb-3">
        <a href="{{ route('rab.template') }}" class="btn btn-outline-success">
          <i class="fas fa-download me-1"></i> Download Template (.xlsx)
        </a>
        <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#rabReadmeModal">
          <i class="fas fa-book me-1"></i> Lihat README Template
        </button>
      </div>

      <div class="card p-3 mb-4 border-dashed animate__animated animate__fadeIn">
        <form action="{{ route('rab.import') }}" method="POST" enctype="multipart/form-data">
          @csrf
          <input type="hidden" name="proyek_id" value="{{ $proyek->id }}">
          <div class="mb-3">
            <label for="file" class="form-label fw-bold">Upload RAB dari Excel (.xlsx, .xls)</label>
            <input type="file" name="file" id="file" class="form-control @error('file') is-invalid @enderror" accept=".xlsx,.xls" required>
            @error('file') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <button type="submit" class="btn btn-success w-100">
            <i class="fas fa-file-excel me-1"></i> Import RAB
          </button>
        </form>
      </div>
    @endif

    {{-- Dropdown Aksi RAB --}}
    <div class="dropdown d-inline-block mb-3 animate__animated animate__fadeIn">
      <button class="btn btn-outline-primary dropdown-toggle shadow-sm" type="button" id="rabActionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
        <i data-feather="tool" class="me-2"></i> Aksi RAB
      </button>
      <ul class="dropdown-menu shadow-lg" aria-labelledby="rabActionsDropdown">
        <li>
          <a class="dropdown-item d-flex align-items-center" href="{{ route('rab.input', $proyek->id) }}">
            <i class="fas fa-plus-circle me-2 text-primary"></i> Buat/Edit RAB Manual
          </a>
        </li>
        <li><hr class="dropdown-divider"></li>
        <li>
          <a class="dropdown-item d-flex align-items-center" href="{{ route('rab.export', $proyek->id) }}">
            <i class="fas fa-file-excel me-2 text-success"></i> Export RAB ke Excel
          </a>
        </li>
        <li>
          <a class="dropdown-item d-flex align-items-center" href="{{ route('rab.template') }}">
            <i class="fas fa-download me-2 text-success"></i> Download Template Import
          </a>
        </li>
        <li>
          <button type="button" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#rabReadmeModal">
            <i class="fas fa-book me-2 text-secondary"></i> README Template
          </button>
        </li>
        <li><hr class="dropdown-divider"></li>
        <li>
          <button type="button" class="dropdown-item text-danger d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#resetRabModal">
            <i class="fas fa-trash-alt me-2"></i> Reset RAB Proyek
          </button>
        </li>
      </ul>
    </div>

    <!-- Modal Konfirmasi Reset RAB -->
    <div class="modal fade" id="resetRabModal" tabindex="-1" aria-labelledby="resetRabModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content animate__animated animate__zoomIn">
          <div class="modal-header bg-danger text-white">
            <h5 class="modal-title" id="resetRabModalLabel"><i class="fas fa-exclamation-triangle me-2"></i> Konfirmasi Reset RAB</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <p class="lead text-center">
              Apakah Anda yakin ingin mereset semua data RAB proyek ini?
            </p>
            <p class="text-danger text-center fw-bold">
              Tindakan ini tidak dapat dibatalkan dan akan menghapus semua header dan detail RAB yang terkait dengan proyek ini.
            </p>
          </div>
          <div class="modal-footer justify-content-center">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <form action="{{ route('proyek.resetRab', $proyek->id) }}" method="POST" id="resetRabForm" class="d-inline-block">
              @csrf
              @method('DELETE')
              <button type="submit" class="btn btn-danger">
                <i class="fas fa-eraser me-1"></i> Reset Sekarang
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>

    {{-- Tampilan RAB jika ada data --}}
    @if($headers->count())
      <div class="table-responsive mt-4 animate__animated animate__fadeIn">
        <h5 class="mb-3 d-flex align-items-center"><i class="fas fa-list-alt me-2"></i> Detail RAB</h5>
        <table class="table table-hover table-sm align-middle rab-table">
          <thead class="table-secondary">
            <tr>
              <th style="width: 10%;">Kode</th>
              <th style="width: 30%;">Deskripsi</th>
              <th style="width: 15%;"></th>
              <th style="width: 15%;"></th>
              <th style="width: 30%;" class="text-end">Nilai</th>
            </tr>
          </thead>
          <tbody>
            @foreach($headers as $h)
              @php
                $isHeaderUtama = !Str::contains($h->kode, '.');
                $hasDetails = $h->rabDetails && $h->rabDetails->count() > 0;
                $collapseId = 'collapse-'.$h->id;
                $rowClass = $isHeaderUtama ? 'bg-light fw-bold text-primary' : '';
              @endphp

              <tr class="{{ $rowClass }} {{ $hasDetails ? 'accordion-toggle cursor-pointer' : '' }}"
                  @if($hasDetails)
                    data-bs-toggle="collapse"
                    data-bs-target="#{{ $collapseId }}"
                    aria-expanded="false"
                    aria-controls="{{ $collapseId }}"
                  @endif>
                <td>{{ $h->kode }}</td>
                <td>
                  {{ $h->deskripsi }}
                  @if($hasDetails)
                    <i class="fas fa-chevron-down float-end collapse-icon"></i>
                  @endif
                </td>
                <td></td>
                <td></td>
                <td class="text-end fw-bold text-success">
                  Rp {{ number_format($h->nilai, 0, ',', '.') }}
                </td>
              </tr>

              @if($hasDetails)
                <tr class="collapse" id="{{ $collapseId }}">
                  <td colspan="5" class="p-0 border-0">
                    <div class="table-responsive bg-white p-2 border-start border-end border-bottom rounded-bottom">
                      <table class="table table-striped table-sm mb-0 child-table">
                        <thead class="table-secondary">
                          <tr>
                            <th style="width: 10%;">Kode</th>
                            <th style="width: 25%;">Deskripsi</th>
                            <th style="width: 20%;">Spesifikasi</th>
                            <th style="width: 8%;">Satuan</th>
                            <th style="width: 10%;" class="text-end">Volume</th>
                            <th style="width: 12%;" class="text-end">Harga</th>
                            <th style="width: 15%;" class="text-end">Total</th>
                          </tr>
                        </thead>
                        <tbody>
                          @php $detailsGrouped = $h->rabDetails->groupBy('area'); @endphp
                          @foreach($detailsGrouped as $area => $groupedDetails)
                            @if($area)
                              <tr class="bg-light fw-semibold text-info">
                                <td colspan="7" class="py-2">
                                  <i class="fas fa-map-marker-alt me-2"></i> {{ $area }}
                                </td>
                              </tr>
                            @endif
                            @foreach($groupedDetails as $d)
                              <tr>
                                <td>{{ $d->kode }}</td>
                                <td>{{ $d->deskripsi }}</td>
                                <td>{{ $d->spesifikasi ?: '-' }}</td>
                                <td>{{ $d->satuan }}</td>
                                <td class="text-end">{{ number_format($d->volume, 2, ',', '.') }}</td>
                                <td class="text-end">Rp {{ number_format($d->harga_satuan, 0, ',', '.') }}</td>
                                <td class="text-end fw-bold">Rp {{ number_format($d->total, 0, ',', '.') }}</td>
                              </tr>
                            @endforeach
                          @endforeach
                        </tbody>
                      </table>
                    </div>
                  </td>
                </tr>
              @endif
            @endforeach
          </tbody>
          <tfoot>
            <tr class="table-secondary fw-bold fs-5">
              <td colspan="4" class="text-end py-3">GRAND TOTAL RAB</td>
              <td class="text-end py-3 text-primary">Rp {{ number_format($grandTotal, 0, ',', '.') }}</td>
            </tr>
          </tfoot>
        </table>
      </div>
    @else
      <div class="card animate__animated animate__fadeInUp animate__faster">
        <div class="card-body text-center py-5">
          <i class="fas fa-file-invoice-dollar fa-3x text-muted mb-3"></i>
          <p class="lead text-muted">Belum ada data RAB yang dibuat atau diimpor.</p>
          <p class="text-muted">Silakan gunakan tombol "Import RAB" atau "Buat/Edit RAB Manual" di atas.</p>
        </div>
      </div>
    @endif

  </div>
</div>

{{-- Modal README Template --}}
<div class="modal fade" id="rabReadmeModal" tabindex="-1" aria-labelledby="rabReadmeModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-light">
        <h5 class="modal-title" id="rabReadmeModalLabel"><i class="fas fa-book me-2"></i> README Template Import RAB</h5>
        <a href="{{ route('rab.template.readme') }}" class="btn btn-sm btn-outline-secondary me-2">
          <i class="fas fa-file-download me-1"></i> Unduh README.txt
        </a>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <h6 class="fw-bold">Struktur File</h6>
        <ul>
          <li><code>RAB_Header</code>: daftar induk & sub-induk.</li>
          <li><code>RAB_Detail</code>: item pekerjaan terhubung ke header via <code>header_kode</code>.</li>
        </ul>

        <h6 class="fw-bold mt-3">Ketentuan Umum</h6>
        <ol>
          <li>Jangan ubah <em>nama sheet</em> dan <em>urutan kolom</em>.</li>
          <li><code>proyek_id</code> tidak diisi di file — sistem mengambil dari proyek yang aktif saat impor.</li>
          <li><code>parent_kode</code> pada <code>RAB_Header</code> mengacu ke <code>kode</code> header induk. Kosongkan untuk header root.</li>
          <li><code>header_kode</code> pada <code>RAB_Detail</code> WAJIB mengacu ke <code>RAB_Header.kode</code>.</li>
          <li><code>kode</code> pada <code>RAB_Detail</code> boleh dikosongkan — sistem akan mengisi otomatis sebagai <code>header_kode.N</code>.</li>
          <li>Harga satuan gabungan = <code>harga_material</code> + <code>harga_upah</code> (tanpa pembulatan).</li>
          <li>Jika <code>ahsp_id</code> / <code>ahsp_kode</code> diisi dan kolom harga kosong, sistem akan ambil harga & satuan dari AHSP.</li>
          <li>Jika kolom <code>total_material</code> / <code>total_upah</code> / <code>total</code> kosong, sistem akan menghitung otomatis.</li>
        </ol>

        <h6 class="fw-bold mt-3">Kolom</h6>
        <p class="mb-1"><code>RAB_Header</code>:</p>
        <pre class="bg-light p-2 rounded mb-3">kategori_id | parent_kode | kode | deskripsi</pre>

        <p class="mb-1"><code>RAB_Detail</code>:</p>
        <pre class="bg-light p-2 rounded">header_kode | kode | deskripsi | area | spesifikasi | satuan | volume |
harga_material | harga_upah | harga_satuan |
total_material | total_upah | total | ahsp_id | ahsp_kode</pre>

        <h6 class="fw-bold mt-3">Contoh Singkat</h6>
        <pre class="bg-light p-2 rounded mb-0">RAB_Header:
1 |   | 1   | PEKERJAAN PERSIAPAN
1 | 1 | 1.1 | PEKERJAAN PEMBERSIHAN

RAB_Detail:
header_kode=1.1 | kode=1.1.1 | deskripsi=Land Clearing | satuan=m2 | volume=53.56 | harga_material=0 | harga_upah=19000</pre>
      </div>
      <div class="modal-footer">
        <a href="{{ route('rab.template') }}" class="btn btn-success">
          <i class="fas fa-download me-1"></i> Download Template (.xlsx)
        </a>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<style>
  .rab-table th, .rab-table td { white-space: nowrap; }
  .rab-table .accordion-toggle { transition: background-color 0.2s ease-in-out; }
  .rab-table .accordion-toggle:hover { background-color: #f8f9fa !important; }
  .rab-table .accordion-toggle[aria-expanded="true"] { background-color: #e9ecef !important; }
  .rab-table .collapse-icon { transition: transform 0.2s ease-in-out; }
  .rab-table .accordion-toggle[aria-expanded="true"] .collapse-icon { transform: rotate(180deg); }
  .child-table thead th { font-size: 0.85rem; padding-top: .5rem; padding-bottom: .5rem; }
  .child-table tbody td { font-size: .875rem; padding-top: .4rem; padding-bottom: .4rem; }
  .border-dashed { border: 2px dashed #e0e0e0; border-radius: .5rem; }
</style>

@push('custom-scripts')
<script>
  if (typeof feather !== 'undefined') { feather.replace(); }
</script>
@endpush

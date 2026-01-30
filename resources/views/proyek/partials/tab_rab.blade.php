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
                $headerDepth = max(0, substr_count((string)$h->kode, '.'));
                $headerPad = $headerDepth * 18; // pixels per level
              @endphp

              <tr class="{{ $rowClass }} {{ $hasDetails ? 'accordion-toggle cursor-pointer' : '' }}"
                  @if($hasDetails)
                    data-bs-toggle="collapse"
                    data-bs-target="#{{ $collapseId }}"
                    aria-expanded="false"
                    aria-controls="{{ $collapseId }}"
                  @endif>
                <td style="padding-left: {{ $headerPad }}px">{{ $h->kode }}</td>
                <td style="padding-left: {{ $headerPad }}px">
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
                                @php
                                  $detailDepth = max(0, substr_count((string)$d->kode, '.'));
                                  $relativeDepth = max(0, $detailDepth - $headerDepth);
                                  $detailPad = $relativeDepth * 14; // smaller indent for details
                                @endphp
                                <tr>
                                  <td style="padding-left: {{ $detailPad }}px">{{ $d->kode }}</td>
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

    {{-- Excel-like filter: kumpulkan data item dari headers/details lalu render UI cari --}}
    @php
      $flatItemsRab = [];
      foreach($headers as $h) {
        $hdrKode = optional($h)->kode ?? '';
        $hdrNama = optional($h)->deskripsi ?? '';
        foreach(($h->rabDetails ?? []) as $d) {
          $vol = (float)($d->volume ?? 0);
          // try multiple possible price fields (from penawaran vs master RAB)
          $unitMat = (float)($d->harga_material_penawaran_item ?? $d->harga_material ?? $d->harga_satuan ?? 0);
          $unitJasa = (float)($d->harga_upah_penawaran_item ?? $d->harga_upah ?? 0);
          // if still zero but there's a combined harga_satuan, use it as material
          if ($unitMat == 0 && isset($d->harga_satuan) && (float)$d->harga_satuan > 0) {
            $unitMat = (float)$d->harga_satuan;
          }
          $flatItemsRab[] = [
            'kode' => (string)($d->kode ?? ''),
            'uraian' => (string)($d->deskripsi ?? ''),
            'spesifikasi' => (string)($d->spesifikasi ?? ''),
            'area' => (string)($d->area ?? ''),
            'header_kode' => (string)$hdrKode,
            'header_nama' => (string)$hdrNama,
            'volume' => $vol,
            'satuan' => (string)($d->satuan ?? ''),
            'unit_mat' => $unitMat,
            'unit_jasa' => $unitJasa,
            'tot_mat' => $unitMat * $vol,
            'tot_jasa' => $unitJasa * $vol,
          ];
        }
      }
    @endphp

    <div class="card mb-4 mt-4 animate__animated animate__fadeInUp">
      <div class="card-header bg-light d-flex align-items-center justify-content-between">
        <h5 class="mb-0 text-primary"><i class="fas fa-filter me-2"></i> Pekerjaan Per Item (Cari)</h5>
      </div>
      <div class="card-body">
        <div class="row g-2 align-items-end mb-3">
          <div class="col-md-6">
            <label class="form-label">Ketik kata kunci (contoh: <code>plafond</code>)</label>
            <input id="excelLikeQueryRab" type="text" class="form-control" placeholder="Cari di kode, uraian, spesifikasi, atau area…">
          </div>
          <div class="col-md-3">
            <label class="form-label">Mode Pencarian</label>
            <select id="excelLikeModeRab" class="form-select">
              <option value="any">Mengandung salah satu kata</option>
              <option value="all" selected>Harus mengandung semua kata</option>
            </select>
          </div>
          <div class="col-md-3">
            <div class="form-check mt-4">
              <input class="form-check-input" type="checkbox" value="1" id="excelLikeUseDiscountRab" checked>
              <label class="form-check-label" for="excelLikeUseDiscountRab">Gunakan diskon global (jika ada)</label>
            </div>
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-hover table-bordered table-sm align-middle" id="tbl-excel-like-rab">
            <thead class="table-light">
              <tr>
                <th style="width:10%">Kode</th>
                <th>Uraian / Spesifikasi</th>
                <th style="width:10%">Area</th>
                <th class="text-end" style="width:10%">Volume</th>
                <th style="width:8%">Sat</th>
                <th class="text-end" style="width:11%">Hrg Sat Material</th>
                <th class="text-end" style="width:11%">Hrg Sat Jasa</th>
                <th class="text-end" style="width:11%">Total Material</th>
                <th class="text-end" style="width:11%">Total Jasa</th>
                <th class="text-end" style="width:11%">Total (Mat+Jasa)</th>
              </tr>
            </thead>
            <tbody>
              <tr><td colspan="10" class="text-center text-muted py-3">Ketik kata kunci untuk menampilkan hasil…</td></tr>
            </tbody>
            <tfoot class="table-light">
              <tr>
                <td colspan="7" class="text-end fw-semibold">TOTAL HASIL
                  <div class="small text-muted">Total Volume: <span id="excelLikeTotVolRab">0</span></div>
                </td>
                <td class="text-end fw-semibold" id="excelLikeTotMatRab">Rp 0</td>
                <td class="text-end fw-semibold" id="excelLikeTotJasaRab">Rp 0</td>
                <td class="text-end fw-bold" id="excelLikeTotAllRab">Rp 0</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>

  </div>
</div>

{{-- Modal README Template --}}
<div class="modal fade" id="rabReadmeModal" tabindex="-1" aria-labelledby="rabReadmeModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-light">
        <h5 class="modal-title" id="rabReadmeModalLabel"><i class="fas fa-book me-2"></i> README Template Import RAB + AHSP Terintegrasi</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-info alert-dismissible fade show" role="alert">
          <i class="fas fa-lightbulb me-2"></i>
          <strong>Fitur Baru:</strong> Template sekarang terintegrasi dengan 6 sheet untuk import RAB + AHSP + Harga Material/Upah dalam satu file!
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>

        <h6 class="fw-bold text-primary">📋 Struktur File (6 Sheet)</h6>
        <ol>
          <li><code>HSD_Material</code>: Daftar harga satuan material</li>
          <li><code>HSD_Upah</code>: Daftar harga satuan upah/jasa</li>
          <li><code>AHSP_Header</code>: Master AHSP (daftar pekerjaan analisa)</li>
          <li><code>AHSP_Detail</code>: Komponen material/upah untuk setiap AHSP</li>
          <li><code>RAB_Header</code>: Daftar header RAB (induk/sub-induk)</li>
          <li><code>RAB_Detail</code>: Daftar item detail RAB dengan referensi ke AHSP</li>
        </ol>

        <h6 class="fw-bold text-primary mt-3">⚙️ Urutan Proses Import (Otomatis)</h6>
        <ol>
          <li>HSD_Material & HSD_Upah diimpor dulu (master harga)</li>
          <li>AHSP_Header & AHSP_Detail diimpor (menciptakan pekerjaan analisa)</li>
          <li>RAB_Header & RAB_Detail diimpor (menciptakan RAB dengan link ke AHSP)</li>
        </ol>

        <h6 class="fw-bold text-primary mt-3">✅ Ketentuan Penting</h6>
        <ul>
          <li>Jangan ubah <strong>nama sheet</strong> dan <strong>urutan kolom</strong> di setiap sheet</li>
          <li>Jangan hapus <strong>baris header</strong> (baris pertama setiap sheet)</li>
          <li>Isi data mulai dari <strong>baris ke-2</strong></li>
          <li><strong>Kolom harga & total bisa kosong</strong> → sistem akan hitung otomatis dari AHSP atau rumus</li>
          <li><strong>ahsp_kode di RAB_Detail</strong> harus match dengan <code>AHSP_Header.kode_pekerjaan</code> untuk auto-linking</li>
        </ul>

        <h6 class="fw-bold text-primary mt-3">📌 Detail Setiap Sheet</h6>

        <p class="mb-2"><strong>HSD_Material:</strong> <code>kode_item | nama_item | satuan | harga_satuan</code></p>
        <p class="text-muted small mb-3">Contoh: MAT.001 | Pasir Malang | m3 | 150000</p>

        <p class="mb-2"><strong>HSD_Upah:</strong> <code>kode_item | nama_item | satuan | harga_satuan</code></p>
        <p class="text-muted small mb-3">Contoh: UPH.001 | Tukang Gali | HOK | 200000</p>

        <p class="mb-2"><strong>AHSP_Header:</strong> <code>kode_pekerjaan | nama_pekerjaan | satuan | catatan</code></p>
        <p class="text-muted small mb-3">Contoh: A.1 | Excavation 1m | m3 | Tanah biasa</p>

        <p class="mb-2"><strong>AHSP_Detail:</strong> <code>ahsp_kode | tipe | kode_item | koefisien</code></p>
        <p class="text-muted small mb-3">Contoh: A.1 | material | MAT.001 | 1.2</p>

        <p class="mb-2"><strong>RAB_Header:</strong> <code>kategori_id | parent_kode | kode | deskripsi</code></p>
        <p class="text-muted small mb-3">Contoh: 1 | 1 | 1.1 | PEKERJAAN PEMBERSIHAN</p>

        <p class="mb-2"><strong>RAB_Detail:</strong> <code>header_kode | kode | deskripsi | area | spesifikasi | satuan | volume | ... | ahsp_kode</code></p>
        <p class="text-muted small mb-3">Contoh: 1.1 | 1.1.1 | Excavation | Lapangan | Tanah biasa | m3 | 50 | ... | A.1</p>

        <h6 class="fw-bold text-primary mt-3">💡 Tips Penggunaan</h6>
        <ul>
          <li>Siapkan <strong>HSD_Material & HSD_Upah dulu</strong> sebelum membuat AHSP_Detail</li>
          <li>Siapkan <strong>AHSP_Header & AHSP_Detail dulu</strong> sebelum membuat RAB_Detail</li>
          <li>Di RAB_Detail, isi <strong>header_kode</strong> (wajib) dan <strong>ahsp_kode</strong> (untuk auto-linking)</li>
          <li>Kolom harga di RAB_Detail bisa kosong - sistem otomatis ambil dari AHSP</li>
          <li>Jika ada peringatan "AHSP tidak ditemukan" → periksa spelling kode AHSP</li>
        </ul>

        <div class="alert alert-warning mt-3 mb-0">
          <strong><i class="fas fa-download me-2"></i>Download Template Lengkap:</strong> Klik tombol di bawah untuk download file template dengan semua 6 sheet
        </div>
      </div>
      <div class="modal-footer">
        <a href="{{ route('rab.template') }}" class="btn btn-success" download>
          <i class="fas fa-download me-1"></i> Download Template 6 Sheet (.xlsx)
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

<script>
  (function(){
    const ALL_ITEMS = @json($flatItemsRab);
    const DISC_COEF = 1.0; // default, project-level discount not applied here

    const qEl   = document.getElementById('excelLikeQueryRab');
    const modeEl= document.getElementById('excelLikeModeRab');
    const discEl= document.getElementById('excelLikeUseDiscountRab');
    const tbody = document.querySelector('#tbl-excel-like-rab tbody');
    const totM  = document.getElementById('excelLikeTotMatRab');
    const totJ  = document.getElementById('excelLikeTotJasaRab');
    const totA  = document.getElementById('excelLikeTotAllRab');
    const totV  = document.getElementById('excelLikeTotVolRab');

    const fmtRp = n => 'Rp ' + (Number(n||0)).toLocaleString('id-ID', {maximumFractionDigits:0});
    const esc   = s => (s||'').replace(/[&<>"]/g, m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;' }[m]));
    const mark  = (text, tokens) => {
      if (!tokens.length) return esc(text||'');
      let out = esc(text||'');
      tokens.forEach(t=>{
        if(!t) return;
        const re = new RegExp('('+t.replace(/[.*+?^${}()|[\]\\]/g,'\\$&')+')','ig');
        out = out.replace(re, '<mark>$1</mark>');
      });
      return out;
    };

    function filterAndRender(){
      if(!tbody) return;
      const raw = (qEl?.value || '').trim();
      const tokens = raw.split(/\s+/).filter(Boolean);
      const mode   = modeEl?.value || 'all';
      const useDisc= !!discEl?.checked;

      if (!tokens.length) {
        tbody.innerHTML = '<tr><td colspan="10" class="text-center text-muted py-3">Ketik kata kunci untuk menampilkan hasil…</td></tr>';
        totM.textContent = fmtRp(0); totJ.textContent = fmtRp(0); totA.textContent = fmtRp(0); totV.textContent = (0).toLocaleString('id-ID', {minimumFractionDigits:2, maximumFractionDigits:2});
        return;
      }

      let sumM=0, sumJ=0, sumA=0, sumV=0, rows=[];

      ALL_ITEMS.forEach(it=>{
        const bucket = [it.kode, it.uraian, it.spesifikasi, it.area].join(' ').toLowerCase();
        const ok = (mode==='any') ? tokens.some(t=>bucket.includes(t.toLowerCase())) : tokens.every(t=>bucket.includes(t.toLowerCase()));
        if (!ok) return;

        const coef = useDisc ? DISC_COEF : 1.0;
        const uMat = (it.unit_mat || 0) * coef;
        const uJas = (it.unit_jasa || 0) * coef;
        const tMat = uMat * (it.volume || 0);
        const tJas = uJas * (it.volume || 0);
        const tAll = tMat + tJas;

        const tVol = Number(it.volume || 0);
        sumM += tMat; sumJ += tJas; sumA += tAll; sumV += tVol;

        rows.push(`
          <tr>
            <td>${mark(it.kode, tokens)}</td>
            <td>
              <div class="fw-semibold">${mark(it.uraian, tokens)}</div>
              ${it.spesifikasi ? `<div class="text-muted small">${mark(it.spesifikasi, tokens)}</div>` : ``}
            </td>
            <td>${mark(it.area, tokens)}</td>
            <td class="text-end">${(it.volume ?? 0).toLocaleString('id-ID', {minimumFractionDigits:2, maximumFractionDigits:2})}</td>
            <td>${esc(it.satuan)}</td>
            <td class="text-end">${fmtRp(uMat)}</td>
            <td class="text-end">${fmtRp(uJas)}</td>
            <td class="text-end">${fmtRp(tMat)}</td>
            <td class="text-end">${fmtRp(tJas)}</td>
            <td class="text-end fw-semibold">${fmtRp(tAll)}</td>
          </tr>
        `);
      });

      tbody.innerHTML = rows.length ? rows.join('') : '<tr><td colspan="10" class="text-center text-muted py-3">Tidak ada item yang cocok.</td></tr>';
      totM.textContent = fmtRp(sumM);
      totJ.textContent = fmtRp(sumJ);
      totA.textContent = fmtRp(sumA);
      totV.textContent = sumV.toLocaleString('id-ID', {minimumFractionDigits:2, maximumFractionDigits:2});
    }

    qEl?.addEventListener('input',  filterAndRender);
    modeEl?.addEventListener('change', filterAndRender);
    discEl?.addEventListener('change', filterAndRender);
  })();
</script>
@endpush

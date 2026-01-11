{{-- resources/views/bapp/create.blade.php --}}
@extends('layout.master')

@section('content')
<form method="POST" action="{{ route('bapp.store', $proyek->id) }}">
  @csrf

  <div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0">Terbitkan BAPP — Minggu ke-{{ $mingguKe }}</h5>
      <a class="btn btn-light btn-sm" href="{{ route('bapp.index', $proyek->id) }}">
        Kembali
      </a>
    </div>

    <div class="card-body">

      {{-- Flash & error --}}
      @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
      @endif
      @if($errors->any())
        <div class="alert alert-danger">
          <div class="fw-semibold mb-1">Gagal menyimpan:</div>
          <ul class="mb-0">
            @foreach($errors->all() as $e)
              <li>{{ $e }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      {{-- Header form --}}
      <div class="row g-3 mb-3">
        <div class="col-md-3">
          <label class="form-label">Nomor BAPP</label>
          <input
            name="nomor_bapp"
            class="form-control @error('nomor_bapp') is-invalid @enderror"
            required
            value="{{ old('nomor_bapp', 'BAPP/'.$proyek->id.'/'.now()->format('Ymd').'/001') }}"
          >
          @error('nomor_bapp') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-3">
          <label class="form-label">Tanggal</label>
          <input
            name="tanggal_bapp"
            type="date"
            id="tanggalBappInput"
            class="form-control @error('tanggal_bapp') is-invalid @enderror"
            required
            value="{{ old('tanggal_bapp', now()->toDateString()) }}"
          >
          <div class="small text-muted mt-1" id="tanggalBappHint"></div>
          @error('tanggal_bapp') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-3">
          <label class="form-label">Penawaran</label>
          <input class="form-control" value="{{ $penawaran?->nama_penawaran ?? '-' }}" disabled>
          <input type="hidden" name="penawaran_id" value="{{ old('penawaran_id', $penawaran->id ?? '') }}">
        </div>

        <div class="col-md-3">
          <label class="form-label">Minggu ke</label>
          <input class="form-control" value="{{ $mingguKe }}" disabled>
          <input type="hidden" name="minggu_ke" value="{{ $mingguKe }}">
          <input type="hidden" name="progress_id" value="{{ old('progress_id', $progress->id ?? '') }}">
        </div>

        <div class="col-12">
          <label class="form-label">Catatan</label>
          <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
        </div>

        <div class="col-md-4">
          <label class="form-label">Penandatangan BAPP</label>
          @php $signBy = old('sign_by', 'sm'); @endphp
          <select name="sign_by" class="form-select">
            <option value="sm" {{ $signBy === 'sm' ? 'selected' : '' }}>Site Manager</option>
            <option value="pm" {{ $signBy === 'pm' ? 'selected' : '' }}>Project Manager</option>
          </select>
          <small class="text-muted">Nama diambil dari detail proyek.</small>
        </div>

        <div class="col-md-4">
          <div class="form-check pt-4">
            <input class="form-check-input" type="checkbox" name="is_final_account" id="isFinalAccount" value="1" 
                   {{ old('is_final_account') ? 'checked' : '' }} onchange="toggleFinalAccountMode()">
            <label class="form-check-label fw-bold" for="isFinalAccount">
              Final Account (Adjustment Qty/Nilai)
            </label>
          </div>
          <small class="text-muted">Centang jika ada kurang/tambah pekerjaan</small>
        </div>

        <div class="col-12" id="finalAccountNotesDiv" style="display: none;">
          <label class="form-label">Catatan Final Account</label>
          <textarea name="final_account_notes" class="form-control" rows="2" placeholder="Jelaskan alasan adjustment qty/nilai (pekerjaan kurang/tambah)">{{ old('final_account_notes') }}</textarea>
        </div>
      </div>

      @php
        // Formatter
        $fmt    = fn($n)=>number_format((float)$n, 2, ',', '.');              // angka/bobot
        $fmtPct = fn($n)=>number_format((float)$n, 2, ',', '.').' %';         // persen
        // Total kolom bobot
        $totWi    = collect($rows)->sum('Wi');
        $totPrev  = collect($rows)->sum('bPrev');
        $totDelta = collect($rows)->sum('bDelta');
        $totNow   = collect($rows)->sum('bNow');
      @endphp

      {{-- Tabel Item Kontrak --}}
      <h6 class="mt-4 mb-2">Item Kontrak</h6>
      {{-- Tabel ringkasan baris (sinkron dgn detail progress) --}}
      <div class="table-responsive">
        <table class="table table-bordered table-sm align-middle" id="bappTable">
          <thead class="table-light">
            <tr>
              <th style="width:8%">KODE</th>
              <th>URAIAN PEKERJAAN</th>

              {{-- Kolom Final Account (hidden by default) --}}
              <th class="final-account-col" style="width:8%; display:none;">QTY KONTRAK</th>
              <th class="final-account-col" style="width:8%; display:none;">QTY REALISASI</th>
              <th class="final-account-col text-end" style="width:10%; display:none;">NILAI KONTRAK</th>
              <th class="final-account-col text-end" style="width:10%; display:none;">NILAI REALISASI</th>
              <th class="final-account-col text-end" style="width:10%; display:none;">ADJUSTMENT</th>

              {{-- BOBOT = % proyek (tanpa tanda %) - Hidden saat Final Account --}}
              <th class="text-end normal-bapp-col" style="width:8%">BOBOT ITEM</th>
              <th class="text-end normal-bapp-col" style="width:10%">BOBOT S/D MINGGU LALU</th>
              <th class="text-end normal-bapp-col" style="width:10%">Δ BOBOT MINGGU INI</th>
              <th class="text-end normal-bapp-col" style="width:10%">BOBOT SAAT INI</th>

              {{-- PROGRESS = % terhadap item (dengan tanda %) - Hanya Prog Saat Ini untuk Final Account --}}
              <th class="text-end normal-bapp-col" style="width:10%">PROG. S/D MINGGU LALU</th>
              <th class="text-end normal-bapp-col" style="width:10%">PROG. MINGGU INI</th>
              <th class="text-end" style="width:10%">PROG. SAAT INI</th>
            </tr>
          </thead>

          <tbody>
            @forelse($rows as $r)
              @php
                // BOBOT (angka) – kiriman dari controller: Wi, bPrev, bDelta, bNow
                $Wi     = (float)($r->Wi     ?? 0);
                $bPrev  = (float)($r->bPrev  ?? 0);
                $bDelta = (float)($r->bDelta ?? 0);
                $bNow   = (float)($r->bNow   ?? 0);

                // PROGRESS (persen terhadap item) – kiriman controller: pPrevItem, pDeltaItem, pNowItem
                // fallback aman bila belum dikirim: hitung dari bobot/Wi.
                $pPrevItem  = isset($r->pPrevItem)  ? (float)$r->pPrevItem  : ($Wi > 0 ? $bPrev  / $Wi * 100 : 0);
                $pDeltaItem = isset($r->pDeltaItem) ? (float)$r->pDeltaItem : ($Wi > 0 ? $bDelta / $Wi * 100 : 0);
                $pNowItem   = isset($r->pNowItem)   ? (float)$r->pNowItem   : ($Wi > 0 ? $bNow   / $Wi * 100 : 0);

                // Final account data
                $qty = (float)($r->qty ?? 0);
                $satuan = $r->satuan ?? '';
                $harga = (float)($r->harga ?? 0);
                $nilaiKontrak = (float)($r->nilai_kontrak ?? 0);
              @endphp

              <tr>
                <td class="text-nowrap">{{ $r->kode }}</td>
                <td>{{ $r->uraian }}</td>

                {{-- Kolom Final Account --}}
                <td class="final-account-col text-center" style="display:none;">
                  {{ number_format($qty, 2) }} {{ $satuan }}
                </td>
                <td class="final-account-col" style="display:none;">
                  <input type="number" step="0.01" min="0" 
                         name="qty_realisasi[{{ $r->id }}]" 
                         class="form-control form-control-sm qty-realisasi-input" 
                         value="{{ old('qty_realisasi.'.$r->id, $qty) }}"
                         data-id="{{ $r->id }}"
                         data-harga="{{ $harga }}"
                         data-qty-kontrak="{{ $qty }}"
                         onchange="updateNilaiRealisasi(this)">
                  <small class="text-muted">{{ $satuan }}</small>
                </td>
                <td class="final-account-col text-end" style="display:none;">
                  Rp <span class="nilai-kontrak" data-id="{{ $r->id }}">{{ number_format($nilaiKontrak, 0, ',', '.') }}</span>
                </td>
                <td class="final-account-col text-end" style="display:none;">
                  Rp <span class="nilai-realisasi" data-id="{{ $r->id }}">{{ number_format($nilaiKontrak, 0, ',', '.') }}</span>
                </td>
                <td class="final-account-col text-end" style="display:none;">
                  Rp <span class="nilai-adjustment" data-id="{{ $r->id }}" style="font-weight:600;">0</span>
                </td>

                {{-- BOBOT = desimal (tanpa %) - Hidden saat Final Account --}}
                <td class="text-end normal-bapp-col">{{ $fmt($Wi) }}</td>
                <td class="text-end normal-bapp-col">{{ $fmt($bPrev) }}</td>
                <td class="text-end normal-bapp-col">{{ $fmt($bDelta) }}</td>
                <td class="text-end normal-bapp-col">{{ $fmt($bNow) }}</td>

                {{-- PROGRESS = persen - Hanya Prog Saat Ini untuk Final Account --}}
                <td class="text-end normal-bapp-col">{{ $fmtPct($pPrevItem) }}</td>
                <td class="text-end normal-bapp-col">{{ $fmtPct($pDeltaItem) }}</td>
                <td class="text-end">{{ $fmtPct($pNowItem) }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="9" class="text-center text-muted py-3">Tidak ada baris untuk ditampilkan.</td>
              </tr>
            @endforelse
          </tbody>

          <tfoot class="table-light">
            <tr>
              <th colspan="2" class="text-end">TOTAL</th>
              {{-- Kolom Final Account totals --}}
              <th class="final-account-col" style="display:none;"></th>
              <th class="final-account-col" style="display:none;"></th>
              <th class="final-account-col text-end" style="display:none;">
                Rp <span id="totalNilaiKontrak" style="font-weight:700;">0</span>
              </th>
              <th class="final-account-col text-end" style="display:none;">
                Rp <span id="totalNilaiRealisasi" style="font-weight:700;">0</span>
              </th>
              <th class="final-account-col text-end" style="display:none;">
                Rp <span id="totalNilaiAdjustment" style="font-weight:700;">0</span>
              </th>
              {{-- Jumlahkan hanya BOBOT (hidden saat Final Account) --}}
              <th class="text-end normal-bapp-col">{{ $fmt($totWi) }}</th>
              <th class="text-end normal-bapp-col">{{ $fmt($totPrev) }}</th>
              <th class="text-end normal-bapp-col">{{ $fmt($totDelta) }}</th>
              <th class="text-end normal-bapp-col">{{ $fmt($totNow) }}</th>
              {{-- Progress tidak dijumlahkan (hidden saat Final Account kecuali Prog Saat Ini) --}}
              <th class="normal-bapp-col"></th>
              <th class="normal-bapp-col"></th>
              <th></th>
            </tr>
          </tfoot>
        </table>
      </div>

      {{-- Section Item Addendum (hanya tampil saat Final Account) --}}
      <div id="addendumSection" style="display: none;" class="mt-4">
        <h6 class="mb-2">Item Addendum (Pekerjaan Tambah di Luar Kontrak)</h6>
        <button type="button" class="btn btn-sm btn-success mb-2" onclick="addAddendumRow()">
          <i class="bi bi-plus-circle"></i> Tambah Item Addendum
        </button>
        
        <div class="table-responsive">
          <table class="table table-bordered table-sm" id="addendumTable">
            <thead class="table-secondary">
              <tr>
                <th width="10%">Kode</th>
                <th width="35%">Uraian Pekerjaan</th>
                <th width="10%">Qty</th>
                <th width="10%">Satuan</th>
                <th width="15%">Harga Satuan</th>
                <th width="15%">Nilai</th>
                <th width="5%">Aksi</th>
              </tr>
            </thead>
            <tbody id="addendumTableBody">
              {{-- Dynamic rows akan ditambahkan via JavaScript --}}
            </tbody>
            <tfoot class="table-light">
              <tr>
                <th colspan="5" class="text-end">TOTAL ADDENDUM</th>
                <th class="text-end">
                  Rp <span id="totalAddendum" style="font-weight:700;">0</span>
                </th>
                <th></th>
              </tr>
            </tfoot>
          </table>
        </div>
        
        {{-- Grand Total Summary --}}
        <div class="card mt-3 border-primary">
          <div class="card-body bg-light">
            <div class="row">
              <div class="col-md-6">
                <table class="table table-sm table-borderless mb-0">
                  <tr>
                    <td class="text-end" style="width: 60%"><strong>Total Nilai Kontrak:</strong></td>
                    <td class="text-end">Rp <span id="summaryNilaiKontrak">0</span></td>
                  </tr>
                  <tr>
                    <td class="text-end"><strong>Total Nilai Realisasi (Kontrak):</strong></td>
                    <td class="text-end">Rp <span id="summaryNilaiRealisasi">0</span></td>
                  </tr>
                  <tr>
                    <td class="text-end"><strong>Total Adjustment:</strong></td>
                    <td class="text-end">Rp <span id="summaryAdjustment" style="font-weight:600;">0</span></td>
                  </tr>
                </table>
              </div>
              <div class="col-md-6">
                <table class="table table-sm table-borderless mb-0">
                  <tr>
                    <td class="text-end" style="width: 60%"><strong>Total Addendum:</strong></td>
                    <td class="text-end">Rp <span id="summaryAddendum">0</span></td>
                  </tr>
                  <tr class="border-top">
                    <td class="text-end" style="font-size: 1.1em;"><strong>NILAI AKHIR:</strong></td>
                    <td class="text-end" style="font-size: 1.1em;">
                      <strong>Rp <span id="nilaiAkhir" style="color: #1967d2;">0</span></strong>
                    </td>
                  </tr>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="text-end">
        <button class="btn btn-primary">Simpan & Terbitkan PDF</button>
      </div>
    </div>
  </div>
</form>
@endsection

@push('custom-scripts')
<script>
function toggleFinalAccountMode() {
  const checkbox = document.getElementById('isFinalAccount');
  const isChecked = checkbox.checked;
  const finalAccountCols = document.querySelectorAll('.final-account-col');
  const normalBappCols = document.querySelectorAll('.normal-bapp-col');
  const finalAccountNotesDiv = document.getElementById('finalAccountNotesDiv');
  const addendumSection = document.getElementById('addendumSection');
  
  // Toggle visibility
  finalAccountCols.forEach(col => {
    col.style.display = isChecked ? '' : 'none';
  });
  
  // Hide normal BAPP columns (Bobot + Prog s/d minggu lalu & Prog minggu ini) when Final Account is checked
  normalBappCols.forEach(col => {
    col.style.display = isChecked ? 'none' : '';
  });
  
  finalAccountNotesDiv.style.display = isChecked ? 'block' : 'none';
  addendumSection.style.display = isChecked ? 'block' : 'none';
  
  // Clear addendum rows when unchecked
  if (!isChecked) {
    document.getElementById('addendumTableBody').innerHTML = '';
    addendumRowIndex = 0;
  }
  
  // Update totals when toggled
  if (isChecked) {
    updateTotals();
  }
}

function updateNilaiRealisasi(input) {
  const itemId = input.dataset.id;
  const harga = parseFloat(input.dataset.harga || 0);
  const qtyKontrak = parseFloat(input.dataset.qtyKontrak || 0);
  const qtyRealisasi = parseFloat(input.value || 0);
  
  const nilaiKontrak = qtyKontrak * harga;
  const nilaiRealisasi = qtyRealisasi * harga;
  const nilaiAdjustment = nilaiRealisasi - nilaiKontrak;
  
  // Update nilai realisasi display
  const nilaiRealisasiSpan = document.querySelector(`.nilai-realisasi[data-id="${itemId}"]`);
  if (nilaiRealisasiSpan) {
    nilaiRealisasiSpan.textContent = new Intl.NumberFormat('id-ID').format(nilaiRealisasi);
  }
  
  // Update nilai adjustment display with color
  const nilaiAdjustmentSpan = document.querySelector(`.nilai-adjustment[data-id="${itemId}"]`);
  if (nilaiAdjustmentSpan) {
    nilaiAdjustmentSpan.textContent = new Intl.NumberFormat('id-ID').format(nilaiAdjustment);
    // Color: green for positive, red for negative, black for zero
    if (nilaiAdjustment > 0) {
      nilaiAdjustmentSpan.style.color = '#137333';
    } else if (nilaiAdjustment < 0) {
      nilaiAdjustmentSpan.style.color = '#d93025';
    } else {
      nilaiAdjustmentSpan.style.color = '#000';
    }
  }
  
  // Update totals
  updateTotals();
}

function updateTotals() {
  let totalKontrak = 0;
  let totalRealisasi = 0;
  let totalAdjustment = 0;
  let totalAddendum = 0;
  
  // Sum all nilai kontrak
  document.querySelectorAll('.nilai-kontrak').forEach(span => {
    const value = parseFloat(span.textContent.replace(/\./g, '').replace(',', '.') || 0);
    totalKontrak += value;
  });
  
  // Sum all nilai realisasi
  document.querySelectorAll('.nilai-realisasi').forEach(span => {
    const value = parseFloat(span.textContent.replace(/\./g, '').replace(',', '.') || 0);
    totalRealisasi += value;
  });
  
  // Sum all nilai adjustment
  document.querySelectorAll('.nilai-adjustment').forEach(span => {
    const value = parseFloat(span.textContent.replace(/\./g, '').replace(',', '.') || 0);
    totalAdjustment += value;
  });
  
  // Sum all addendum items
  document.querySelectorAll('[id^="addendum-nilai-"]').forEach(span => {
    const value = parseFloat(span.textContent.replace(/\./g, '').replace(',', '.') || 0);
    totalAddendum += value;
  });
  
  // Calculate nilai akhir (total realisasi + total addendum)
  const nilaiAkhir = totalRealisasi + totalAddendum;
  
  // Update footer totals (contract items table)
  const totalKontrakSpan = document.getElementById('totalNilaiKontrak');
  const totalRealisasiSpan = document.getElementById('totalNilaiRealisasi');
  const totalAdjustmentSpan = document.getElementById('totalNilaiAdjustment');
  
  if (totalKontrakSpan) {
    totalKontrakSpan.textContent = new Intl.NumberFormat('id-ID').format(totalKontrak);
  }
  if (totalRealisasiSpan) {
    totalRealisasiSpan.textContent = new Intl.NumberFormat('id-ID').format(totalRealisasi);
  }
  if (totalAdjustmentSpan) {
    totalAdjustmentSpan.textContent = new Intl.NumberFormat('id-ID').format(totalAdjustment);
    // Color for total adjustment
    if (totalAdjustment > 0) {
      totalAdjustmentSpan.style.color = '#137333';
    } else if (totalAdjustment < 0) {
      totalAdjustmentSpan.style.color = '#d93025';
    } else {
      totalAdjustmentSpan.style.color = '#000';
    }
  }
  
  // Update addendum total
  const totalAddendumSpan = document.getElementById('totalAddendum');
  if (totalAddendumSpan) {
    totalAddendumSpan.textContent = new Intl.NumberFormat('id-ID').format(totalAddendum);
  }
  
  // Update summary section
  const summaryKontrak = document.getElementById('summaryNilaiKontrak');
  const summaryRealisasi = document.getElementById('summaryNilaiRealisasi');
  const summaryAdjustment = document.getElementById('summaryAdjustment');
  const summaryAddendum = document.getElementById('summaryAddendum');
  const nilaiAkhirSpan = document.getElementById('nilaiAkhir');
  
  if (summaryKontrak) {
    summaryKontrak.textContent = new Intl.NumberFormat('id-ID').format(totalKontrak);
  }
  if (summaryRealisasi) {
    summaryRealisasi.textContent = new Intl.NumberFormat('id-ID').format(totalRealisasi);
  }
  if (summaryAdjustment) {
    summaryAdjustment.textContent = new Intl.NumberFormat('id-ID').format(totalAdjustment);
    if (totalAdjustment > 0) {
      summaryAdjustment.style.color = '#137333';
    } else if (totalAdjustment < 0) {
      summaryAdjustment.style.color = '#d93025';
    } else {
      summaryAdjustment.style.color = '#000';
    }
  }
  if (summaryAddendum) {
    summaryAddendum.textContent = new Intl.NumberFormat('id-ID').format(totalAddendum);
  }
  if (nilaiAkhirSpan) {
    nilaiAkhirSpan.textContent = new Intl.NumberFormat('id-ID').format(nilaiAkhir);
  }
}

// Addendum items management
let addendumRowIndex = 0;

function addAddendumRow() {
  const tbody = document.getElementById('addendumTableBody');
  const index = addendumRowIndex++;
  
  const row = document.createElement('tr');
  row.id = `addendum-row-${index}`;
  row.innerHTML = `
    <td>
      <input type="text" name="addendum_items[${index}][kode]" class="form-control form-control-sm" placeholder="Kode" required>
    </td>
    <td>
      <input type="text" name="addendum_items[${index}][uraian]" class="form-control form-control-sm" placeholder="Uraian pekerjaan" required>
    </td>
    <td>
      <input type="number" step="0.01" name="addendum_items[${index}][qty]" 
             class="form-control form-control-sm text-end" 
             placeholder="0" required
             onchange="calculateAddendumNilai(${index})">
    </td>
    <td>
      <input type="text" name="addendum_items[${index}][satuan]" class="form-control form-control-sm" placeholder="Satuan" required>
    </td>
    <td>
      <input type="number" step="0.01" name="addendum_items[${index}][harga]" 
             class="form-control form-control-sm text-end" 
             placeholder="0" required
             onchange="calculateAddendumNilai(${index})">
    </td>
    <td class="text-end">
      <span id="addendum-nilai-${index}" class="fw-bold">0</span>
    </td>
    <td class="text-center">
      <button type="button" class="btn btn-sm btn-danger" onclick="removeAddendumRow(${index})">
        <i class="bi bi-trash"></i>
      </button>
    </td>
  `;
  
  tbody.appendChild(row);
}

function removeAddendumRow(index) {
  const row = document.getElementById(`addendum-row-${index}`);
  if (row) {
    row.remove();
    updateTotals(); // Update totals after removing row
  }
}

function calculateAddendumNilai(index) {
  const qtyInput = document.querySelector(`input[name="addendum_items[${index}][qty]"]`);
  const hargaInput = document.querySelector(`input[name="addendum_items[${index}][harga]"]`);
  const nilaiSpan = document.getElementById(`addendum-nilai-${index}`);
  
  if (qtyInput && hargaInput && nilaiSpan) {
    const qty = parseFloat(qtyInput.value || 0);
    const harga = parseFloat(hargaInput.value || 0);
    const nilai = qty * harga;
    
    nilaiSpan.textContent = new Intl.NumberFormat('id-ID').format(nilai);
    
    // Update all totals
    updateTotals();
  }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
  const checkbox = document.getElementById('isFinalAccount');
  if (checkbox && checkbox.checked) {
    toggleFinalAccountMode();
  }
  // Initialize totals on load
  updateTotals();
});

(function(){
  // ==== BAPP DATE VALIDATION ====
  @if($proyek->tanggal_mulai && $proyek->tanggal_selesai && isset($tanggalProgress))
  const proyekStart = new Date('{{ $proyek->tanggal_mulai }}');
  const proyekEnd = new Date('{{ $proyek->tanggal_selesai }}');
  const tanggalProgress = new Date('{{ $tanggalProgress }}');
  const mingguKe = {{ $mingguKe }};
  const tanggalBappInput = document.getElementById('tanggalBappInput');
  const tanggalBappHint = document.getElementById('tanggalBappHint');

  function updateBappDateRange() {
    // Hitung start date minggu ke-N (dimulai dari minggu 1)
    const weekStart = new Date(proyekStart);
    weekStart.setDate(weekStart.getDate() + (mingguKe - 1) * 7);
    
    // Hitung end date minggu ke-N
    const weekEnd = new Date(weekStart);
    weekEnd.setDate(weekEnd.getDate() + 6);
    
    // Clamp dengan tanggal akhir proyek
    const weekEndClamped = weekEnd > proyekEnd ? proyekEnd : weekEnd;
    
    // Min = tanggal progress, Max = end of week (atau end proyek)
    const minDate = tanggalProgress;
    const maxDate = weekEndClamped;
    
    // Format untuk input date (YYYY-MM-DD)
    const minStr = minDate.toISOString().split('T')[0];
    const maxStr = maxDate.toISOString().split('T')[0];
    
    // Set min & max
    tanggalBappInput.min = minStr;
    tanggalBappInput.max = maxStr;
    
    // Set default value jika kosong atau di luar range
    const currentVal = tanggalBappInput.value;
    if (!currentVal || currentVal < minStr || currentVal > maxStr) {
      tanggalBappInput.value = minStr; // default = tanggal progress
    }
    
    // Update hint
    const formatDate = (d) => d.toLocaleDateString('id-ID', {day: '2-digit', month: 'short', year: 'numeric'});
    tanggalBappHint.textContent = `Rentang: ${formatDate(minDate)} - ${formatDate(maxDate)}`;
  }

  // Initial update
  updateBappDateRange();
  @endif
  // ==== END BAPP DATE VALIDATION ====
})();
</script>
@endpush

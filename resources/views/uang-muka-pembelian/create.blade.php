@extends('layout.master')

@section('content')
<nav class="page-breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ url('/') }}">Pembelian</a></li>
    <li class="breadcrumb-item"><a href="{{ route('uang-muka-pembelian.index') }}">Uang Muka Pembelian</a></li>
    <li class="breadcrumb-item active" aria-current="page">Buat Uang Muka</li>
  </ol>
</nav>

<div class="row">
  <div class="col-lg-8 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <h6 class="card-title mb-4">Buat Uang Muka Pembelian</h6>

        <form action="{{ route('uang-muka-pembelian.store') }}" method="POST" enctype="multipart/form-data">
          @csrf

          <div class="mb-3">
            <label class="form-label">Purchase Order <span class="text-danger">*</span></label>
            <select name="po_id" class="form-select" id="po_id" required>
              <option value="">-- Pilih PO --</option>
              @foreach(($pos ?? []) as $p)
                <option value="{{ $p->id }}" data-ppn="{{ $p->ppn_persen ?? 0 }}" data-total="{{ $p->total ?? 0 }}" {{ ($po && $po->id === $p->id) ? 'selected' : '' }}>
                  {{ $p->no_po }} — {{ $p->nama_supplier }} ({{ \Carbon\Carbon::parse($p->tanggal)->format('d/m/Y') }})
                </option>
              @endforeach
            </select>
            @error('po_id')
              <div class="text-danger">{{ $message }}</div>
            @enderror
          </div>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Tanggal <span class="text-danger">*</span></label>
              <input type="date" name="tanggal" class="form-control" value="{{ now()->format('Y-m-d') }}" required>
              @error('tanggal')
                <div class="text-danger">{{ $message }}</div>
              @enderror
            </div>
            <div class="col-md-6">
              <label class="form-label">Persentase UM (%)</label>
              <input type="number" id="persentase" class="form-control" placeholder="Contoh: 30" step="0.01" min="0" max="100">
              <small class="text-muted">Isi persentase untuk auto-hitung nominal</small>
            </div>
          </div>

          <div class="row g-3 mt-2">
            <div class="col-md-12">
              <label class="form-label">Nominal <span class="text-danger">*</span></label>
              <input type="number" name="nominal" id="nominal" class="form-control" placeholder="0" step="0.01" required>
              <small class="text-muted">Atau isi manual jika tidak menggunakan persentase</small>
              @error('nominal')
                <div class="text-danger">{{ $message }}</div>
              @enderror
            </div>
          </div>

          <div id="um-breakdown-card" class="card mt-3" style="display:none;">
            <div class="card-body py-3">
              <h6 class="card-title mb-2">Breakdown Uang Muka (Perkiraan)</h6>
              <table class="table table-sm table-borderless mb-0">
                <tr>
                  <td><strong>PPN PO</strong></td>
                  <td class="text-end"><span id="ppn-rate-display">0%</span></td>
                </tr>
                <tr>
                  <td><strong>Perkiraan DPP</strong></td>
                  <td class="text-end"><span id="um-dpp">Rp 0</span></td>
                </tr>
                <tr>
                  <td><strong>Perkiraan PPN</strong></td>
                  <td class="text-end"><span id="um-ppn">Rp 0</span></td>
                </tr>
              </table>
              <small class="text-muted">Breakdown dihitung dari nominal UM dan PPN PO.</small>
            </div>
          </div>

          <div class="mb-3 mt-3">
            <label class="form-label">Keterangan</label>
            <textarea name="keterangan" class="form-control" rows="3" placeholder="Catatan uang muka pembelian..."></textarea>
          </div>

          <div class="mb-3">
            <label class="form-label">File Lampiran (PDF)</label>
            <input type="file" name="file_path" class="form-control" accept=".pdf">
            <small class="text-muted">Max 30MB. Misal: invoice proforma, surat permohonan, dsb</small>
          </div>

          <div class="alert alert-info mt-4">
            <i class="link-icon" data-feather="info"></i>
            <strong>Catatan:</strong> Detail pembayaran (metode, bank, bukti transfer) akan diisi saat proses pembayaran via BKK.
          </div>

          <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-success">
              <i data-feather="save"></i> Simpan
            </button>
            <a href="{{ route('uang-muka-pembelian.index') }}" class="btn btn-secondary">
              <i data-feather="x"></i> Batal
            </a>
          </div>
        </form>
      </div>
    </div>
  </div>

  @if($po)
  <div class="col-lg-4 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <h6 class="card-title mb-3">Info PO</h6>
        <table class="table table-sm table-borderless">
          <tr>
            <td><strong>No. PO</strong></td>
            <td>: {{ $po->no_po }}</td>
          </tr>
          <tr>
            <td><strong>Supplier</strong></td>
            <td>: {{ $po->nama_supplier }}</td>
          </tr>
          <tr>
            <td><strong>Tanggal</strong></td>
            <td>: {{ $po->tanggal ? $po->tanggal->format('d/m/Y') : '-' }}</td>
          </tr>
          <tr>
            <td><strong>PPN PO</strong></td>
            <td>: <span id="po-ppn-label">{{ $po->ppn_persen ?? 0 }}%</span></td>
          </tr>
          <tr>
            <td><strong>Total PO</strong></td>
            <td id="po-total" data-total="{{ $po->total }}">: Rp {{ number_format($po->total, 0, ',', '.') }}</td>
          </tr>
          <tr>
            <td><strong>Proyek</strong></td>
            <td>: {{ $po->proyek?->nama_proyek ?? '-' }}</td>
          </tr>
        </table>
        <span id="po-ppn" data-ppn="{{ $po->ppn_persen ?? 0 }}" style="display:none"></span>
      </div>
    </div>
  </div>
  @endif
</div>

<script>
let poTotals = {};
let poPpnPercent = 0;
document.addEventListener('DOMContentLoaded', function() {
  // Preselect PO if available
  const poSelect = document.getElementById('po_id');
  const breakdownCard = document.getElementById('um-breakdown-card');
  if (poSelect && poSelect.value) {
    loadPoTotal(poSelect);
    loadPoPpnFromSelected(poSelect);
    if (breakdownCard) breakdownCard.style.display = 'block';
  }

  // Load PO data on selection change
  if (poSelect) {
    poSelect.addEventListener('change', function() {
      if (this.value) {
        loadPoTotal(this);
        loadPoPpnFromSelected(this);
        updateUmBreakdown();
        if (breakdownCard) breakdownCard.style.display = 'block';
      } else {
        if (breakdownCard) breakdownCard.style.display = 'none';
      }
    });
  }

  // Auto-calculate nominal from percentage
  const persentaseInput = document.getElementById('persentase');
  if (persentaseInput) {
    persentaseInput.addEventListener('input', function() {
      calculateNominal();
      updateUmBreakdown();
    });
  }

  // Update breakdown on nominal input
  const nominalInput = document.getElementById('nominal');
  if (nominalInput) {
    nominalInput.addEventListener('input', function() {
      updateUmBreakdown();
    });
  }

  // Initial setup from server-provided PO data if available
  if (!poPpnPercent) {
    const poPpnEl = document.getElementById('po-ppn');
    if (poPpnEl) {
      poPpnPercent = parseFloat(poPpnEl.dataset.ppn || 0) || 0;
      const rateDisp = document.getElementById('ppn-rate-display');
      if (rateDisp) rateDisp.textContent = (poPpnPercent || 0) + '%';
    }
  }

  updateUmBreakdown();
});

function loadPoTotal(selectElOrId) {
  // Prefer reading total from selected option's data-total; fallback to Info PO panel
  let poId = typeof selectElOrId === 'string' ? selectElOrId : (selectElOrId?.value || '');
  let total = 0;
  if (typeof selectElOrId !== 'string' && selectElOrId) {
    const opt = selectElOrId.options[selectElOrId.selectedIndex];
    total = parseFloat(opt?.getAttribute('data-total') || 0) || 0;
  }
  if (!total) {
    const poTotalEl = document.getElementById('po-total');
    if (poTotalEl) {
      total = parseFloat(poTotalEl.dataset.total || 0) || 0;
    }
  }
  if (poId) poTotals[poId] = total;
}

function loadPoPpnFromSelected(selectEl) {
  const opt = selectEl.options[selectEl.selectedIndex];
  const ppn = parseFloat(opt?.getAttribute('data-ppn') || 0) || 0;
  poPpnPercent = ppn;
  const rateDisp = document.getElementById('ppn-rate-display');
  if (rateDisp) rateDisp.textContent = (poPpnPercent || 0) + '%';
}

function calculateNominal() {
  const persentase = parseFloat(document.getElementById('persentase').value) || 0;
  const poSelect = document.getElementById('po_id');
  const nominalInput = document.getElementById('nominal');
  
  if (!poSelect || !poSelect.value || !nominalInput) return;
  
  const poTotal = poTotals[poSelect.value] || 0;
  if (poTotal > 0 && persentase > 0) {
    const nominal = (poTotal * persentase) / 100;
    nominalInput.value = Math.round(nominal);
  }
}

function formatRupiah(num) {
  const n = Math.round(Number(num) || 0);
  return 'Rp ' + n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

function updateUmBreakdown() {
  const nominalInput = document.getElementById('nominal');
  if (!nominalInput) return;
  const nominal = parseFloat(nominalInput.value || 0) || 0;
  const rate = parseFloat(poPpnPercent || 0) || 0;

  let dpp = nominal;
  let ppn = 0;
  if (rate > 0) {
    dpp = nominal * (100 / (100 + rate));
    ppn = nominal - dpp;
  }

  const dppEl = document.getElementById('um-dpp');
  const ppnEl = document.getElementById('um-ppn');
  if (dppEl) dppEl.textContent = formatRupiah(dpp);
  if (ppnEl) ppnEl.textContent = formatRupiah(ppn);
}
</script>
@endsection

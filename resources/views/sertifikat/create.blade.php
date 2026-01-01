{{-- resources/views/sertifikat/create.blade.php --}}
@extends('layout.master')

@push('plugin-styles')
<link href="{{ asset('assets/plugins/datatables-net-bs5/dataTables.bootstrap5.css') }}" rel="stylesheet" />
@endpush

@section('content')
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Buat Sertifikat Pembayaran</h5>
  </div>
  <div class="card-body">
    @if ($errors->any())
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>Gagal menyimpan!</strong> Ada beberapa kesalahan:
        <ul class="mb-0 mt-2">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    <form method="POST" action="{{ route('sertifikat.store') }}" id="spForm">
      @csrf
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Pilih BAPP (approved)</label>
          <select class="form-select" name="bapp_id" id="bapp_id" required>
            <option value="">-- Pilih --</option>
            @foreach($bappsPayload as $row)
              <option value="{{ $row['id'] }}">{{ $row['label'] }}</option>
            @endforeach
          </select>
        </div>

        <div class="col-md-3">
          <label class="form-label">Tanggal</label>
          <input type="date" class="form-control" name="tanggal" value="{{ now()->toDateString() }}" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">Termin Ke</label>
          <input type="number" class="form-control" name="termin_ke" id="termin_ke" min="1" required>
        </div>

        <div class="col-md-3">
          <label class="form-label">% Progress</label>
          <input type="number" step="0.0001" class="form-control" name="persen_progress" id="persen_progress" required>
        </div>

        <!-- Untuk mode PISAH: tampilkan Material + Upah terpisah -->
        <div id="split_price_fields" style="display: flex; gap: 15px;">
          <div class="col-md-3">
            <label class="form-label">WO Material (Rp)</label>
            <input type="number" step="0.01" class="form-control" id="nilai_wo_material_input">
            <small class="text-muted">Format: <span id="fmt_nilai_wo_material">Rp 0,00</span></small>
          </div>
          <div class="col-md-3">
            <label class="form-label">WO Upah (Rp)</label>
            <input type="number" step="0.01" class="form-control" id="nilai_wo_jasa_input">
            <small class="text-muted">Format: <span id="fmt_nilai_wo_jasa">Rp 0,00</span></small>
          </div>
          <div class="col-md-3">
            <label class="form-label">Total Penawaran (Rp)</label>
            <input type="number" step="0.01" class="form-control" id="final_total" readonly>
            <small class="text-muted">Format: <span id="fmt_final_total">Rp 0,00</span></small>
          </div>
        </div>

        <!-- Untuk mode GABUNG: tampilkan hanya Total -->
        <div id="combined_price_fields" style="display: none; flex-gap: 15px;">
          <div class="col-md-3">
            <label class="form-label">Total Penawaran (Rp)</label>
            <input type="number" step="0.01" class="form-control" id="final_total_gabung" readonly style="font-weight: bold;" title="Nilai ini akan dibagi otomatis: 50% Material + 50% Upah">
            <small class="text-muted">Format: <span id="fmt_final_total_gabung">Rp 0,00</span></small>
          </div>
        </div>

        <!-- Hidden fields untuk material & upah (diisi via JavaScript sebelum submit) -->
        <input type="hidden" name="nilai_wo_material" id="nilai_wo_material">
        <input type="hidden" name="nilai_wo_jasa" id="nilai_wo_jasa">
        
        <input type="hidden" name="uang_muka_persen" id="uang_muka_persen">
        <input type="hidden" name="price_mode" id="price_mode">

        <!-- Hidden field for uang_muka_penjualan_id -->
        <input type="hidden" name="uang_muka_penjualan_id" id="uang_muka_penjualan_id">

        <!-- UM Info display -->
        <div class="col-md-12" id="um_info_container" style="display: none;">
          <div class="alert alert-info">
            <strong>Info Uang Muka Penjualan:</strong><br>
            Nominal: <span id="um_nominal">-</span> | 
            Digunakan: <span id="um_digunakan">-</span> | 
            Sisa: <span id="um_sisa">-</span>
          </div>
        </div>

        <div class="col-md-12" id="um_rule_container" style="display: none;">
          <div class="alert alert-warning mb-0">
            <strong>Rule Pemotongan Uang Muka:</strong>
            <div id="um_rule_text" class="mt-1">-</div>
          </div>
        </div>
        <div class="col-md-3">
          <label class="form-label">Retensi %</label>
          <input type="number" step="0.01" class="form-control" name="retensi_persen" id="retensi_persen" readonly>
        </div>
        <div class="col-md-3">
          <label class="form-label">PPN %</label>
          <input type="number" step="0.01" class="form-control" name="ppn_persen" id="ppn_persen" readonly>
        </div>

        <div class="col-md-6">
          <label class="form-label">Pemberi Tugas</label>
          <div class="input-group mb-2">
            <input class="form-control" name="pemberi_tugas_nama" id="pt_nama" placeholder="Nama">
            <input class="form-control" name="pemberi_tugas_jabatan" id="pt_jabatan" placeholder="Jabatan">
          </div>
          <input class="form-control" name="pemberi_tugas_perusahaan" id="pt_perusahaan" placeholder="Perusahaan">
        </div>

        <div class="col-md-6">
          <label class="form-label">Penerima Tugas</label>
          <div class="input-group mb-2">
            <input class="form-control" name="penerima_tugas_nama" id="pk_nama" placeholder="Nama">
            <input class="form-control" name="penerima_tugas_jabatan" id="pk_jabatan" placeholder="Jabatan">
          </div>
          <input class="form-control" name="penerima_tugas_perusahaan" id="pk_perusahaan" placeholder="Perusahaan">
        </div>
      </div>

      <div class="mt-4 d-flex gap-2">
        <a href="{{ route('sertifikat.index') }}" class="btn btn-secondary">Batal</a>
        <button type="submit" class="btn btn-primary" id="submitBtn" disabled title="Pilih BAPP terlebih dahulu">Simpan</button>
      </div>
    </form>
  </div>
</div>
@endsection

@push('custom-scripts')
<script>
  // Dataset dari server (keyed by id)
  const BAPPS = (() => {
    const arr = @json($bappsPayload);
    const map = {};
    (arr || []).forEach(x => map[String(x.id)] = x);
    return map;
  })();

  const el  = id => document.getElementById(id);
  const set = (id, v) => { const e = el(id); if (e) e.value = (v ?? ''); };

  const fmtRp = (num) => new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(Number(num) || 0);

  const updateFmt = (inputId, spanId) => {
    const val = Number(el(inputId)?.value || 0);
    const span = el(spanId);
    if (span) span.textContent = fmtRp(val);
  };

  function updateSubmitButtonState() {
    const bappId = el('bapp_id')?.value;
    const submitBtn = el('submitBtn');
    if (submitBtn) {
      if (bappId) {
        submitBtn.disabled = false;
        submitBtn.title = '';
      } else {
        submitBtn.disabled = true;
        submitBtn.title = 'Pilih BAPP terlebih dahulu';
      }
    }
  }

  function fillFromBappId(id) {
    const d = BAPPS[String(id)];
    if (!d) return;

    const priceMode = (d.price_mode || 'pisah').toLowerCase();
    set('price_mode', priceMode);

    if (priceMode === 'gabung') {
      // Mode GABUNG: tampilkan hanya total
      el('split_price_fields').style.display = 'none';
      el('combined_price_fields').style.display = 'block';
      
      set('final_total_gabung', d.final_total);
      updateFmt('final_total_gabung', 'fmt_final_total_gabung');
      
      // Hidden fields akan diisi saat submit dengan 50:50
    } else {
      // Mode PISAH: tampilkan material & upah terpisah
      el('split_price_fields').style.display = 'block';
      el('combined_price_fields').style.display = 'none';
      
      set('nilai_wo_material_input', d.nilai_wo_material);
      set('nilai_wo_jasa_input',     d.nilai_wo_jasa);
      set('final_total',       d.final_total);

      updateFmt('nilai_wo_material_input', 'fmt_nilai_wo_material');
      updateFmt('nilai_wo_jasa_input', 'fmt_nilai_wo_jasa');
      updateFmt('final_total', 'fmt_final_total');
    }

    set('uang_muka_persen',  d.uang_muka_persen);
    set('retensi_persen',    d.retensi_persen);
    set('ppn_persen',        d.ppn_persen);

    // PILIH salah satu: kumulatif (default) atau delta
    set('persen_progress',   d.persen_progress);
    // set('persen_progress',   d.persen_progress_delta); // <- kalau mau periode ini saja

    const mode = (d.uang_muka_mode || 'proporsional').toLowerCase();

    set('termin_ke',         d.termin_ke);

    // Rule pemotongan UM
    const ruleCtr = el('um_rule_container');
    const ruleTxt = el('um_rule_text');
    if (ruleCtr && ruleTxt) {
      ruleCtr.style.display = 'block';
      if (mode === 'utuh') {
        ruleTxt.textContent = 'Mode UTUH: pemotongan Uang Muka dilakukan penuh pada sertifikat ini (sisa UM akan menjadi 0).';
      } else {
        ruleTxt.textContent = 'Mode PROPORSIONAL: pemotongan Uang Muka mengikuti persentase progres (proporsional kumulatif).';
      }
    }

    // Uang Muka Penjualan
    if (d.uang_muka_penjualan_id) {
      set('uang_muka_penjualan_id', d.uang_muka_penjualan_id);
      const infoCtr = el('um_info_container');
      if (infoCtr) {
        infoCtr.style.display = 'block';
        el('um_nominal').textContent = formatRupiah(d.uang_muka_nominal || 0);
        el('um_digunakan').textContent = formatRupiah(d.uang_muka_digunakan || 0);
        el('um_sisa').textContent = formatRupiah((d.uang_muka_nominal || 0) - (d.uang_muka_digunakan || 0));
      }
    } else {
      const infoCtr = el('um_info_container');
      if (infoCtr) infoCtr.style.display = 'none';
      set('uang_muka_penjualan_id', '');
    }

    updateSubmitButtonState();
  }

  function formatRupiah(num) {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      minimumFractionDigits: 0,
      maximumFractionDigits: 0
    }).format(num);
  }

  // Validate form before submission
  document.getElementById('spForm')?.addEventListener('submit', function(e) {
    const bappId = el('bapp_id')?.value;
    if (!bappId) {
      e.preventDefault();
      alert('Pilih BAPP terlebih dahulu!');
      el('bapp_id')?.focus();
      return false;
    }

    const persen = el('persen_progress')?.value;
    if (!persen || parseFloat(persen) <= 0) {
      e.preventDefault();
      alert('Progress % harus diisi dan lebih dari 0!');
      el('persen_progress')?.focus();
      return false;
    }

    const priceMode = el('price_mode')?.value || 'pisah';
    
    if (priceMode === 'gabung') {
      // Mode GABUNG: validasi hanya total, bagi 50:50 untuk hidden fields
      const total = el('final_total_gabung')?.value;
      if (!total || parseFloat(total) <= 0) {
        e.preventDefault();
        alert('Total Penawaran harus diisi dan lebih dari 0!');
        el('final_total_gabung')?.focus();
        return false;
      }
      // Bagi 50:50 untuk hidden fields
      const totalVal = parseFloat(total);
      const half = totalVal / 2;
      el('nilai_wo_material').value = half;
      el('nilai_wo_jasa').value = half;
    } else {
      // Mode PISAH: validasi material & upah terpisah dari visible inputs
      const material = el('nilai_wo_material_input')?.value;
      if (!material || parseFloat(material) <= 0) {
        e.preventDefault();
        alert('WO Material harus diisi dan lebih dari 0!');
        el('nilai_wo_material_input')?.focus();
        return false;
      }

      const jasa = el('nilai_wo_jasa_input')?.value;
      if (!jasa || parseFloat(jasa) <= 0) {
        e.preventDefault();
        alert('WO Upah harus diisi dan lebih dari 0!');
        el('nilai_wo_jasa_input')?.focus();
        return false;
      }
      
      // Salin ke hidden fields
      el('nilai_wo_material').value = material;
      el('nilai_wo_jasa').value = jasa;
    }

    // Show loading
    const submitBtn = el('submitBtn');
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...';
    }
  });

  el('bapp_id')?.addEventListener('change', function(){
    if (this.value) {
      fillFromBappId(this.value);
    } else {
      updateSubmitButtonState();
    }
  });

  ['nilai_wo_material_input','nilai_wo_jasa_input','final_total','final_total_gabung'].forEach(id => {
    const input = el(id);
    if (input) input.addEventListener('input', () => {
      if (id === 'nilai_wo_material_input') updateFmt(id, 'fmt_nilai_wo_material');
      else if (id === 'nilai_wo_jasa_input') updateFmt(id, 'fmt_nilai_wo_jasa');
      else if (id === 'final_total') updateFmt(id, 'fmt_final_total');
      else if (id === 'final_total_gabung') updateFmt(id, 'fmt_final_total_gabung');
    });
  });

  document.addEventListener('DOMContentLoaded', () => {
    const pre = @json($prefillBappId);
    if (pre) {
      const sel = el('bapp_id');
      const opt = [...sel.options].find(o => o.value == String(pre));
      if (opt) { sel.value = String(pre); fillFromBappId(pre); }
    } else {
      updateSubmitButtonState();
    }

    updateFmt('nilai_wo_material_input', 'fmt_nilai_wo_material');
    updateFmt('nilai_wo_jasa_input', 'fmt_nilai_wo_jasa');
    updateFmt('final_total', 'fmt_final_total');
    updateFmt('final_total_gabung', 'fmt_final_total_gabung');
  });
</script>
@endpush

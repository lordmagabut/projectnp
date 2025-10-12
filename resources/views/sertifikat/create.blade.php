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

        <div class="col-md-3">
          <label class="form-label">WO Material (Rp)</label>
          <input type="number" step="0.01" class="form-control" name="nilai_wo_material" id="nilai_wo_material" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">WO Upah (Rp)</label>
          <input type="number" step="0.01" class="form-control" name="nilai_wo_jasa" id="nilai_wo_jasa" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">Total Penawaran (Rp)</label>
          <input type="number" step="0.01" class="form-control" id="final_total" readonly>
        </div>

        <div class="col-md-3">
          <label class="form-label">Uang Muka %</label>
          <input type="number" step="0.01" class="form-control" name="uang_muka_persen" id="uang_muka_persen" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">Pemotongan UM % (= % progress)</label>
          <input type="number" step="0.01" class="form-control" name="pemotongan_um_persen" id="pemotongan_um_persen" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">Retensi %</label>
          <input type="number" step="0.01" class="form-control" name="retensi_persen" id="retensi_persen" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">PPN %</label>
          <input type="number" step="0.01" class="form-control" name="ppn_persen" id="ppn_persen" required>
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
        <button class="btn btn-primary">Simpan</button>
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

  function fillFromBappId(id) {
    const d = BAPPS[String(id)];
    if (!d) return;

    set('nilai_wo_material', d.nilai_wo_material);
    set('nilai_wo_jasa',     d.nilai_wo_jasa);
    set('final_total',       d.final_total);

    set('uang_muka_persen',  d.uang_muka_persen);
    set('retensi_persen',    d.retensi_persen);
    set('ppn_persen',        d.ppn_persen);

    // PILIH salah satu: kumulatif (default) atau delta
    set('persen_progress',   d.persen_progress);
    // set('persen_progress',   d.persen_progress_delta); // <- kalau mau periode ini saja

    set('pemotongan_um_persen', d.persen_progress); // = % progress
    set('termin_ke',         d.termin_ke);
  }

  el('bapp_id').addEventListener('change', function(){
    if (this.value) fillFromBappId(this.value);
  });

  document.addEventListener('DOMContentLoaded', () => {
    const pre = @json($prefillBappId);
    if (pre) {
      const sel = el('bapp_id');
      const opt = [...sel.options].find(o => o.value == String(pre));
      if (opt) { sel.value = String(pre); fillFromBappId(pre); }
    }
  });
</script>
@endpush

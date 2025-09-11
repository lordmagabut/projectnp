@extends('layout.master')

@section('content')
<form method="POST" action="{{ route('proyek.progress.store', $proyek->id) }}">
@csrf
<div class="card shadow-sm">
  <div class="card-header d-flex align-items-center justify-content-between flex-wrap">
    <h5 class="mb-2 mb-md-0">Input Progress Mingguan (Kumulatif per Item)</h5>
    <a href="{{ route('proyek.show',$proyek->id) }}?tab=progress" class="btn btn-light btn-sm">Kembali</a>
  </div>

  <div class="card-body">

    {{-- Flash & Error --}}
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

    <div class="row g-3 align-items-end">
      <div class="col-md-6">
        <label class="form-label">Penawaran Final</label>
        <select class="form-select"
                onchange="location.href='{{ route('proyek.progress.create',$proyek->id) }}?penawaran_id='+this.value">
          @foreach($finalPenawarans as $p)
            <option value="{{ $p->id }}" {{ (int)$p->id === (int)$penawaranId ? 'selected' : '' }}>
              {{ $p->nama_penawaran }} ({{ \Carbon\Carbon::parse($p->tanggal_penawaran)->format('d/m/y') }})
            </option>
          @endforeach
        </select>
        <input type="hidden" name="penawaran_id" value="{{ $penawaranId }}">
      </div>
      <div class="col-md-3">
        <label class="form-label">Minggu ke</label>
        <input type="number" class="form-control" name="minggu_ke" value="{{ $mingguKe }}" min="1">
      </div>
      <div class="col-md-3">
        <label class="form-label">Tanggal</label>
        <input type="date" class="form-control" name="tanggal" value="{{ $tanggal }}">
      </div>
    </div>

    <hr>

    {{-- ==== Cara Mengisi (panduan) ==== --}}
    <div class="mb-3">
      <button class="btn btn-sm btn-outline-secondary" type="button"
              data-bs-toggle="collapse" data-bs-target="#caraIsi"
              aria-expanded="true" aria-controls="caraIsi">
        <i data-feather="help-circle" class="me-1"></i> Cara mengisi form ini
      </button>
      <div id="caraIsi" class="collapse show mt-2">
        <div class="alert alert-secondary small mb-0">
          <ul class="mb-2">
            <li>Kolom <strong>Progress Saat Ini (%)</strong> diisi <u>kumulatif</u> per item (0–100), bukan per-minggu.</li>
            <li>Sistem akan hitung otomatis:
              <div class="ms-2 mt-1">
                <code>Δ% minggu ini = Progress Saat Ini − Progress s/d Minggu Lalu</code><br>
                <code>Δ Bobot minggu ini = Bobot Item × (Δ% / 100)</code><br>
                <code>Bobot Saat Ini = Bobot s/d Minggu Lalu + Δ Bobot minggu ini</code>
              </div>
            </li>
            <li><strong>Target s/d Minggu ke-{{ $mingguKe }}</strong> adalah rencana kumulatif (Kurva-S) hingga minggu ini.</li>
            <li>Gunakan tanda titik untuk desimal (misal <code>12.5</code>), nilai di luar 0–100 akan otomatis dibatasi.</li>
            <li>Jika Anda memasukkan nilai <em>lebih kecil</em> dari progres minggu lalu, selisih negatif akan diabaikan saat simpan (tidak mengurangi progres).</li>
          </ul>
          <div class="mb-0"><em>Tips:</em> isi hanya item yang berubah; kolom lain bisa dibiarkan kosong.</div>
        </div>
      </div>
    </div>
    {{-- ==== /Cara Mengisi ==== --}}

    <div class="table-responsive">
      <table class="table table-bordered table-sm align-middle" id="tbl-progress">
        <thead class="table-light">
          <tr>
            <th style="width:10%">KODE</th>
            <th>URAIAN</th>
            <th class="text-end" style="width:10%">BOBOT</th>
            <th class="text-end" style="width:11%">TARGET S/D MINGGU KE-{{ $mingguKe }}</th>
            <th class="text-end" style="width:11%">BOBOT S/D MINGGU LALU</th>
            <th class="text-end" style="width:10%">BOBOT SAAT INI</th>
            <th class="text-end" style="width:11%">PROG. S/D MINGGU LALU (%)</th>
            <th style="width:12%">PROGRESS SAAT INI (%)</th>
            <th class="text-end" style="width:10%">Δ% MINGGU INI</th>
            <th class="text-end" style="width:11%">Δ BOBOT MINGGU INI</th>
          </tr>
        </thead>
        <tbody>
@php
  $fmt = fn($n)=>number_format((float)$n, 2, ',', '.');
@endphp

@forelse($rows as $it)

  @if(!empty($it->is_header))
    {{-- HEADER (level-1 / level-2) --}}
    <tr class="table-secondary">
      <td class="fw-bold">{{ $it->kode }}</td>
      <td class="fw-bold">{{ $it->uraian }}</td>
      <td class="text-end"></td>
      <td class="text-end"></td>
      <td class="text-end"></td>
      <td class="text-end"></td>
      <td class="text-end"></td>
      <td class="text-end"></td>
      <td class="text-end"></td>
      <td class="text-end"></td>
    </tr>
  @else
    @php
      $id       = $it->id;
      $bobot    = (float)($it->bobot ?? ($bobotMap[$id] ?? 0));  // % proyek utk item
      $targetCum = (float)($plannedToMap[$id] ?? 0);             // target kumulatif s/d minggu N
      $prevArr  = $prevMap[$id] ?? ['prev_bobot_pct_project'=>0.0,'prev_pct_of_item'=>0.0];
      $prevPct  = (float)$prevArr['prev_pct_of_item'];           // % terhadap item
      $prevProj = (float)$prevArr['prev_bobot_pct_project'];     // % terhadap proyek
    @endphp

    <tr data-row="item"
        data-bobot="{{ $bobot }}"
        data-prevpct="{{ $prevPct }}"
        data-realprev="{{ $prevProj }}"
        data-target="{{ $targetCum }}">
      <td class="fw-semibold">{{ $it->kode }}</td>
      <td>{{ $it->uraian }}</td>

      <td class="text-end bobot-item">{{ $fmt($bobot) }}</td>
      <td class="text-end target-2week">{{ $fmt($targetCum) }}</td>
      <td class="text-end prev-bobot">{{ $fmt($prevProj) }}</td>
      <td class="text-end now-bobot">0,00</td>
      <td class="text-end prev-pct">{{ $fmt($prevPct) }}</td>

      {{-- input progress sekarang (kumulatif % terhadap item) --}}
      <td>
        <input type="number"
              inputmode="decimal" lang="en"
              step="0.01" min="0" max="100"
              class="form-control form-control-sm text-end input-pct"
              name="details_pct[{{ $id }}]"
              value="{{ old('details_pct.'.$id) ?? '' }}"
              placeholder="0.00">

        {{-- kirim referensi yang dipakai di layar, pastikan titik sebagai desimal --}}
        <input type="hidden" name="bobot_item[{{ $id }}]" value="{{ number_format($bobot,   6, '.', '') }}">
        <input type="hidden" name="prev_pct[{{ $id }}]"   value="{{ number_format($prevPct, 6, '.', '') }}">
      </td>

      <td class="text-end delta-pct">0,00</td>
      <td class="text-end delta-bobot">0,00</td>
    </tr>
  @endif

@empty
  <tr><td colspan="10" class="text-center text-muted py-4">Tidak ada item pada penawaran ini.</td></tr>
@endforelse
        </tbody>

        {{-- FOOTER: total per kolom (target kumulatif, bobot s/d minggu lalu, bobot saat ini, delta bobot) --}}
        <tfoot class="table-light">
          <tr>
            <th colspan="3" class="text-end">TOTAL</th>
            <th class="text-end" id="tot-target">0,00</th>
            <th class="text-end" id="tot-prev-bobot">0,00</th>
            <th class="text-end" id="tot-now">0,00</th>
            <th></th> {{-- prev % tidak ditotal --}}
            <th></th> {{-- input % tidak ditotal --}}
            <th class="text-end visually-hidden" id="tot-delta-pct">0,00</th>
            <th class="text-end" id="tot-delta-bobot">0,00</th>
          </tr>
        </tfoot>

      </table>
    </div>

    <div class="text-end mt-3">
      <button class="btn btn-primary">Simpan Draft</button>
    </div>
  </div>
</div>
</form>
@endsection

@push('custom-scripts')
<script>
(function(){
  const fmt = n => (Number(n||0)).toLocaleString('id-ID', {minimumFractionDigits:2, maximumFractionDigits:2});
  const deID = s => { if (s==null) return 0; const t=String(s).replace(/\./g,'').replace(',', '.'); const v=parseFloat(t); return isNaN(v)?0:v; };

  function recalcRow(tr){
    const bobot     = parseFloat(tr.dataset.bobot || 0);   // % proyek (item)
    const prevPct   = parseFloat(tr.dataset.prevpct || 0); // % item
    const realPrev  = parseFloat(tr.dataset.realprev || 0);// % proyek

    const inp = tr.querySelector('.input-pct');
    let pct = parseFloat(inp?.value || 0);
    if (isNaN(pct)) pct = 0;
    pct = Math.max(0, Math.min(100, pct)); // clamp 0..100

    const nowBobot   = bobot * pct / 100;       // % proyek
    const deltaPct   = pct - prevPct;           // % item
    const deltaBobot = Math.max(0, nowBobot - realPrev); // jangan kurangi progres

    tr.querySelector('.now-bobot').textContent   = fmt(nowBobot);
    tr.querySelector('.delta-pct').textContent   = fmt(deltaPct);
    tr.querySelector('.delta-bobot').textContent = fmt(deltaBobot);
  }

  function recalcTotal(){
    let tTarget = 0, tPrevBob = 0, tNow = 0, tDB = 0, tDP = 0;
    document.querySelectorAll('#tbl-progress tbody tr[data-row="item"]').forEach(tr=>{
      tTarget += deID(tr.querySelector('.target-2week')?.textContent);
      tPrevBob+= deID(tr.querySelector('.prev-bobot')?.textContent);
      tNow    += deID(tr.querySelector('.now-bobot')?.textContent);
      tDB     += deID(tr.querySelector('.delta-bobot')?.textContent);
      tDP     += deID(tr.querySelector('.delta-pct')?.textContent);
    });
    document.getElementById('tot-target').textContent      = fmt(tTarget);
    document.getElementById('tot-prev-bobot').textContent  = fmt(tPrevBob);
    document.getElementById('tot-now').textContent         = fmt(tNow);
    document.getElementById('tot-delta-bobot').textContent = fmt(tDB);
    const elDP = document.getElementById('tot-delta-pct'); if (elDP) elDP.textContent = fmt(tDP);
  }

  document.querySelectorAll('#tbl-progress tbody tr[data-row="item"]').forEach(tr=>{
    recalcRow(tr);
    tr.querySelector('.input-pct')?.addEventListener('input', ()=>{ recalcRow(tr); recalcTotal(); });
  });
  recalcTotal();
})();
</script>
<style>
  #tbl-progress .table-secondary td{ font-weight:600; }
  #tbl-progress td, #tbl-progress th { vertical-align: middle; }
  .input-pct::-webkit-outer-spin-button,
  .input-pct::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
</style>
@endpush

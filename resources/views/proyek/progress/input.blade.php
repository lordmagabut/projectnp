@extends('layout.master')

@section('content')
<div class="card shadow-sm">
  <div class="card-header d-flex align-items-center justify-content-between">
    <h5 class="mb-0">Input Progress @if($period==='2w') 2-Mingguan @else Mingguan @endif</h5>
    <a href="{{ route('proyek.show',$proyek->id) }}?tab=progress&penawaran_id={{ $penawaranId }}" class="btn btn-light btn-sm">Kembali</a>
  </div>

  <div class="card-body">
    <form method="POST" action="{{ route('proyek.progress.store', $proyek->id) }}">
      @csrf
      <input type="hidden" name="period" id="period" value="{{ $period }}">

      <div class="row g-3 align-items-end">
        <div class="col-md-4">
          <label class="form-label">Penawaran Final</label>
          <select name="penawaran_id" class="form-select"
                  onchange="location.href='{{ route('proyek.progress.create',$proyek->id) }}?penawaran_id='+this.value+'&period={{ $period }}'">
            @foreach($finalPenawarans as $p)
              <option value="{{ $p->id }}" {{ (int)$p->id === (int)$penawaranId ? 'selected':'' }}>
                {{ $p->nama_penawaran }} ({{ \Carbon\Carbon::parse($p->tanggal_penawaran)->format('d/m/y') }})
              </option>
            @endforeach
          </select>
        </div>

        <div class="col-md-2">
          <label class="form-label d-block">Periode</label>
          <div class="btn-group" role="group">
            <button type="button" class="btn btn-sm {{ $period==='1w'?'btn-primary':'btn-outline-primary' }}"
                    onclick="switchPeriod('1w')">Mingguan</button>
            <button type="button" class="btn btn-sm {{ $period==='2w'?'btn-primary':'btn-outline-primary' }}"
                    onclick="switchPeriod('2w')">2-Mingguan</button>
          </div>
          @if($period==='2w')
            <div class="form-text">Gunakan minggu genap (mencakup N-1 & N).</div>
          @endif
        </div>

        <div class="col-md-2">
          <label class="form-label">Minggu ke</label>
          <input type="number" min="1" class="form-control" name="minggu_ke" id="minggu_ke" value="{{ old('minggu_ke', $nextWeek) }}">
        </div>

        <div class="col-md-3">
          <label class="form-label">Tanggal</label>
          <input type="date" class="form-control" name="tanggal" value="{{ old('tanggal', now()->toDateString()) }}">
        </div>
      </div>

      <hr class="my-3">

      {{-- Toolbar cepat --}}
      <div class="d-flex flex-wrap gap-2 mb-2">
        <button type="button" class="btn btn-sm btn-outline-secondary" id="btnPrefill">Isi = Rencana</button>
        <button type="button" class="btn btn-sm btn-outline-secondary" id="btnClear">Kosongkan</button>
        <div class="form-check ms-auto">
          <input class="form-check-input" type="checkbox" value="" id="onlyPlanned" checked>
          <label class="form-check-label" for="onlyPlanned">
            Hanya tampilkan item yang punya rencana periode ini
          </label>
        </div>
      </div>

      <div class="table-responsive">
        <table class="table table-bordered table-sm align-middle" id="progressTable">
          <thead class="table-light">
            <tr>
              <th style="width:14%">Kode</th>
              <th>Uraian</th>
              <th class="text-end" style="width:12%">Bobot Item (%)</th>
              <th class="text-end" style="width:14%">Rencana Periode (%)</th>
              <th class="text-end" style="width:12%">Sisa (%)</th>
              <th class="text-end" style="width:16%">Minggu Ini (%)</th>
            </tr>
          </thead>
          <tbody>
            @forelse($rows as $it)
              <tr data-planned="{{ $it->planned }}">
                <td class="fw-semibold">{{ $it->kode }}</td>
                <td>{{ $it->uraian }}</td>
                <td class="text-end">{{ number_format($it->bobot, 2, ',', '.') }}</td>
                <td class="text-end planned">{{ number_format($it->planned, 2, ',', '.') }}</td>
                <td class="text-end sisa">{{ number_format($it->sisa, 2, ',', '.') }}</td>
                <td>
                  <input type="number" step="0.01" min="0" class="form-control form-control-sm text-end input-val"
                         name="detail[{{ $it->id }}]" value="{{ old('detail.'.$it->id, $it->prefill) }}">
                </td>
              </tr>
            @empty
              <tr><td colspan="6" class="text-center text-muted">Tidak ada item untuk periode ini.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="text-end">
        <button class="btn btn-primary">Simpan Draft</button>
      </div>
    </form>
  </div>
</div>
@endsection

@push('custom-scripts')
<script>
function switchPeriod(p){
  const url = new URL(location.href);
  url.searchParams.set('period', p);
  url.searchParams.set('penawaran_id', '{{ $penawaranId }}');
  const minggu = document.getElementById('minggu_ke')?.value || '';
  if (minggu) url.searchParams.set('minggu_ke', minggu);
  location.href = url.toString();
}

document.addEventListener('DOMContentLoaded', function(){
  const onlyPlanned = document.getElementById('onlyPlanned');
  const tbody = document.querySelector('#progressTable tbody');
  const btnPrefill = document.getElementById('btnPrefill');
  const btnClear = document.getElementById('btnClear');

  function refreshFilter(){
    if (!onlyPlanned || !tbody) return;
    tbody.querySelectorAll('tr').forEach(tr=>{
      const planned = parseFloat(tr.dataset.planned || '0');
      tr.style.display = (onlyPlanned.checked && planned <= 0.0001) ? 'none' : '';
    });
  }
  onlyPlanned?.addEventListener('change', refreshFilter);
  refreshFilter();

  btnPrefill?.addEventListener('click', ()=>{
    tbody.querySelectorAll('tr').forEach(tr=>{
      const planned = parseFloat(tr.dataset.planned || '0');
      const sisaEl = tr.querySelector('.sisa');
      const inp = tr.querySelector('.input-val');
      if (!inp || !sisaEl) return;
      const sisa = parseFloat((sisaEl.textContent || '0').replace(/\./g,'').replace(',','.'));
      const val = Math.min(planned, sisa);
      inp.value = isFinite(val) ? val.toFixed(2) : 0;
    });
  });

  btnClear?.addEventListener('click', ()=>{
    tbody.querySelectorAll('.input-val').forEach(inp=> inp.value = '');
  });
});
</script>
@endpush

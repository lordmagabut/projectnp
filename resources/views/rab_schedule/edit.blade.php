@extends('layout.master')

@push('plugin-styles')
<style>
  .card { border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,.08); border: none; }
  .card-header { border-top-left-radius: 12px; border-top-right-radius: 12px; }
  .badge { border-radius: 1rem; }
  .table thead th { background:#f6f8fb; font-weight:600; }
  .table tbody tr:hover { background:#f9fbff; }
  .table-primary { background:#e9f3ff !important; }
  .table-secondary { background:#f2f5f9 !important; }
  .nowrap { white-space: nowrap; }
  .small-muted { font-size: .8rem; color:#6c757d; }

  .table-area { background:#fff7e6 !important; } /* header area */
  .schedule-preview { position: relative; overflow-x: auto; }
  .schedule-preview table { width: 100%; border-collapse: collapse; font-size: 9px; }
  .schedule-preview th, .schedule-preview td { border: 1px solid #333; padding: 2px 4px; }
  .schedule-preview th { background: #f0f0f0; font-weight: 700; text-align: center; }
  .schedule-preview .row-header { background:#f7f7f7; font-weight:700; }
  .schedule-preview .row-subheader { background:#fdf7e8; font-weight:600; }
  .schedule-preview .curve-overlay { position: absolute; pointer-events: none; opacity: .9; }
</style>
@endpush

@section('content')
<div class="card shadow-sm animate__animated animate__fadeIn">
  <div class="card-header d-flex justify-content-between align-items-center bg-primary text-white">
    <h4 class="m-0 d-flex align-items-center">
      <i data-feather="calendar" class="me-2"></i>
      Atur Schedule — {{ $penawaran->nama_penawaran }}
    </h4>
    <div class="d-flex gap-2">
      <a href="{{ route('rabSchedule.pdf', [$proyek->id, $penawaran->id]) }}" class="btn btn-light btn-sm">
        <i data-feather="printer" class="me-1"></i> Cetak PDF
      </a>
      <a href="{{ route('proyek.show', $proyek->id) }}#schedule" class="btn btn-light btn-sm">
        <i data-feather="arrow-left" class="me-1"></i> Kembali
      </a>
    </div>
  </div>

  <div class="card-body p-3 p-md-4">

    {{-- Alert snapshot --}}
    @if(!$hasSnapshot)
      <div class="alert alert-warning d-flex justify-content-between align-items-center">
        <div>
          <i class="fas fa-info-circle me-1"></i>
          Belum ada snapshot bobot untuk penawaran ini.
        </div>
        <form method="POST" action="{{ route('proyek.penawaran.snapshot', [$proyek->id, $penawaran->id]) }}">
          @csrf
          <button class="btn btn-dark btn-sm" type="submit">
            <i class="fas fa-balance-scale me-1"></i> Buat Bobot Sekarang
          </button>
        </form>
      </div>
    @endif

    {{-- Validasi --}}
    @if ($errors->any())
      <div class="alert alert-danger">
        <div class="fw-bold mb-1"><i class="fas fa-exclamation-triangle me-1"></i> Periksa kembali input Anda:</div>
        <ul class="mb-0">
          @foreach ($errors->all() as $err)
            <li>{{ $err }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    @if($items->isEmpty())
      <div class="alert alert-info mb-0">Tidak ada item pekerjaan pada penawaran ini.</div>
    @else

    <div class="card mb-3">
      <div class="card-header bg-light">
        <h6 class="mb-0 d-flex align-items-center">
          <i data-feather="trending-up" class="me-2 text-primary"></i>
          Preview Schedule & Kurva-S
        </h6>
      </div>
      <div class="card-body">
        <div id="schedulePreview" class="schedule-preview"></div>
      </div>
    </div>

    {{-- FORM SETUP --}}
    <form method="POST" action="{{ route('rabSchedule.save', [$proyek->id, $penawaran->id]) }}" class="mb-3">
      @csrf

      {{-- Meta tanggal schedule --}}
      <div class="card mb-3">
        <div class="card-header bg-light">
          <h6 class="mb-0 d-flex align-items-center">
            <i data-feather="clock" class="me-2 text-info"></i>
            Periode Schedule
          </h6>
        </div>
        <div class="card-body row g-3 align-items-end">
          <div class="col-sm-4">
            <label class="form-label">Tanggal Mulai</label>
            <input type="text" class="form-control form-control-sm" value="{{ $proyek->tanggal_mulai ? \Carbon\Carbon::parse($proyek->tanggal_mulai)->format('d-m-Y') : '—' }}" disabled>
            <div class="small-muted mt-1">Dari data Proyek</div>
          </div>
          <div class="col-sm-4">
            <label class="form-label">Tanggal Selesai</label>
            <input type="text" class="form-control form-control-sm" value="{{ $proyek->tanggal_selesai ? \Carbon\Carbon::parse($proyek->tanggal_selesai)->format('d-m-Y') : '—' }}" disabled>
            <div class="small-muted mt-1">Dari data Proyek</div>
          </div>
          <div class="col-sm-4">
            <label class="form-label">Total Minggu (otomatis)</label>
            <input type="text" class="form-control form-control-sm" value="{{ optional($meta)->total_weeks ?? '0' }}" disabled>
            <div class="small-muted mt-1">Dihitung dari tanggal Proyek</div>
          </div>
        </div>
      </div>

      {{-- Tabel setup (grup: TOP → LEAF → AREA → ITEM) --}}
      <div class="table-responsive">
        <table class="table table-bordered table-sm align-middle">
          <thead class="table-light">
            <tr>
              <th style="width:12%">Kode</th>
              <th>Uraian</th>
              <th class="text-end" style="width:12%">Bobot % Proyek</th>
              <th style="width:12%">Minggu Mulai</th>
              <th style="width:12%">Durasi (minggu)</th>
            </tr>
          </thead>
          <tbody>
          @php
            // Pastikan urutan global stabil: root_sort → leaf_sort → item_sort (dengan area sebagai key tambahan paling belakang)
            $itemsOrdered = $items->sortBy(function($r){
              $rootSort = (int) data_get($r, 'root_sort', data_get($r,'parent_sort',0));
              $leafSort = (int) data_get($r, 'leaf_sort', 0);
              $itemSort = (string) data_get($r, 'item_sort', data_get($r,'kode',''));
              $areaKey  = trim((string) data_get($r, 'area', ''));
              return sprintf('%08d-%08d-%s-%s', $rootSort, $leafSort, $itemSort, $areaKey);
            });

            // Grup TOP (root jika ada; jika tidak, pakai parent)
            $groupsTop = $itemsOrdered->groupBy(function($r){
              return data_get($r, 'root_id', data_get($r, 'parent_id'));
            })->sortBy(function($grp){
              $f = $grp->first();
              return (int) data_get($f,'root_sort', data_get($f,'parent_sort',0));
            });

            $rowIdx = 0;
          @endphp

          @foreach($groupsTop as $topId => $rowsTop)
            @php
              $f       = $rowsTop->first();
              $topKode = data_get($f, 'root_id') ? data_get($f, 'root_kode') : data_get($f, 'parent_kode');
              $topDesc = data_get($f, 'root_id') ? data_get($f, 'root_desc') : data_get($f, 'parent_desc');
            @endphp

            {{-- HEADER LEVEL 1 (TOP) --}}
            <tr class="table-primary">
              <td colspan="5" class="fw-bold">{{ $topKode }} — {{ $topDesc }}</td>
            </tr>

            @php
              // Grup LEAF di dalam TOP
              $leafGroups = $rowsTop->groupBy('leaf_id')->sortBy(function($grp){
                $lf = $grp->first();
                return (int) data_get($lf, 'leaf_sort', 0);
              });
            @endphp

            @foreach($leafGroups as $leafId => $rowsLeaf)
              @php $leaf = $rowsLeaf->first(); @endphp

              @if($leafId)
                <tr class="table-secondary">
                  <td colspan="5" class="fw-bold">{{ data_get($leaf,'leaf_kode') }} — {{ data_get($leaf,'leaf_desc') }}</td>
                </tr>
              @endif

              @php
                // Grup AREA di dalam Leaf (urutkan alfabetis; '__NOAREA__' diakhir)
                $byArea = $rowsLeaf->groupBy(function($r){
                  $a = trim((string) data_get($r,'area',''));
                  return $a !== '' ? $a : '__NOAREA__';
                })->sortKeysUsing(function($a,$b){
                  // '__NOAREA__' selalu di belakang
                  if ($a === '__NOAREA__' && $b !== '__NOAREA__') return 1;
                  if ($b === '__NOAREA__' && $a !== '__NOAREA__') return -1;
                  return strcmp($a,$b);
                });
              @endphp

              @foreach($byArea as $areaName => $rowsArea)
                @php $areaLabel = $areaName === '__NOAREA__' ? 'Tanpa Area' : $areaName; @endphp

                {{-- HEADER AREA --}}
                <tr class="table-area">
                  <td colspan="5" class="fw-bold">
                    <i class="fas fa-layer-group me-1"></i> AREA: {{ $areaLabel }}
                  </td>
                </tr>

                @php
                  // Item dalam Area: urut by item_sort (fallback kode)
                  $rowsAreaOrdered = $rowsArea->sortBy(function($r){
                    return (string) data_get($r,'item_sort', data_get($r,'kode',''));
                  });
                @endphp

                {{-- ITEM (editable) --}}
                @foreach($rowsAreaOrdered as $it)
                  @php
                    $itemId = data_get($it,'item_id');
                    $sch    = $existingSched[$itemId] ?? null;
                    $pct    = (float) data_get($it,'pct',0);
                  @endphp
                  <tr data-item-id="{{ $itemId }}"
                      data-pct="{{ number_format($pct, 4, '.', '') }}"
                      data-leaf-id="{{ data_get($it,'leaf_id') }}"
                      data-parent-id="{{ data_get($it,'parent_id') }}"
                      data-root-id="{{ data_get($it,'root_id') }}">
                    <td class="nowrap">{{ data_get($it,'kode') }}</td>
                    <td>{{ data_get($it,'deskripsi') }}</td>
                    <td class="text-end">{{ number_format($pct, 2, ',', '.') }}</td>
                    <td style="max-width:140px;">
                      <input type="number" min="1" class="form-control form-control-sm"
                             name="rows[{{ $rowIdx }}][minggu_ke]"
                             value="{{ old("rows.$rowIdx.minggu_ke", data_get($sch,'minggu_ke',1)) }}">
                    </td>
                    <td style="max-width:140px;">
                      <input type="number" min="1" class="form-control form-control-sm"
                             name="rows[{{ $rowIdx }}][durasi]"
                             value="{{ old("rows.$rowIdx.durasi", data_get($sch,'durasi',1)) }}">
                    </td>
                    <input type="hidden" name="rows[{{ $rowIdx }}][rab_penawaran_item_id]" value="{{ $itemId }}">
                  </tr>
                  @php $rowIdx++; @endphp
                @endforeach
              @endforeach
            @endforeach
          @endforeach
          </tbody>
        </table>
      </div>

      <div class="d-flex gap-2 mt-3">
        <button class="btn btn-primary btn-sm">
          <i class="fas fa-save me-1"></i> Simpan Setup
        </button>
        <a href="{{ route('rabSchedule.index', $proyek->id) }}" class="btn btn-outline-secondary btn-sm">
          Batal
        </a>
      </div>
    </form>

    {{-- Generate detail mingguan (Kurva-S) --}}
    <form method="POST" action="{{ route('rabSchedule.generate', [$proyek->id, $penawaran->id]) }}" class="mt-2">
      @csrf
      <button class="btn btn-dark btn-sm">
        <i class="fas fa-chart-line me-1"></i> Generate Schedule (Kurva-S)
      </button>
      <div class="small-muted mt-1">
        Tombol ini membagi bobot per item berdasarkan minggu mulai & durasi yang Anda set di atas.
      </div>
    </form>

    @endif
  </div>
</div>
@endsection

@push('custom-scripts')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    if (window.feather) feather.replace();
  });
</script>

@php
  $itemsPreview = $items->map(function($i){
    return [
      'item_id' => (int) $i->item_id,
      'pct' => (float) $i->pct,
      'leaf_id' => $i->leaf_id,
      'leaf_kode' => $i->leaf_kode,
      'leaf_desc' => $i->leaf_desc,
      'leaf_sort' => $i->leaf_sort,
      'parent_id' => $i->parent_id,
      'parent_kode' => $i->parent_kode,
      'parent_desc' => $i->parent_desc,
      'parent_sort' => $i->parent_sort,
      'root_id' => $i->root_id,
      'root_kode' => $i->root_kode,
      'root_desc' => $i->root_desc,
      'root_sort' => $i->root_sort,
    ];
  })->values();
@endphp

<script>
  (function(){
    const totalWeeks = {{ (int)($meta->total_weeks ?? 0) }};
    const itemsData = @json($itemsPreview);

    const itemMeta = {};
    const headerMeta = {};

    itemsData.forEach(it => {
      const rootId = it.root_id ?? it.parent_id ?? it.leaf_id;
      const rootKode = it.root_kode ?? it.parent_kode ?? it.leaf_kode;
      const rootDesc = it.root_desc ?? it.parent_desc ?? it.leaf_desc;
      const rootSort = parseInt(it.root_sort ?? it.parent_sort ?? it.leaf_sort ?? 0);

      const leafId = it.leaf_id ?? it.parent_id ?? it.root_id;
      const leafKode = it.leaf_kode ?? it.parent_kode ?? it.root_kode;
      const leafDesc = it.leaf_desc ?? it.parent_desc ?? it.root_desc;
      const leafSort = parseInt(it.leaf_sort ?? it.parent_sort ?? it.root_sort ?? 0);

      itemMeta[it.item_id] = { leafId, rootId, pct: parseFloat(it.pct || 0) };

      if (!headerMeta[rootId]) {
        headerMeta[rootId] = {
          id: rootId,
          kode: rootKode || '-',
          desc: rootDesc || '-',
          sort: rootSort,
          children: {}
        };
      }
      if (leafId && leafId !== rootId) {
        if (!headerMeta[rootId].children[leafId]) {
          headerMeta[rootId].children[leafId] = {
            id: leafId,
            kode: leafKode || '-',
            desc: leafDesc || '-',
            sort: leafSort
          };
        }
      }
    });

    function formatNum(n) {
      const v = parseFloat(n || 0);
      if (!v) return '';
      return v.toFixed(2).replace('.', ',');
    }

    function buildPreviewTable(rows, weeklyTotals, weeklyCumulative) {
      let html = '<table id="previewTable">';
      html += '<thead><tr>';
      html += '<th style="width:4%">NO</th>';
      html += '<th style="width:22%">WORK DESCRIPTION</th>';
      html += '<th style="width:7%">WEIGHT (%)</th>';
      for (let w = 1; w <= totalWeeks; w++) {
        html += `<th class="week-col">W${w}</th>`;
      }
      html += '<th style="width:6%">REMARKS</th>';
      html += '</tr></thead><tbody>';

      rows.forEach((r, idx) => {
        if (r.depth === 0 && idx > 0) {
          html += `<tr class="work-row"><td colspan="${3 + totalWeeks + 1}">&nbsp;</td></tr>`;
        }
        html += `<tr class="${r.depth === 0 ? 'row-header' : 'row-subheader'} work-row">`;
        html += `<td class="text-center">${r.kode}</td>`;
        html += `<td>${'&nbsp;'.repeat(r.depth * 4)}${r.desc}</td>`;
        html += `<td class="text-end">${formatNum(r.weight)}</td>`;
        for (let w = 1; w <= totalWeeks; w++) {
          const v = r.weeks[w] || 0;
          html += `<td class="text-end">${formatNum(v)}</td>`;
        }
        html += '<td></td>';
        html += '</tr>';
      });

      const totalWeight = weeklyTotals.reduce((a,b) => a + b, 0);
      html += '<tr class="row-header total-row">';
      html += '<td colspan="2" class="text-center">TOTAL</td>';
      html += `<td class="text-end">${formatNum(totalWeight)}</td>`;
      for (let w = 1; w <= totalWeeks; w++) {
        html += `<td class="text-end">${formatNum(weeklyTotals[w-1] || 0)}</td>`;
      }
      html += '<td></td></tr>';

      html += '<tr class="row-header cumulative-row">';
      html += '<td colspan="2" class="text-center">CUMULATIVE</td>';
      html += `<td class="text-end">${formatNum(totalWeight)}</td>`;
      for (let w = 1; w <= totalWeeks; w++) {
        html += `<td class="text-end">${formatNum(weeklyCumulative[w] || 0)}</td>`;
      }
      html += '<td></td></tr>';

      html += '</tbody></table>';
      return html;
    }

    function renderCurve(weeklyCumulative) {
      const wrap = document.getElementById('schedulePreview');
      const table = document.getElementById('previewTable');
      if (!wrap || !table) return;

      const weekHeaders = table.querySelectorAll('th.week-col');
      if (!weekHeaders.length) return;

      const first = weekHeaders[0].getBoundingClientRect();
      const last = weekHeaders[weekHeaders.length - 1].getBoundingClientRect();
      const tableRect = table.getBoundingClientRect();

      const left = first.left - tableRect.left + table.offsetLeft;
      const width = last.right - first.left;

      const workRows = table.querySelectorAll('tbody tr.work-row');
      if (!workRows.length) return;
      const firstRowRect = workRows[0].getBoundingClientRect();
      const lastRowRect = workRows[workRows.length - 1].getBoundingClientRect();
      const top = firstRowRect.top - tableRect.top + table.offsetTop;
      const height = lastRowRect.bottom - firstRowRect.top;

      const maxX = Math.max(1, totalWeeks - 1);
      const stepX = width / maxX;
      const points = [];
      for (let w = 1; w <= totalWeeks; w++) {
        const x = (w - 1) * stepX;
        const yVal = weeklyCumulative[w] || 0;
        const y = height - ((yVal / 100) * height);
        points.push(`${x.toFixed(2)},${y.toFixed(2)}`);
      }

      let svg = `<svg class="curve-overlay" width="${width}" height="${height}" style="left:${left}px; top:${top}px" viewBox="0 0 ${width} ${height}" xmlns="http://www.w3.org/2000/svg">`;
      svg += `<polyline points="${points.join(' ')}" fill="none" stroke="#1f6feb" stroke-width="2" />`;
      svg += '</svg>';

      const old = wrap.querySelector('.curve-overlay');
      if (old) old.remove();
      wrap.insertAdjacentHTML('beforeend', svg);
    }

    function recalcPreview() {
      const weeklyTotals = Array(totalWeeks).fill(0);
      const weeklyByHeader = {};

      document.querySelectorAll('tr[data-item-id]').forEach(row => {
        const itemId = parseInt(row.getAttribute('data-item-id'));
        const meta = itemMeta[itemId];
        if (!meta) return;

        const start = parseInt(row.querySelector('input[name$="[minggu_ke]"]')?.value || 0);
        const dur = parseInt(row.querySelector('input[name$="[durasi]"]')?.value || 0);
        if (!start || !dur) return;

        const pct = meta.pct || 0;
        const per = pct / dur;

        for (let i = 0; i < dur; i++) {
          const w = start + i;
          if (w < 1 || w > totalWeeks) continue;
          weeklyByHeader[meta.leafId] = weeklyByHeader[meta.leafId] || {};
          weeklyByHeader[meta.leafId][w] = (weeklyByHeader[meta.leafId][w] || 0) + per;
          weeklyTotals[w-1] = (weeklyTotals[w-1] || 0) + per;
        }
      });

      Object.values(headerMeta).forEach(root => {
        const rootId = root.id;
        if (!rootId) return;
        Object.values(root.children || {}).forEach(ch => {
          const leafWeeks = weeklyByHeader[ch.id];
          if (!leafWeeks) return;
          weeklyByHeader[rootId] = weeklyByHeader[rootId] || {};
          Object.keys(leafWeeks).forEach(w => {
            const wk = parseInt(w);
            weeklyByHeader[rootId][wk] = (weeklyByHeader[rootId][wk] || 0) + leafWeeks[wk];
          });
        });
      });

      const rows = [];
      const roots = Object.values(headerMeta).sort((a,b) => (a.sort||0) - (b.sort||0));
      roots.forEach(root => {
        const weeks = weeklyByHeader[root.id] || {};
        const weight = Object.values(weeks).reduce((a,b)=>a+b,0);
        if (weight > 0) {
          rows.push({ kode: root.kode, desc: root.desc, depth: 0, weight, weeks });
        }
        const children = Object.values(root.children).sort((a,b)=>(a.sort||0)-(b.sort||0));
        children.forEach(ch => {
          const wks = weeklyByHeader[ch.id] || {};
          const wt = Object.values(wks).reduce((a,b)=>a+b,0);
          if (wt > 0) {
            rows.push({ kode: ch.kode, desc: ch.desc, depth: 1, weight: wt, weeks: wks });
          }
        });
      });

      const weeklyCumulative = {};
      let acc = 0;
      for (let w = 1; w <= totalWeeks; w++) {
        acc += (weeklyTotals[w-1] || 0);
        weeklyCumulative[w] = acc;
      }

      const wrap = document.getElementById('schedulePreview');
      wrap.innerHTML = buildPreviewTable(rows, weeklyTotals, weeklyCumulative);
      renderCurve(weeklyCumulative);
    }

    document.addEventListener('input', function(e){
      const name = (e.target && e.target.name) ? e.target.name : '';
      if (name.includes('[minggu_ke]') || name.includes('[durasi]')) {
        recalcPreview();
      }
    });

    window.addEventListener('resize', function(){
      recalcPreview();
    });

    recalcPreview();
  })();
</script>
@endpush

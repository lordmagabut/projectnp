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
</style>
@endpush

@section('content')
<div class="card shadow-sm animate__animated animate__fadeIn">
  <div class="card-header d-flex justify-content-between align-items-center bg-primary text-white">
    <h4 class="m-0 d-flex align-items-center">
      <i data-feather="calendar" class="me-2"></i>
      Atur Schedule — {{ $penawaran->nama_penawaran }}
    </h4>
    <a href="{{ route('proyek.show', $proyek->id) }}#schedule" class="btn btn-light btn-sm">
      <i data-feather="arrow-left" class="me-1"></i> Kembali
    </a>
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
                  <tr>
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
@endpush

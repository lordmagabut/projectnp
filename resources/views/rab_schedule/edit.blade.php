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
</style>
@endpush

@section('content')
<div class="card shadow-sm animate__animated animate__fadeIn">
  <div class="card-header d-flex justify-content-between align-items-center bg-primary text-white">
    <h4 class="m-0 d-flex align-items-center">
      <i data-feather="calendar" class="me-2"></i>
      Atur Schedule — {{ $penawaran->nama_penawaran }}
    </h4>
    <a href="{{ route('rabSchedule.index', $proyek->id) }}" class="btn btn-light btn-sm">
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
            <label class="form-label">Tanggal Mulai Schedule</label>
            <input type="date" name="start_date" class="form-control form-control-sm"
                   value="{{ old('start_date', optional($meta)->start_date) }}">
          </div>
          <div class="col-sm-4">
            <label class="form-label">Tanggal Selesai Schedule</label>
            <input type="date" name="end_date" class="form-control form-control-sm"
                   value="{{ old('end_date', optional($meta)->end_date) }}">
          </div>
          <div class="col-sm-4">
            <label class="form-label">Total Minggu (otomatis)</label>
            <input type="text" class="form-control form-control-sm" value="{{ optional($meta)->total_weeks }}" disabled>
            <div class="small-muted mt-1">Nilai dihitung dari tanggal mulai & selesai saat disimpan.</div>
          </div>
        </div>
      </div>

      {{-- Tabel setup per item --}}
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
            // Urutkan rapi lalu grup per header TOP (root jika ada; kalau tidak, pakai parent).
            $sorted = $items->sortBy(function($r){
                $topSort  = $r->root_sort ?? $r->parent_sort ?? 0;
                $leafSort = $r->leaf_sort ?? 0;
                return sprintf('%08d-%08d-%s', $topSort, $leafSort, $r->kode);
            });

            $groupsTop = $sorted->groupBy(function($r){
                return $r->root_id ?: $r->parent_id;
            });

            $rowIdx = 0;
          @endphp

          @foreach($groupsTop as $topId => $rowsTop)
            @php
              $f       = $rowsTop->first();
              $topKode = $f->root_id ? $f->root_kode : $f->parent_kode;
              $topDesc = $f->root_id ? $f->root_desc : $f->parent_desc;
            @endphp

            {{-- HEADER LEVEL 1 (contoh: "1 - PEKERJAAN PERSIAPAN") --}}
            <tr class="table-primary">
              <td colspan="5" class="fw-bold">{{ $topKode }} — {{ $topDesc }}</td>
            </tr>

            {{-- HEADER LEVEL 2 (contoh: "1.1 - PEKERJAAN SITE", "1.2 - ...") --}}
            @foreach($rowsTop->groupBy('leaf_id') as $leafId => $rowsLeaf)
              @php $leaf = $rowsLeaf->first(); @endphp
              @if($leafId)
                <tr class="table-secondary">
                  <td colspan="5" class="fw-bold">{{ $leaf->leaf_kode }} — {{ $leaf->leaf_desc }}</td>
                </tr>
              @endif

              {{-- ITEM LEVEL (editable): contoh 1.1.1, 1.1.2 --}}
              @foreach($rowsLeaf as $it)
                @php $sch = $existingSched[$it->item_id] ?? null; @endphp
                <tr>
                  <td>{{ $it->kode }}</td>
                  <td>{{ $it->deskripsi }}</td>
                  <td class="text-end">{{ number_format($it->pct, 2, ',', '.') }}</td>
                  <td style="max-width:140px;">
                    <input type="number" min="1" class="form-control form-control-sm"
                           name="rows[{{ $rowIdx }}][minggu_ke]"
                           value="{{ old("rows.$rowIdx.minggu_ke", $sch->minggu_ke ?? 1) }}">
                  </td>
                  <td style="max-width:140px;">
                    <input type="number" min="1" class="form-control form-control-sm"
                           name="rows[{{ $rowIdx }}][durasi]"
                           value="{{ old("rows.$rowIdx.durasi", $sch->durasi ?? 1) }}">
                  </td>
                  <input type="hidden" name="rows[{{ $rowIdx }}][rab_penawaran_item_id]" value="{{ $it->item_id }}">
                </tr>
                @php $rowIdx++; @endphp
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

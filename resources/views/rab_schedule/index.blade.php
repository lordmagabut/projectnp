@extends('layout.master')

@push('plugin-styles')
<link href="{{ asset('assets/plugins/datatables-net-bs5/dataTables.bootstrap5.css') }}" rel="stylesheet" />
<link href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap5.min.css" rel="stylesheet" />
<style>
  .card { border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,.08); border: none; }
  .card-header { border-top-left-radius: 12px; border-top-right-radius: 12px; }
  .badge { border-radius: 1rem; }
  .table thead th { background:#f6f8fb; font-weight:600; }
  .table tbody tr:hover { background:#f9fbff; }
  .nowrap { white-space: nowrap; }
</style>
@endpush

@section('content')
<div class="card shadow-sm animate__animated animate__fadeIn">
  <div class="card-header d-flex justify-content-between align-items-center bg-primary text-white">
    <h4 class="m-0 d-flex align-items-center">
      <i data-feather="calendar" class="me-2"></i>
      RAB Schedule — {{ $proyek->nama_proyek }}
    </h4>
    <a href="{{ route('proyek.show', $proyek->id) }}" class="btn btn-light btn-sm">
      <i data-feather="arrow-left" class="me-1"></i> Kembali ke Proyek
    </a>
  </div>

  <div class="card-body p-3 p-md-4">

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif
    @if(session('error'))
      <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
        <i class="fas fa-times-circle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    @if($penawarans->isEmpty())
      <div class="alert alert-info mb-0">
        Tidak ada penawaran berstatus <strong>FINAL</strong> untuk proyek ini.
      </div>
    @else
      <div class="table-responsive">
        <table id="tblSchedule" class="table table-hover table-bordered table-sm align-middle nowrap" style="width:100%">
          <thead>
            <tr>
              <th>Nama Penawaran</th>
              <th style="width:110px">Tanggal</th>
              <th style="width:70px">Versi</th>
              <th class="text-end" style="width:160px">Total (Rp)</th>
              <th style="width:110px">Snapshot</th>
              <th style="width:110px">Setup</th>
              <th style="width:320px">Aksi</th>
            </tr>
          </thead>
          <tbody>
            @foreach($penawarans as $p)
              @php
                $snap  = ($hasSnapshots[$p->id] ?? 0) > 0;
                $setup = ($hasSetup[$p->id] ?? 0) > 0;
              @endphp
              <tr>
                <td class="fw-500">
                  {{ $p->nama_penawaran }}
                  <div class="small text-muted">Proyek: {{ $proyek->nama_proyek }}</div>
                </td>
                <td>{{ \Carbon\Carbon::parse($p->tanggal_penawaran)->format('d-m-Y') }}</td>
                <td class="text-center">{{ $p->versi }}</td>
                <td class="text-end">Rp {{ number_format($p->final_total_penawaran, 0, ',', '.') }}</td>
                <td>
                  @if($snap)
                    <span class="badge bg-success">Ada</span>
                  @else
                    <span class="badge bg-warning text-dark">Belum</span>
                  @endif
                </td>
                <td>
                  @if($setup)
                    <span class="badge bg-primary">Siap</span>
                  @else
                    <span class="badge bg-secondary">Belum</span>
                  @endif
                </td>
                <td>
                  <div class="d-flex flex-wrap gap-1">
                    @unless($snap)
                      <form method="POST" action="{{ route('proyek.penawaran.snapshot', [$proyek->id, $p->id]) }}">
                        @csrf
                        <button class="btn btn-sm btn-outline-secondary">
                          <i data-feather="database" class="me-1"></i> Buat Bobot
                        </button>
                      </form>
                    @endunless

                    <a href="{{ route('rabSchedule.edit', [$proyek->id, $p->id]) }}"
                       class="btn btn-sm btn-primary">
                      <i data-feather="edit-3" class="me-1"></i> Atur Schedule
                    </a>

                    <form method="POST" action="{{ route('rabSchedule.generate', [$proyek->id, $p->id]) }}">
                      @csrf
                      <button class="btn btn-sm btn-dark" {{ ($snap && $setup) ? '' : 'disabled' }}>
                        <i data-feather="activity" class="me-1"></i> Generate Kurva-S
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>
</div>
@endsection

@push('plugin-scripts')
<script src="{{ asset('assets/plugins/datatables-net/jquery.dataTables.js') }}"></script>
<script src="{{ asset('assets/plugins/datatables-net-bs5/dataTables.bootstrap5.js') }}"></script>
<script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap5.min.js"></script>
<script src="https://unpkg.com/feather-icons"></script>
@endpush

@push('custom-scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  if (window.feather) feather.replace();

  if ($.fn.DataTable) {
    $('#tblSchedule').DataTable({
      responsive: true,
      pageLength: 10,
      order: [[1,'desc']],
      columnDefs: [{ targets: [6], orderable: false }],
      language: {
        search: "Cari:",
        lengthMenu: "Tampil _MENU_",
        info: "Menampilkan _START_-_END_ dari _TOTAL_",
        paginate: { previous: "Sebelumnya", next: "Berikutnya" },
        zeroRecords: "Tidak ada data"
      }
    });
  }
});
</script>
@endpush

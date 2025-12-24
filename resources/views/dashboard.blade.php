@extends('layout.master')

@push('plugin-styles')
  <link href="{{ asset('assets/plugins/flatpickr/flatpickr.min.css') }}" rel="stylesheet" />
@endpush


@section('content')
{{-- Data sudah dipass dari controller --}}


<div class="row mb-4">
  <div class="col-12 mb-3">
    <form method="GET" action="">
      <div class="d-flex align-items-center gap-2">
        <label for="proyek_id" class="form-label mb-0 me-2">Pilih Proyek:</label>
        <select name="proyek_id" id="proyek_id" class="form-select w-auto" onchange="this.form.submit()">
          @foreach($proyeks as $p)
            <option value="{{ $p->id }}" @if($selectedProyek == $p->id) selected @endif>{{ $p->nama_proyek }}</option>
          @endforeach
        </select>
      </div>
    </form>
  </div>
  <div class="col-12 mb-4">
    <div class="card">
      <div class="card-body">
        <h6 class="card-title mb-3">Kurva S Proyek</h6>
        <div id="kurvaSChart" style="height:320px;"></div>
      </div>
    </div>
  </div>
</div>

<div class="row mb-4">
  <div class="col-md-3">
    <div class="card text-center">
      <div class="card-body">
        <h6 class="card-title mb-1">Proyek Aktif</h6>
        <h2 class="fw-bold text-primary mb-0">{{ $aktif }}</h2>
        <div class="small text-muted">dari {{ $total }} proyek</div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card text-center">
      <div class="card-body">
        <h6 class="card-title mb-1">Selesai</h6>
        <h2 class="fw-bold text-success mb-0">{{ $selesai }}</h2>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card text-center">
      <div class="card-body">
        <h6 class="card-title mb-1">Perencanaan</h6>
        <h2 class="fw-bold text-warning mb-0">{{ $perencanaan }}</h2>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card text-center">
      <div class="card-body">
        <h6 class="card-title mb-1">Tertunda</h6>
        <h2 class="fw-bold text-danger mb-0">{{ $tertunda }}</h2>
      </div>
    </div>
  </div>
</div>


@endsection

@push('plugin-scripts')
  <script src="{{ asset('assets/plugins/flatpickr/flatpickr.min.js') }}"></script>
  <!-- Hanya gunakan CDN ApexCharts di bawah -->
@endpush

@push('custom-scripts')
  <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
  <script>
    // Data Kurva S dari backend
    const minggu = @json($minggu ?? []);
    const akumulasi = @json($akumulasi ?? []);
    const realisasi = @json($realisasi ?? []);
    const categories = minggu.map(m => 'Minggu ' + m);

    if (minggu.length > 0 && akumulasi.length > 0 && realisasi.length > 0) {
      const options = {
        chart: { type: 'line', height: 320, toolbar: { show: false } },
        series: [
          { name: 'Rencana', data: akumulasi },
          { name: 'Realisasi', data: realisasi }
        ],
        xaxis: {
          categories: categories,
          type: 'category',
          labels: { rotate: -45 }
        },
        yaxis: {
          min: 0, max: 100, tickAmount: 5, labels: { formatter: v => v + '%' }
        },
        stroke: { width: 3 },
        markers: { size: 4 },
        colors: ['#008ffb', '#00e396'],
        tooltip: { y: { formatter: v => v + '%' } },
        legend: { position: 'top' },
        grid: { padding: { left: 10, right: 10 } }
      };
      const chart = new ApexCharts(document.querySelector("#kurvaSChart"), options);
      chart.render();
    } else {
      document.querySelector('#kurvaSChart').innerHTML = '<div class="text-center text-muted pt-5 pb-5">Data Kurva S belum tersedia.</div>';
    }
  </script>
@endpush
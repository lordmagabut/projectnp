@extends('layout.master')

@push('plugin-styles')
  <link href="{{ asset('assets/plugins/flatpickr/flatpickr.min.css') }}" rel="stylesheet" />
@endpush


@section('content')
{{-- Data sudah dipass dari controller --}}

{{-- BAST 2 Retention Reminder --}}
@if(isset($upcomingRetention) && $upcomingRetention->isNotEmpty())
<div class="row mb-4">
  <div class="col-12">
    <div class="card border-warning">
      <div class="card-header bg-warning text-dark d-flex align-items-center">
        <i data-feather="alert-triangle" class="me-2"></i>
        <h6 class="mb-0 fw-bold">Reminder: Masa Retensi BAST 2 Akan Berakhir</h6>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th style="width:20%">Nomor BAST</th>
                <th>Proyek</th>
                <th style="width:12%" class="text-center">Tanggal BAST</th>
                <th style="width:12%" class="text-center">Jatuh Tempo</th>
                <th style="width:10%" class="text-center">Sisa Hari</th>
                <th style="width:15%" class="text-end">Nilai Retensi</th>
                <th style="width:8%" class="text-center">Aksi</th>
              </tr>
            </thead>
            <tbody>
              @foreach($upcomingRetention as $ret)
              <tr class="{{ $ret['sisa_hari'] <= 7 ? 'table-danger' : ($ret['sisa_hari'] <= 14 ? 'table-warning' : '') }}">
                <td class="fw-semibold">{{ $ret['nomor_bast'] }}</td>
                <td>{{ $ret['proyek_nama'] }}</td>
                <td class="text-center">{{ $ret['tanggal_bast'] }}</td>
                <td class="text-center">{{ $ret['tanggal_jatuh_tempo'] }}</td>
                <td class="text-center">
                  <span class="badge {{ $ret['sisa_hari'] <= 7 ? 'bg-danger' : ($ret['sisa_hari'] <= 14 ? 'bg-warning text-dark' : 'bg-info') }}">
                    {{ $ret['sisa_hari'] }} hari
                  </span>
                </td>
                <td class="text-end">Rp {{ number_format($ret['nilai_retensi'], 0, ',', '.') }}</td>
                <td class="text-center">
                  <a href="{{ route('bast.show', [$ret['proyek_id'], $ret['id']]) }}" 
                     class="btn btn-sm btn-outline-primary" 
                     title="Lihat Detail BAST">
                    <i data-feather="eye" style="width:14px;height:14px;"></i>
                  </a>
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        <div class="mt-3 small text-muted">
          <i data-feather="info" style="width:14px;height:14px;"></i>
          <em>Tabel ini menampilkan BAST 2 (Retensi) yang masa retensinya akan berakhir dalam 30 hari ke depan.</em>
        </div>
      </div>
    </div>
  </div>
</div>
@endif

<div class="row mb-4">
  <div class="col-12 mb-3">
    <form method="GET" action="" id="filterForm">
      <div class="d-flex align-items-center gap-3 flex-wrap">
        <div class="d-flex align-items-center gap-2">
          <label for="proyek_id" class="form-label mb-0">Proyek:</label>
          <select name="proyek_id" id="proyek_id" class="form-select" style="min-width:200px;" onchange="this.form.submit()">
            @foreach($proyeks as $p)
              <option value="{{ $p->id }}" @if($selectedProyek == $p->id) selected @endif>{{ $p->nama_proyek }}</option>
            @endforeach
          </select>
        </div>
        
        @if($finalPenawarans->isNotEmpty())
        <div class="d-flex align-items-center gap-2">
          <label for="penawaran_id" class="form-label mb-0">Penawaran:</label>
          <select name="penawaran_id" id="penawaran_id" class="form-select" style="min-width:250px;" onchange="this.form.submit()">
            @foreach($finalPenawarans as $pen)
              <option value="{{ $pen->id }}" @if($selectedPenawaranId == $pen->id) selected @endif>
                {{ $pen->nama_penawaran }} ({{ \Carbon\Carbon::parse($pen->tanggal_penawaran)->format('d/m/Y') }})
              </option>
            @endforeach
          </select>
        </div>
        @endif
      </div>
    </form>
  </div>
  <div class="col-12 mb-4">
    <div class="card">
      <div class="card-body">
        <h6 class="card-title mb-3">
          Kurva S Proyek
          @if(isset($selectedPenawaran))
            <span class="badge bg-primary ms-2">{{ $selectedPenawaran->nama_penawaran }}</span>
          @endif
        </h6>
        <div id="kurvaSChart" style="height:320px;"></div>
      </div>
    </div>
  </div>
</div>

{{-- BAST 2 Retention Reminder - Dipindahkan ke atas --}}
@if(isset($upcomingRetention) && $upcomingRetention->isNotEmpty())
<div class="row mb-4">
  <div class="col-12">
    <div class="card border-warning">
      <div class="card-header bg-warning text-dark d-flex align-items-center">
        <i data-feather="alert-triangle" class="me-2"></i>
        <h6 class="mb-0 fw-bold">Reminder: Masa Retensi BAST 2 Akan Berakhir</h6>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th style="width:18%">Nomor BAST</th>
                <th style="width:18%">Proyek</th>
                <th style="width:18%">Penawaran</th>
                <th style="width:10%" class="text-center">Tanggal BAST</th>
                <th style="width:10%" class="text-center">Jatuh Tempo</th>
                <th style="width:8%" class="text-center">Sisa Hari</th>
                <th style="width:12%" class="text-end">Nilai Retensi</th>
                <th style="width:8%" class="text-center">Aksi</th>
              </tr>
            </thead>
            <tbody>
              @foreach($upcomingRetention as $ret)
              <tr class="{{ $ret['sisa_hari'] <= 7 ? 'table-danger' : ($ret['sisa_hari'] <= 14 ? 'table-warning' : '') }}">
                <td class="fw-semibold">{{ $ret['nomor_bast'] }}</td>
                <td>{{ $ret['proyek_nama'] }}</td>
                <td>{{ $ret['penawaran_nama'] }}</td>
                <td class="text-center">{{ $ret['tanggal_bast'] }}</td>
                <td class="text-center">{{ $ret['tanggal_jatuh_tempo'] }}</td>
                <td class="text-center">
                  <span class="badge {{ $ret['sisa_hari'] <= 7 ? 'bg-danger' : ($ret['sisa_hari'] <= 14 ? 'bg-warning text-dark' : 'bg-info') }}">
                    {{ $ret['sisa_hari'] }} hari
                  </span>
                </td>
                <td class="text-end">Rp {{ number_format($ret['nilai_retensi'], 0, ',', '.') }}</td>
                <td class="text-center">
                  <a href="{{ route('bast.show', $ret['id']) }}" 
                     class="btn btn-sm btn-outline-primary" 
                     title="Lihat Detail BAST - Klik untuk membuka halaman detail BAST 2 dan melihat semua informasi serah terima, retensi, dan dapat memproses pelunasan retensi">
                    <i data-feather="eye" style="width:14px;height:14px;"></i>
                  </a>
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        <div class="mt-3 small text-muted">
          <i data-feather="info" style="width:14px;height:14px;"></i>
          <em>Tabel ini menampilkan BAST 2 (Retensi) yang masa retensinya akan berakhir dalam 30 hari ke depan. Kolom <strong>Aksi</strong> memiliki tombol untuk melihat detail BAST lengkap dengan informasi serah terima, nilai retensi, dan opsi untuk memproses pelunasan retensi.</em>
        </div>
      </div>
    </div>
  </div>
</div>
@endif

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
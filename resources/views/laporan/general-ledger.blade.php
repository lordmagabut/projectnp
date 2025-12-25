@extends('layout.master')

@push('plugin-styles')
  <link href="{{ asset('assets/plugins/datatables-net-bs5/dataTables.bootstrap5.css') }}" rel="stylesheet" />
@endpush

@section('content')
<nav class="page-breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ url('/') }}">Laporan</a></li>
    <li class="breadcrumb-item active" aria-current="page">General Ledger</li>
  </ol>
</nav>

<div class="row">
  <div class="col-md-12 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <h6 class="card-title">General Ledger - {{ $nama_perusahaan }}</h6>

        <!-- Filter Form -->
        <form method="GET" action="{{ route('laporan.general-ledger') }}" class="mb-4">
          <div class="row g-3 align-items-end">
            <div class="col-md-3">
              <label class="form-label">Perusahaan</label>
              <select name="id_perusahaan" class="form-select">
                @foreach($perusahaans as $p)
                  <option value="{{ $p->id }}" {{ $selectedPerusahaanId == $p->id ? 'selected' : '' }}>
                    {{ $p->nama_perusahaan }}
                  </option>
                @endforeach
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Tanggal Awal</label>
              <input type="date" name="tanggal_awal" class="form-control" value="{{ $tanggalAwal }}">
            </div>
            <div class="col-md-3">
              <label class="form-label">Tanggal Akhir</label>
              <input type="date" name="tanggal_akhir" class="form-control" value="{{ $tanggalAkhir }}">
            </div>
            <div class="col-md-3">
              <button type="submit" class="btn btn-primary w-100">
                <i class="link-icon" data-feather="filter"></i> Filter
              </button>
            </div>
          </div>
        </form>

        <p class="text-muted mb-3">
          Periode: {{ \Carbon\Carbon::parse($tanggalAwal)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($tanggalAkhir)->format('d/m/Y') }}
        </p>

        @if(session('error'))
          <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <!-- Table -->
        <div class="table-responsive">
          <table id="glTable" class="table table-bordered table-hover">
            <thead class="table-light">
              <tr>
                <th>No. Akun</th>
                <th>Nama Akun</th>
                <th>Tipe</th>
                <th class="text-end">Saldo Awal</th>
                <th class="text-end">Debit</th>
                <th class="text-end">Kredit</th>
                <th class="text-end">Saldo Akhir</th>
              </tr>
            </thead>
            <tbody>
              @php
                $totalSaldoAwal = 0;
                $totalDebit = 0;
                $totalKredit = 0;
                $totalSaldoAkhir = 0;
              @endphp
              
              @forelse($glData as $row)
                @php
                  $totalSaldoAwal += $row['saldo_awal'];
                  $totalDebit += $row['debit'];
                  $totalKredit += $row['kredit'];
                  $totalSaldoAkhir += $row['saldo_akhir'];
                @endphp
                <tr>
                  <td>{{ $row['no_akun'] }}</td>
                  <td>{{ $row['nama_akun'] }}</td>
                  <td>
                    <span class="badge 
                      @if($row['tipe'] == 'Aset') bg-primary
                      @elseif($row['tipe'] == 'Liabilitas') bg-danger
                      @elseif($row['tipe'] == 'Ekuitas') bg-success
                      @elseif(in_array($row['tipe'], ['Pendapatan', 'Penjualan'])) bg-info
                      @elseif($row['tipe'] == 'Beban') bg-warning
                      @else bg-secondary
                      @endif">
                      {{ $row['tipe'] }}
                    </span>
                  </td>
                  <td class="text-end {{ $row['saldo_awal'] < 0 ? 'text-danger' : '' }}">
                    {{ number_format($row['saldo_awal'], 2, ',', '.') }}
                  </td>
                  <td class="text-end">{{ number_format($row['debit'], 2, ',', '.') }}</td>
                  <td class="text-end">{{ number_format($row['kredit'], 2, ',', '.') }}</td>
                  <td class="text-end {{ $row['saldo_akhir'] < 0 ? 'text-danger' : '' }}">
                    <strong>{{ number_format($row['saldo_akhir'], 2, ',', '.') }}</strong>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="7" class="text-center text-muted">Tidak ada transaksi pada periode ini</td>
                </tr>
              @endforelse
            </tbody>
            @if(count($glData) > 0)
            <tfoot class="table-secondary">
              <tr>
                <th colspan="3" class="text-end">TOTAL:</th>
                <th class="text-end">{{ number_format($totalSaldoAwal, 2, ',', '.') }}</th>
                <th class="text-end">{{ number_format($totalDebit, 2, ',', '.') }}</th>
                <th class="text-end">{{ number_format($totalKredit, 2, ',', '.') }}</th>
                <th class="text-end">{{ number_format($totalSaldoAkhir, 2, ',', '.') }}</th>
              </tr>
            </tfoot>
            @endif
          </table>
        </div>

        <!-- Export Buttons -->
        <div class="mt-3">
          <button onclick="window.print()" class="btn btn-secondary">
            <i class="link-icon" data-feather="printer"></i> Print
          </button>
        </div>

      </div>
    </div>
  </div>
</div>

<style>
  @media print {
    .sidebar, .navbar, .page-breadcrumb, form, .btn, nav { display: none !important; }
    .card { border: none !important; box-shadow: none !important; }
    .table { font-size: 12px; }
  }
</style>

@endsection

@push('plugin-scripts')
  <script src="{{ asset('assets/plugins/datatables-net/jquery.dataTables.js') }}"></script>
  <script src="{{ asset('assets/plugins/datatables-net-bs5/dataTables.bootstrap5.js') }}"></script>
@endpush

@push('custom-scripts')
<script>
  $(function() {
    'use strict';

    if ($('#glTable').length) {
      $('#glTable').DataTable({
        "aLengthMenu": [[10, 30, 50, -1], [10, 30, 50, "All"]],
        "iDisplayLength": 30,
        "language": { search: "Cari:" },
        "order": [[0, "asc"]], // Sort by No. Akun
        "dom": 'Bfrtip',
      });
    }

    // Initialize Feather Icons
    if (feather) {
      feather.replace();
    }
  });
</script>
@endpush

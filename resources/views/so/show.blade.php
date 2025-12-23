@extends('layout.master')

@section('content')
<nav class="page-breadcrumb d-print-none">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('so.index') }}">Penjualan</a></li>
    <li class="breadcrumb-item active" aria-current="page">Detail SO</li>
  </ol>
</nav>

<div class="row justify-content-center">
  <div class="col-md-10">
    <div class="card shadow-none border-0">
      <div class="card-header d-print-none bg-transparent border-bottom d-flex justify-content-between align-items-center p-3">
        <a href="{{ route('so.index') }}" class="btn btn-outline-secondary">
          <i data-feather="arrow-left" class="icon-sm"></i> Kembali
        </a>
        <div class="d-flex align-items-center">
          <button onclick="window.print()" class="btn btn-info text-white me-2"><i data-feather="printer" class="icon-sm"></i> Cetak</button>
          <span class="badge bg-primary py-2 px-3">SO</span>
        </div>
      </div>

      <div class="print-container">
        <div class="so-print-box">

          <div class="row align-items-center mb-2">
            <div class="col-7">
              <h4 class="text-primary fw-bolder mb-0">SALES ORDER (SO)</h4>
              <p class="text-muted small mb-0">No: <strong>{{ $so->no_so }}</strong></p>
            </div>
            <div class="col-5 text-end">
              <h6 class="fw-bold mb-0 text-dark">{{ $so->proyek->nama_proyek ?? ($so->penawaran->proyek->nama_proyek ?? 'NAMA PROYEK') }}</h6>
            </div>
          </div>

          <div style="border-top: 2px solid #6571ff; margin-bottom: 12px;"></div>

          <div class="row mb-3" style="font-size: 11px;">
            <div class="col-6">
              <table class="table table-sm table-borderless mb-0">
                <tr>
                  <td class="p-0 text-muted" width="35%">Penawaran</td>
                  <td class="p-0 text-dark">: <strong>{{ $so->penawaran->nama_penawaran ?? '-' }}</strong></td>
                </tr>
                <tr>
                  <td class="p-0 text-muted">Tanggal SO</td>
                  <td class="p-0 text-dark">: {{ optional($so->tanggal)->format('d/m/Y') }}</td>
                </tr>
              </table>
            </div>
            <div class="col-6">
              <table class="table table-sm table-borderless mb-0">
                <tr>
                  <td class="p-0 text-muted text-end" width="60%">ID SO</td>
                  <td class="p-0 text-end text-dark">: #{{ $so->id }}</td>
                </tr>
                <tr>
                  <td class="p-0 text-muted text-end">Dibuat Oleh</td>
                  <td class="p-0 text-end text-dark">: {{ optional($so->creator)->name ?? '-' }}</td>
                </tr>
              </table>
            </div>
          </div>

          <div class="table-responsive">
            <table class="table table-bordered custom-table-print">
              <thead>
                <tr>
                  <th class="text-center" width="10%">No</th>
                  <th>Deskripsi</th>
                  <th class="text-end" width="25%">Jumlah (Rp)</th>
                </tr>
              </thead>
              <tbody>
                @foreach($so->lines as $i => $line)
                <tr>
                  <td class="text-center">{{ $i + 1 }}</td>
                  <td>{{ $line->description }}</td>
                  <td class="text-end">{{ number_format($line->amount, 0, ',', '.') }}</td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>

          <div class="row mt-3">
            <div class="col-8"></div>
            <div class="col-4">
              <table class="table table-sm table-borderless text-end fw-bold" style="font-size: 11px;">
                <tr>
                  <td class="text-muted fw-normal">Total SO:</td>
                  <td class="text-dark">Rp {{ number_format($so->total ?? 0,0,',','.') }}</td>
                </tr>
              </table>
            </div>
          </div>

        </div>
      </div>

    </div>
  </div>
</div>

<style>
.so-print-box { background:#fff; padding:30px; border-radius:8px; box-shadow:0 0 10px rgba(0,0,0,0.05); margin-top:10px; }
.custom-table-print { border:1px solid #000 !important; }
.custom-table-print th { background:#f4f7f6 !important; color:#000; font-size:10px; text-transform:uppercase; border:1px solid #000 !important; }
.custom-table-print td { border:1px solid #000 !important; vertical-align:middle; }
@media print { .d-print-none { display:none !important; } }
</style>

@endsection

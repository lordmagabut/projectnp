@extends('layout.master')

@section('content')
@php
  $fmt = fn($n) => number_format((float)$n, 2, ',', '.');
  // make sure relations are available (lazy is fine, but this avoids N+1)
  $bapp->loadMissing('details', 'penawaran');
@endphp

<div class="card shadow-sm animate__animated animate__fadeIn">
  <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
    <h5 class="mb-2 mb-md-0 d-flex align-items-center">
      <i data-feather="file-text" class="me-2"></i>
      <span>
        BAPP — {{ $bapp->nomor_bapp }}
        @switch($bapp->status)
          @case('draft')     <span class="badge bg-warning text-dark ms-2">Draft</span> @break
          @case('submitted') <span class="badge bg-info text-dark ms-2">Submitted</span> @break
          @case('approved')  <span class="badge bg-success ms-2">Approved</span> @break
          @default           <span class="badge bg-secondary ms-2">{{ ucfirst($bapp->status) }}</span>
        @endswitch
      </span>
    </h5>

    <div class="d-flex gap-2">
      <a href="{{ route('bapp.index', $proyek->id) }}" class="btn btn-light">
        <i data-feather="arrow-left" class="me-1"></i>Kembali
      </a>

      @if($bapp->file_pdf_path)
        <a class="btn btn-outline-primary" target="_blank"
           href="{{ route('bapp.pdf', [$proyek->id, $bapp->id]) }}">
          <i data-feather="download" class="me-1"></i>Unduh PDF
        </a>
      @endif

      @if($bapp->status === 'draft')
        <form method="POST" action="{{ route('bapp.submit', [$proyek->id, $bapp->id]) }}">
          @csrf
          <button class="btn btn-primary">
            <i data-feather="send" class="me-1"></i>Kirim untuk Persetujuan
          </button>
        </form>
      @elseif($bapp->status === 'submitted')
        <form method="POST" action="{{ route('bapp.approve', [$proyek->id, $bapp->id]) }}">
          @csrf
          <button class="btn btn-success">
            <i data-feather="check-circle" class="me-1"></i>Setujui
          </button>
        </form>
      @endif
    </div>
  </div>

  <div class="card-body">
    {{-- Ringkasan --}}
    <div class="row g-2 small text-muted mb-3">
      <div class="col-12"><strong>Proyek:</strong> {{ $proyek->nama_proyek }}</div>
      <div class="col-12"><strong>Penawaran:</strong> {{ $bapp->penawaran?->nama_penawaran ?? '-' }}</div>
      <div class="col-12"><strong>Minggu ke:</strong> {{ $bapp->minggu_ke }}</div>
      <div class="col-12"><strong>Tanggal BAPP:</strong> {{ \Carbon\Carbon::parse($bapp->tanggal_bapp)->format('d-m-Y') }}</div>
      <div class="col-12"><strong>Catatan:</strong> {{ $bapp->notes ?: '-' }}</div>
    </div>

    {{-- Tabel detail --}}
    <div class="table-responsive">
      <table class="table table-bordered table-sm align-middle">
        <thead class="table-light text-center">
          <tr>
            <th style="width:12%">KODE</th>
            <th>URAIAN</th>
            <th class="text-end" style="width:12%">BOBOT ITEM (%)</th>
            <th class="text-end" style="width:12%">PROG. S/D MINGGU LALU (%)</th>
            <th class="text-end" style="width:12%">PROG. MINGGU INI (%)</th>
            <th class="text-end" style="width:12%">PROG. SAAT INI (%)</th>
          </tr>
        </thead>
        <tbody>
        @forelse($bapp->details->sortBy('kode', SORT_NATURAL) as $d)
          <tr>
            <td class="text-nowrap">{{ $d->kode }}</td>
            <td>{{ $d->uraian }}</td>
            <td class="text-end">{{ $fmt($d->bobot_item) }}</td>
            <td class="text-end">{{ $fmt($d->prev_pct) }}</td>
            <td class="text-end">{{ $fmt($d->delta_pct) }}</td>
            <td class="text-end">{{ $fmt($d->now_pct) }}</td>
          </tr>
        @empty
          <tr><td colspan="6" class="text-center text-muted py-3">Tidak ada detail.</td></tr>
        @endforelse
        </tbody>
        <tfoot class="table-light fw-semibold">
          <tr>
            <td colspan="2" class="text-end">TOTAL</td>
            <td></td>
            <td class="text-end">{{ $fmt($bapp->total_prev_pct) }}</td>
            <td class="text-end">{{ $fmt($bapp->total_delta_pct) }}</td>
            <td class="text-end">{{ $fmt($bapp->total_now_pct) }}</td>
          </tr>
        </tfoot>
      </table>
    </div>

    @if($bapp->file_pdf_path)
      <div class="alert alert-info mt-3 mb-0">
        File PDF tersimpan di: <code>storage/{{ $bapp->file_pdf_path }}</code>.
        Jika tidak bisa dibuka, pastikan sudah menjalankan <code>php artisan storage:link</code>.
      </div>
    @endif
  </div>
</div>
@endsection

@extends('layout.master')

@section('content')
@php
  $fmt = fn($n)=>number_format((float)$n, 2, ',', '.');
  $bapp->loadMissing('details','penawaran');
  // hindari drift: akumulasi integer (x100)
  $totWiInt = $totPrevInt = $totDeltaInt = $totNowInt = 0;
  foreach ($bapp->details as $d) {
    $totWiInt    += (int) round(((float)$d->bobot_item) * 100);
    $totPrevInt  += (int) round(((float)$d->prev_pct) * 100);
    $totDeltaInt += (int) round(((float)$d->delta_pct) * 100);
    $totNowInt   += (int) round(((float)$d->now_pct) * 100);
  }
  $totWi    = round($totWiInt / 100, 2);
  $totPrev  = round($totPrevInt / 100, 2);
  $totDelta = round($totDeltaInt / 100, 2);
  $totNow   = round($totNowInt / 100, 2);
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
    <a href="{{ route('proyek.show', $proyek->id) }}?tab=bapp" class="btn btn-light">
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
        <form method="POST" action="{{ route('bapp.approve', [$proyek->id, $bapp->id]) }}" class="d-inline">
          @csrf
          <button class="btn btn-success">
            <i data-feather="check-circle" class="me-1"></i>Setujui
          </button>
        </form>
        <form method="POST" action="{{ route('bapp.revise', [$proyek->id, $bapp->id]) }}" class="d-inline">
          @csrf
          <button class="btn btn-warning">
            <i data-feather="edit" class="me-1"></i>Revisi
          </button>
        </form>
      @elseif($bapp->status === 'approved')
        <form method="POST" action="{{ route('bapp.revise', [$proyek->id, $bapp->id]) }}" class="d-inline">
          @csrf
          <button class="btn btn-warning">
            <i data-feather="edit" class="me-1"></i>Revisi
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

    {{-- Tabel detail (sinkron dengan Create) --}}
    <div class="table-responsive">
      <table class="table table-bordered table-sm align-middle">
        <thead class="table-light text-center">
          <tr>
            <th style="width:12%">KODE</th>
            <th>URAIAN</th>
            {{-- Bobot = % proyek (tanpa “%”) --}}
            <th class="text-end" style="width:10%">BOBOT ITEM</th>
            <th class="text-end" style="width:12%">BOBOT S/D MINGGU LALU</th>
            <th class="text-end" style="width:12%">Δ BOBOT MINGGU INI</th>
            <th class="text-end" style="width:12%">BOBOT SAAT INI</th>
            {{-- Progress = % terhadap item (dengan “%”) --}}
            <th class="text-end" style="width:12%">PROG. S/D MINGGU LALU</th>
            <th class="text-end" style="width:12%">PROG. MINGGU INI</th>
            <th class="text-end" style="width:12%">PROG. SAAT INI</th>
          </tr>
        </thead>
        <tbody>
        @forelse($bapp->details->sortBy('kode', SORT_NATURAL) as $d)
          <tr>
            <td class="text-nowrap">{{ $d->kode }}</td>
            <td>{{ $d->uraian }}</td>

            {{-- Bobot (angka/desimal) --}}
            <td class="text-end">{{ $fmt($d->bobot_item) }}</td>
            <td class="text-end">{{ $fmt($d->prev_pct) }}</td>
            <td class="text-end">{{ $fmt($d->delta_pct) }}</td>
            <td class="text-end">{{ $fmt($d->now_pct) }}</td>

            {{-- Progress (% terhadap item) --}}
            <td class="text-end">{{ $fmt($d->prev_item_pct) }} %</td>
            <td class="text-end">{{ $fmt($d->delta_item_pct) }} %</td>
            <td class="text-end">{{ $fmt($d->now_item_pct) }} %</td>
          </tr>
        @empty
          <tr><td colspan="9" class="text-center text-muted py-3">Tidak ada detail.</td></tr>
        @endforelse
        </tbody>

        {{-- Footer: jumlahkan hanya kolom bobot --}}
        <tfoot class="table-light fw-semibold">
          <tr>
            <td colspan="2" class="text-end">TOTAL</td>
            <td class="text-end">{{ $fmt($totWi) }}</td>
            <td class="text-end">{{ $fmt($totPrev) }}</td>
            <td class="text-end">{{ $fmt($totDelta) }}</td>
            <td class="text-end">{{ $fmt($totNow) }}</td>
            <td></td><td></td><td></td>
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

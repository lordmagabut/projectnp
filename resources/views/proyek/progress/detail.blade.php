@extends('layout.master')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-3">
  <h5 class="mb-0">
    Detail Progress — Minggu ke-{{ $progress->minggu_ke }}
    @if($progress->status !== 'final')
      <span class="badge bg-warning text-dark ms-2">DRAFT</span>
    @else
      <span class="badge bg-success ms-2">FINAL</span>
    @endif
  </h5>

  <div class="d-flex gap-2">
    <a href="{{ route('proyek.show', $proyek->id) }}?tab=progress" class="btn btn-light">Kembali</a>
    @if($progress->status !== 'final')
      <form method="POST" action="{{ route('proyek.progress.finalize', [$proyek->id, $progress->id]) }}">
        @csrf
        <button class="btn btn-success">Sahkan</button>
      </form>
    @endif
  </div>
</div>

<div class="mb-3 small text-muted">
  <div><strong>Tanggal:</strong> {{ \Carbon\Carbon::parse($progress->tanggal)->format('d-m-Y') }}</div>
  @if($progress->penawaran_id)
    <div><strong>Penawaran:</strong> #{{ $progress->penawaran_id }}</div>
  @endif
</div>

<div class="table-responsive">
  <table class="table table-bordered table-sm align-middle">
    <thead class="table-light text-center">
      <tr>
        <th style="width:12%">KODE</th>
        <th>URAIAN</th>
        <th style="width:10%" class="text-end">BOBOT ITEM</th>
        <th style="width:12%" class="text-end">TARGET S/D MINGGU INI</th>
        <th style="width:12%" class="text-end">BOBOT S/D MINGGU LALU</th>
        <th style="width:12%" class="text-end">Δ BOBOT MINGGU INI</th>
        <th style="width:12%" class="text-end">BOBOT SAAT INI</th>
        <th style="width:10%" class="text-end">PROG. SAAT INI (%)</th>
      </tr>
    </thead>
    <tbody>
      @php
        $fmt = fn($n)=>number_format((float)$n, 2, ',', '.');
      @endphp
      @forelse($rows as $r)
        <tr>
          <td class="text-nowrap">{{ $r->kode }}</td>
          <td>{{ $r->uraian }}</td>
          <td class="text-end"></td>
          <td class="text-end">{{ $fmt($r->tgt) }}</td>
          <td class="text-end">{{ $fmt($r->bPrev) }}</td>
          <td class="text-end">{{ $fmt($r->bDelta) }}</td>
          <td class="text-end">{{ $fmt($r->bNow) }}</td>
          <td class="text-end"></td>
        </tr>
      @empty
        <tr><td colspan="8" class="text-center text-muted py-3">Tidak ada item.</td></tr>
      @endforelse
    </tbody>
    <tfoot class="table-light fw-semibold">
      <tr>
        <td colspan="2" class="text-end">TOTAL</td>
        <td class="text-end"></td>
        <td class="text-end">{{ $fmt($totTarget) }}</td>
        <td class="text-end">{{ $fmt($totPrev) }}</td>
        <td class="text-end">{{ $fmt($totDelta) }}</td>
        <td class="text-end">{{ $fmt($totNow) }}</td>
        <td></td>
      </tr>
    </tfoot>
  </table>
</div>

@if($progress->status !== 'final')
  <div class="alert alert-info mt-3 mb-0">
    Untuk menyelesaikan proses, klik tombol <strong>Sahkan</strong> di kanan atas.
  </div>
@endif
@endsection

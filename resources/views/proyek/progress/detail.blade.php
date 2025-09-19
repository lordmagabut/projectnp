@extends('layout.master')

@section('content')
<div class="card shadow-sm animate__animated animate__fadeIn">
  {{-- Header Card --}}
  <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
    <h5 class="mb-2 mb-md-0 d-flex align-items-center">
      <i data-feather="file-text" class="me-2"></i>
      <span>
        BERITA ACARA PROGRESS PEKERJAAN
        @if($progress->status !== 'final')
          <span class="badge bg-warning text-dark ms-2">DRAFT</span>
        @else
          <span class="badge bg-success ms-2">FINAL</span>
        @endif
      </span>
    </h5>

    <div class="d-flex gap-2">
      <a href="{{ route('proyek.show', $proyek->id) }}?tab=progress" class="btn btn-light">
        <i data-feather="arrow-left" class="me-1"></i>Kembali
      </a>
      @if($progress->status !== 'final')
        <form method="POST" action="{{ route('proyek.progress.finalize', [$proyek->id, $progress->id]) }}">
          @csrf
          <button class="btn btn-success">
            <i data-feather="check-circle" class="me-1"></i>Sahkan
          </button>
        </form>
      @endif
    </div>
  </div>

  {{-- Body Card --}}
  <div class="card-body">
    @php
      $penawaranNama = $penawaranNama
        ?? optional($progress->penawaran)->nama_penawaran
        ?? (isset($penawaran) ? $penawaran->nama_penawaran : null)
        ?? (isset($penawaranHeader) ? $penawaranHeader->nama_penawaran : null);
      $fmt = fn($n)=>number_format((float)$n, 2, ',', '.');
    @endphp

    {{-- Info ringkas --}}
    <div class="row g-2 small text-muted mb-3">
      <div class="col-12"><strong>Minggu ke:</strong> {{ $progress->minggu_ke }}</div>
      <div class="col-12"><strong>Tanggal:</strong> {{ \Carbon\Carbon::parse($progress->tanggal)->format('d-m-Y') }}</div>
      @if($progress->penawaran_id)
        <div class="col-12"><strong>Penawaran:</strong> {{ $penawaranNama ?: ('#'.$progress->penawaran_id) }}</div>
      @endif
    </div>

    {{-- Cara membaca (collapse) --}}
    <div class="mb-3">
      <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#howToRead" aria-expanded="false">
        <i data-feather="help-circle" class="me-1"></i> Cara membaca tabel ini
      </button>
      <div class="collapse mt-2" id="howToRead">
        <div class="alert alert-secondary mb-0 small">
          <ul class="mb-0">
            <li><strong>Bobot Item</strong> = porsi item terhadap total proyek (dalam % proyek).</li>
            <li><strong>Target s/d Minggu Ini</strong> = rencana kumulatif (Kurva-S rencana) hingga minggu ini.</li>
            <li><strong>Bobot s/d Minggu Lalu</strong> = realisasi kumulatif <em>final</em> sampai minggu sebelumnya.</li>
            <li><strong>Δ Bobot Minggu Ini</strong> = penambahan bobot proyek yang dicapai pada minggu ini.</li>
            <li><strong>Bobot Saat Ini</strong> = realisasi kumulatif sampai minggu ini (<em>Bobot s/d Minggu Lalu</em> + <em>Δ Bobot Minggu Ini</em>).</li>
            <li><strong>Progress s/d Minggu Lalu (%)</strong> = <code>Bobot s/d Minggu Lalu / Bobot Item × 100</code>.</li>
            <li><strong>Progress Minggu Ini (%)</strong> = <code>Δ Bobot Minggu Ini / Bobot Item × 100</code>.</li>
            <li><strong>Progress Saat Ini (%)</strong> = <code>Bobot Saat Ini / Bobot Item × 100</code>.</li>
            <li>Footer <strong>hanya menjumlahkan kolom bobot</strong> (dipakai untuk sertifikat pembayaran). Kolom persentase <strong>tidak dijumlahkan</strong>.</li>
          </ul>
        </div>
      </div>
    </div>

    {{-- Tabel detail --}}
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
            <th style="width:12%" class="text-end">PROG. S/D MINGGU LALU</th>
            <th style="width:12%" class="text-end">PROG. MINGGU INI</th>
            <th style="width:12%" class="text-end">PROG. SAAT INI</th>
          </tr>
        </thead>
        <tbody>
          @forelse($rows as $r)
            @php
              $Wi    = (float)($r->Wi ?? 0);
              $tgt   = (float)($r->tgt ?? 0);
              $bPrev = (float)($r->bPrev ?? 0);
              $bDel  = (float)($r->bDelta ?? 0);
              $bNow  = (float)($r->bNow ?? 0);

              $pPrev = $Wi > 0 ? ($bPrev / $Wi * 100) : 0;
              $pDel  = $Wi > 0 ? ($bDel  / $Wi * 100) : 0;
              $pNow  = $Wi > 0 ? ($bNow  / $Wi * 100) : 0;
            @endphp
            <tr>
              <td class="text-nowrap">{{ $r->kode }}</td>
              <td>{{ $r->uraian }}</td>
              <td class="text-end">@isset($r->Wi) {{ $fmt($Wi) }} @endisset</td>
              <td class="text-end">{{ $fmt($tgt) }}</td>
              <td class="text-end">{{ $fmt($bPrev) }}</td>
              <td class="text-end">{{ $fmt($bDel) }}</td>
              <td class="text-end">{{ $fmt($bNow) }}</td>
              <td class="text-end">{{ $fmt($pPrev) }} %</td>
              <td class="text-end">{{ $fmt($pDel) }} %</td>
              <td class="text-end">{{ $fmt($pNow) }} %</td>
            </tr>
          @empty
            <tr><td colspan="10" class="text-center text-muted py-3">Tidak ada item.</td></tr>
          @endforelse
        </tbody>

        {{-- TOTAL: hanya untuk kolom bobot --}}
        <tfoot class="table-light fw-semibold">
          <tr>
            <td colspan="2" class="text-end">TOTAL</td>
            <td class="text-end">@isset($totWi) {{ $fmt($totWi) }} @endisset</td>
            <td class="text-end">@isset($totTarget) {{ $fmt($totTarget) }} @endisset</td>
            <td class="text-end">@isset($totPrev) {{ $fmt($totPrev) }} @endisset</td>
            <td class="text-end">@isset($totDelta) {{ $fmt($totDelta) }} @endisset</td>
            <td class="text-end">@isset($totNow) {{ $fmt($totNow) }} @endisset</td>
            <td></td><td></td><td></td>
          </tr>
        </tfoot>
      </table>
    </div>

    @if($progress->status !== 'final')
      <div class="alert alert-info mt-3 mb-0">
        Untuk menyelesaikan proses, klik tombol <strong>Sahkan</strong> di kanan atas.
      </div>
    @endif

    @php
      $existingBapp = \App\Models\Bapp::where('proyek_id', $proyek->id)
        ->where('penawaran_id', $progress->penawaran_id)
        ->where('minggu_ke', $progress->minggu_ke)
        ->first();
    @endphp

    @if($progress->status === 'final' && !$existingBapp)
      <a class="btn btn-sm btn-outline-primary"
        href="{{ route('bapp.create', ['proyek' => $proyek->id, 'penawaran_id' => $progress->penawaran_id, 'minggu_ke' => $progress->minggu_ke]) }}">
        Terbitkan BAPP
      </a>
    @elseif($existingBapp)
      <a class="btn btn-sm btn-primary" href="{{ route('bapp.show', [$proyek->id, $existingBapp->id]) }}">
        Lihat BAPP
      </a>
    @endif
  </div>
</div>


@endsection

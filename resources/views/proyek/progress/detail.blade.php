@extends('layout.master')

@section('content')
<div class="card shadow-sm animate__animated animate__fadeIn">
  {{-- ===== Header ===== --}}
  <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
    <h5 class="mb-2 mb-md-0 d-flex align-items-center">
      <i data-feather="file-text" class="me-2"></i>
      <span>
        BERITA ACARA PROGRESS PEKERJAAN
        @switch($progress->status)
          @case('draft')   <span class="badge bg-warning text-dark ms-2">DRAFT</span> @break
          @case('final')   <span class="badge bg-success ms-2">FINAL</span>           @break
          @case('revised') <span class="badge bg-secondary ms-2">DIREVISI</span>      @break
          @default         <span class="badge bg-light text-dark ms-2">{{ strtoupper($progress->status) }}</span>
        @endswitch
      </span>
    </h5>

    <div class="d-flex gap-2">
      <a href="{{ route('proyek.show', $proyek->id) }}?tab=progress" class="btn btn-light">
        <i data-feather="arrow-left" class="me-1"></i>Kembali
      </a>

      @if(in_array($progress->status, ['draft','revised']))
        <a href="{{ route('proyek.progress.edit', ['proyek' => $proyek->id, 'progress' => $progress->id]) }}"
           class="btn btn-primary">
          <i data-feather="edit-2" class="me-1"></i>Edit
        </a>
      @endif

      @if($progress->status === 'draft')
        <form method="POST" action="{{ route('proyek.progress.finalize', [$proyek->id, $progress->id]) }}">
          @csrf
          <button class="btn btn-success">
            <i data-feather="check-circle" class="me-1"></i>Sahkan
          </button>
        </form>
      @endif

      @if($progress->status === 'final')
        <form method="POST"
              action="{{ route('proyek.progress.revisi', ['proyek'=>$proyek->id, 'progress'=>$progress->id]) }}"
              onsubmit="return confirm('Buat revisi dari progress ini? Versi lama akan ditandai DIREVISI.');">
          @csrf
          <button class="btn btn-warning text-dark">
            <i data-feather="edit-2" class="me-1"></i>Revisi
          </button>
        </form>
      @endif
    </div>
  </div>

  {{-- ===== Banner versi direvisi ===== --}}
  @if($progress->status === 'revised')
    <div class="alert alert-warning m-3 d-flex align-items-center">
      <i data-feather="alert-triangle" class="me-2"></i>
      <div>
        Progress ini sudah <strong>direvisi</strong>.
        @if($progress->revisi_ke_id)
          <span class="ms-2">
            Lihat versi terbaru:
            <a class="link-primary"
               href="{{ route('proyek.progress.detail', ['proyek'=>$proyek->id, 'progress'=>$progress->revisi_ke_id]) }}">
              #{{ $progress->revisi_ke_id }}
            </a>
          </span>
        @endif
      </div>
    </div>
  @endif

  {{-- ===== Body ===== --}}
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

    {{-- Cara membaca --}}
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
            <li><strong>Δ Bobot Minggu Ini</strong> = penambahan bobot proyek minggu ini.</li>
            <li><strong>Bobot Saat Ini</strong> = <em>Bobot s/d Minggu Lalu</em> + <em>Δ Bobot Minggu Ini</em>.</li>
            <li><strong>Progress s/d Minggu Lalu (%)</strong> = <code>Bobot s/d Minggu Lalu / Bobot Item × 100</code>.</li>
            <li><strong>Progress Minggu Ini (%)</strong> = <code>Δ Bobot Minggu Ini / Bobot Item × 100</code>.</li>
            <li><strong>Progress Saat Ini (%)</strong> = <code>Bobot Saat Ini / Bobot Item × 100</code>.</li>
            <li>Footer <strong>hanya menjumlahkan kolom bobot</strong>. Kolom persentase <strong>tidak dijumlahkan</strong>.</li>
          </ul>
        </div>
      </div>
    </div>

    {{-- ===== Tabel detail (header/footer sticky) ===== --}}
    <div class="table-scroll border rounded">
      <table class="table table-bordered table-sm align-middle mb-0 table-fixed">
        <thead class="table-light text-center sticky-head">
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
              // Nilai bobot (desimal, % PROYEK) – ditampilkan TANPA tanda %
              $Wi    = (float)($r->Wi ?? 0);
              $tgt   = (float)($r->tgt ?? 0);
              $bPrev = (float)($r->bPrev ?? 0);
              $bDel  = (float)($r->bDelta ?? 0);
              $bNow  = (float)($r->bNow ?? 0);

              // Persentase terhadap item – ditampilkan DENGAN tanda %
              // Pakai nilai yang sudah dihitung controller bila ada; fallback dari bobot/Wi.
              $pPrevItem = isset($r->pPrevItem)  ? (float)$r->pPrevItem  : ($Wi>0?($bPrev/$Wi*100):0);
              $pDeltaItem= isset($r->pDeltaItem) ? (float)$r->pDeltaItem : ($Wi>0?($bDel/$Wi*100):0);
              $pNowItem  = isset($r->pNowItem)   ? (float)$r->pNowItem   : ($Wi>0?($bNow/$Wi*100):0);
            @endphp
            <tr>
              <td class="text-nowrap">{{ $r->kode }}</td>
              <td>{{ $r->uraian }}</td>

              {{-- Kolom BOBOT = angka/desimal (tanpa %) --}}
              <td class="text-end">@isset($r->Wi) {{ $fmt($Wi) }} @endisset</td>
              <td class="text-end">{{ $fmt($tgt) }}</td>
              <td class="text-end">{{ $fmt($bPrev) }}</td>
              <td class="text-end">{{ $fmt($bDel) }}</td>
              <td class="text-end">{{ $fmt($bNow) }}</td>

              {{-- Kolom PROG = persentase terhadap item --}}
              <td class="text-end">{{ $fmt($pPrevItem) }} %</td>
              <td class="text-end">{{ $fmt($pDeltaItem) }} %</td>
              <td class="text-end">{{ $fmt($pNowItem) }} %</td>
            </tr>
          @empty
            <tr><td colspan="10" class="text-center text-muted py-3">Tidak ada item.</td></tr>
          @endforelse
        </tbody>

        {{-- FOOTER: hanya jumlah kolom BOBOT --}}
        <tfoot class="table-light fw-semibold sticky-foot">
          <tr>
            <td colspan="2" class="text-end">TOTAL</td>
            <td class="text-end">@isset($totWi)     {{ $fmt($totWi) }}     @endisset</td>
            <td class="text-end">@isset($totTarget) {{ $fmt($totTarget) }} @endisset</td>
            <td class="text-end">@isset($totPrev)   {{ $fmt($totPrev) }}   @endisset</td>
            <td class="text-end">@isset($totDelta)  {{ $fmt($totDelta) }}  @endisset</td>
            <td class="text-end">@isset($totNow)    {{ $fmt($totNow) }}    @endisset</td>
            <td></td><td></td><td></td>
          </tr>
        </tfoot>
      </table>
    </div>

    {{-- Info penyelesaian --}}
    @if($progress->status === 'draft')
      <div class="alert alert-info mt-3 mb-0">
        Untuk menyelesaikan proses, klik tombol <strong>Sahkan</strong> di kanan atas.
      </div>
    @endif

    {{-- Riwayat Revisi --}}
    @if($progress->revisi_dari_id || $progress->revisi_ke_id)
      <div class="mt-4">
        <h6 class="mb-2">Riwayat Revisi</h6>
        <ul class="small mb-0">
          @if($progress->revisi_dari_id)
            <li>
              Direvisi dari:
              <a href="{{ route('proyek.progress.detail', ['proyek'=>$proyek->id, 'progress'=>$progress->revisi_dari_id]) }}">
                #{{ $progress->revisi_dari_id }}
              </a>
            </li>
          @endif
          @if($progress->revisi_ke_id)
            <li>
              Direvisi oleh (versi terbaru):
              <a href="{{ route('proyek.progress.detail', ['proyek'=>$proyek->id, 'progress'=>$progress->revisi_ke_id]) }}">
                #{{ $progress->revisi_ke_id }}
              </a>
            </li>
          @endif
        </ul>
      </div>
    @endif

    {{-- BAPP --}}
    @php
      $existingBapp = \App\Models\Bapp::where('proyek_id', $proyek->id)
        ->where('penawaran_id', $progress->penawaran_id)
        ->where('minggu_ke', $progress->minggu_ke)
        ->first();
    @endphp
    <div class="mt-3">
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
</div>

{{-- Sticky header/foot + scroll container --}}
@push('custom-styles')
<style>
  .table-scroll{
    max-height: 70vh;   /* area gulir */
    overflow: auto;
  }
  .table-fixed{
    table-layout: fixed; /* stabilkan lebar kolom */
    width: 100%;
  }
  .sticky-head th{
    position: sticky;
    top: 0;
    z-index: 3;
    background: #f8f9fa; /* sama dengan .table-light */
  }
  .sticky-foot tr{
    position: sticky;
    bottom: 0;
    z-index: 2;
    background: #f8f9fa;
  }
</style>
@endpush
@endsection

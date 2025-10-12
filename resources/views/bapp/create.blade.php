{{-- resources/views/bapp/create.blade.php --}}
@extends('layout.master')

@section('content')
<form method="POST" action="{{ route('bapp.store', $proyek->id) }}">
  @csrf

  <div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0">Terbitkan BAPP — Minggu ke-{{ $mingguKe }}</h5>
      <a class="btn btn-light btn-sm" href="{{ route('bapp.index', $proyek->id) }}">
        Kembali
      </a>
    </div>

    <div class="card-body">

      {{-- Flash & error --}}
      @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
      @endif
      @if($errors->any())
        <div class="alert alert-danger">
          <div class="fw-semibold mb-1">Gagal menyimpan:</div>
          <ul class="mb-0">
            @foreach($errors->all() as $e)
              <li>{{ $e }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      {{-- Header form --}}
      <div class="row g-3 mb-3">
        <div class="col-md-3">
          <label class="form-label">Nomor BAPP</label>
          <input
            name="nomor_bapp"
            class="form-control @error('nomor_bapp') is-invalid @enderror"
            required
            value="{{ old('nomor_bapp', 'BAPP/'.$proyek->id.'/'.now()->format('Ymd').'/001') }}"
          >
          @error('nomor_bapp') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-3">
          <label class="form-label">Tanggal</label>
          <input
            name="tanggal_bapp"
            type="date"
            class="form-control @error('tanggal_bapp') is-invalid @enderror"
            required
            value="{{ old('tanggal_bapp', now()->toDateString()) }}"
          >
          @error('tanggal_bapp') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-3">
          <label class="form-label">Penawaran</label>
          <input class="form-control" value="{{ $penawaran?->nama_penawaran ?? '-' }}" disabled>
          <input type="hidden" name="penawaran_id" value="{{ old('penawaran_id', $penawaran->id ?? '') }}">
        </div>

        <div class="col-md-3">
          <label class="form-label">Minggu ke</label>
          <input class="form-control" value="{{ $mingguKe }}" disabled>
          <input type="hidden" name="minggu_ke" value="{{ $mingguKe }}">
          <input type="hidden" name="progress_id" value="{{ old('progress_id', $progress->id ?? '') }}">
        </div>

        <div class="col-12">
          <label class="form-label">Catatan</label>
          <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
        </div>
      </div>

      @php
        // Formatter
        $fmt    = fn($n)=>number_format((float)$n, 2, ',', '.');              // angka/bobot
        $fmtPct = fn($n)=>number_format((float)$n, 2, ',', '.').' %';         // persen
        // Total kolom bobot
        $totWi    = collect($rows)->sum('Wi');
        $totPrev  = collect($rows)->sum('bPrev');
        $totDelta = collect($rows)->sum('bDelta');
        $totNow   = collect($rows)->sum('bNow');
      @endphp

      {{-- Tabel ringkasan baris (sinkron dgn detail progress) --}}
      <div class="table-responsive">
        <table class="table table-bordered table-sm align-middle">
          <thead class="table-light">
            <tr>
              <th style="width:10%">KODE</th>
              <th>URAIAN PEKERJAAN</th>

              {{-- BOBOT = % proyek (tanpa tanda %) --}}
              <th class="text-end" style="width:10%">BOBOT ITEM</th>
              <th class="text-end" style="width:12%">BOBOT S/D MINGGU LALU</th>
              <th class="text-end" style="width:12%">Δ BOBOT MINGGU INI</th>
              <th class="text-end" style="width:12%">BOBOT SAAT INI</th>

              {{-- PROGRESS = % terhadap item (dengan tanda %) --}}
              <th class="text-end" style="width:12%">PROG. S/D MINGGU LALU</th>
              <th class="text-end" style="width:12%">PROG. MINGGU INI</th>
              <th class="text-end" style="width:12%">PROG. SAAT INI</th>
            </tr>
          </thead>

          <tbody>
            @forelse($rows as $r)
              @php
                // BOBOT (angka) – kiriman dari controller: Wi, bPrev, bDelta, bNow
                $Wi     = (float)($r->Wi     ?? 0);
                $bPrev  = (float)($r->bPrev  ?? 0);
                $bDelta = (float)($r->bDelta ?? 0);
                $bNow   = (float)($r->bNow   ?? 0);

                // PROGRESS (persen terhadap item) – kiriman controller: pPrevItem, pDeltaItem, pNowItem
                // fallback aman bila belum dikirim: hitung dari bobot/Wi.
                $pPrevItem  = isset($r->pPrevItem)  ? (float)$r->pPrevItem  : ($Wi > 0 ? $bPrev  / $Wi * 100 : 0);
                $pDeltaItem = isset($r->pDeltaItem) ? (float)$r->pDeltaItem : ($Wi > 0 ? $bDelta / $Wi * 100 : 0);
                $pNowItem   = isset($r->pNowItem)   ? (float)$r->pNowItem   : ($Wi > 0 ? $bNow   / $Wi * 100 : 0);
              @endphp

              <tr>
                <td class="text-nowrap">{{ $r->kode }}</td>
                <td>{{ $r->uraian }}</td>

                {{-- BOBOT = desimal (tanpa %) --}}
                <td class="text-end">{{ $fmt($Wi) }}</td>
                <td class="text-end">{{ $fmt($bPrev) }}</td>
                <td class="text-end">{{ $fmt($bDelta) }}</td>
                <td class="text-end">{{ $fmt($bNow) }}</td>

                {{-- PROGRESS = persen --}}
                <td class="text-end">{{ $fmtPct($pPrevItem) }}</td>
                <td class="text-end">{{ $fmtPct($pDeltaItem) }}</td>
                <td class="text-end">{{ $fmtPct($pNowItem) }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="9" class="text-center text-muted py-3">Tidak ada baris untuk ditampilkan.</td>
              </tr>
            @endforelse
          </tbody>

          <tfoot class="table-light">
            <tr>
              <th colspan="2" class="text-end">TOTAL</th>
              {{-- Jumlahkan hanya BOBOT --}}
              <th class="text-end">{{ $fmt($totWi) }}</th>
              <th class="text-end">{{ $fmt($totPrev) }}</th>
              <th class="text-end">{{ $fmt($totDelta) }}</th>
              <th class="text-end">{{ $fmt($totNow) }}</th>
              {{-- Progress tidak dijumlahkan --}}
              <th></th>
              <th></th>
              <th></th>
            </tr>
          </tfoot>
        </table>
      </div>

      <div class="text-end">
        <button class="btn btn-primary">Simpan & Terbitkan PDF</button>
      </div>
    </div>
  </div>
</form>
@endsection

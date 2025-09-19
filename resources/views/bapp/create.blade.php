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

      <div class="table-responsive">
        <table class="table table-bordered table-sm align-middle">
          <thead class="table-light">
            <tr>
              <th style="width:10%">Kode</th>
              <th>Uraian Pekerjaan</th>
              <th class="text-end" style="width:10%">Bobot (%)</th>
              <th class="text-end" style="width:12%">Prog. s/d Minggu Lalu (%)</th>
              <th class="text-end" style="width:12%">Prog. Minggu Ini (%)</th>
              <th class="text-end" style="width:12%">Prog. Saat Ini (%)</th>
            </tr>
          </thead>
          <tbody>
            @forelse($rows as $r)
              <tr>
                <td class="text-nowrap">{{ $r->kode }}</td>
                <td>{{ $r->uraian }}</td>
                <td class="text-end">{{ number_format((float)$r->bobot_item, 2, ',', '.') }}</td>
                <td class="text-end">{{ number_format((float)$r->prev_pct,   2, ',', '.') }}</td>
                <td class="text-end">{{ number_format((float)$r->delta_pct,  2, ',', '.') }}</td>
                <td class="text-end">{{ number_format((float)$r->now_pct,    2, ',', '.') }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="text-center text-muted py-3">Tidak ada baris untuk ditampilkan.</td>
              </tr>
            @endforelse
          </tbody>
          <tfoot class="table-light">
            <tr>
              <th colspan="3" class="text-end">TOTAL</th>
              <th class="text-end">{{ number_format((float)$totPrev,  2, ',', '.') }}</th>
              <th class="text-end">{{ number_format((float)$totDelta, 2, ',', '.') }}</th>
              <th class="text-end">{{ number_format((float)$totNow,   2, ',', '.') }}</th>
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

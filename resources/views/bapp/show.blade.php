@extends('layout.master')

@section('content')
@php
  use Illuminate\Support\Facades\Storage;

  $fmt = fn($n)=>number_format((float)$n, 2, ',', '.');
  $bapp->loadMissing('details','penawaran');
  
  // Hitung total dengan precision tinggi, lalu batasi max 100
  $totWi = $totPrev = $totDelta = $totNow = 0;
  foreach ($bapp->details as $d) {
    $totWi    += (float)$d->bobot_item;
    $totPrev  += (float)$d->prev_pct;
    $totDelta += (float)$d->delta_pct;
    $totNow   += (float)$d->now_pct;
  }
  
  // Batasi maksimal 100.00 untuk progress
  $totWi    = round($totWi, 2);
  $totPrev  = min(100.00, round($totPrev, 2));
  $totDelta = round($totDelta, 2);
  $totNow   = min(100.00, round($totNow, 2));
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

      <a class="btn btn-outline-primary" target="_blank"
         href="{{ route('bapp.pdf', [$proyek->id, $bapp->id]) }}">
        <i data-feather="download" class="me-1"></i>Unduh PDF
      </a>

      @if($bapp->status === 'submitted')
        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#approveModal">
          <i data-feather="check-circle" class="me-1"></i>Setujui
        </button>
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
    @if($errors->any())
      <div class="alert alert-danger">
        <div class="fw-semibold mb-1">Gagal mengirim:</div>
        <ul class="mb-0">
          @foreach($errors->all() as $e)
            <li>{{ $e }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    @if($bapp->status === 'draft')
      <form method="POST" action="{{ route('bapp.submit', [$proyek->id, $bapp->id]) }}" enctype="multipart/form-data" class="row g-3 mb-3">
        @csrf
        <div class="col-12">
          <div class="alert alert-info mb-0">
            <i data-feather="info" class="me-1"></i>
            <strong>Opsional:</strong> Anda bisa upload tanda terima sekarang atau nanti saat persetujuan.
          </div>
        </div>
        <div class="col-md-6">
          <label class="form-label">Upload Tanda Terima BAPP (PDF) - Opsional</label>
          <input type="file" name="tanda_terima_pdf" accept="application/pdf"
                 class="form-control form-control-sm @error('tanda_terima_pdf') is-invalid @enderror">
          <div class="form-text">Upload sekarang untuk keperluan follow up persetujuan. Format: PDF, Max: 10MB</div>
          @error('tanda_terima_pdf') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-12">
          <button class="btn btn-primary">
            <i data-feather="send" class="me-1"></i>Kirim untuk Persetujuan
          </button>
        </div>
        </div>
      </form>
    @endif

    {{-- Ringkasan --}}
    <div class="row g-2 small text-muted mb-3">
      <div class="col-12"><strong>Proyek:</strong> {{ $proyek->nama_proyek }}</div>
      <div class="col-12"><strong>Penawaran:</strong> {{ $bapp->penawaran?->nama_penawaran ?? '-' }}</div>
      <div class="col-12"><strong>Minggu ke:</strong> {{ $bapp->minggu_ke }}</div>
      <div class="col-12"><strong>Tanggal BAPP:</strong> {{ \Carbon\Carbon::parse($bapp->tanggal_bapp)->format('d-m-Y') }}</div>
      <div class="col-12"><strong>Penandatangan:</strong> {{ ($bapp->sign_by === 'pm') ? ($proyek->project_manager_name ?? 'Project Manager') : ($proyek->site_manager_name ?? 'Site Manager') }}</div>
      <div class="col-12"><strong>Catatan:</strong> {{ $bapp->notes ?: '-' }}</div>
      <div class="col-12">
        <strong>Tanda Terima:</strong>
        @if($bapp->file_pdf_path)
          <a href="{{ Storage::url($bapp->file_pdf_path) }}" target="_blank" rel="noopener">Lihat PDF</a>
        @else
          -
        @endif
      </div>
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

  </div>
</div>

{{-- Modal Upload PDF untuk Approve --}}
<div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="{{ route('bapp.approve', [$proyek->id, $bapp->id]) }}" enctype="multipart/form-data">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title" id="approveModalLabel">
            <i data-feather="check-circle" class="me-2"></i>Setujui BAPP
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          @if($bapp->file_pdf_path)
            <div class="alert alert-success mb-3">
              <i data-feather="check-circle" class="me-1"></i>
              <strong>Tanda terima sudah ada:</strong>
              <a href="{{ Storage::url($bapp->file_pdf_path) }}" target="_blank" class="alert-link">
                Lihat File
              </a>
            </div>
            <div class="alert alert-warning">
              <i data-feather="info" class="me-1"></i>
              Upload file baru jika ingin mengganti tanda terima yang ada.
            </div>
          @else
            <div class="alert alert-info">
              <i data-feather="info" class="me-1"></i>
              Silakan upload file PDF tanda terima kirim BAPP yang sudah ditandatangani.
            </div>
          @endif
          <div class="mb-3">
            <label class="form-label fw-semibold">
              Upload Tanda Terima BAPP (PDF)
              @if(!$bapp->file_pdf_path)
                <span class="text-danger">*</span>
              @endif
            </label>
            <input type="file" name="tanda_terima_pdf" accept="application/pdf" 
                   @if(!$bapp->file_pdf_path) required @endif
                   class="form-control @error('tanda_terima_pdf') is-invalid @enderror">
            <div class="form-text">
              @if($bapp->file_pdf_path)
                Opsional - Upload untuk mengganti file yang ada
              @else
                Wajib - Format: PDF, Maksimal: 10MB
              @endif
            </div>
            @error('tanda_terima_pdf') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-success">
            <i data-feather="check-circle" class="me-1"></i>Setujui & Simpan
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

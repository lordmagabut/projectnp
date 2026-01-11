@extends('layout.master')

@section('title', 'Detail BAST - ' . ($bast->nomor ?? 'N/A'))

@section('content')
<div class="container-fluid mt-4">
  <div class="row">
    <div class="col-12">
      <div class="card shadow-sm">
        <div class="card-header bg-light d-flex align-items-center justify-content-between">
          <div>
            <h5 class="mb-0">
              <i data-feather="file-plus" class="me-2 text-primary"></i>
              Detail BAST
            </h5>
          </div>
          <div class="d-flex gap-2">
            <a href="{{ route('bast.pdf', $bast->id) }}" target="_blank" class="btn btn-sm btn-outline-primary">
              <i data-feather="download" class="me-1"></i> Unduh PDF
            </a>
            @if(($bast->status ?? 'draft') !== 'approved')
              <form action="{{ route('bast.approve', $bast->id) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Setujui BAST ini?');">
                  <i data-feather="check-circle" class="me-1"></i> Setujui
                </button>
              </form>
            @endif
            <a href="{{ route('proyek.show', $bast->proyek_id) }}?tab=bast" class="btn btn-sm btn-secondary">
              <i data-feather="arrow-left" class="me-1"></i> Kembali
            </a>
          </div>
        </div>

        <div class="card-body">
          <div class="row mb-4">
            <div class="col-md-6">
              <table class="table table-sm table-borderless">
                <tbody>
                  <tr>
                    <th style="width:35%">Nomor BAST</th>
                    <td>{{ $bast->nomor ?? '-' }}</td>
                  </tr>
                  <tr>
                    <th>Jenis BAST</th>
                    <td>
                      <span class="badge text-bg-light border">
                        {{ match($bast->jenis_bast) {
                            'bast_1' => 'BAST 1',
                            'bast_2' => 'BAST 2',
                            default => strtoupper($bast->jenis_bast ?? '-')
                        } }}
                      </span>
                    </td>
                  </tr>
                  <tr>
                    <th>Status</th>
                    <td>
                      @php
                        $statusClass = match($bast->status) {
                          'approved', 'done' => 'bg-success',
                          'scheduled' => 'bg-warning text-dark',
                          'draft' => 'bg-secondary',
                          default => 'bg-light text-dark',
                        };
                      @endphp
                      <span class="badge {{ $statusClass }}">{{ strtoupper($bast->status ?? 'draft') }}</span>
                    </td>
                  </tr>
                  <tr>
                    <th>Tanggal BAST</th>
                    <td>{{ optional($bast->tanggal_bast)->format('d-m-Y') ?? '-' }}</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="col-md-6">
              <table class="table table-sm table-borderless">
                <tbody>
                  <tr>
                    <th style="width:35%">Proyek</th>
                    <td>{{ $bast->proyek?->nama_proyek ?? '-' }}</td>
                  </tr>
                  <tr>
                    <th>Sertifikat Pembayaran</th>
                    <td>
                      @if($bast->sertifikatPembayaran)
                        <a href="{{ route('sertifikat.show', $bast->sertifikatPembayaran->id) }}">
                          {{ $bast->sertifikatPembayaran->nomor }}
                        </a>
                        @if($bast->sertifikatPembayaran->termin_ke)
                          <span class="text-muted">(Termin {{ $bast->sertifikatPembayaran->termin_ke }})</span>
                        @endif
                      @else
                        -
                      @endif
                    </td>
                  </tr>
                  <tr>
                    <th>Jatuh Tempo Retensi</th>
                    <td>{{ optional($bast->tanggal_jatuh_tempo_retensi)->format('d-m-Y') ?? '-' }}</td>
                  </tr>
                  <tr>
                    <th>Durasi Retensi (Hari)</th>
                    <td>{{ $bast->durasi_retensi_hari ?? '-' }} hari</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <hr>

          <h6 class="mb-3">Detail Retensi</h6>
          <div class="row">
            <div class="col-md-4">
              <div class="card bg-light border-0">
                <div class="card-body text-center">
                  <small class="text-muted d-block">% Retensi</small>
                  <h4 class="mb-0">
                    {{ $bast->persen_retensi !== null ? number_format((float)$bast->persen_retensi, 2, ',', '.') . ' %' : '-' }}
                  </h4>
                </div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="card bg-light border-0">
                <div class="card-body text-center">
                  <small class="text-muted d-block">Nilai Retensi</small>
                  <h4 class="mb-0">
                    @php
                      $rp = fn($n) => 'Rp ' . number_format((float)$n, 0, ',', '.');
                      
                      $penawaranId = optional($bast->sertifikatPembayaran)->penawaran_id;
                      $totalRetensi = 0;
                      if ($penawaranId) {
                        $totalRetensi = \DB::table('sertifikat_pembayaran')
                            ->where('penawaran_id', $penawaranId)
                            ->sum('retensi_nilai');
                      }
                    @endphp
                    {{ $rp($totalRetensi ?? 0) }}
                  </h4>
                </div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="card bg-light border-0">
                <div class="card-body text-center">
                  <small class="text-muted d-block">Status BAST</small>
                  <span class="badge {{ $statusClass }} p-2">{{ strtoupper($bast->status ?? 'draft') }}</span>
                </div>
              </div>
            </div>
          </div>

          <hr>

          <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Catatan:</strong> BAST 1 adalah berita acara penyelesaian pekerjaan. BAST 2 adalah berita acara 
            pelepasan retensi setelah durasi retensi berakhir ({{ $bast->durasi_retensi_hari ?? '—' }} hari dari tanggal BAST 1).
          </div>

          <hr>

          <div class="card">
            <div class="card-header bg-light">
              <h6 class="mb-0"><i data-feather="edit" class="me-2"></i>Edit Ketentuan BAST</h6>
            </div>
            <div class="card-body">
              @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                  <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
              @endif

              <form action="{{ route('bast.updateKetentuan', $bast->id) }}" method="POST">
                @csrf
                
                <h6 class="mb-3 text-primary"><i data-feather="file-text" class="me-2"></i>Ketentuan BAST 1 (Serah Terima Pertama)</h6>
                
                <div class="mb-3">
                  <label class="form-label fw-bold">Poin a:</label>
                  <textarea name="bast1_a" class="form-control" rows="2" required>{{ $bast->ketentuan['bast_1']['a'] ?? '' }}</textarea>
                </div>
                <div class="mb-3">
                  <label class="form-label fw-bold">Poin b:</label>
                  <textarea name="bast1_b" class="form-control" rows="2" required>{{ $bast->ketentuan['bast_1']['b'] ?? '' }}</textarea>
                </div>
                <div class="mb-3">
                  <label class="form-label fw-bold">Poin c:</label>
                  <textarea name="bast1_c" class="form-control" rows="2" required>{{ $bast->ketentuan['bast_1']['c'] ?? '' }}</textarea>
                </div>

                <hr class="my-4">

                <h6 class="mb-3 text-success"><i data-feather="file-text" class="me-2"></i>Ketentuan BAST 2 (Pelepasan Retensi)</h6>
                
                <div class="mb-3">
                  <label class="form-label fw-bold">Poin a:</label>
                  <textarea name="bast2_a" class="form-control" rows="2" required>{{ $bast->ketentuan['bast_2']['a'] ?? '' }}</textarea>
                </div>
                <div class="mb-3">
                  <label class="form-label fw-bold">Poin b:</label>
                  <textarea name="bast2_b" class="form-control" rows="2" required>{{ $bast->ketentuan['bast_2']['b'] ?? '' }}</textarea>
                </div>
                <div class="mb-3">
                  <label class="form-label fw-bold">Poin c:</label>
                  <textarea name="bast2_c" class="form-control" rows="2" required>{{ $bast->ketentuan['bast_2']['c'] ?? '' }}</textarea>
                </div>

                <button type="submit" class="btn btn-primary">
                  <i data-feather="save" class="me-1"></i> Simpan Semua Ketentuan
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

@push('custom-scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
  feather?.replace?.();
});
</script>
@endpush
@endsection

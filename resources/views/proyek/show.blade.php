@extends('layout.master')

@push('plugin-styles')
    <link href="{{ asset('assets/plugins/datatables-net-bs5/dataTables.bootstrap5.css') }}" rel="stylesheet" />
    <link href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap5.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/main.min.css">
@endpush

@section('content')
<div class="card shadow-sm animate__animated animate__fadeIn">
  <div class="card-header d-flex justify-content-between align-items-center flex-wrap bg-primary text-white rounded-top">
    <h4 class="page-title m-0 text-center text-md-start w-100 w-md-auto mb-2 mb-md-0 d-flex align-items-center">
      <i data-feather="briefcase" class="me-2"></i>
      <span>{{ $proyek->pemberiKerja->nama_pemberi_kerja ?? '-' }} / {{ $proyek->nama_proyek }}</span>
    </h4>
    <a href="{{ route('proyek.index') }}" class="btn btn-light btn-sm d-none d-md-inline-flex align-items-center">
      <i data-feather="arrow-left" class="me-1"></i> Kembali
    </a>
    <a href="{{ route('proyek.index') }}" class="btn btn-light w-100 d-block d-md-none mt-2">
      <i data-feather="arrow-left" class="me-1"></i> Kembali
    </a>
  </div>

  <div class="card-body p-3 p-md-4">

    {{-- =======================
         TAB PANEL
    ======================== --}}
    <div class="card shadow-sm animate__animated animate__fadeInUp animate__fast">
      {{-- Tab Header --}}
      <ul class="nav nav-tabs nav-tabs-bordered d-flex flex-wrap" id="lineTab" role="tablist">
        <li class="nav-item flex-grow-1 flex-md-grow-0 text-center" role="presentation">
          <a class="nav-link active" id="detproyek-tab" data-bs-toggle="tab" href="#detproyekContent" role="tab" aria-controls="detproyekContent" aria-selected="true">
            <i data-feather="info" class="me-1"></i> Detail Proyek
          </a>
        </li>
        <li class="nav-item flex-grow-1 flex-md-grow-0 text-center" role="presentation">
          <a class="nav-link" id="rab-tab" data-bs-toggle="tab" href="#rabContent" role="tab" aria-controls="rabContent" aria-selected="false">
            <i data-feather="dollar-sign" class="me-1"></i> RAB Proyek
          </a>
        </li>
        <li class="nav-item flex-grow-1 flex-md-grow-0 text-center" role="presentation">
          <a class="nav-link" id="rabpenawaran-tab" data-bs-toggle="tab" href="#rabpenawaranContent" role="tab" aria-controls="rabpenawaranContent" aria-selected="false">
            <i data-feather="dollar-sign" class="me-1"></i> RAB Penawaran
          </a>
        </li>
        <li class="nav-item flex-grow-1 flex-md-grow-0 text-center" role="presentation">
          <a class="nav-link" id="sch-tab" data-bs-toggle="tab" href="#schContent" role="tab" aria-controls="schContent" aria-selected="false">
            <i data-feather="calendar" class="me-1"></i> Schedule
          </a>
        </li>
        <li class="nav-item flex-grow-1 flex-md-grow-0 text-center" role="presentation">
          <a class="nav-link" id="progress-tab" data-bs-toggle="tab" href="#progressContent" role="tab" aria-controls="progressContent" aria-selected="false">
            <i data-feather="trending-up" class="me-1"></i> Progress
          </a>
        </li>
        <li class="nav-item flex-grow-1 flex-md-grow-0 text-center" role="presentation">
          <a class="nav-link" id="bapp-tab" data-bs-toggle="tab" href="#bappContent" role="tab"
            aria-controls="bappContent" aria-selected="false">
            <i data-feather="file-text" class="me-1"></i> BAPP
          </a>
        </li>
        <li class="nav-item flex-grow-1 flex-md-grow-0 text-center" role="presentation">
          <a class="nav-link" id="sertifikat-tab" data-bs-toggle="tab" href="#sertifikatContent" role="tab"
            aria-controls="sertifikatContent" aria-selected="false">
            <i data-feather="award" class="me-1"></i> Sertifikat Pembayaran
          </a>
        </li>

      </ul>



      {{-- ==== Resolve penawaran aktif utk semua tab ==== --}}
      @php
        $currentPenawaranId =
            (isset($selectedId) && $selectedId) ? $selectedId :
            (request('penawaran_id') ?: optional($finalPenawarans->last())->id);

        /** VARIABEL KHUSUS TAB SCHEDULE
        *  Agar tidak ketimpa re-definisi $currentPenawaranId di Tab Progress,
        *  kita “bekukan” nilai buat dipakai di JS Schedule.
        */
        $schedulePenawaranId = $currentPenawaranId;
      @endphp

      {{-- Tab Content --}}
      <div class="tab-content border border-top-0 p-3 p-md-4 mt-0" id="lineTabContent">

        {{-- Tab Detail Proyek --}}
        <div class="tab-pane fade show active" id="detproyekContent" role="tabpanel">
          @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show animate__animated animate__fadeIn mb-3" role="alert">
              <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
          @endif

          <div class="table-responsive">
            <table class="table table-borderless table-sm detail-table">
              <tbody>
                <tr>
                  <th style="width: 40%"><i data-feather="user" class="me-2 text-muted"></i> PIC</th>
                  <td>{{ $proyek->pemberiKerja->pic ?? '-' }}</td>
                </tr>
                <tr>
                  <th><i data-feather="phone" class="me-2 text-muted"></i> No PIC</th>
                  <td>{{ $proyek->pemberiKerja->no_kontak ?? '-' }}</td>
                </tr>
                <tr>
                  <th><i data-feather="file-text" class="me-2 text-muted"></i> SPK</th>
                  <td>
                    @if($proyek->file_spk)
                      <a href="{{ asset('storage/'.$proyek->file_spk) }}" target="_blank" class="text-decoration-none text-primary d-flex align-items-center">
                        <i data-feather="link" class="me-1"></i> {{ $proyek->no_spk }}
                      </a>
                    @else
                      <span class="text-muted">-</span>
                    @endif
                  </td>
                </tr>
                <tr>
                  <th><i data-feather="tag" class="me-2 text-muted"></i> Nilai SPK</th>
                  <td class="fw-bold text-success">Rp {{ number_format($proyek->nilai_spk, 0, ',', '.') }}</td>
                </tr>
                <tr>
                  <th><i data-feather="clipboard" class="me-2 text-muted"></i> Jenis Proyek</th>
                  <td>{{ $proyek->jenis_proyek }}</td>
                </tr>
                <tr>
                  <th><i data-feather="tag" class="me-2 text-muted"></i> Mode Harga Penawaran</th>
                  <td>
                    @php $pmode = $proyek->penawaran_price_mode ?? 'pisah'; @endphp
                    <span class="badge bg-outline-primary text-primary border">{{ strtoupper($pmode) }}</span>
                    <span class="text-muted ms-2">{{ $pmode === 'gabung' ? 'Harga gabungan pakai pembulatan AHSP' : 'Pisah material + jasa' }}</span>
                  </td>
                </tr>
                <tr>
                  <th><i data-feather="credit-card" class="me-2 text-muted"></i> Kebijakan Uang Muka</th>
                  <td>
                    @php $umMode = $proyek->uang_muka_mode ?? 'proporsional'; @endphp
                    <span class="badge bg-outline-primary text-primary border">{{ strtoupper($umMode) }}</span>
                    <span class="text-muted ms-2">
                      {{ $umMode === 'utuh' ? 'Dipulihkan utuh pada sertifikat/invoice pertama' : 'Dipulihkan proporsional mengikuti progres BAPP' }}
                    </span>
                  </td>
                </tr>
                <tr>
                  <th><i data-feather="check-circle" class="me-2 text-muted"></i> Status</th>
                  <td>
                    @if($proyek->status == 'aktif')
                      <span class="badge bg-success"><i class="fas fa-circle me-1"></i> Aktif</span>
                    @elseif($proyek->status == 'selesai')
                      <span class="badge bg-info"><i class="fas fa-circle me-1"></i> Selesai</span>
                    @else
                      <span class="badge bg-secondary"><i class="fas fa-circle me-1"></i> {{ ucfirst($proyek->status) }}</span>
                    @endif
                  </td>
                </tr>
                <tr>
                  <th><i data-feather="play" class="me-2 text-muted"></i> Tanggal Mulai</th>
                  <td>{{ \Carbon\Carbon::parse($proyek->tanggal_mulai)->format('d-m-Y') }}</td>
                </tr>
                <tr>
                  <th><i data-feather="stop-circle" class="me-2 text-muted"></i> Tanggal Selesai</th>
                  <td>{{ \Carbon\Carbon::parse($proyek->tanggal_selesai)->format('d-m-Y') }}</td>
                </tr>
                <tr>
                  <th><i data-feather="map-pin" class="me-2 text-muted"></i> Lokasi</th>
                  <td>{{ $proyek->lokasi }}</td>
                </tr>

                @php
                  $tax = optional($proyek->taxProfileAktif);
                  $efFrom = $tax->effective_from ? \Carbon\Carbon::parse($tax->effective_from)->format('d-m-Y') : '—';
                  $efTo   = $tax->effective_to   ? \Carbon\Carbon::parse($tax->effective_to)->format('d-m-Y')   : '— (berlaku terus)';
                  $ppnRateStr = rtrim(rtrim(number_format((float)($tax->ppn_rate ?? 0), 3, ',', '.'), '0'), ',');
                  $pphRateStr = rtrim(rtrim(number_format((float)($tax->pph_rate ?? 0), 3, ',', '.'), '0'), ',');
                  $extraOpts  = is_array($tax->extra_options ?? null) ? $tax->extra_options : [];
                  $pphSrcStr  = ($extraOpts['pph_dpp_source'] ?? 'jasa') === 'material_jasa' ? 'Material+Jasa' : 'Jasa';
                @endphp

                <tr>
                  <th><i data-feather="percent" class="me-2 text-muted"></i> Pajak – PPN</th>
                  <td>
                    @if($proyek->taxProfileAktif)
                      <span class="badge {{ ($tax->is_taxable??false) ? 'bg-success' : 'bg-secondary' }}">
                        {{ ($tax->is_taxable??false) ? 'Kena PPN' : 'Tidak Kena PPN' }}
                      </span>
                      @if($tax->is_taxable)
                        <span class="ms-2">Mode: 
                          <span class="badge bg-outline-primary text-primary border">
                            {{ strtoupper($tax->ppn_mode ?? 'exclude') }}
                          </span>
                        </span>
                        <span class="ms-2">Tarif: <strong>{{ $ppnRateStr }}%</strong></span>
                      @endif
                    @else
                      <span class="text-muted">Belum diset</span>
                    @endif
                  </td>
                </tr>

                <tr>
                  <th><i data-feather="scissors" class="me-2 text-muted"></i> Pajak – PPh</th>
                  <td>
                    @if($proyek->taxProfileAktif)
                      <span class="badge {{ ($tax->apply_pph??false) ? 'bg-warning text-dark' : 'bg-secondary' }}">
                        {{ ($tax->apply_pph??false) ? 'Dipungut' : 'Tidak Dipungut' }}
                      </span>
                      @if($tax->apply_pph)
                        <span class="ms-2">Dasar: 
                          <span class="badge bg-outline-primary text-primary border">
                            {{ strtoupper($tax->pph_base ?? 'dpp') }}
                          </span>
                        </span>
                        <span class="ms-2">Tarif: <strong>{{ $pphRateStr }}%</strong></span>
                        <span class="ms-2">Sumber DPP: <strong>{{ $pphSrcStr }}</strong></span>
                      @endif
                    @else
                      <span class="text-muted">Belum diset</span>
                    @endif
                  </td>
                </tr>

                <tr>
                  <th><i data-feather="calendar" class="me-2 text-muted"></i> Periode Berlaku Profil</th>
                  <td>
                    @if($proyek->taxProfileAktif)
                      <span>Dari <strong>{{ $efFrom }}</strong> s.d. <strong>{{ $efTo }}</strong></span>
                    @else
                      <span class="text-muted">Belum diset</span>
                    @endif
                  </td>
                </tr>

                <tr>
                  <th><i data-feather="hash" class="me-2 text-muted"></i> Pembulatan</th>
                  <td>
                    @if($proyek->taxProfileAktif)
                      <code>{{ $tax->rounding ?? 'HALF_UP' }}</code>
                    @else
                      <span class="text-muted">Belum diset</span>
                    @endif
                  </td>
                </tr>

                <tr>
                  <th><i data-feather="settings" class="me-2 text-muted"></i> Opsi Tambahan</th>
                  <td>
                    @if($proyek->taxProfileAktif && !empty($tax->extra_options))
                      <pre class="mb-0 small">{{ json_encode($tax->extra_options, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre>
                    @else
                      <span class="text-muted">—</span>
                    @endif
                  </td>
                </tr>

              </tbody>
            </table>
          </div>

          <div class="mt-4 text-center text-md-start">
            <div class="btn-group" role="group">
              <button id="aksiDropdown" type="button" class="btn btn-sm btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                <i data-feather="settings" class="me-1"></i> Aksi
              </button>
              <div class="dropdown-menu shadow" aria-labelledby="aksiDropdown">
                @if(auth()->user()->edit_proyek == 1)
                  <a href="{{ route('proyek.edit', $proyek->id) }}" class="dropdown-item d-flex align-items-center">
                    <i data-feather="edit" class="me-2"></i> Edit Proyek
                  </a>
                @endif
              </div>
            </div>
          </div>
        </div>

        {{-- Tab RAB --}}
        <div class="tab-pane fade" id="rabContent" role="tabpanel">
          @include('proyek.partials.tab_rab', ['headers' => $headers, 'proyek' => $proyek, 'grandTotal' => $grandTotal])
        </div>

        {{-- Tab RAB Penawaran --}}
        <div class="tab-pane fade" id="rabpenawaranContent" role="tabpanel">
          @include('proyek.partials.tab_penawaran', ['proyek' => $proyek])
        </div>

        {{-- =======================
             Tab Schedule
        ======================== --}}
        <div class="tab-pane fade" id="schContent" role="tabpanel">
          <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
            <h5 class="mb-2 mb-md-0 d-flex align-items-center">
              <i data-feather="calendar" class="me-2"></i> Kurva-S (Rencana) per Penawaran
            </h5>

            @if($finalPenawarans->isNotEmpty())
              <form method="GET" action="{{ route('proyek.show', $proyek->id) }}" class="d-flex align-items-center gap-2">
                <input type="hidden" name="tab" value="sch">
                <select name="penawaran_id" class="form-select form-select-sm" onchange="this.form.submit()">
                  @foreach($finalPenawarans as $p)
                    <option value="{{ $p->id }}" {{ (int)$p->id === (int)$currentPenawaranId ? 'selected' : '' }}>
                      {{ $p->nama_penawaran }} ({{ \Carbon\Carbon::parse($p->tanggal_penawaran)->format('d/m/y') }})
                    </option>
                  @endforeach
                </select>
                <noscript><button class="btn btn-primary btn-sm">Tampilkan</button></noscript>
              </form>
            @endif
          </div>

          @if(!$currentPenawaranId || !$hasScheduleSelected || empty($minggu))
            <div class="alert alert-warning">
              Belum ada schedule detail untuk penawaran terpilih.
            </div>
          @else
            @if($selectedMeta)
              <div class="row g-2 mb-2 small text-muted">
                <div class="col-auto"><strong>Mulai:</strong> {{ \Carbon\Carbon::parse($selectedMeta->start_date)->format('d-m-Y') }}</div>
                <div class="col-auto"><strong>Selesai:</strong> {{ \Carbon\Carbon::parse($selectedMeta->end_date)->format('d-m-Y') }}</div>
                <div class="col-auto"><strong>Total Minggu:</strong> {{ $selectedMeta->total_weeks }}</div>
              </div>
            @endif

            <div class="card mb-4 shadow-sm">
              <div class="card-body">
                <div id="kurvaSChart" style="height: 500px;"></div>
              </div>
            </div>
          @endif

          {{-- Kalender --}}
          <div class="card mb-4 shadow-sm">
            <div class="card-header bg-light">
              <h6 class="mb-0 d-flex align-items-center">
                <i data-feather="calendar" class="me-2 text-primary"></i> Kalender Schedule (per Item)
              </h6>
            </div>
            <div class="card-body">
              <div id="scheduleCalendar" style="min-height: 650px;"></div>
            </div>
          </div>

          {{-- Ringkasan Tree --}}
          <div class="card mb-4 shadow-sm" id="treeCard">
            <div class="card-header bg-light d-flex align-items-center">
              <i data-feather="menu" class="me-2 text-secondary"></i>
              <strong>Ringkasan Tanggal per Item</strong>
              <div class="ms-auto small text-muted">Klik ▶ untuk expand, ▼ untuk collapse</div>
            </div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-hover table-bordered table-sm mb-0" id="tree-summary">
                  <thead class="table-light">
                    <tr>
                      <th style="min-width: 360px">KODE / URAIAN</th>
                      <th class="text-end" style="width: 10%">MINGGU MULAI</th>
                      <th class="text-end" style="width: 12%">DURASI (MINGGU)</th>
                      <th style="width: 13%">TGL MULAI</th>
                      <th style="width: 13%">TGL SELESAI</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr><td colspan="5" class="text-muted py-3 text-center">Memuat ringkasan…</td></tr>
                  </tbody>
                </table>
              </div>
            </div>
            <div class="card-footer bg-light d-flex justify-content-end">
              @if($currentPenawaranId)
                <a href="{{ route('rabSchedule.edit', ['proyek' => $proyek->id, 'penawaran' => $currentPenawaranId]) }}"
                   class="btn btn-sm btn-primary">
                  <i data-feather="edit-2" class="me-1"></i>
                  Edit Jadwal (Penawaran:
                  {{ optional($finalPenawarans->firstWhere('id',$currentPenawaranId))->nama_penawaran ?? '#'.$currentPenawaranId }})
                </a>
              @else
                <button class="btn btn-sm btn-secondary" disabled>
                  <i data-feather="edit-2" class="me-1"></i> Pilih penawaran dulu
                </button>
              @endif
            </div>
          </div>
        </div>

        {{-- =======================
             Tab Progress
        ======================= --}}
@php
  $currentPenawaranId = ($selectedId ?? null)
      ?? request('penawaran_id')
      ?? optional($finalPenawarans->last())->id;

  $currentPenawaran = $finalPenawarans->firstWhere('id', $currentPenawaranId);
@endphp

<div class="tab-pane fade" id="progressContent" role="tabpanel">
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <h5 class="mb-0 d-flex align-items-center">
      <i data-feather="bar-chart-2" class="me-2"></i>
      Progress Mingguan
    </h5>

    @if($finalPenawarans->isNotEmpty())
      <form method="GET" action="{{ route('proyek.show', $proyek->id) }}" class="d-flex align-items-center gap-2">
        <input type="hidden" name="tab" value="progress">
        <select name="penawaran_id" class="form-select form-select-sm" onchange="this.form.submit()">
          @foreach($finalPenawarans as $p)
            <option value="{{ $p->id }}" {{ (int)$p->id === (int)$currentPenawaranId ? 'selected' : '' }}>
              {{ $p->nama_penawaran }} ({{ \Carbon\Carbon::parse($p->tanggal_penawaran)->format('d/m/y') }})
            </option>
          @endforeach
        </select>
        <noscript><button class="btn btn-primary btn-sm">Tampilkan</button></noscript>
      </form>
    @endif
  </div>

  <div class="mb-3">
    <span class="badge rounded-pill text-bg-light border">
      <i class="me-1" data-feather="file-text"></i>
      Penawaran: {{ $currentPenawaran->nama_penawaran ?? ('#'.$currentPenawaranId) }}
    </span>
  </div>

  <div class="mb-3 text-center text-md-start">
    <a href="{{ route('proyek.progress.create', $proyek->id) }}?penawaran_id={{ $currentPenawaranId }}&period=1w"
       class="btn btn-primary btn-sm w-100 w-md-auto">
      <i data-feather="plus-circle" class="me-1"></i> Input Progress
    </a>
  </div>

  {{-- Mobile card view --}}
  <div class="d-block d-md-none">
    @forelse ($progressSummary as $item)
      <div class="card mb-3 p-3 shadow-sm animate__animated animate__fadeInUp animate__faster">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h6 class="mb-0">Minggu ke-{{ $item['minggu_ke'] }}</h6>
          @if($item['status'] == 'final')
            <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i> Final</span>
          @else
            <span class="badge bg-warning text-dark"><i class="fas fa-hourglass-half me-1"></i> Draft</span>
          @endif
        </div>
        <hr class="my-2">
        <p class="mb-1"><strong class="text-muted">Tanggal:</strong> {{ \Carbon\Carbon::parse($item['tanggal'])->format('d-m-Y') }}</p>
        <p class="mb-1"><strong class="text-muted">Progress Sebelumnya:</strong> <span class="fw-bold">{{ number_format($item['progress_sebelumnya'], 2, ',', '.') }}%</span></p>
        <p class="mb-1"><strong class="text-muted">Pertumbuhan:</strong> <span class="fw-bold text-info">{{ number_format($item['pertumbuhan'], 2, ',', '.') }}%</span></p>
        <p class="mb-1"><strong class="text-muted">Progress Saat Ini:</strong> <span class="fw-bold text-primary">{{ number_format($item['progress_saat_ini'], 2, ',', '.') }}%</span></p>
        <div class="mt-3 d-grid gap-2">
          <a
            href="{{ route('proyek.progress.detail', ['proyek' => $proyek->id, 'progress' => $item['id']]) }}?penawaran_id={{ $currentPenawaranId }}"
            class="btn btn-sm btn-outline-teal"
          >
            Detail
          </a>

          @if($item['status'] == 'draft')
            <form
              action="{{ route('proyek.progress.destroy', ['proyek'=>$proyek->id, 'progress'=>$item['id']]) }}?penawaran_id={{ $currentPenawaranId }}"
              method="POST"
              onsubmit="return confirm('Yakin ingin menghapus progress minggu ke-{{ $item['minggu_ke'] }}? Tindakan ini tidak dapat dibatalkan.')">
              @csrf
              @method('DELETE')
              <button type="submit" class="btn btn-danger btn-sm w-100">
                <i data-feather="trash-2" class="me-1"></i> Hapus
              </button>
            </form>
          @endif
        </div>
      </div>
    @empty
      <div class="card animate__animated animate__fadeInUp animate__faster">
        <div class="card-body text-center py-5">
          <i class="fas fa-info-circle fa-3x text-muted mb-3"></i>
          <p class="lead text-muted">Belum ada data progress untuk penawaran ini.</p>
        </div>
      </div>
    @endforelse
  </div>

  {{-- Desktop table --}}
  <div class="table-responsive d-none d-md-block">
    <table class="table table-hover table-bordered table-sm">
      <thead class="table-light">
        <tr>
          <th>Minggu Ke</th>
          <th>Tanggal</th>
          <th class="text-end">Progress Sebelumnya (%)</th>
          <th class="text-end">Pertumbuhan (%)</th>
          <th class="text-end">Progress Saat Ini (%)</th>
          <th class="text-center">Status</th>
          <th class="text-center">Aksi</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($progressSummary as $item)
          <tr>
            <td>Minggu ke-{{ $item['minggu_ke'] }}</td>
            <td class="date">{{ \Carbon\Carbon::parse($item['tanggal'])->format('d-m-Y') }}</td>
            <td class="text-end">{{ number_format($item['progress_sebelumnya'], 2, ',', '.') }}%</td>
            <td class="text-end text-info fw-bold">{{ number_format($item['pertumbuhan'], 2, ',', '.') }}%</td>
            <td class="text-end text-primary fw-bold">{{ number_format($item['progress_saat_ini'], 2, ',', '.') }}%</td>
            <td class="text-center">
              @switch($item['status'])
                @case('final')     <span class="badge bg-warning text-dark">Final</span> @break
                @case('approved')  <span class="badge bg-success">Disetujui</span> @break
                @case('revised')   <span class="badge bg-secondary">Direvisi</span> @break
                @default           <span class="badge bg-secondary">{{ ucfirst($item['status']) }}</span>
              @endswitch
            </td>
            <td class="text-center">
              <a
                href="{{ route('proyek.progress.detail', ['proyek'=>$proyek->id, 'progress'=>$item['id']]) }}?penawaran_id={{ $currentPenawaranId }}"
                class="btn btn-sm btn-info me-1">
                <i data-feather="eye" class="me-1"></i> Detail
              </a>

              @if($item['status'] == 'draft')
                <form
                  action="{{ route('proyek.progress.destroy', ['proyek'=>$proyek->id, 'progress'=>$item['id']]) }}?penawaran_id={{ $currentPenawaranId }}"
                  method="POST"
                  class="d-inline"
                  onsubmit="return confirm('Yakin ingin menghapus progress minggu ke-{{ $item['minggu_ke'] }}? Tindakan ini tidak dapat dibatalkan.')">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="btn btn-sm btn-danger">
                    <i data-feather="trash-2" class="me-1"></i> Hapus
                  </button>
                </form>
              @endif
            </td>
          </tr>
        @empty
          <tr><td colspan="7" class="text-center text-muted py-3">Belum ada data progress untuk penawaran ini.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>



      {{-- =======================
     Tab BAPP (Index)
======================= --}}
@php
  // pakai penawaran yang sama dengan tab lain
  $bappPenawaranId = ($selectedId ?? null)
      ?? request('penawaran_id')
      ?? optional($finalPenawarans->last())->id;

  // ambil daftar BAPP untuk proyek (dan filter penawaran bila dipilih)
  $bapps = \App\Models\Bapp::with('penawaran')
      ->where('proyek_id', $proyek->id)
      ->when($bappPenawaranId, fn($q)=>$q->where('penawaran_id', $bappPenawaranId))
      ->orderByDesc('tanggal_bapp')->orderByDesc('id')
      ->get();

  $fmt = fn($n)=>number_format((float)$n, 2, ',', '.');
@endphp

<div class="tab-pane fade" id="bappContent" role="tabpanel">
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <h5 class="mb-0 d-flex align-items-center">
      <i data-feather="file-text" class="me-2"></i>
      Daftar BAPP
    </h5>

    @if($finalPenawarans->isNotEmpty())
      <form method="GET" action="{{ route('proyek.show', $proyek->id) }}" class="d-flex align-items-center gap-2">
        <input type="hidden" name="tab" value="bapp">
        <select name="penawaran_id" class="form-select form-select-sm" onchange="this.form.submit()">
          @foreach($finalPenawarans as $p)
            <option value="{{ $p->id }}" {{ (int)$p->id === (int)$bappPenawaranId ? 'selected' : '' }}>
              {{ $p->nama_penawaran }} ({{ \Carbon\Carbon::parse($p->tanggal_penawaran)->format('d/m/y') }})
            </option>
          @endforeach
        </select>
        <noscript><button class="btn btn-primary btn-sm">Tampilkan</button></noscript>
      </form>
    @endif
  </div>

  <div class="mb-3">
    <span class="badge rounded-pill text-bg-light border">
      <i class="me-1" data-feather="briefcase"></i>
      Penawaran: {{ optional($finalPenawarans->firstWhere('id',$bappPenawaranId))->nama_penawaran ?? ('#'.$bappPenawaranId) }}
    </span>
    <a class="btn btn-sm btn-outline-primary ms-2"
       href="{{ route('bapp.index', $proyek->id) }}">
      Kelola di Halaman BAPP
    </a>
  </div>

  <div class="table-responsive">
    <table class="table table-hover table-bordered table-sm align-middle" id="tbl-bapp">
      <thead class="table-light">
        <tr>
          <th style="width:4%">#</th>
          <th>Nomor BAPP</th>
          <th style="width:10%">Tanggal</th>
          <th style="width:8%">Minggu</th>
          <th>Penawaran</th>
          <th class="text-end" style="width:12%">Prog. s/d Lalu (%)</th>
          <th class="text-end" style="width:12%">Prog. Minggu Ini (%)</th>
          <th class="text-end" style="width:12%">Prog. Saat Ini (%)</th>
          <th class="text-center" style="width:10%">Status</th>
          <th class="text-center" style="width:16%">Aksi</th>
        </tr>
      </thead>
      <tbody>
        @forelse($bapps as $i => $b)
          <tr>
            <td>{{ $i+1 }}</td>
            <td class="text-nowrap">{{ $b->nomor_bapp }}</td>
            <td>{{ \Carbon\Carbon::parse($b->tanggal_bapp)->format('d-m-Y') }}</td>
            <td class="text-nowrap">Minggu ke-{{ $b->minggu_ke }}</td>
            <td>{{ $b->penawaran?->nama_penawaran ?? '-' }}</td>
            <td class="text-end">{{ $fmt($b->total_prev_pct) }}</td>
            <td class="text-end text-info fw-semibold">{{ $fmt($b->total_delta_pct) }}</td>
            <td class="text-end text-primary fw-semibold">{{ $fmt($b->total_now_pct) }}</td>
            <td class="text-center">
              @switch($b->status)
                @case('draft')     <span class="badge bg-warning text-dark">Draft</span> @break
                @case('submitted') <span class="badge bg-info text-dark">Submitted</span> @break
                @case('approved')  <span class="badge bg-success">Approved</span> @break
                @default           <span class="badge bg-secondary">{{ ucfirst($b->status) }}</span>
              @endswitch
            </td>
            <td class="text-center">
              <a href="{{ route('bapp.show', [$proyek->id, $b->id]) }}"
                 class="btn btn-sm btn-outline-teal me-1">
                <i data-feather="eye" class="me-1"></i> Detail
              </a>
              @if($b->file_pdf_path)
                <a target="_blank" href="{{ route('bapp.pdf', [$proyek->id, $b->id]) }}"
                   class="btn btn-sm btn-outline-primary me-1">
                  <i data-feather="download" class="me-1"></i> PDF
                </a>
              @endif

              @if($b->status === 'draft')
                <form method="POST" action="{{ route('bapp.submit', [$proyek->id, $b->id]) }}" class="d-inline">
                  @csrf
                  <button class="btn btn-sm btn-primary">
                    <i data-feather="send" class="me-1"></i> Submit
                  </button>
                </form>
              @elseif($b->status === 'submitted')
                <form method="POST" action="{{ route('bapp.approve', [$proyek->id, $b->id]) }}" class="d-inline"
                      onsubmit="return confirm('Setujui BAPP {{ $b->nomor_bapp }}?');">
                  @csrf
                  <button class="btn btn-sm btn-success">
                    <i data-feather="check-circle" class="me-1"></i> Approve
                  </button>
                </form>
              @endif

              {{-- tombol HAPUS: hanya jika belum approved --}}
              @if($b->status !== 'approved')
                <form method="POST"
                      action="{{ route('bapp.destroy', [$proyek->id, $b->id]) }}"
                      class="d-inline"
                      onsubmit="return confirm('Hapus BAPP {{ $b->nomor_bapp }}? Tindakan ini tidak dapat dibatalkan.');">
                  @csrf
                  @method('DELETE')
                  {{-- supaya kembali ke halaman & tab yang sama --}}
                  <input type="hidden" name="redirect_to" value="{{ request()->fullUrl() }}">
                  <button class="btn btn-sm btn-danger">
                    <i data-feather="trash-2" class="me-1"></i> Hapus
                  </button>
                </form>
              @endif

              @if($b->status === 'approved')
                <a class="btn btn-sm btn-success" href="{{ route('sertifikat.create') }}?bapp_id={{ $b->id }}">
                  Buat Sertifikat Pembayaran
                </a>
              @endif

            </td>
          </tr>
        @empty
          <tr><td colspan="10" class="text-center text-muted py-3">Belum ada BAPP untuk penawaran ini.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="alert alert-secondary mt-3 mb-0 small">
    Untuk menerbitkan BAPP, buka tab <strong>Progress</strong> &rarr; pilih minggu yang ingin ditagihkan &rarr; klik
    <em>Terbitkan BAPP</em> pada halaman detail progress.
  </div>
</div>
{{-- =======================
     Tab Sertifikat Pembayaran (Index)
======================= --}}
@php
  // gunakan penawaran yang sama dengan tab BAPP
  $sertifikatPenawaranId = ($selectedId ?? null)
      ?? request('penawaran_id')
      ?? optional($finalPenawarans->last())->id;

  // ambil daftar sertifikat untuk proyek ini (join via bapps)
  $sertifikats = \App\Models\SertifikatPembayaran::query()
      ->with(['bapp.penawaran'])
      ->whereHas('bapp', function($q) use ($proyek, $sertifikatPenawaranId){
          $q->where('proyek_id', $proyek->id)
            ->when($sertifikatPenawaranId, fn($qq)=>$qq->where('penawaran_id', $sertifikatPenawaranId));
      })
      ->orderByDesc('tanggal')->orderByDesc('id')
      ->get();

  $rp = fn($n)=>'Rp '.number_format((float)$n, 0, ',', '.');
@endphp

<div class="tab-pane fade" id="sertifikatContent" role="tabpanel">
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <h5 class="mb-0 d-flex align-items-center">
      <i data-feather="award" class="me-2"></i>
      Daftar Sertifikat Pembayaran
    </h5>

    @if($finalPenawarans->isNotEmpty())
      <form method="GET" action="{{ route('proyek.show', $proyek->id) }}" class="d-flex align-items-center gap-2">
        <input type="hidden" name="tab" value="sertifikat">
        <select name="penawaran_id" class="form-select form-select-sm" onchange="this.form.submit()">
          @foreach($finalPenawarans as $p)
            <option value="{{ $p->id }}" {{ (int)$p->id === (int)$sertifikatPenawaranId ? 'selected' : '' }}>
              {{ $p->nama_penawaran }} ({{ \Carbon\Carbon::parse($p->tanggal_penawaran)->format('d/m/y') }})
            </option>
          @endforeach
        </select>
        <noscript><button class="btn btn-primary btn-sm">Tampilkan</button></noscript>
      </form>
    @endif
  </div>

  <div class="mb-3">
    <span class="badge rounded-pill text-bg-light border">
      <i class="me-1" data-feather="briefcase"></i>
      Penawaran:
      {{ optional($finalPenawarans->firstWhere('id',$sertifikatPenawaranId))->nama_penawaran ?? ('#'.$sertifikatPenawaranId) }}
    </span>
  </div>
    @php
    $tax = optional($proyek->taxProfileAktif);
    $pphBase = strtoupper($tax->pph_base ?? 'DPP');
    $pphRate = is_null($tax->pph_rate) ? null : rtrim(rtrim(number_format((float)$tax->pph_rate,3,',','.'),'0'),',').'%';
  @endphp

  <div class="mb-2">
    <span class="badge rounded-pill text-bg-light border me-2">
      Basis PPh: <strong class="ms-1">{{ $pphBase }}</strong>
    </span>
    <span class="badge rounded-pill text-bg-light border">
      Tarif PPh: <strong class="ms-1">{{ $pphRate ?? '—' }}</strong>
    </span>
  </div>

  <div class="table-responsive">
    <table class="table table-hover table-bordered table-sm align-middle" id="tbl-sertifikat">
    <thead class="table-light">
      <tr>
        <th style="width:4%">#</th>
        <th>No. Sertifikat</th>
        <th style="width:11%">Tanggal</th>
        <th style="width:10%">Termin</th>
        <th>No. BAPP</th>
        <th class="text-end" style="width:12%">WO Material</th>
        <th class="text-end" style="width:12%">WO Upah</th>
        <th class="text-end" style="width:12%">DPP Material</th>  {{-- baru --}}
        <th class="text-end" style="width:12%">DPP Jasa</th>      {{-- baru --}}
        <th class="text-end" style="width:14%">Tagihan (Bruto+PPN)</th>
        <th class="text-center" style="width:16%">Aksi</th>
      </tr>
    </thead>

      <tbody>
        @forelse($sertifikats as $i => $s)
          <tr>
            <td>{{ $i+1 }}</td>
            <td class="text-nowrap">{{ $s->nomor }}</td>
            <td>{{ \Carbon\Carbon::parse($s->tanggal)->format('d-m-Y') }}</td>
            <td class="text-nowrap">Ke-{{ $s->termin_ke }}</td>
            <td class="text-nowrap">{{ $s->bapp?->nomor_bapp ?? '-' }}</td>
            <td class="text-end">{{ $rp($s->nilai_wo_material) }}</td>
            <td class="text-end">{{ $rp($s->nilai_wo_jasa) }}</td>
            <td class="text-end">{{ $rp($s->dpp_material) }}</td>
            <td class="text-end">{{ $rp($s->dpp_jasa) }}</td>
            <td class="text-end fw-semibold text-primary">{{ $rp($s->total_tagihan) }}</td>
            <td class="text-center">
              <a href="{{ route('sertifikat.show', $s->id) }}" class="btn btn-sm btn-outline-teal me-1">
                <i data-feather="eye" class="me-1"></i> Detail
              </a>
              <a href="{{ route('sertifikat.edit', $s->id) }}" class="btn btn-sm btn-outline-primary me-1">
                <i data-feather="edit" class="me-1"></i> Edit
              </a>
              <a href="{{ route('sertifikat.create', ['bapp_id' => $s->bapp_id]) }}" class="btn btn-sm btn-outline-warning me-1">
                <i data-feather="refresh-ccw" class="me-1"></i> Revisi
              </a>
              <a href="{{ route('sertifikat.cetak', $s->id) }}" class="btn btn-sm btn-outline-primary">
                <i data-feather="download" class="me-1"></i> PDF
              </a>
              <form action="{{ route('sertifikat.destroy', $s->id) }}" method="POST" class="d-inline ms-1" onsubmit="return confirm('Hapus sertifikat ini?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger">
                  <i data-feather="trash-2" class="me-1"></i> Hapus
                </button>
              </form>
            </td>
          </tr>
        @empty
          <tr><td colspan="9" class="text-center text-muted py-3">Belum ada sertifikat untuk penawaran ini.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

      </div> {{-- End tab-content --}}
    </div> {{-- End card tab panel --}}
  </div> {{-- End card-body --}}
</div> {{-- End card --}}
@endsection


@push('plugin-scripts')
  <script src="{{ asset('assets/plugins/datatables-net/jquery.dataTables.js') }}"></script>
  <script src="{{ asset('assets/plugins/datatables-net-bs5/dataTables.bootstrap5.js') }}"></script>
  <script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
  <script src="https://cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap5.min.js"></script>

  <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
  <script src="https://unpkg.com/feather-icons"></script>

  <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/locales-all.global.min.js"></script>
@endpush

@push('custom-scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  feather.replace();

  // pertahankan tab dari query ?tab=
  (function keepTabFromQuery(){
    const params = new URLSearchParams(window.location.search);
    const tab = params.get('tab');
    if (tab) {
      const el = document.getElementById(tab + '-tab');
      if (el) new bootstrap.Tab(el).show();
    }
  })();

  // Kurva S
  const chartEl = document.querySelector('#kurvaSChart');
  if (chartEl) {
    const options = {
      chart: { type: 'line', height: 500, zoom: { enabled: false }, toolbar: { show: false } },
      series: [
        { name: 'Rencana',   data: @json($akumulasi) },
        { name: 'Realisasi', data: @json($realisasi) }
      ],
      grid: { row: { colors: ['#f8f9fa', 'transparent'], opacity: 0.5 } },
      xaxis: { categories: @json($minggu), title: { text: 'Minggu' }, labels: { style: { colors: '#6c757d' } } },
      yaxis: {
        max: 100,
        title: { text: 'Bobot (%)' },
        labels: { formatter: (v) => (v ?? 0).toFixed(2), style: { colors: '#6c757d' } }
      },
      tooltip: {
        x: { formatter: (v) => 'Minggu ke-' + v },
        y: { formatter: (v) => (v === null ? '-' : Number(v).toFixed(2) + ' %') }
      },
      title: { text: 'Kurva S Rencana - Realisasi', align: 'left', style: { fontSize: '16px', fontWeight: 'bold', color: '#343a40' } },
      markers: { size: 4, strokeWidth: 2, hover: { sizeOffset: 2 } },
      stroke: { width: 3, curve: 'straight' },
      colors: ['#28a745', '#dc3545']
    };
    const chart = new ApexCharts(chartEl, options);
    chart.render();
  }

  // Kalender
 
  // === FullCalendar: render hanya saat tab Schedule terlihat ===
  let calendar;               // instance disimpan di sini
  const calEl = document.getElementById('scheduleCalendar');

  function buildCalendar() {
    if (!calEl || calendar) return; // sudah dibuat
    calendar = new FullCalendar.Calendar(calEl, {
      height: 650,
      initialView: 'dayGridMonth',
      headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,listWeek' },
      firstDay: 1,
      locale: 'id',
      navLinks: true,
      nowIndicator: true,
      dayMaxEvents: true,
      initialDate: '{{ $selectedMeta ? \Carbon\Carbon::parse($selectedMeta->start_date)->toDateString() : now()->toDateString() }}',
      events: @json($calendarEvents),
      eventTimeFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
      eventDidMount(info){
        const startStr = info.event.start ? info.event.start.toLocaleDateString('id-ID') : '-';
        let endStr = '-';
        if (info.event.end) {
          const endAdj = new Date(info.event.end.getTime() - 86400000);
          endStr = endAdj.toLocaleDateString('id-ID');
        }
        info.el.title = `${info.event.title} (${startStr} - ${endStr})`;
      }
    });
    calendar.render();
  }

  // render saat tab Schedule pertama kali dibuka
  document.querySelector('a#sch-tab')?.addEventListener('shown.bs.tab', () => {
    // beri 1 frame supaya tab benar-benar visible dahulu
    requestAnimationFrame(() => {
      if (!calendar) buildCalendar(); else calendar.updateSize();
    });
  });

  // jika halaman langsung dibuka dengan ?tab=sch, tunggu sampai tab di-show lalu render
  (function renderIfScheduleActiveOnLoad() {
    const params = new URLSearchParams(location.search);
    if (params.get('tab') === 'sch') {
      // tab akan di-show oleh script "keepTabFromQuery" milik kamu
      // sebagai jaga-jaga, jalankan setelah event loop berikutnya
      setTimeout(() => {
        if (document.getElementById('schContent')?.classList.contains('active')) {
          buildCalendar();
        }
      }, 0);
    }
  })();

  // bila ukuran container berubah (resize), pastikan kalender menyesuaikan
  window.addEventListener('resize', () => { if (calendar) calendar.updateSize(); });
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  /* ... kode lain kamu tetap ... */

  // ===== Ringkasan Tree (AJAX) - FIXED =====
  const PENAWARAN_ID = @json($schedulePenawaranId ?? null);
  const treeTbody    = document.querySelector('#tree-summary tbody');

  function toggleIcon(el, open){ if(el) el.textContent = open ? '▼' : '▶'; }
  function collapse(branchKey){
    const rows = treeTbody.querySelectorAll(`tr[data-parent="${CSS.escape(branchKey)}"]`);
    rows.forEach(r => {
      r.style.display = 'none';
      const caret = r.querySelector('.caret');
      if (caret && !caret.classList.contains('disabled')) {
        toggleIcon(caret, false);
        collapse(r.dataset.key);
      }
    });
  }
  function expand(branchKey){
    const rows = treeTbody.querySelectorAll(`tr[data-parent="${CSS.escape(branchKey)}"]`);
    rows.forEach(r => r.style.display = '');
  }

  // Toggle expand/collapse
  treeTbody?.addEventListener('click', (e) => {
    const caret = e.target.closest('.caret');
    if (!caret || caret.classList.contains('disabled')) return;
    const tr  = caret.closest('tr');
    const key = tr.dataset.key;
    const open = caret.textContent.trim() === '▼';
    if (open) { toggleIcon(caret, false); collapse(key); }
    else      { toggleIcon(caret, true);  expand(key);   }
  });

  // Renderer untuk respon JSON
  function renderRowsFromJson(payload){
    const rows = (payload?.items || []);
    if (!rows.length) {
      treeTbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-3">Belum ada data.</td></tr>';
      return;
    }
    const fmtDate = (s) => {
      if (!s) return '—';
      const d = new Date(s);
      return isNaN(d) ? s : d.toLocaleDateString('id-ID');
    };

    const html = rows.map(r => {
      const caret = r.has_children ? '<span class="caret">▶</span>' : '<span class="caret disabled">•</span>';
      return `
        <tr data-key="${r.key}" data-parent="${r.parent_key || ''}" class="level-${r.level||1}">
          <td class="tree-cell">${caret}<span class="wbs">${r.kode||''}</span> ${r.uraian||''}</td>
          <td class="text-end">${r.minggu_mulai ?? ''}</td>
          <td class="text-end">${r.durasi_minggu ?? ''}</td>
          <td>${fmtDate(r.tgl_mulai)}</td>
          <td>${fmtDate(r.tgl_selesai)}</td>
        </tr>`;
    }).join('');
    treeTbody.innerHTML = html;
  }

  async function loadTreeBody(){
    if (!treeTbody) return;

    // Pakai route() langsung tanpa new URL (menghindari URL double-host)
    let url = @json(route('proyek.schedule.summary.tree', ['proyek' => $proyek->id]));
    if (PENAWARAN_ID) {
      url += (url.includes('?') ? '&' : '?') + 'penawaran_id=' + encodeURIComponent(PENAWARAN_ID);
    }

    try {
      const res  = await fetch(url, { headers: { 'X-Requested-With':'XMLHttpRequest' }});
      if (!res.ok) throw new Error('HTTP ' + res.status);

      // Coba baca sebagai teks, lalu deteksi apakah JSON
      const text = await res.text();
      const isJson = (res.headers.get('content-type') || '').includes('application/json')
                  || (text.trim().startsWith('{') && text.trim().endsWith('}'));

      if (isJson) {
        try {
          const data = JSON.parse(text);
          renderRowsFromJson(data);
        } catch (e) {
          console.error('Parse JSON gagal, isi:', text);
          treeTbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-3">Format data tidak valid.</td></tr>';
        }
      } else {
        // Server kirim partial <tr>…</tr>
        treeTbody.innerHTML = text || '<tr><td colspan="5" class="text-center text-muted py-3">Belum ada data.</td></tr>';
      }
    } catch (err) {
      console.error('Gagal load tree:', err);
      treeTbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-3">Gagal memuat ringkasan.</td></tr>';
    }
  }

  loadTreeBody();
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const t1 = document.getElementById('tbl-sertifikat');
  if (t1 && window.jQuery) {
    $(t1).DataTable({
      paging: true,
      searching: true,
      responsive: true,
      order: [[2,'desc']], // sort by tanggal
      language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json' }
    });
  }
});
</script>

<style>
#scheduleCalendar .fc .fc-toolbar-title { font-size: 1.1rem; font-weight: 600; }
#scheduleCalendar .fc .fc-daygrid-event { border-radius: .5rem; }

#tree-summary td, #tree-summary th { vertical-align: middle; }
#tree-summary .tree-cell { white-space: nowrap; }
#tree-summary .caret{
  display:inline-block;width:1.2rem;text-align:center;margin-right:.35rem;
  cursor:pointer;user-select:none
}
#tree-summary .caret.disabled{opacity:.35;cursor:default}
#tree-summary .wbs{font-weight:600;margin-right:.5rem}
#tree-summary .level-2 .tree-cell{padding-left:1.5rem}
#tree-summary .level-3 .tree-cell{padding-left:3.0rem}
#tree-summary .dot{display:inline-block;width:1.2rem;text-align:center;color:#888}
</style>
@endpush

@extends('layout.master')

@push('plugin-styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
<style>
  .card{border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,.1);border:none}
  .card-header{border-top-left-radius:12px;border-top-right-radius:12px;padding:1.5rem;display:flex;justify-content:space-between;align-items:center;background:#007bff;color:#fff}
  .table-borderless th,.table-borderless td{padding:1rem;vertical-align:top;border-top:none}
  .table-borderless th{color:#495057;font-weight:600;width:200px}
  .table-bordered thead th{background:#e9ecef;color:#495057;font-weight:600;border-bottom:2px solid #dee2e6}
  .table-bordered tbody tr:hover{background:#f2f2f2}
  .badge{font-size:.85em;padding:.5em .75em;border-radius:1rem}
  .section-header-row{background:#e9f5ff;color:#0056b3;font-weight:bold;cursor:pointer;transition:background-color .2s ease}
  .section-header-row:hover{background:#d1e7ff}
  .item-table-container{padding:10px;background:#f8fafd;border:1px solid #e0e0e0;border-radius:8px}
  .item-table th,.item-table td{white-space:nowrap;font-size:.875rem}
  .item-table thead th{background:#f0f8ff;color:#495057}
  .item-table tfoot td{background:#f8fbff;font-weight:600}
  .row-area td{background:#eef8ff;font-weight:600;font-style:italic}
  .spec{display:block;margin-top:4px;font-size:.8rem;color:#6c757d;white-space:pre-line}
</style>
<style>
  .disabled-btn{ pointer-events:none; opacity:.6; cursor:not-allowed; }
</style>
@endpush

@section('content')
<div class="card shadow-sm animate__animated animate__fadeIn">
  <div class="card-header">
    <h4 class="card-title mb-0 d-flex align-items-center">
      <i class="fas fa-file-invoice-dollar me-2"></i> Detail Penawaran
    </h4>
    <a href="{{ route('proyek.show', $proyek->id) }}#rabContent" class="btn btn-light btn-sm d-inline-flex align-items-center" data-bs-toggle="tooltip" title="Kembali ke RAB Proyek">
      <i class="fas fa-arrow-left me-1"></i> Kembali
    </a>
  </div>
  <div class="card-body p-4">
    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif
    @if(session('error'))
      <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        <i class="fas fa-times-circle me-2"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    {{-- ================================
         Persiapan total & pajak
       ================================= --}}
    @php
      // Totalkan material & jasa dari item (berbasis data yang tersimpan di penawaran)
      $sumMaterial = 0.0; $sumJasa = 0.0;
      foreach ($penawaran->sections as $sec) {
          foreach (($sec->items ?? []) as $it) {
              $vol = (float) ($it->volume ?? 0);
              $sumMaterial += (float)($it->harga_material_penawaran_item ?? 0) * $vol;
              $sumJasa     += (float)($it->harga_upah_penawaran_item     ?? 0) * $vol;
          }
      }

      $discPct   = (float)($penawaran->discount_percentage ?? 0);
      $discCoef  = max(0, 1 - ($discPct / 100));
      $matNet    = $sumMaterial * $discCoef;    // material setelah diskon
      $jasaNet   = $sumJasa     * $discCoef;    // jasa setelah diskon
      $subtotal  = $matNet + $jasaNet;          // subtotal setelah diskon

      // Ambil profil pajak aktif proyek (fallback default)
      $tax       = optional($proyek->taxProfileAktif);
      $isTaxable = (int)($tax->is_taxable ?? 1) === 1;
      $ppnMode   = $tax->ppn_mode ?? 'exclude';        // 'exclude' | 'include'
      $ppnRate   = (float)($tax->ppn_rate ?? 11.0);    // persen
      $applyPph  = (int)($tax->apply_pph ?? 0) === 1;
      $pphRate   = (float)($tax->pph_rate ?? 2.0);     // persen
      $pphBaseKind = (string)($tax->pph_base ?? 'dpp'); // 'dpp' | 'subtotal'
      $extraOpts = is_array($tax->extra_options ?? null) ? $tax->extra_options : [];
      $pphDppSource = (string)($extraOpts['pph_dpp_source'] ?? 'jasa'); // 'jasa' | 'material_jasa'

      // Hitung DPP & PPN
      if (!$isTaxable) {
          $dpp = $subtotal; $ppn = 0.0; $totalPlusPpn = $subtotal;
      } elseif ($ppnMode === 'include') {
          $dpp = $subtotal / (1 + $ppnRate/100);
          $ppn = $subtotal - $dpp;
          $totalPlusPpn = $subtotal; // sudah termasuk PPN
      } else { // exclude
          $dpp = $subtotal;
          $ppn = $dpp * ($ppnRate/100);
          $totalPlusPpn = $dpp + $ppn;
      }

        // Hitung DPP untuk jasa (untuk sumber 'jasa')
        $jasaDpp = ($isTaxable && $ppnMode === 'include') ? ($jasaNet / (1 + $ppnRate/100)) : $jasaNet;

        // Tentukan basis PPh sesuai konfigurasi
        if ($applyPph) {
          if ($pphDppSource === 'material_jasa') {
            $pphBaseAmount = ($pphBaseKind === 'dpp') ? $dpp : $subtotal;
          } else { // 'jasa' saja
            $pphBaseAmount = ($pphBaseKind === 'dpp') ? $jasaDpp : $jasaNet;
          }
          $pph = $pphBaseAmount * ($pphRate/100);
        } else {
          $pph = 0.0;
        }

      // Total dibayar (umum di ID): Total + PPN - PPh
      $totalDibayar = $totalPlusPpn - $pph;

      // Helper tampilan rupiah
      $rf = fn($n) => 'Rp ' . number_format((float)$n, 0, ',', '.');
    @endphp

    <h5 class="mb-3 text-primary"><i class="fas fa-info-circle me-2"></i> Informasi Penawaran</h5>
    <div class="table-responsive mb-4">
      <table class="table table-borderless table-sm detail-table">
        <tbody>
          <tr><th>Nomor Penawaran</th><td>: {{ $penawaran->nomor_penawaran ?? '—' }}</td></tr>
          <tr><th>Nama Penawaran</th><td>: {{ $penawaran->nama_penawaran }}</td></tr>
          <tr><th>Tanggal Penawaran</th><td>: {{ \Carbon\Carbon::parse($penawaran->tanggal_penawaran)->format('d-m-Y') }}</td></tr>
          <tr><th>Proyek</th><td>: {{ $proyek->nama_proyek }} - {{ $proyek->pemberiKerja->nama_pemberi_kerja ?? '' }}</td></tr>
          <tr><th>Total Material (sebelum diskon)</th><td class="fw-bold">: {{ $rf($sumMaterial) }}</td></tr>
          <tr><th>Total Jasa (sebelum diskon)</th>    <td class="fw-bold">: {{ $rf($sumJasa) }}</td></tr>
          <tr><th>Diskon (%)</th>                     <td>: {{ number_format($discPct, 2, ',', '.') }}%</td></tr>
          <tr><th>Subtotal setelah Diskon</th>        <td class="fw-bold text-info">: {{ $rf($subtotal) }}</td></tr>
          <tr>
            <th>Status</th>
            <td>:
              @if($penawaran->status == 'draft')
                <span class="badge bg-warning text-dark"><i class="fas fa-hourglass-half me-1"></i> Draft</span>
              @elseif($penawaran->status == 'final')
                <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i> Final</span>
              @else
                <span class="badge bg-secondary">{{ ucfirst($penawaran->status) }}</span>
              @endif
            </td>
          </tr>
          @if($penawaran->status === 'final')
            @php
              $docs = collect($penawaran->approval_doc_paths ?? [])
                ->when(empty($penawaran->approval_doc_paths ?? null) && !empty($penawaran->approval_doc_path ?? null),
                  fn($c)=>$c->push($penawaran->approval_doc_path));
            @endphp
            @if($docs->isNotEmpty())
              <tr>
                <th>Dokumen SPK/PO</th>
                <td>:
                  <ul class="mb-0 ps-3">
                    @foreach($docs as $idx => $p)
                      <li>
                        <a href="{{ route('proyek.penawaran.approval.download', [$proyek->id, $penawaran->id, base64_encode($p)]) }}" target="_blank" class="text-primary">
                          <i class="fas fa-file-pdf me-1"></i> Dokumen {{ $idx + 1 }}
                        </a>
                      </li>
                    @endforeach
                  </ul>
                </td>
              </tr>
            @endif
          @endif
        </tbody>
      </table>
    </div>

    {{-- ================================
         Ringkasan Pajak & Total
       ================================= --}}
    <h5 class="mb-3 text-primary"><i class="fas fa-calculator me-2"></i> Ringkasan Pajak</h5>
    <div class="table-responsive mb-4">
      <table class="table table-bordered table-sm">
        <thead>
          <tr>
            <th>Komponen</th>
            <th>Keterangan</th>
            <th class="text-end">Nilai</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>Mode PPN</td>
            <td>{{ $isTaxable ? strtoupper($ppnMode) : 'Tidak Kena PPN' }} (Tarif {{ rtrim(rtrim(number_format($ppnRate,3,',','.'),'0'),',') }}%)</td>
            <td class="text-end">—</td>
          </tr>
          <tr>
            <td>DPP (setelah diskon)</td>
            <td>Material + Jasa</td>
            <td class="text-end">{{ $rf($dpp) }}</td>
          </tr>
          <tr>
            <td>PPN</td>
            <td>{{ $isTaxable ? ($ppnMode==='include' ? 'Tersirat di subtotal' : 'Ditambahkan ke DPP') : '—' }}</td>
            <td class="text-end">{{ $rf($ppn) }}</td>
          </tr>
          <tr>
            <td>PPh</td>
            <td>
              @if($applyPph)
                Dipungut atas {{ $pphDppSource === 'material_jasa' ? 'Material + Jasa' : 'Jasa saja' }}
                — Basis {{ strtoupper($pphBaseKind) }}, Tarif {{ rtrim(rtrim(number_format($pphRate,3,',','.'),'0'),',') }}%
              @else
                Tidak dipotong
              @endif
            </td>
            <td class="text-end">- {{ $rf($pph) }}</td>
          </tr>
        </tbody>
        <tfoot>
          <tr>
            <td colspan="2" class="text-end">Total + PPN</td>
            <td class="text-end">{{ $rf($totalPlusPpn) }}</td>
          </tr>
          <tr>
            <td colspan="2" class="text-end fw-bold">Total Dibayar (Nett) = (Total + PPN) − PPh</td>
            <td class="text-end fw-bold text-success fs-5">{{ $rf($totalDibayar) }}</td>
          </tr>
        </tfoot>
      </table>
    </div>


  {{-- ================================
  Keterangan / Term of Payment (pakai modal)
    ================================= --}}
    <h5 class="mb-3 text-primary">
      <i class="fas fa-clipboard-list me-2"></i> Keterangan / Term of Payment
    </h5>

    <div class="d-flex align-items-center gap-2 mb-3">
      <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#keteranganModal">
        <i class="fas fa-pen-to-square me-1"></i> Kelola Keterangan
      </button>
      @if(!empty($penawaran->keterangan))
        <span class="text-muted">Terakhir disimpan.</span>
      @endif
    </div>

    {{-- Pratinjau dari server (isi yang tersimpan) --}}
    <div class="card mb-4">
      <div class="card-header bg-light">
        <strong><i class="fas fa-eye me-2"></i>Pratinjau Keterangan</strong>
      </div>
      <div class="card-body pt-2">
        @php
          $lines = preg_split("/\r\n|\n|\r/", (string)($penawaran->keterangan ?? ''));
          $hasAny = collect($lines)->contains(fn($l)=>trim($l)!=='');
        @endphp
        @if($hasAny)
          <ul class="mb-0" id="keteranganPreviewServer">
            @foreach($lines as $line)
              @if(trim($line)!=='')
                <li>{{ $line }}</li>
              @endif
            @endforeach
          </ul>
        @else
          <em class="text-muted">Belum ada keterangan. Klik “Kelola Keterangan”.</em>
        @endif
      </div>
    </div>



    {{-- ================================
         Detail per Section & Item
       ================================= --}}
    <h5 class="mb-3 text-primary"><i class="fas fa-list-alt me-2"></i> Detail Bagian Penawaran</h5>

    @foreach($penawaran->sections as $section)
      @php
        $hasItems = $section->items && $section->items->isNotEmpty();

        $sectionMat  = $hasItems ? $section->items->sum(fn($it)=>(float)($it->harga_material_penawaran_item ?? 0) * (float)($it->volume ?? 0)) : 0;
        $sectionJasa = $hasItems ? $section->items->sum(fn($it)=>(float)($it->harga_upah_penawaran_item ?? 0)     * (float)($it->volume ?? 0)) : 0;
        $sectionTotal = $sectionMat + $sectionJasa;
      @endphp

      <div class="card mb-3 animate__animated animate__fadeInUp animate__faster">
        <div class="card-header section-header-row d-flex justify-content-between align-items-center"
             @if($hasItems)
               data-bs-toggle="collapse"
               data-bs-target="#sectionCollapse{{ $section->id }}"
               aria-expanded="false"
               aria-controls="sectionCollapse{{ $section->id }}"
             @endif>
          <div class="d-flex align-items-center gap-2">
            <i class="fas fa-folder me-2"></i>
            {{ $section->rabHeader->kode ?? 'N/A' }} - {{ $section->rabHeader->deskripsi ?? 'Bagian RAB Tidak Ditemukan' }}
            @if($hasItems)
              <span class="ms-3 badge bg-dark">Profit: {{ number_format($section->profit_percentage, 2, ',', '.') }}%</span>
              <span class="ms-2 badge bg-dark">Overhead: {{ number_format($section->overhead_percentage, 2, ',', '.') }}%</span>
            @endif
          </div>
          @if($hasItems)
            <div class="text-end">
              <div class="small mb-1">
                <span class="me-2">Material: <span class="fw-bold">Rp {{ number_format($sectionMat, 0, ',', '.') }}</span></span>
                <span>Jasa: <span class="fw-bold">Rp {{ number_format($sectionJasa, 0, ',', '.') }}</span></span>
              </div>
              <div>
                Total Bagian:
                <span class="fw-bold text-success">Rp {{ number_format($sectionTotal, 0, ',', '.') }}</span>
                <i class="fas fa-chevron-down ms-2 collapse-icon"></i>
              </div>
            </div>
          @endif
        </div>

        @if($hasItems)
          <div class="collapse" id="sectionCollapse{{ $section->id }}">
            <div class="card-body item-table-container">
              <div class="table-responsive">
                <table class="table table-hover table-bordered table-sm item-table">
                  <thead class="table-light">
                    <tr>
                      <th>Kode</th>
                      <th>Uraian / Spesifikasi</th>
                      <th class="text-end">Volume</th>
                      <th>Satuan</th>
                      <th class="text-end">Harga Satuan Material</th>
                      <th class="text-end">Harga Satuan Jasa</th>
                      <th class="text-end">Total Material</th>
                      <th class="text-end">Total Jasa</th>
                    </tr>
                  </thead>
                  <tbody>
                    @php
                      $itemsByArea = $section->items->groupBy(function($it){
                        $a = is_string($it->area) ? trim($it->area) : '';
                        return $a !== '' ? $a : '__NOAREA__';
                      });
                      $subMaterial = 0; $subJasa = 0;
                    @endphp

                    @foreach($itemsByArea as $areaName => $items)
                      @if($areaName !== '__NOAREA__')
                        <tr class="row-area"><td colspan="8">Area: {{ $areaName }}</td></tr>
                      @endif

                      @foreach($items as $item)
                        @php
                          $vol      = (float) ($item->volume ?? 0);
                          $unitMat  = (float) ($item->harga_material_penawaran_item ?? 0);
                          $unitJasa = (float) ($item->harga_upah_penawaran_item ?? 0);
                          $totMat   = $unitMat  * $vol;
                          $totJasa  = $unitJasa * $vol;
                          $subMaterial += $totMat; $subJasa += $totJasa;
                        @endphp
                        <tr>
                          <td>{{ $item->kode }}</td>
                          <td>
                            {{ $item->deskripsi }}
                            @if(!empty($item->spesifikasi))
                              <span class="spec">{!! nl2br(e($item->spesifikasi)) !!}</span>
                            @endif
                          </td>
                          <td class="text-end">{{ number_format($vol, 2, ',', '.') }}</td>
                          <td>{{ $item->satuan }}</td>
                          <td class="text-end">Rp {{ number_format($unitMat, 0, ',', '.') }}</td>
                          <td class="text-end">Rp {{ number_format($unitJasa, 0, ',', '.') }}</td>
                          <td class="text-end">Rp {{ number_format($totMat, 0, ',', '.') }}</td>
                          <td class="text-end">Rp {{ number_format($totJasa, 0, ',', '.') }}</td>
                        </tr>
                      @endforeach
                    @endforeach
                  </tbody>
                  <tfoot>
                    <tr>
                      <td colspan="6" class="text-end">Subtotal Bagian</td>
                      <td class="text-end">Rp {{ number_format($subMaterial, 0, ',', '.') }}</td>
                      <td class="text-end">Rp {{ number_format($subJasa, 0, ',', '.') }}</td>
                    </tr>
                  </tfoot>
                </table>
              </div>
            </div>
          </div>
        @endif
      </div>
    @endforeach

    {{-- ================================
     Filter Item ala Excel
================================ --}}
@php
  // Flatten semua item supaya gampang difilter di sisi klien
  $flatItems = [];
  foreach ($penawaran->sections as $sec) {
    $hdrKode = $sec->rabHeader->kode ?? '';
    $hdrNama = $sec->rabHeader->deskripsi ?? '';
    foreach (($sec->items ?? []) as $it) {
      $vol      = (float)($it->volume ?? 0);
      $unitMat  = (float)($it->harga_material_penawaran_item ?? 0);
      $unitJasa = (float)($it->harga_upah_penawaran_item ?? 0);
      $flatItems[] = [
        'kode'        => (string)$it->kode,
        'uraian'      => (string)($it->deskripsi ?? ''),
        'spesifikasi' => (string)($it->spesifikasi ?? ''),
        'area'        => (string)($it->area ?? ''),
        'header_kode' => (string)$hdrKode,
        'header_nama' => (string)$hdrNama,
        'volume'      => $vol,
        'satuan'      => (string)($it->satuan ?? ''),
        'unit_mat'    => $unitMat,
        'unit_jasa'   => $unitJasa,
        'tot_mat'     => $unitMat * $vol,
        'tot_jasa'    => $unitJasa * $vol,
      ];
    }
  }
@endphp

<div class="card mb-4 animate__animated animate__fadeInUp">
  <div class="card-header bg-light d-flex align-items-center justify-content-between">
    <h5 class="mb-0 text-primary">
      <i class="fas fa-filter me-2"></i> Pekerjaan Per Item
    
    </h5>
  </div>
  <div class="card-body">
    <div class="row g-2 align-items-end mb-3">
      <div class="col-md-6">
        <label class="form-label">Ketik kata kunci (contoh: <code>plafond</code>)</label>
        <input id="excelLikeQuery" type="text" class="form-control" placeholder="Cari di kode, uraian, spesifikasi, atau area…">
      </div>
      <div class="col-md-3">
        <label class="form-label">Mode Pencarian</label>
        <select id="excelLikeMode" class="form-select">
          <option value="any">Mengandung salah satu kata</option>
          <option value="all" selected>Harus mengandung semua kata</option>
        </select>
      </div>
      <div class="col-md-3">
        <div class="form-check mt-4">
          <input class="form-check-input" type="checkbox" value="1" id="excelLikeUseDiscount" checked>
          <label class="form-check-label" for="excelLikeUseDiscount">
            Gunakan diskon global ({{ number_format($discPct,2,',','.') }}%)
          </label>
        </div>
      </div>
    </div>

    <div class="table-responsive">
      <table class="table table-hover table-bordered table-sm align-middle" id="tbl-excel-like">
        <thead class="table-light">
          <tr>
            <th style="width:10%">Kode</th>
            <th>Uraian / Spesifikasi</th>
            <th style="width:10%">Area</th>
            <th class="text-end" style="width:10%">Volume</th>
            <th style="width:8%">Sat</th>
            <th class="text-end" style="width:11%">Hrg Sat Material</th>
            <th class="text-end" style="width:11%">Hrg Sat Jasa</th>
            <th class="text-end" style="width:11%">Total Material</th>
            <th class="text-end" style="width:11%">Total Jasa</th>
            <th class="text-end" style="width:11%">Total (Mat+Jasa)</th>
          </tr>
        </thead>
        <tbody>
          <tr><td colspan="10" class="text-center text-muted py-3">Ketik kata kunci untuk menampilkan hasil…</td></tr>
        </tbody>
        <tfoot class="table-light">
          <tr>
            <td colspan="7" class="text-end fw-semibold">TOTAL HASIL</td>
            <td class="text-end fw-semibold" id="excelLikeTotMat">Rp 0</td>
            <td class="text-end fw-semibold" id="excelLikeTotJasa">Rp 0</td>
            <td class="text-end fw-bold"     id="excelLikeTotAll">Rp 0</td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
</div>


    <div class="small text-muted mt-2">
      Tips: pisahkan banyak kata kunci dengan spasi (mis. <code>plafon gypsum</code>). Mode “Semua kata” akan mencari item yang mengandung <em>kedua</em> kata.
    </div>
  </div>
</div>


    @php $isFinal = ($penawaran->status === 'final'); @endphp

    <div class="mt-4 d-flex flex-wrap gap-2 align-items-center">
      <a href="{{ route('proyek.penawaran.edit', ['proyek' => $proyek->id, 'penawaran' => $penawaran->id]) }}" class="btn btn-warning" data-bs-toggle="tooltip" title="Edit Penawaran">
        <i class="fas fa-edit me-1"></i> Edit
      </a>
      <a target="_blank" class="btn btn-outline-primary btn-sm" href="{{ route('proyek.penawaran.PdfSinglePrice', [$proyek->id, $penawaran->id]) }}">
        PDF (Gabungan)
      </a>

      <a target="_blank" class="btn btn-outline-primary btn-sm" href="{{ route('proyek.penawaran.pdf-mixed', [$proyek->id, $penawaran->id]) }}">
        PDF (Material + Jasa)
      </a>

      <a class="btn btn-outline-success btn-sm" href="{{ route('proyek.penawaran.exportExcel', [$proyek->id, $penawaran->id, 'mode' => 'pisah']) }}">
        <i class="fas fa-file-excel me-1"></i> Export Excel
      </a>

      @if($isFinal)
        {{-- Tombol untuk kembalikan ke Draft --}}
        <form method="POST" action="{{ route('proyek.penawaran.unapprove', [$proyek->id, $penawaran->id]) }}" class="d-inline" onsubmit="return confirm('Yakin mengembalikan penawaran ini ke status Draft?');">
          @csrf
          <button type="submit" class="btn btn-warning btn-sm">
            <i class="fas fa-undo me-1"></i> Batalkan Persetujuan
          </button>
        </form>
      @else
        {{-- SETUJUI → buka modal upload PDF --}}
        <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#approveModal">
          <i class="fas fa-check-circle me-1"></i> Setujui
        </button>
      @endif

      {{-- Bobot (hanya aktif jika FINAL) --}}
      @if($isFinal)
        <form method="POST" action="{{ route('proyek.penawaran.snapshot', [$proyek->id, $penawaran->id]) }}" class="m-0">
          @csrf
          <button class="btn btn-dark btn-sm" type="submit" title="Buat Bobot">
            <i class="fas fa-balance-scale me-1"></i> Bobot
          </button>
        </form>
      @else
        <button class="btn btn-dark btn-sm disabled-btn" type="button" data-bs-toggle="tooltip" title="Finalkan penawaran (upload PO/WO/SPK) untuk mengakses Bobot">
          <i class="fas fa-balance-scale me-1"></i> Bobot
        </button>
      @endif

      {{-- RAB Schedule (hanya aktif jika FINAL) --}}
      @if($isFinal)
        <a class="btn btn-outline-primary btn-sm" href="{{ route('rabSchedule.index', $proyek->id) }}">
          <i class="fas fa-calendar-alt me-1"></i> RAB Schedule
        </a>
      @else
        <a class="btn btn-outline-primary btn-sm disabled-btn" href="javascript:void(0)" data-bs-toggle="tooltip" title="Finalkan penawaran (upload PO/WO/SPK) untuk mengakses Schedule">
          <i class="fas fa-calendar-alt me-1"></i> RAB Schedule
        </a>
      @endif
    </div>

    @php
      $savedDocs = collect($penawaran->approval_doc_paths ?? [])
          ->when(empty($penawaran->approval_doc_paths ?? null) && !empty($penawaran->approval_doc_path ?? null),
              fn($c)=>$c->push($penawaran->approval_doc_path))
          ->filter(fn($p)=>trim((string)$p) !== '');
    @endphp

    @if($savedDocs->isNotEmpty())
      <div class="card mt-3 mb-4">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
          <strong><i class="fas fa-paperclip me-2"></i>Dokumen PO/WO/SPK</strong>
          <span class="badge bg-primary">{{ $savedDocs->count() }} file</span>
        </div>
        <div class="card-body pt-2">
          <ul class="list-unstyled mb-0">
            @foreach($savedDocs as $i => $p)
              @php
                $name = pathinfo($p, PATHINFO_BASENAME) ?: 'Dokumen #'.($i+1);
                $encoded = base64_encode($p);
              @endphp
              <li class="mb-2 d-flex align-items-center">
                <i class="fas fa-file-pdf me-2 text-danger"></i>
                <span class="me-2">{{ $name }}</span>

                {{-- Lihat (inline) --}}
                <a class="btn btn-sm btn-outline-secondary me-2"
                  target="_blank"
                  href="{{ route('proyek.penawaran.approval.view', [$proyek->id, $penawaran->id, 'encoded' => $encoded]) }}">
                  <i class="fas fa-eye me-1"></i> Lihat
                </a>

                {{-- Unduh --}}
                <a class="btn btn-sm btn-outline-primary"
                  href="{{ route('proyek.penawaran.approval.download', [$proyek->id, $penawaran->id, 'encoded' => $encoded]) }}">
                  <i class="fas fa-download me-1"></i> Unduh
                </a>
              </li>
            @endforeach
          </ul>
        </div>
      </div>
    @endif

  </div>
</div>

{{-- Modal Keterangan --}}
<div class="modal fade" id="keteranganModal" tabindex="-1" aria-labelledby="keteranganModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <form action="{{ route('proyek.penawaran.updateKeterangan', [$proyek->id, $penawaran->id]) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="modal-header">
          <h5 class="modal-title" id="keteranganModalLabel">
            <i class="fas fa-clipboard-list me-2"></i> Kelola Keterangan / Term of Payment
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
        </div>

        <div class="modal-body">
          <div class="mb-2">
            <label class="form-label">Isi keterangan (satu poin per baris)</label>
            <textarea id="keteranganTextarea" name="keterangan" rows="8"
              class="form-control @error('keterangan') is-invalid @enderror"
              placeholder="Contoh:
Termin 1: DP 30% setelah SPK
Termin 2: 40% saat progres 50%
Termin 3: 30% saat serah terima">{{ old('keterangan', $penawaran->keterangan ?? '') }}</textarea>
            @error('keterangan')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="input-group mb-3">
            <input type="text" id="quickLineModal" class="form-control" placeholder="Tambah baris cepat (mis. 'Termin 1: DP 30%')">
            <button type="button" id="addLineBtnModal" class="btn btn-outline-secondary">
              <i class="fas fa-plus me-1"></i> Tambah Baris
            </button>
          </div>

          <div class="border rounded p-2">
            <div class="small text-muted mb-2"><i class="fas fa-eye me-1"></i>Pratinjau</div>
            <ul class="mb-0" id="keteranganPreviewLiveModal"></ul>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-save me-1"></i> Simpan Keterangan
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Modal Setujui (Multi PDF) --}}
<div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form action="{{ route('proyek.penawaran.approve', [$proyek->id, $penawaran->id]) }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title" id="approveModalLabel">
            <i class="fas fa-file-upload me-2"></i> Finalisasi Penawaran
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
        </div>

        <div class="modal-body">
          <p class="mb-3">
            Unggah <strong>minimal 1 dokumen</strong> <strong>PO/WO/SPK</strong> berformat <strong>PDF</strong> dan lengkapi data proyek.
          </p>

          <div class="mb-3">
            <label class="form-label">Dokumen PO/WO/SPK (PDF) <span class="text-danger">*</span></label>
            <input type="file"
                   name="approval_files[]"
                   class="form-control @error('approval_files') is-invalid @enderror @error('approval_files.*') is-invalid @enderror"
                   accept="application/pdf"
                   multiple
                   required>
            @error('approval_files')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            @error('approval_files.*')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            <div class="form-text">Pilih satu atau beberapa file (PDF, maks 5 MB per file).</div>
          </div>

          <hr>

          <h6 class="mb-3"><i class="fas fa-calendar-alt me-2"></i>Data Proyek</h6>
          
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Nomor SPK <span class="text-danger">*</span></label>
              <input type="text" name="no_spk" class="form-control @error('no_spk') is-invalid @enderror"
                     value="{{ old('no_spk', $proyek->no_spk) }}" placeholder="Nomor Surat Perintah Kerja" required>
              @error('no_spk')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-6 mb-3">
              <label class="form-label">&nbsp;</label>
              <div class="alert alert-info mb-0">
                <small><i class="fas fa-info-circle me-1"></i>Masukkan nomor SPK/WO/PO dari klien</small>
              </div>
            </div>
          </div>
          
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Tanggal Mulai Proyek <span class="text-danger">*</span></label>
              <input type="date" name="tanggal_mulai" class="form-control @error('tanggal_mulai') is-invalid @enderror" 
                     value="{{ old('tanggal_mulai', $proyek->tanggal_mulai) }}" required>
              @error('tanggal_mulai')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-6 mb-3">
              <label class="form-label">Tanggal Selesai Proyek <span class="text-danger">*</span></label>
              <input type="date" name="tanggal_selesai" class="form-control @error('tanggal_selesai') is-invalid @enderror"
                     value="{{ old('tanggal_selesai', $proyek->tanggal_selesai) }}" required>
              @error('tanggal_selesai')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>

          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label">Persentase DP (%) <span class="text-danger">*</span></label>
              <input type="number" step="0.01" name="persen_dp" class="form-control @error('persen_dp') is-invalid @enderror"
                     value="{{ old('persen_dp', $proyek->persen_dp ?? 0) }}" min="0" max="100" required>
              @error('persen_dp')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-4 mb-3">
              <label class="form-label">Persentase Retensi (%) <span class="text-danger">*</span></label>
              <input type="number" step="0.01" name="persen_retensi" class="form-control @error('persen_retensi') is-invalid @enderror"
                     value="{{ old('persen_retensi', $proyek->persen_retensi ?? 0) }}" min="0" max="100" required>
              @error('persen_retensi')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-4 mb-3">
              <label class="form-label">Durasi Retensi (Hari) <span class="text-danger">*</span></label>
              <input type="number" name="durasi_retensi" class="form-control @error('durasi_retensi') is-invalid @enderror"
                     value="{{ old('durasi_retensi', $proyek->durasi_retensi ?? 0) }}" min="0" required>
              @error('durasi_retensi')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>

          @php
            $docs = collect($penawaran->approval_doc_paths ?? [])
              ->when(empty($penawaran->approval_doc_paths ?? null) && !empty($penawaran->approval_doc_path ?? null),
                fn($c)=>$c->push($penawaran->approval_doc_path));
          @endphp

          @if($docs->isNotEmpty())
            <hr>
            <div class="border rounded p-2">
              <div class="small text-muted mb-1"><i class="fas fa-paperclip me-1"></i>Dokumen yang tersimpan:</div>
              <ul class="mb-0">
                @foreach($docs as $p)
                  <li><a target="_blank" href="{{ route('proyek.penawaran.approval.download', [$proyek->id, $penawaran->id, base64_encode($p)]) }}">Lihat PDF</a></li>
                @endforeach
              </ul>
            </div>
          @endif
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-success">
            <i class="fas fa-check me-1"></i> Unggah & Finalkan
          </button>
        </div>
      </form>
    </div>
  </div>
</div>


@endsection

@push('custom-scripts')

<script>
(function(){
  const ALL_ITEMS = @json($flatItems);   // pastikan tiap item hanya punya: kode, uraian, spesifikasi, area, volume, satuan, tot_mat, tot_jasa
  const DISC_COEF = {{ json_encode($discCoef) }};

  const qEl   = document.getElementById('excelLikeQuery');
  const modeEl= document.getElementById('excelLikeMode');
  const discEl= document.getElementById('excelLikeUseDiscount');
  const tbody = document.querySelector('#tbl-excel-like tbody');
  const totM  = document.getElementById('excelLikeTotMat');
  const totJ  = document.getElementById('excelLikeTotJasa');
  const totA  = document.getElementById('excelLikeTotAll');

  const fmtRp = n => 'Rp ' + (Number(n||0)).toLocaleString('id-ID', {maximumFractionDigits:0});
  const esc   = s => (s||'').replace(/[&<>"]/g, m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;' }[m]));
  const mark  = (text, tokens) => {
    if (!tokens.length) return esc(text||'');
    let out = esc(text||'');
    tokens.forEach(t=>{
      if(!t) return;
      const re = new RegExp('('+t.replace(/[.*+?^${}()|[\]\\]/g,'\\$&')+')','ig');
      out = out.replace(re, '<mark>$1</mark>');
    });
    return out;
  };

  function filterAndRender(){
    if(!tbody) return;

    const raw = (qEl?.value || '').trim();
    const tokens = raw.split(/\s+/).filter(Boolean);
    const mode   = modeEl?.value || 'all';
    const useDisc= !!discEl?.checked;

    if (!tokens.length) {
      tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-3">Ketik kata kunci untuk menampilkan hasil…</td></tr>';
      totM.textContent = fmtRp(0); totJ.textContent = fmtRp(0); totA.textContent = fmtRp(0);
      return;
    }

    let sumM=0, sumJ=0, sumA=0, rows=[];

    ALL_ITEMS.forEach(it=>{
      // cari pada: kode, uraian, spesifikasi, area
      const bucket = [
        it.kode, it.uraian, it.spesifikasi, it.area
      ].join(' ').toLowerCase();

      const ok = (mode==='any')
        ? tokens.some(t=>bucket.includes(t.toLowerCase()))
        : tokens.every(t=>bucket.includes(t.toLowerCase()));
      if (!ok) return;

      const coef = useDisc ? DISC_COEF : 1.0;
      const uMat = (it.unit_mat || 0) * coef;
      const uJas = (it.unit_jasa || 0) * coef;
      const tMat = uMat * (it.volume || 0);
      const tJas = uJas * (it.volume || 0);
      const tAll = tMat + tJas;

      sumM += tMat; sumJ += tJas; sumA += tAll;

      rows.push(`
        <tr>
          <td>${mark(it.kode, tokens)}</td>
          <td>
            <div class="fw-semibold">${mark(it.uraian, tokens)}</div>
            ${it.spesifikasi ? `<div class="text-muted small">${mark(it.spesifikasi, tokens)}</div>` : ``}
          </td>
          <td>${mark(it.area, tokens)}</td>
          <td class="text-end">${(it.volume ?? 0).toLocaleString('id-ID', {minimumFractionDigits:2, maximumFractionDigits:2})}</td>
          <td>${esc(it.satuan)}</td>
          <td class="text-end">${fmtRp(uMat)}</td>
          <td class="text-end">${fmtRp(uJas)}</td>
          <td class="text-end">${fmtRp(tMat)}</td>
          <td class="text-end">${fmtRp(tJas)}</td>
          <td class="text-end fw-semibold">${fmtRp(tAll)}</td>
        </tr>
      `);
    });

    tbody.innerHTML = rows.length ? rows.join('') : '<tr><td colspan="10" class="text-center text-muted py-3">Tidak ada item yang cocok.</td></tr>';
    totM.textContent = fmtRp(sumM);
    totJ.textContent = fmtRp(sumJ);
    totA.textContent = fmtRp(sumA);
  }

  qEl?.addEventListener('input',  filterAndRender);
  modeEl?.addEventListener('change', filterAndRender);
  discEl?.addEventListener('change', filterAndRender);
})();
</script>

<script>
  (function(){
    const modalEl   = document.getElementById('keteranganModal');
    const ta        = document.getElementById('keteranganTextarea');
    const quick     = document.getElementById('quickLineModal');
    const addBtn    = document.getElementById('addLineBtnModal');
    const previewUl = document.getElementById('keteranganPreviewLiveModal');

    function renderPreview() {
      if(!previewUl || !ta) return;
      previewUl.innerHTML = '';
      (ta.value || '').split(/\r?\n/).forEach(line=>{
        if(line.trim()){
          const li = document.createElement('li');
          li.textContent = line.trim();
          previewUl.appendChild(li);
        }
      });
    }

    if(addBtn && quick && ta){
      addBtn.addEventListener('click', function(){
        const val = (quick.value || '').trim();
        if(!val) return;
        ta.value = (ta.value.trim() ? (ta.value.replace(/\s+$/,'') + '\n') : '') + val;
        quick.value = '';
        ta.focus();
        renderPreview();
      });
    }

    if (modalEl) {
      modalEl.addEventListener('shown.bs.modal', renderPreview);
      if (ta) ta.addEventListener('input', renderPreview);
    }
  })();

  // existing icons & tooltips
  if (typeof feather !== 'undefined') { feather.replace(); }
  [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    .map(function (el) { return new bootstrap.Tooltip(el); });
</script>
<script>
  if (typeof feather !== 'undefined') { feather.replace(); }
  [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    .map(function (el) { return new bootstrap.Tooltip(el); });
</script>
@endpush

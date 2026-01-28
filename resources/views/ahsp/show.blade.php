@extends('layout.master')

@push('plugin-styles')
{{-- Font Awesome untuk ikon --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
{{-- Animate.css untuk animasi (opsional) --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
<style>
    /* Kustomisasi tambahan untuk tampilan */
    .card {
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        border: none;
    }
    .card-header {
        background-color: #f8f9fa; /* Warna latar belakang header kartu */
        border-bottom: 1px solid #e9ecef;
        border-top-left-radius: 12px;
        border-top-right-radius: 12px;
        padding: 1.25rem 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .table-borderless th, .table-borderless td {
        padding-top: 0.75rem;
        padding-bottom: 0.75rem;
        vertical-align: top;
        border-top: none;
    }
    .table-borderless th {
        color: #495057;
        font-weight: 600;
        width: 180px; /* Lebar tetap untuk label */
    }
    .table-bordered thead th {
        background-color: #e9ecef;
        color: #495057;
        font-weight: 600;
        border-bottom: 2px solid #dee2e6;
    }
    .table-bordered tbody tr:hover {
        background-color: #f2f2f2;
    }
    .badge {
        font-size: 0.85em;
        padding: 0.5em 0.75em;
        border-radius: 1rem; /* Lebih bulat */
    }
    .badge.bg-danger {
        background-color: #dc3545 !important;
    }
    .badge.bg-success {
        background-color: #28a745 !important;
    }


    /* Cetak: sembunyikan elemen yang tidak perlu saat print */
    @media print {
        @page {
            size: A4;
            margin: 12mm;
        }

        html, body {
            width: 210mm;
        }

        .no-print,
        .no-print * {
            display: none !important;
            visibility: hidden !important;
        }


        .card {
            box-shadow: none;
        }
        .table-bordered thead th {
            background-color: #ffffff !important;
        }

        /* Hindari pemotongan yang buruk pada tabel saat pindah halaman */
        table { page-break-inside: auto; }
        tr    { page-break-inside: avoid; page-break-after: auto; }
        thead { display: table-header-group; }
        tfoot { display: table-footer-group; }

        /* Pastikan tabel pas A4: lebar penuh dan tipografi efisien */
        .table {
            width: 100% !important;
            max-width: 100% !important;
        }
        .table th, .table td {
            word-break: break-word;
            white-space: normal;
            font-size: 11pt;
            padding: 6pt 6pt;
        }
        .table thead th {
            font-size: 11pt;
            padding: 6pt 6pt;
        }
    }
</style>
@endpush

@section('content')
<div id="ahsp-{{ $ahsp->id }}" class="card animate__animated animate__fadeInDown">
    <div class="card-header">
        <h4 class="card-title mb-0"><i class="fas fa-info-circle me-2"></i> Detail Analisa Harga Satuan Pekerjaan</h4>
        <div class="d-flex gap-2 no-print">
            <a href="{{ route('ahsp.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill">
                <i class="fas fa-arrow-left me-1"></i> Kembali ke Daftar
            </a>
            <form action="{{ route('ahsp.duplicate', $ahsp->id) }}" method="POST" class="d-inline-block" onsubmit="return confirm('Apakah Anda yakin ingin menduplikasi AHSP ini?');">
                @csrf
                <button type="submit" class="btn btn-sm btn-info rounded-pill" title="Duplikat AHSP">
                    <i class="fas fa-copy me-1"></i> Duplikat
                </button>
            </form>
            <button type="button" class="btn btn-sm btn-outline-dark rounded-pill" onclick="window.print()" title="Cetak">
                <i class="fas fa-print me-1"></i> Cetak
            </button>
            <a href="{{ route('ahsp.edit', $ahsp->id) }}" class="btn btn-sm btn-primary rounded-pill" title="Edit AHSP">
                <i class="fas fa-edit me-1"></i> Edit
            </a>
        </div>
    </div>
    <div class="card-body">
        

        <h5 class="mb-3 text-primary"><i class="fas fa-clipboard-list me-2"></i> Informasi Umum</h5>
        <table class="table table-borderless mb-4">
            <tr>
                <th>ID</th>
                <td>: {{ $ahsp->id }}</td>
            </tr>
            <tr>
                <th>Kode Pekerjaan</th>
                <td>: {{ $ahsp->kode_pekerjaan }}</td>
            </tr>
            <tr>
                <th>Nama Pekerjaan</th>
                <td>: {{ $ahsp->nama_pekerjaan }}</td>
            </tr>
            <tr>
                <th>Satuan</th>
                <td>: {{ $ahsp->satuan }}</td>
            </tr>
            <tr>
                <th>Kategori</th>
                <td>: {{ $ahsp->kategori->nama ?? '-' }}</td>
            </tr>
            <tr>
                <th>Status</th>
                <td>
                    @if($ahsp->is_locked)
                        <span class="badge bg-danger"><i class="fas fa-lock me-1"></i> Terkunci</span>
                    @else
                        <span class="badge bg-success"><i class="fas fa-unlock-alt me-1"></i> Draft</span>
                    @endif
                </td>
            </tr>
        </table>

        <h5 class="mb-3 text-primary"><i class="fas fa-tools me-2"></i> Komponen Material / Upah</h5>
        
        {{-- Informasi tentang perhitungan Diskon & PPN --}}
        @if($ahsp->details->where('diskon_persen', '>', 0)->count() > 0 || $ahsp->details->where('ppn_persen', '>', 0)->count() > 0)
        <div class="alert alert-info alert-dismissible fade show mb-3" role="alert">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Catatan Perhitungan:</strong> AHSP ini memiliki <strong>Diskon dan/atau PPN</strong>. 
            Kolom <strong>Final</strong> menunjukkan nilai setelah dikurangi diskon dan ditambah PPN.
            <br><small>Formula: Subtotal → kurangi Diskon % → tambah PPN % → Final</small>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        <div class="table-responsive">
            <table class="table table-bordered">
                <thead class="table-light">
                    <tr>
                        <th style="width: 12%">Tipe</th>
                        <th style="width: 25%">Item</th>
                        <th style="width: 8%">Satuan</th>
                        <th style="width: 10%" class="text-end">Koefisien</th>
                        <th style="width: 12%" class="text-end">Harga Satuan</th>
                        <th style="width: 10%" class="text-end">Subtotal</th>
                        <th style="width: 8%" class="text-center">Diskon %</th>
                        <th style="width: 8%" class="text-center">PPN %</th>
                        <th style="width: 12%" class="text-end">Final</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($ahsp->details as $d)
                    <tr>
                        <td>{{ ucfirst($d->tipe) }}</td>
                        <td>
                            @php
                                $itemName = '-';
                                $itemSatuan = '-';
                                if ($d->tipe === 'material') {
                                    $item = App\Models\HsdMaterial::find($d->referensi_id);
                                    $itemName = $item->nama ?? '-';
                                    $itemSatuan = $item->satuan ?? '-';
                                } elseif ($d->tipe === 'upah') {
                                    $item = App\Models\HsdUpah::find($d->referensi_id);
                                    $itemName = $item->jenis_pekerja ?? '-';
                                    $itemSatuan = $item->satuan ?? '-';
                                }
                            @endphp
                            {{ $itemName }}
                        </td>
                        <td class="text-center">{{ $itemSatuan }}</td>
                        <td class="text-end">{{ number_format($d->koefisien, 4, ',', '.') }}</td>
                        <td class="text-end">Rp {{ number_format($d->harga_satuan, 0, ',', '.') }}</td>
                        <td class="text-end">Rp {{ number_format($d->subtotal, 0, ',', '.') }}</td>
                        <td class="text-center">{{ number_format($d->diskon_persen ?? 0, 2, ',', '.') }}%</td>
                        <td class="text-center">{{ number_format($d->ppn_persen ?? 0, 2, ',', '.') }}%</td>
                        <td class="text-end"><strong>Rp {{ number_format($d->subtotal_final ?? $d->subtotal, 0, ',', '.') }}</strong></td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    @php
                        $totalMaterial = $ahsp->details->where('tipe', 'material')->sum(function($d) {
                            return $d->subtotal_final ?? $d->subtotal;
                        });
                        $totalUpah = $ahsp->details->where('tipe', 'upah')->sum(function($d) {
                            return $d->subtotal_final ?? $d->subtotal;
                        });
                    @endphp
                    <tr class="table-light">
                        <th colspan="8" class="text-end">Total Material (setelah Diskon & PPN)</th>
                        <th class="text-end">Rp {{ number_format($totalMaterial, 0, ',', '.') }}</th>
                    </tr>
                    <tr class="table-light">
                        <th colspan="8" class="text-end">Total Upah (setelah Diskon & PPN)</th>
                        <th class="text-end">Rp {{ number_format($totalUpah, 0, ',', '.') }}</th>
                    </tr>
                    <tr>
                        <th colspan="8" class="text-end">Total Harga Sebenarnya</th>
                        <th class="text-end fw-bold">Rp {{ number_format($ahsp->total_harga, 0, ',', '.') }}</th>
                    </tr>
                    <tr>
                        <th colspan="8" class="text-end">Total Harga Pembulatan</th>
                        <th class="text-end fw-bold text-primary">Rp {{ number_format($ahsp->total_harga_pembulatan ?? 0, 0, ',', '.') }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection

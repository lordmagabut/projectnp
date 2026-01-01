@extends('layout.master')

@section('title', 'Detail Penerimaan Penjualan')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1>Detail Penerimaan Pembayaran</h1>
        </div>
    </div>

    @if ($message = Session::get('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle"></i> {{ $message }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if ($message = Session::get('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle"></i> {{ $message }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Penerimaan: {{ $penerimaanPenjualan->no_bukti }}</h5>
                    <span class="badge bg-light text-dark">
                        @if ($penerimaanPenjualan->status === 'draft')
                            <i class="fas fa-circle text-warning"></i> Draft
                        @else
                            <i class="fas fa-circle text-success"></i> Disetujui
                        @endif
                    </span>
                </div>

                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <p class="mb-1"><small class="text-muted">No. Bukti</small></p>
                            <p class="h6">{{ $penerimaanPenjualan->no_bukti }}</p>

                            <p class="mb-1 mt-3"><small class="text-muted">Tanggal</small></p>
                            <p class="h6">{{ $penerimaanPenjualan->tanggal->format('d F Y') }}</p>

                            <p class="mb-1 mt-3"><small class="text-muted">Metode Pembayaran</small></p>
                            <p class="h6">{{ $penerimaanPenjualan->metode_pembayaran }}</p>
                        </div>

                        <div class="col-md-6">
                            <p class="mb-1"><small class="text-muted">Nominal</small></p>
                            <p class="h6">Rp {{ number_format($penerimaanPenjualan->nominal, 2, ',', '.') }}</p>

                            <p class="mb-1 mt-3"><small class="text-muted">PPh Dipotong</small></p>
                            <p class="h6">Rp {{ number_format($penerimaanPenjualan->pph_dipotong ?? 0, 2, ',', '.') }}
                                @if($penerimaanPenjualan->keterangan_pph)
                                    <small class="text-muted">({{ $penerimaanPenjualan->keterangan_pph }})</small>
                                @endif
                            </p>

                            <p class="mb-1 mt-3"><small class="text-muted">Status</small></p>
                            <p class="h6">
                                @if ($penerimaanPenjualan->status === 'draft')
                                    <span class="badge bg-warning">Draft</span>
                                @else
                                    <span class="badge bg-success">Disetujui</span>
                                @endif
                            </p>

                            <p class="mb-1 mt-3"><small class="text-muted">Keterangan</small></p>
                            <p class="h6">{{ $penerimaanPenjualan->keterangan ?? '-' }}</p>
                        </div>
                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-1"><small class="text-muted">Dibuat Oleh</small></p>
                            <p class="h6">{{ $penerimaanPenjualan->pembuatnya->name ?? '-' }}</p>

                            <p class="mb-1 mt-3"><small class="text-muted">Tanggal Dibuat</small></p>
                            <p class="h6">{{ $penerimaanPenjualan->created_at->format('d F Y H:i') }}</p>
                        </div>

                        <div class="col-md-6">
                            @if ($penerimaanPenjualan->status === 'approved')
                                <p class="mb-1"><small class="text-muted">Disetujui Oleh</small></p>
                                <p class="h6">{{ $penerimaanPenjualan->penyetujunya->name ?? '-' }}</p>

                                <p class="mb-1 mt-3"><small class="text-muted">Tanggal Disetujui</small></p>
                                <p class="h6">{{ $penerimaanPenjualan->tanggal_disetujui->format('d F Y H:i') }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Faktur Penjualan Info -->
            <div class="card mt-3">
                <div class="card-header bg-info text-white">
                    <h5 class="card-title mb-0">Data Faktur Penjualan</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-1"><small class="text-muted">No. Faktur</small></p>
                            <p class="h6">
                                <a href="{{ route('faktur-penjualan.show', $penerimaanPenjualan->fakturPenjualan->id) }}"
                                   target="_blank">
                                    {{ $penerimaanPenjualan->fakturPenjualan->no_faktur }}
                                </a>
                            </p>

                            <p class="mb-1 mt-3"><small class="text-muted">Total Faktur</small></p>
                            <p class="h6">Rp {{ number_format($penerimaanPenjualan->fakturPenjualan->total, 2, ',', '.') }}</p>
                        </div>

                        <div class="col-md-6">
                            <p class="mb-1"><small class="text-muted">Tanggal Faktur</small></p>
                            <p class="h6">{{ $penerimaanPenjualan->fakturPenjualan->tanggal->format('d F Y') }}</p>

                            <p class="mb-1 mt-3"><small class="text-muted">Status Pembayaran</small></p>
                            <p class="h6">
                                @php
                                    $statusMap = [
                                        'belum_dibayar' => ['badge bg-danger', 'Belum Dibayar'],
                                        'sebagian' => ['badge bg-warning', 'Sebagian Dibayar'],
                                        'lunas' => ['badge bg-success', 'Lunas'],
                                    ];
                                    $status = $penerimaanPenjualan->fakturPenjualan->status_pembayaran ?? 'belum_dibayar';
                                    [$badgeClass, $badgeText] = $statusMap[$status] ?? ['badge bg-secondary', 'Tidak Diketahui'];
                                @endphp
                                <span class="{{ $badgeClass }}">{{ $badgeText }}</span>
                            </p>
                        </div>
                    </div>

                    <hr>

                    <p class="mb-2"><strong>Riwayat Penerimaan Pembayaran Lainnya:</strong></p>
                    <table class="table table-sm table-striped">
                        <thead class="table-light">
                            <tr>
                                <th>No. Bukti</th>
                                <th class="text-right">Nominal</th>
                                <th>Status</th>
                                <th>Tanggal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($penerimaanPenjualan->fakturPenjualan->penerimaanPenjualan as $penerimaan)
                                <tr class="{{ $penerimaan->id === $penerimaanPenjualan->id ? 'table-primary' : '' }}">
                                    <td>
                                        @if ($penerimaan->id === $penerimaanPenjualan->id)
                                            <strong>{{ $penerimaan->no_bukti }}</strong>
                                        @else
                                            <a href="{{ route('penerimaan-penjualan.show', $penerimaan->id) }}">
                                                {{ $penerimaan->no_bukti }}
                                            </a>
                                        @endif
                                    </td>
                                    <td class="text-right">Rp {{ number_format($penerimaan->nominal, 2, ',', '.') }}</td>
                                    <td>
                                        @if ($penerimaan->status === 'draft')
                                            <span class="badge bg-warning">Draft</span>
                                        @else
                                            <span class="badge bg-success">Disetujui</span>
                                        @endif
                                    </td>
                                    <td>{{ $penerimaan->tanggal->format('d/m/Y') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">Tidak ada penerimaan lainnya</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Action Buttons -->
            <div class="card mb-3">
                <div class="card-header bg-secondary text-white">
                    <h5 class="card-title mb-0">Aksi</h5>
                </div>
                <div class="card-body">
                    @if ($penerimaanPenjualan->status === 'draft')
                        <a href="{{ route('penerimaan-penjualan.edit', $penerimaanPenjualan->id) }}" class="btn btn-warning w-100 mb-2">
                            <i class="fas fa-edit"></i> Edit
                        </a>

                        <form action="{{ route('penerimaan-penjualan.approve', $penerimaanPenjualan->id) }}" 
                              method="POST" class="mb-2">
                            @csrf
                            <button type="submit" class="btn btn-success w-100">
                                <i class="fas fa-check"></i> Setujui
                            </button>
                        </form>

                        <form action="{{ route('penerimaan-penjualan.destroy', $penerimaanPenjualan->id) }}" 
                              method="POST"
                              onsubmit="return confirm('Apakah Anda yakin ingin menghapus data ini?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger w-100">
                                <i class="fas fa-trash"></i> Hapus
                            </button>
                        </form>
                    @else
                        <form action="{{ route('penerimaan-penjualan.revisi', $penerimaanPenjualan->id) }}" 
                              method="POST" class="mb-2"
                              onsubmit="return confirm('Apakah ingin mengembalikan ke draft untuk edit?');">
                            @csrf
                            <button type="submit" class="btn btn-warning w-100">
                                <i class="fas fa-redo"></i> Revisi ke Draft
                            </button>
                        </form>
                    @endif

                    <a href="{{ route('penerimaan-penjualan.index') }}" class="btn btn-secondary w-100 mt-2">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </div>
            </div>

            <!-- Summary -->
            <div class="card bg-light">
                <div class="card-header">
                    <h5 class="card-title mb-0">Ringkasan</h5>
                </div>
                <div class="card-body">
                    <p class="mb-2">
                        <small class="text-muted">Total Faktur:</small><br>
                        <strong>Rp {{ number_format($penerimaanPenjualan->fakturPenjualan->total, 2, ',', '.') }}</strong>
                    </p>

                    @php
                        $totalDiterima = $penerimaanPenjualan->fakturPenjualan->penerimaanPenjualan
                            ->whereIn('status', ['draft', 'approved'])
                            ->sum('nominal');
                        $sisaPembayaran = $penerimaanPenjualan->fakturPenjualan->total - $totalDiterima;
                    @endphp

                    <p class="mb-2">
                        <small class="text-muted">Total Diterima:</small><br>
                        <strong class="text-success">Rp {{ number_format($totalDiterima, 2, ',', '.') }}</strong>
                    </p>

                    <p class="mb-0">
                        <small class="text-muted">Sisa Pembayaran:</small><br>
                        <strong class="text-danger">Rp {{ number_format($sisaPembayaran, 2, ',', '.') }}</strong>
                    </p>

                    @if ($sisaPembayaran > 0)
                        <div class="mt-3">
                            <a href="{{ route('penerimaan-penjualan.create') }}" class="btn btn-sm btn-primary w-100">
                                <i class="fas fa-plus"></i> Terima Lagi
                            </a>
                        </div>
                    @else
                        <div class="mt-3">
                            <span class="badge bg-success w-100 py-2">Pembayaran Lunas</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@extends('layout.master')

@section('title', 'Penerimaan Penjualan')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1>Penerimaan Penjualan</h1>
        </div>
        <div class="col-md-6 text-right">
            <a href="{{ route('penerimaan-penjualan.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Buat Penerimaan Baru
            </a>
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

    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="card-title mb-0">Daftar Penerimaan Pembayaran</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 100px">No. Bukti</th>
                            <th style="width: 80px">Tanggal</th>
                            <th>Faktur Dibayar</th>
                            <th style="width: 120px" class="text-right">Nominal</th>
                            <th style="width: 100px">Metode</th>
                            <th style="width: 80px">Status</th>
                            <th style="width: 80px" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($penerimaanPenjualan as $item)
                        <tr>
                            <td>
                                <strong>{{ $item->no_bukti }}</strong>
                            </td>
                            <td>{{ $item->tanggal->format('d/m/Y') }}</td>
                            <td>
                                @php
                                    $fakturLabels = $item->details->map(function($d){
                                        return $d->faktur?->no_faktur;
                                    })->filter();
                                    if ($fakturLabels->isEmpty() && $item->fakturPenjualan) {
                                        $fakturLabels = collect([$item->fakturPenjualan->no_faktur]);
                                    }
                                @endphp
                                @if ($fakturLabels->count() === 1)
                                    <a href="{{ route('faktur-penjualan.show', $item->details->first()?->faktur?->id ?? $item->fakturPenjualan?->id) }}" target="_blank">
                                        {{ $fakturLabels->first() }}
                                    </a>
                                @else
                                    <span class="badge bg-info">{{ $fakturLabels->count() }} faktur</span>
                                    <div class="small text-muted">{{ $fakturLabels->join(', ') }}</div>
                                @endif
                            </td>
                            <td class="text-right">
                                Rp {{ number_format($item->nominal, 2, ',', '.') }}
                            </td>
                            <td>{{ $item->metode_pembayaran }}</td>
                            <td>
                                @if ($item->status === 'draft')
                                    <span class="badge bg-warning">Draft</span>
                                @elseif ($item->status === 'approved')
                                    <span class="badge bg-success">Disetujui</span>
                                @else
                                    <span class="badge bg-secondary">{{ $item->status }}</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('penerimaan-penjualan.show', $item->id) }}" 
                                   class="btn btn-sm btn-info"
                                   title="Lihat Detail">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @if ($item->status === 'draft')
                                    <a href="{{ route('penerimaan-penjualan.edit', $item->id) }}" 
                                       class="btn btn-sm btn-warning"
                                       title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-2x mb-2"></i>
                                <p>Belum ada data penerimaan pembayaran</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($penerimaanPenjualan->hasPages())
            <nav>
                {{ $penerimaanPenjualan->links('pagination::bootstrap-4') }}
            </nav>
            @endif
        </div>
    </div>
</div>
@endsection

@extends('layout.master')

@section('content')
<nav class="page-breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="#">Penjualan</a></li>
        <li class="breadcrumb-item active" aria-current="page">Faktur Penjualan</li>
    </ol>
</nav>

<div class="card shadow-sm border-0">
    <div class="card-header bg-transparent py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold">Daftar Faktur Penjualan</h5>
    </div>
    
    <div class="card-body p-0">
        <div class="table-responsive" style="overflow: visible;"> {{-- Fix Overflow agar dropdown muncul --}}
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">No. Faktur</th>
                        <th>Tanggal</th>
                        <th>Proyek</th>
                        <th class="text-end">Total</th>
                        <th class="text-center">Status Bayar</th>
                        <th class="text-center" width="150">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($fakturs as $faktur)
                        <tr>
                            <td class="ps-3 fw-bold text-primary">{{ $faktur->no_faktur }}</td>
                            <td>{{ optional($faktur->tanggal)->format('d/m/Y') }}</td>
                            <td>{{ optional($faktur->proyek)->nama_proyek ?? '-' }}</td>
                            <td class="text-end fw-bold">Rp {{ number_format($faktur->total, 0, ',', '.') }}</td>
                            <td class="text-center">
                                @php
                                    $status = strtolower($faktur->status_pembayaran ?? 'belum');
                                    $badgeClass = $status == 'lunas' ? 'bg-success' : ($status == 'sebagian' ? 'bg-info' : 'bg-warning');
                                @endphp
                                <span class="badge {{ $badgeClass }} text-uppercase">{{ $status }}</span>
                            </td>
                            <td class="text-center">
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown" data-toggle="dropdown" aria-expanded="false">
                                        Menu Aksi
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('faktur-penjualan.show', $faktur->id) }}">
                                                Detail Faktur
                                            </a>
                                        </li>
                                        
                                        @if($faktur->status === 'draft')
                                            <li>
                                                <a class="dropdown-item" href="{{ route('faktur-penjualan.edit', $faktur->id) }}">
                                                    Edit Faktur
                                                </a>
                                            </li>
                                        @endif

                                        @if($faktur->status_pembayaran !== 'lunas')
                                            <li>
                                                <a class="dropdown-item text-success" href="{{ route('penerimaan-penjualan.create', ['faktur_penjualan_id' => $faktur->id]) }}">
                                                    Terima Bayar
                                                </a>
                                            </li>
                                        @endif

                                        @if($faktur->status === 'approved')
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <form action="{{ route('faktur-penjualan.revisi', $faktur->id) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="dropdown-item" onclick="return confirm('Revisi faktur ini?')">
                                                        Revisi ke Draft
                                                    </button>
                                                </form>
                                            </li>
                                        @endif

                                        @if($faktur->status === 'draft')
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <form action="{{ route('faktur-penjualan.destroy', $faktur->id) }}" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="dropdown-item text-danger" onclick="return confirm('Hapus permanen?')">
                                                        Hapus
                                                    </button>
                                                </form>
                                            </li>
                                        @endif
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">Data faktur tidak ditemukan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="p-3">
            {{ $fakturs->links() }}
        </div>
    </div>
</div>

<style>
    /* CSS CRITICAL: Memastikan menu dropdown tidak terpotong tabel */
    .table-responsive {
        overflow: visible !important;
        min-height: 250px; /* Memberi ruang untuk dropdown jika data sedikit */
    }

    /* Memperbaiki tampilan dropdown menu agar muncul di atas elemen lain */
    .dropdown-menu {
        z-index: 1060 !important;
    }

    .table td {
        white-space: nowrap; /* Mencegah baris terlalu tinggi */
    }
</style>
@endsection
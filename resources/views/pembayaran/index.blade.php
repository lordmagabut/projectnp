@extends('layout.master')

@push('plugin-styles')
<link href="{{ asset('assets/plugins/datatables-net-bs5/dataTables.bootstrap5.css') }}" rel="stylesheet" />
<link href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap5.min.css" rel="stylesheet" />
@endpush

@section('content')
<nav class="page-breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('faktur.index') }}">Faktur</a></li>
        <li class="breadcrumb-item active" aria-current="page">Riwayat Pembayaran</li>
    </ol>
</nav>

<div class="row">
    <div class="col-md-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="card-title">Riwayat Pembayaran Pembelian</h4>
                    <a href="{{ route('faktur.index') }}" class="btn btn-outline-primary btn-sm">
                        <i data-feather="plus" class="icon-sm"></i> Bayar Faktur Baru
                    </a>
                </div>

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <div class="table-responsive">
                    <table id="dataTablePembayaran" class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>No. Bukti (BKK)</th>
                                <th>Faktur Reff</th>
                                <th>Akun Pembayaran</th>
                                <th>Nominal</th>
                                <th>Keterangan</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pembayarans as $bayar)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($bayar->tanggal)->format('d/m/Y') }}</td>
                                <td class="fw-bold text-primary">{{ $bayar->no_pembayaran }}</td>
                                <td>
                                    <span class="text-muted small">No:</span> {{ $bayar->faktur->no_faktur }} <br>
                                    <span class="badge bg-light text-dark border">{{ $bayar->faktur->nama_supplier }}</span>
                                </td>
                                <td>
                                    <div class="fw-bold">{{ $bayar->coa->nama_akun }}</div>
                                    <div class="text-muted small">{{ $bayar->coa->no_akun }}</div>
                                </td>
                                <td class="fw-bold text-success">
                                    Rp {{ number_format($bayar->nominal, 0, ',', '.') }}
                                </td>
                                <td>{{ $bayar->keterangan }}</td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        <a href="{{ route('faktur.show', $bayar->faktur_id) }}" class="btn btn-xs btn-outline-info" title="Detail Faktur">
                                            <i data-feather="eye" class="icon-sm"></i>
                                        </a>
                                        {{-- Tombol Edit --}}
                                        {{-- Tombol Hapus --}}
                                        <form action="{{ route('pembayaran.destroy', $bayar->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-xs btn-outline-danger" title="Hapus Pembayaran" 
                                                onclick="return confirm('Menghapus pembayaran akan mengembalikan saldo hutang faktur dan menghapus jurnal terkait. Lanjutkan?')">
                                                <i data-feather="trash" class="icon-sm"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('plugin-scripts')
<script src="{{ asset('assets/plugins/datatables-net/jquery.dataTables.js') }}"></script>
<script src="{{ asset('assets/plugins/datatables-net-bs5/dataTables.bootstrap5.js') }}"></script>
<script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap5.min.js"></script>
@endpush

@push('custom-scripts')
<script>
    $(document).ready(function () {
        if ($('#dataTablePembayaran').length) {
            $('#dataTablePembayaran').DataTable({
                responsive: true,
                order: [[0, 'desc']], // Terbaru di atas
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Cari riwayat...",
                }
            });
        }
    });
</script>
@endpush
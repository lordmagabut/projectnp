@extends('layout.master')

@section('content')
<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="card-title mb-0">Detail Retur Pembelian</h4>
                    <a href="{{ route('retur.index') }}" class="btn btn-secondary">Kembali</a>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <td width="200"><strong>No. Retur</strong></td>
                                <td>: {{ $retur->no_retur }}</td>
                            </tr>
                            <tr>
                                <td><strong>Tanggal</strong></td>
                                <td>: {{ \Carbon\Carbon::parse($retur->tanggal)->format('d/m/Y') }}</td>
                            </tr>
                            <tr>
                                <td><strong>No. Penerimaan</strong></td>
                                <td>: {{ $retur->penerimaan->no_penerimaan }}</td>
                            </tr>
                            <tr>
                                <td><strong>No. PO</strong></td>
                                <td>: {{ $retur->penerimaan->po->no_po }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <td width="200"><strong>Supplier</strong></td>
                                <td>: {{ $retur->nama_supplier }}</td>
                            </tr>
                            <tr>
                                <td><strong>Proyek</strong></td>
                                <td>: {{ $retur->proyek->nama_proyek ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td><strong>Status</strong></td>
                                <td>: 
                                    @if($retur->status == 'draft')
                                        <span class="badge bg-secondary">Draft</span>
                                    @else
                                        <span class="badge bg-success">Approved</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Alasan</strong></td>
                                <td>: {{ $retur->alasan ?? '-' }}</td>
                            </tr>
                        </table>
                    </div>
                </div>

                <h5 class="mt-4 mb-3">Detail Item yang Diretur</h5>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>Kode Item</th>
                                <th>Uraian</th>
                                <th>Qty Retur</th>
                                <th>Satuan</th>
                                <th>Harga</th>
                                <th>Total</th>
                                <th>Alasan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $totalRetur = 0; @endphp
                            @foreach($retur->details as $index => $detail)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $detail->kode_item }}</td>
                                <td>{{ $detail->uraian }}</td>
                                <td class="text-end">{{ number_format($detail->qty_retur, 2) }}</td>
                                <td>{{ $detail->uom }}</td>
                                <td class="text-end">Rp {{ number_format($detail->harga, 0, ',', '.') }}</td>
                                <td class="text-end">Rp {{ number_format($detail->total, 0, ',', '.') }}</td>
                                <td>{{ $detail->alasan ?? '-' }}</td>
                            </tr>
                            @php $totalRetur += $detail->total; @endphp
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="6" class="text-end">Total Retur:</th>
                                <th class="text-end">Rp {{ number_format($totalRetur, 0, ',', '.') }}</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="mt-4">
                    @if($retur->status == 'draft')
                        <form action="{{ route('retur.approve', $retur->id) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-success" 
                                onclick="return confirm('Approve retur ini? Jurnal akan dibuat secara otomatis.')">
                                <i data-feather="check-circle"></i> Approve Retur
                            </button>
                        </form>
                    @else
                        <form action="{{ route('retur.revisi', $retur->id) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-warning" 
                                onclick="return confirm('Revisi retur ini? Jurnal & kredit akan dibalik.')">
                                <i data-feather="edit-3"></i> Revisi Retur
                            </button>
                        </form>
                        <div class="alert alert-info d-inline ms-2">
                            <i data-feather="info"></i> Retur sudah disetujui. 
                            @if($retur->jurnal_id)
                                Jurnal ID: <strong>{{ $retur->jurnal_id }}</strong>
                            @endif
                        </div>
                    @endif

                    <form action="{{ route('retur.destroy', $retur->id) }}" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger" 
                            onclick="return confirm('Yakin hapus retur ini? Jurnal & kredit akan dibalik.')">
                            <i data-feather="trash-2"></i> Hapus
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('custom-scripts')
<script>
    feather.replace();
</script>
@endpush

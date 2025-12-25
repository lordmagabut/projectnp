@extends('layout.master')

@section('content')
<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="card-title mb-0">Detail Penerimaan Pembelian</h4>
                    <a href="{{ route('penerimaan.index') }}" class="btn btn-secondary">Kembali</a>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <td width="200"><strong>No. Penerimaan</strong></td>
                                <td>: {{ $penerimaan->no_penerimaan }}</td>
                            </tr>
                            <tr>
                                <td><strong>Tanggal</strong></td>
                                <td>: {{ \Carbon\Carbon::parse($penerimaan->tanggal)->format('d/m/Y') }}</td>
                            </tr>
                            <tr>
                                <td><strong>No. PO</strong></td>
                                <td>: {{ $penerimaan->po->no_po }}</td>
                            </tr>
                            <tr>
                                <td><strong>No. Surat Jalan</strong></td>
                                <td>: {{ $penerimaan->no_surat_jalan ?? '-' }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <td width="200"><strong>Supplier</strong></td>
                                <td>: {{ $penerimaan->nama_supplier }}</td>
                            </tr>
                            <tr>
                                <td><strong>Proyek</strong></td>
                                <td>: {{ $penerimaan->proyek->nama_proyek ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td><strong>Status</strong></td>
                                <td>: 
                                    @if($penerimaan->status == 'draft')
                                        <span class="badge bg-secondary">Draft</span>
                                    @else
                                        <span class="badge bg-success">Approved</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Status Penagihan</strong></td>
                                <td>:
                                    @switch($penerimaan->status_penagihan)
                                        @case('lunas')
                                            <span class="badge bg-success">Lunas</span>
                                            @break
                                        @case('sebagian')
                                            <span class="badge bg-warning text-dark">Sebagian</span>
                                            @break
                                        @default
                                            <span class="badge bg-secondary">Belum</span>
                                    @endswitch
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Keterangan</strong></td>
                                <td>: {{ $penerimaan->keterangan ?? '-' }}</td>
                            </tr>
                        </table>
                    </div>
                </div>

                <h5 class="mt-4 mb-3">Detail Item & Status Penagihan</h5>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>Kode Item</th>
                                <th>Uraian</th>
                                <th class="text-end">Qty PO</th>
                                <th class="text-end">Qty Diterima</th>
                                <th class="text-end">Sudah Difakturkan</th>
                                <th class="text-end">Sisa Belum Difaktur</th>
                                <th class="text-end">Progress</th>
                                <th>Satuan</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $totalDiterima = 0; $totalTerfaktur = 0; @endphp
                            @foreach($penerimaan->details as $index => $detail)
                            @php
                                $qtyTerfaktur = $detail->qty_terfaktur ?? 0;
                                $qtyReturApproved = \App\Models\ReturPembelianDetail::where('penerimaan_detail_id', $detail->id)
                                    ->whereHas('retur', function($q){ $q->where('status','approved'); })
                                    ->sum('qty_retur');
                                $netDiterima = max(0, ($detail->qty_diterima - $qtyReturApproved));
                                $sisaBelumDifaktur = max(0, ($netDiterima - $qtyTerfaktur));
                                $progress = ($netDiterima > 0)
                                    ? min(100, round(($qtyTerfaktur / $netDiterima) * 100))
                                    : 0;
                                $totalDiterima += $netDiterima;
                                $totalTerfaktur += $qtyTerfaktur;
                            @endphp
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $detail->kode_item }}</td>
                                <td>{{ $detail->uraian }}</td>
                                <td class="text-end">{{ number_format($detail->qty_po, 2) }}</td>
                                <td class="text-end">
                                    {{ number_format($detail->qty_diterima, 2) }}
                                    @if($qtyReturApproved > 0)
                                        <div><small class="text-danger">Retur: -{{ number_format($qtyReturApproved, 2) }}</small></div>
                                        <div><small class="text-muted">Net: {{ number_format($netDiterima, 2) }}</small></div>
                                    @endif
                                </td>
                                <td class="text-end text-primary">{{ number_format($qtyTerfaktur, 2) }}</td>
                                <td class="text-end text-danger">{{ number_format($sisaBelumDifaktur, 2) }}</td>
                                <td>
                                    <div class="progress" style="height:8px;">
                                        <div class="progress-bar" role="progressbar" style="width: {{ $progress }}%;" aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <small class="text-muted">{{ $progress }}%</small>
                                </td>
                                <td>{{ $detail->uom }}</td>
                                <td>{{ $detail->keterangan ?? '-' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="4" class="text-end">Total Diterima (Net)</th>
                                <th class="text-end">{{ number_format($totalDiterima, 2) }}</th>
                                <th colspan="3"></th>
                            </tr>
                            <tr>
                                <th colspan="4" class="text-end">Total Sudah Difakturkan</th>
                                <th class="text-end">{{ number_format($totalTerfaktur, 2) }}</th>
                                <th colspan="3"></th>
                            </tr>
                            <tr>
                                <th colspan="4" class="text-end">Sisa Belum Difaktur</th>
                                <th class="text-end">{{ number_format(max(0, $totalDiterima - $totalTerfaktur), 2) }}</th>
                                <th colspan="3"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="mt-4">
                    @if($penerimaan->status == 'draft')
                    <form action="{{ route('penerimaan.approve', $penerimaan->id) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-success" onclick="return confirm('Yakin approve penerimaan ini?')">
                            <i data-feather="check"></i> Approve
                        </button>
                    </form>
                    @else
                    <form action="{{ route('penerimaan.revisi', $penerimaan->id) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-warning" onclick="return confirm('Revisi penerimaan ini? Akan kembali ke draft dan tidak dapat difakturkan hingga di-approve lagi.')">
                            <i data-feather="edit-3"></i> Revisi
                        </button>
                    </form>
                    @endif
                    @php
                        $totalSisaBelumDifaktur = 0;
                        foreach($penerimaan->details as $detail) {
                            $qtyReturApproved = \App\Models\ReturPembelianDetail::where('penerimaan_detail_id', $detail->id)
                                ->whereHas('retur', function($q){ $q->where('status','approved'); })
                                ->sum('qty_retur');
                            $netDiterima = max(0, ($detail->qty_diterima - $qtyReturApproved));
                            $sisaBelumDifaktur = max(0, ($netDiterima - ($detail->qty_terfaktur ?? 0)));
                            $totalSisaBelumDifaktur += $sisaBelumDifaktur;
                        }
                    @endphp
                    
                    @if($penerimaan->status == 'approved' && $totalSisaBelumDifaktur > 0)
                    <a href="{{ route('faktur.createFromPenerimaan', $penerimaan->id) }}" class="btn btn-success">
                        <i data-feather="file-text"></i> Buat Faktur
                    </a>
                    @endif
                    
                    <a href="{{ route('retur.create', $penerimaan->id) }}" class="btn btn-warning">
                        <i data-feather="rotate-ccw"></i> Buat Retur
                    </a>
                    
                    @if($penerimaan->status == 'draft')
                    <form action="{{ route('penerimaan.destroy', $penerimaan->id) }}" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger" 
                            onclick="return confirm('Yakin hapus penerimaan ini?')">
                            <i data-feather="trash-2"></i> Hapus
                        </button>
                    </form>
                    @endif
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

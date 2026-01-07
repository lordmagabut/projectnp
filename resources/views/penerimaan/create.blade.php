@extends('layout.master')

@section('content')
<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Buat Penerimaan Pembelian dari PO: {{ $po->no_po }}</h4>
                
                @if(session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif

                <form action="{{ route('penerimaan.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="po_id" value="{{ $po->id }}">

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label>No. Penerimaan <span class="text-danger">*</span></label>
                            <input type="text" name="no_penerimaan" class="form-control" required 
                                value="GR-{{ date('YmdHis') }}">
                        </div>
                        <div class="col-md-6">
                            <label>Tanggal <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal" class="form-control" required 
                                value="{{ date('Y-m-d') }}">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label>No. Surat Jalan</label>
                            <input type="text" name="no_surat_jalan" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label>Upload PDF Surat Jalan</label>
                            <input type="file" name="file_surat_jalan" class="form-control" accept=".pdf">
                            <small class="text-muted">Format: PDF, Maksimal 2MB</small>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label>Supplier</label>
                            <input type="text" class="form-control" value="{{ $po->nama_supplier }}" readonly>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label>Keterangan</label>
                        <textarea name="keterangan" class="form-control" rows="2"></textarea>
                    </div>

                    <h5 class="mt-4">Item yang Diterima</h5>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Kode Item</th>
                                <th>Uraian</th>
                                <th>Qty PO</th>
                                <th>Sudah Diterima</th>
                                <th>Sisa</th>
                                <th>Qty Diterima <span class="text-danger">*</span></th>
                                <th>Satuan</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($po->poDetails as $index => $detail)
                            @php
                                $sisa = $detail->qty - $detail->qty_diterima;
                            @endphp
                            @if($sisa > 0)
                            <tr>
                                <td>{{ $detail->kode_item }}</td>
                                <td>{{ $detail->uraian }}</td>
                                <td>{{ number_format($detail->qty, 2) }}</td>
                                <td>{{ number_format($detail->qty_diterima, 2) }}</td>
                                <td>{{ number_format($sisa, 2) }}</td>
                                <td>
                                    <input type="hidden" name="items[{{ $index }}][po_detail_id]" value="{{ $detail->id }}">
                                    <input type="number" name="items[{{ $index }}][qty_diterima]" 
                                        class="form-control" step="0.01" max="{{ $sisa }}" value="{{ $sisa }}">
                                </td>
                                <td>{{ $detail->uom }}</td>
                                <td>
                                    <input type="text" name="items[{{ $index }}][keterangan]" class="form-control" placeholder="Opsional">
                                </td>
                            </tr>
                            @endif
                            @endforeach
                        </tbody>
                    </table>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">Simpan Penerimaan</button>
                        <a href="{{ route('penerimaan.index') }}" class="btn btn-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

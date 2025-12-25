@extends('layout.master')

@section('content')
<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Buat Retur Pembelian dari Penerimaan: {{ $penerimaan->no_penerimaan }}</h4>
                
                @if(session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif

                <form action="{{ route('retur.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="penerimaan_id" value="{{ $penerimaan->id }}">

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label>No. Retur <span class="text-danger">*</span></label>
                            <input type="text" name="no_retur" class="form-control" required 
                                value="RTR-{{ date('YmdHis') }}">
                        </div>
                        <div class="col-md-6">
                            <label>Tanggal <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal" class="form-control" required 
                                value="{{ date('Y-m-d') }}">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label>Alasan Retur</label>
                            <textarea name="alasan" class="form-control" rows="2" 
                                placeholder="Contoh: Barang rusak, tidak sesuai spesifikasi, dll"></textarea>
                        </div>
                    </div>

                    <h5 class="mt-4">Item yang Diretur</h5>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Kode Item</th>
                                <th>Uraian</th>
                                <th>Qty Diterima</th>
                                <th>Qty Retur <span class="text-danger">*</span></th>
                                <th>Satuan</th>
                                <th>Alasan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($penerimaan->details as $index => $detail)
                            <tr>
                                <td>{{ $detail->kode_item }}</td>
                                <td>{{ $detail->uraian }}</td>
                                <td>{{ number_format($detail->qty_diterima, 2) }}</td>
                                <td>
                                    <input type="hidden" name="items[{{ $index }}][penerimaan_detail_id]" value="{{ $detail->id }}">
                                    <input type="number" name="items[{{ $index }}][qty_retur]" 
                                        class="form-control" step="0.01" max="{{ $detail->qty_diterima }}" value="0">
                                </td>
                                <td>{{ $detail->uom }}</td>
                                <td>
                                    <input type="text" name="items[{{ $index }}][alasan]" class="form-control" 
                                        placeholder="Opsional">
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-warning">Simpan Retur</button>
                        <a href="{{ route('retur.index') }}" class="btn btn-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

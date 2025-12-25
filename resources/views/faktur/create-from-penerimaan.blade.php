@extends('layout.master')

@section('content')
<nav class="page-breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ url('/') }}">Pembelian</a></li>
    <li class="breadcrumb-item"><a href="{{ route('penerimaan.index') }}">Penerimaan Barang</a></li>
    <li class="breadcrumb-item active" aria-current="page">Buat Faktur</li>
  </ol>
</nav>

<div class="row">
  <div class="col-lg-12 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <h6 class="card-title mb-4">Buat Faktur dari Penerimaan: {{ $penerimaan->no_penerimaan }}</h6>

        <form action="{{ route('faktur.store') }}" method="POST" enctype="multipart/form-data">
          @csrf

          <input type="hidden" name="penerimaan_id" value="{{ $penerimaan->id }}">
          <input type="hidden" name="id_supplier" value="{{ $penerimaan->po->id_supplier }}">
          <input type="hidden" name="id_perusahaan" value="{{ $penerimaan->po->id_perusahaan }}">
          <input type="hidden" name="id_proyek" value="{{ $penerimaan->po->id_proyek }}">

          <!-- Info Section -->
          <div class="row g-3 mb-4">
            <div class="col-md-4">
              <label class="form-label">Perusahaan</label>
              <input type="text" class="form-control" value="{{ $penerimaan->po->perusahaan->nama_perusahaan }}" readonly>
            </div>
            <div class="col-md-4">
              <label class="form-label">Tanggal Faktur <span class="text-danger">*</span></label>
              <input type="date" name="tanggal" class="form-control" value="{{ now()->format('Y-m-d') }}" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">No. Faktur <span class="text-danger">*</span></label>
              <input type="text" name="no_faktur" class="form-control" placeholder="Misal: INV-001" required>
            </div>
          </div>

          <div class="row g-3 mb-4">
            <div class="col-md-4">
              <label class="form-label">Supplier</label>
              <input type="text" class="form-control" value="{{ $penerimaan->po->supplier->nama_supplier }}" readonly>
            </div>
            <div class="col-md-4">
              <label class="form-label">No. PO</label>
              <input type="text" class="form-control" value="{{ $penerimaan->po->no_po }}" readonly>
            </div>
            <div class="col-md-4">
              <label class="form-label">Proyek</label>
              <input type="text" class="form-control" value="{{ $penerimaan->po->proyek->nama_proyek ?? '-' }}" readonly>
            </div>
          </div>

          <!-- Alert -->
          <div class="alert alert-info mb-4">
            <i class="link-icon" data-feather="info"></i>
            <strong>Catatan:</strong> Qty yang bisa difaktur adalah qty yang sudah diterima dan belum difaktur.
          </div>

          <!-- Items Table -->
          <h6 class="card-title mb-3">Detail Barang</h6>
          <div class="table-responsive mb-4">
            <table class="table table-bordered table-sm align-middle">
              <thead class="table-light">
                <tr>
                  <th width="50">#</th>
                  <th>Kode Item</th>
                  <th>Uraian</th>
                  <th>UOM</th>
                  <th class="text-center">Qty Diterima</th>
                  <th class="text-center">Qty Retur</th>
                  <th class="text-center">Qty Faktur</th>
                  <th class="text-end">Harga</th>
                  <th class="text-end">Total</th>
                </tr>
              </thead>
              <tbody>
                @foreach($penerimaan->details as $index => $detail)
                  @php
                    $qtyReturApproved = \App\Models\ReturPembelianDetail::where('penerimaan_detail_id', $detail->id)
                      ->whereHas('retur', function($q){ $q->where('status','approved'); })
                      ->sum('qty_retur');
                    $netQty = $detail->qty_diterima - $qtyReturApproved;
                    $sisaFaktur = $netQty - $detail->qty_terfaktur;
                  @endphp

                  @if($sisaFaktur > 0)
                  <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $detail->poDetail->kode_item }}</td>
                    <td>{{ $detail->poDetail->uraian }}</td>
                    <td>{{ $detail->poDetail->uom }}</td>
                    <td class="text-center">{{ $detail->qty_diterima }}</td>
                    <td class="text-center">{{ $qtyReturApproved }}</td>
                    <td class="text-center">
                      <input type="hidden" name="items[{{ $index }}][penerimaan_detail_id]" value="{{ $detail->id }}">
                      <input type="hidden" name="items[{{ $index }}][po_detail_id]" value="{{ $detail->po_detail_id }}">
                      <input type="hidden" name="items[{{ $index }}][kode_item]" value="{{ $detail->poDetail->kode_item }}">
                      <input type="hidden" name="items[{{ $index }}][uraian]" value="{{ $detail->poDetail->uraian }}">
                      <input type="hidden" name="items[{{ $index }}][uom]" value="{{ $detail->poDetail->uom }}">
                      <input type="hidden" name="items[{{ $index }}][harga]" value="{{ $detail->poDetail->harga }}">
                      <input type="number" name="items[{{ $index }}][qty]" class="form-control form-control-sm qty-input" 
                             value="{{ $sisaFaktur }}" max="{{ $sisaFaktur }}" min="0" required data-max="{{ $sisaFaktur }}">
                    </td>
                    <td class="text-end">Rp {{ number_format($detail->poDetail->harga, 0, ',', '.') }}</td>
                    <td class="text-end">
                      <span class="total-baris">Rp 0</span>
                    </td>
                  </tr>
                  @endif
                @endforeach
              </tbody>
              <tfoot class="table-light">
                <tr>
                  <th colspan="8" class="text-end">Subtotal:</th>
                  <th class="text-end"><span id="subtotal">Rp 0</span></th>
                </tr>
                <tr>
                  <th colspan="7">
                    <div class="row g-3">
                      <div class="col-md-4">
                        <label class="form-label">Diskon (%)</label>
                        <input type="number" name="diskon_persen" class="form-control form-control-sm" value="0" min="0" max="100" step="0.01" id="diskon_persen">
                      </div>
                      <div class="col-md-4">
                        <label class="form-label">PPN (%)</label>
                        <input type="number" name="ppn_persen" class="form-control form-control-sm" value="0" min="0" max="100" step="0.01" id="ppn_persen">
                      </div>
                    </div>
                  </th>
                  <th colspan="2" class="text-end">
                    <div>Diskon: <span id="diskon_nominal">Rp 0</span></div>
                    <div>PPN: <span id="ppn_nominal">Rp 0</span></div>
                    <div class="mt-2"><strong>Total: <span id="total">Rp 0</span></strong></div>
                  </th>
                </tr>
              </tfoot>
            </table>
          </div>

          <!-- File Upload -->
          <div class="mb-4">
            <label class="form-label">File Faktur (PDF)</label>
            <input type="file" name="file_path" class="form-control" accept=".pdf">
            <small class="text-muted">Ukuran max: 30MB</small>
          </div>

          <!-- Submit -->
          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">
              <i class="link-icon" data-feather="save"></i> Simpan Faktur
            </button>
            <a href="{{ route('penerimaan.index') }}" class="btn btn-secondary">
              <i class="link-icon" data-feather="x"></i> Batal
            </a>
          </div>

        </form>

      </div>
    </div>
  </div>
</div>

@endsection

@push('custom-scripts')
<script>
  $(function() {
    'use strict';

    function hitungTotal() {
      let subtotal = 0;
      
      $('.qty-input').each(function() {
        let qty = parseFloat($(this).val()) || 0;
        let harga = parseFloat($(this).closest('tr').find('input[name*="harga"]').val()) || 0;
        let total = qty * harga;
        $(this).closest('tr').find('.total-baris').text('Rp ' + total.toLocaleString('id-ID', {style: 'decimal', minimumFractionDigits: 0}));
        subtotal += total;
      });

      let diskonPersen = parseFloat($('#diskon_persen').val()) || 0;
      let ppnPersen = parseFloat($('#ppn_persen').val()) || 0;

      let diskonNominal = subtotal * diskonPersen / 100;
      let afterDiskon = subtotal - diskonNominal;
      let ppnNominal = afterDiskon * ppnPersen / 100;
      let total = afterDiskon + ppnNominal;

      $('#subtotal').text('Rp ' + subtotal.toLocaleString('id-ID', {style: 'decimal', minimumFractionDigits: 0}));
      $('#diskon_nominal').text('Rp ' + diskonNominal.toLocaleString('id-ID', {style: 'decimal', minimumFractionDigits: 0}));
      $('#ppn_nominal').text('Rp ' + ppnNominal.toLocaleString('id-ID', {style: 'decimal', minimumFractionDigits: 0}));
      $('#total').text('Rp ' + total.toLocaleString('id-ID', {style: 'decimal', minimumFractionDigits: 0}));
    }

    $(document).on('change', '.qty-input, #diskon_persen, #ppn_persen', function() {
      hitungTotal();
    });

    hitungTotal();

    if (feather) {
      feather.replace();
    }
  });
</script>
@endpush

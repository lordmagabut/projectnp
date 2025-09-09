@extends('layout.master')

@section('content')
<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">Buat Faktur dari PO: {{ $po->no_po }}</h4>

                <form action="{{ route('faktur.store') }}" method="POST" enctype="multipart/form-data">

                    @csrf

                    <input type="hidden" name="po_id" value="{{ $po->id }}">
                    <input type="hidden" name="id_supplier" value="{{ $po->id_supplier }}">
                    <input type="hidden" name="id_perusahaan" value="{{ $po->id_perusahaan }}">
                    <input type="hidden" name="id_proyek" value="{{ $po->id_proyek }}">

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label">Perusahaan</label>
                            <input type="text" class="form-control" value="{{ $po->perusahaan->nama_perusahaan }}" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Tanggal Faktur</label>
                            <input type="date" name="tanggal" class="form-control" value="{{ $po->tanggal }}" required value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">No. Faktur</label>
                            <input type="text" name="no_faktur" class="form-control" value="{{ $po->no_po }}" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Supplier</label>
                            <input type="text" class="form-control" value="{{ $po->supplier->nama_supplier }}" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Proyek</label>
                            <input type="text" class="form-control" value="{{ $po->proyek->nama_proyek ?? '-' }}" readonly>
                        </div>
                    </div>

                    <h5 class="mb-3">Detail Barang dari PO</h5>
                    <table class="table table-bordered table-sm align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Kode Item</th>
                                <th>Uraian</th>
                                <th>Qty</th>
                                <th>UOM</th>
                                <th>Harga</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($po->poDetails as $i => $detail)
                                @php
                                    $qty_sisa = $detail->qty - $detail->qty_terfaktur;
                                @endphp
                                @if($qty_sisa > 0)
                                <tr>
                                    <td>
                                        {{ $detail->kode_item }}
                                        <input type="hidden" name="items[{{ $i }}][kode_item]" value="{{ $detail->kode_item }}">
                                        <input type="hidden" name="items[{{ $i }}][po_detail_id]" value="{{ $detail->id }}">
                                        <input type="hidden" name="items[{{ $i }}][uraian]" value="{{ $detail->uraian }}">
                                        <input type="hidden" name="items[{{ $i }}][uom]" value="{{ $detail->uom }}">
                                        <input type="hidden" name="items[{{ $i }}][harga]" value="{{ $detail->harga }}">
                                        <input type="hidden" name="items[{{ $i }}][coa_beban_id]" value="{{ $detail->coa_beban_id }}">
                                        <input type="hidden" name="items[{{ $i }}][coa_persediaan_id]" value="{{ $detail->coa_persediaan_id }}">
                                        <input type="hidden" name="items[{{ $i }}][coa_hpp_id]" value="{{ $detail->coa_hpp_id }}">
                                    </td>
                                    <td>
                                        {{ $detail->uraian }}
                                    </td>
                                    <td>
                                        <input type="number"
                                            name="items[{{ $i }}][qty]"
                                            value="{{ $qty_sisa }}"
                                            min="0"
                                            step="0.000001"
                                            max="{{ $qty_sisa }}"
                                            class="form-control form-control-sm qty-input"
                                            required>
                                    </td>
                                    <td>{{ $detail->uom }}</td>
                                    <td>
                                        Rp {{ number_format($detail->harga, 0, ',', '.') }}
                                    </td>
                                    <td class="text-end">
                                        <span class="total-text">Rp {{ number_format($qty_sisa * $detail->harga, 0, ',', '.') }}</span>
                                        <input type="hidden" name="items[{{ $i }}][total]" class="total-input" value="{{ $qty_sisa * $detail->harga }}">
                                    </td>
                                </tr>
                                @endif
                            @endforeach
                        </tbody>

                    </table>

                    <div class="row g-3 mt-4">
                        <div class="col-md-6">
                            <label class="form-label">Diskon (%)</label>
                            <input type="number" name="diskon_persen" class="form-control" id="diskon-global" value="{{ $po->diskon_persen }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">PPN (%)</label>
                            <input type="number" name="ppn_persen" class="form-control" id="ppn-global" value="{{ $po->ppn_persen }}">
                        </div>
                    </div>

                    <div class="mt-4">
                        <label class="form-label">Keterangan</label>
                        <textarea name="keterangan" class="form-control" rows="3">{{ $po->keterangan }}</textarea>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Upload Faktur (PDF)</label>
                        <input type="file" name="file_path" class="form-control" accept="application/pdf">
                    </div>
                    <div class="mb-4">
                        <h5 class="mb-1">Subtotal: <span id="subtotalLabel" class="text-muted">Rp 0</span></h5>
                        <h5 class="mb-1">Diskon ({{ old('diskon_persen', $po->diskon_persen ?? 0) }}%): <span id="diskonLabel" class="text-muted">Rp 0</span></h5>
                        <h5 class="mb-1">PPN ({{ old('ppn_persen', $po->ppn_persen ?? 0) }}%): <span id="ppnLabel" class="text-muted">Rp 0</span></h5>
                        <h5 class="fw-bold">Grand Total: <span id="grandTotal" class="text-primary">Rp 0</span></h5>
                    </div>

                    <div class="text-end mt-4">
                        <button type="submit" class="btn btn-success">Simpan Faktur</button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('custom-scripts')
<script>
function formatRupiah(angka) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(angka).replace(',00', '');
}

function hitungGrandTotal() {
    let subtotal = 0;
    const rows = document.querySelectorAll('table tbody tr');

    rows.forEach(row => {
    const qtyInput = row.querySelector('input[name$="[qty]"]');
    const hargaInput = row.querySelector('input[name$="[harga]"]');
    const totalInput = row.querySelector('input[name$="[total]"]');
    const totalText = row.querySelector('.total-text');

    if (!qtyInput || !hargaInput) return;

    const qty = parseFloat(qtyInput.value) || 0;
    const harga = parseFloat(hargaInput.value) || 0;
    const total = qty * harga;

    subtotal += total;

    if (totalInput) totalInput.value = total;
    if (totalText) totalText.textContent = formatRupiah(total);
});

    let diskonPersen = parseFloat(document.getElementById('diskon-global')?.value || 0);
    let ppnPersen = parseFloat(document.getElementById('ppn-global')?.value || 0);

    let diskonRp = subtotal * (diskonPersen / 100);
    let setelahDiskon = subtotal - diskonRp;
    let ppnRp = setelahDiskon * (ppnPersen / 100);
    let grandTotal = setelahDiskon + ppnRp;

    document.getElementById('subtotalLabel').innerText = formatRupiah(subtotal);
    document.getElementById('diskonLabel').innerText = formatRupiah(diskonRp);
    document.getElementById('ppnLabel').innerText = formatRupiah(ppnRp);
    document.getElementById('grandTotal').innerText = formatRupiah(grandTotal);
}

document.addEventListener('input', function (e) {
    if (
        e.target.name?.includes('[qty]') ||
        e.target.name?.includes('[harga]') ||
        e.target.id === 'diskon-global' ||
        e.target.id === 'ppn-global'
    ) {
        hitungGrandTotal();
    }
});

document.addEventListener('DOMContentLoaded', hitungGrandTotal);
</script>
@endpush

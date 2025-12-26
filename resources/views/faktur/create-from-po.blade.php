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
                            <input type="date" name="tanggal" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">No. Faktur</label>
                            <input type="text" name="no_faktur" class="form-control bg-light" value="{{ $nomorFaktur }}" readonly>
                            <small class="text-muted">Nomor otomatis sistem</small>
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
                    <div class="alert alert-info">
                        <i data-feather="info"></i> <strong>Catatan:</strong> Qty yang bisa difaktur adalah qty yang sudah diterima dan belum difaktur.
                    </div>
                    <table class="table table-bordered table-sm align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Kode Item</th>
                                <th>Uraian</th>
                                <th>Qty PO</th>
                                <th>Qty Diterima</th>
                                <th>Sudah Difaktur</th>
                                <th>Sisa Bisa Difaktur</th>
                                <th>Qty Faktur</th>
                                <th>UOM</th>
                                <th>Harga</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($po->poDetails as $i => $detail)
                                @php
                                    // Hanya qty dari penerimaan approved dikurangi retur approved yang dihitung
                                    $qty_approved = \App\Models\PenerimaanPembelianDetail::where('po_detail_id', $detail->id)
                                        ->whereHas('penerimaan', function($q){ $q->where('status','approved'); })
                                        ->sum('qty_diterima');
                                    $qty_retur_approved = \App\Models\ReturPembelianDetail::whereHas('retur', function($q){ $q->where('status','approved'); })
                                        ->whereHas('penerimaanDetail', function($q) use ($detail){ $q->where('po_detail_id', $detail->id); })
                                        ->sum('qty_retur');
                                    $qty_diterima = max(0, $qty_approved - $qty_retur_approved);
                                    $qty_terfaktur = $detail->qty_terfaktur;
                                    $qty_sisa_bisa_difaktur = max(0, $qty_diterima - $qty_terfaktur);
                                @endphp
                                @if($qty_sisa_bisa_difaktur > 0)
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
                                    <td>{{ $detail->uraian }}</td>
                                    <td class="text-end">{{ number_format($detail->qty, 2) }}</td>
                                    <td class="text-end">
                                        <span class="badge bg-success">{{ number_format($qty_diterima, 2) }}</span>
                                    </td>
                                    <td class="text-end">{{ number_format($qty_terfaktur, 2) }}</td>
                                    <td class="text-end">
                                        <strong class="text-primary">{{ number_format($qty_sisa_bisa_difaktur, 2) }}</strong>
                                    </td>
                                    <td>
                                        <input type="number"
                                            name="items[{{ $i }}][qty]"
                                            value="{{ $qty_sisa_bisa_difaktur }}"
                                            min="0"
                                            step="0.000001"
                                            max="{{ $qty_sisa_bisa_difaktur }}"
                                            class="form-control form-control-sm qty-input"
                                            required>
                                    </td>
                                    <td>{{ $detail->uom }}</td>
                                    <td class="text-end">
                                        Rp {{ number_format($detail->harga, 0, ',', '.') }}
                                    </td>
                                    <td class="text-end">
                                        <span class="total-text">Rp {{ number_format($qty_sisa_bisa_difaktur * $detail->harga, 0, ',', '.') }}</span>
                                        <input type="hidden" name="items[{{ $i }}][total]" class="total-input" value="{{ $qty_sisa_bisa_difaktur * $detail->harga }}">
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

                    <!-- Uang Muka Section -->
                    <div class="card bg-light p-3 my-4">
                        <h5 class="mb-3">Uang Muka Pembelian (Opsional)</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Pilih Uang Muka</label>
                                <select name="uang_muka_id" id="uangMukaSelect" class="form-select">
                                    <option value="">-- Tanpa Uang Muka --</option>
                                    @if($uangMukaList = \App\Models\UangMukaPembelian::where('id_supplier', $po->id_supplier)->where('status', 'approved')->get())
                                        @foreach($uangMukaList as $um)
                                            <option value="{{ $um->id }}" data-nominal="{{ $um->nominal }}" data-sisa="{{ $um->nominal - $um->nominal_digunakan }}">
                                                {{ $um->no_uang_muka }} - Sisa: Rp {{ number_format($um->nominal - $um->nominal_digunakan, 0, ',', '.') }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Jumlah UM yang Dipakai (Rp)</label>
                                <input type="number" name="uang_muka_dipakai" id="uangMukaDipakai" class="form-control" min="0" step="0.01" placeholder="0">
                                <small class="form-text text-muted">Kosongkan jika tidak menggunakan UM</small>
                            </div>
                        </div>
                        <div id="uangMukaInfo" class="alert alert-info mt-2" style="display:none;">
                            <small>
                                <strong>Nominal UM:</strong> Rp <span id="uangMukaNominal">0</span><br>
                                <strong>Sisa UM:</strong> Rp <span id="uangMukaSisa">0</span>
                            </small>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">Upload Faktur (PDF)</label>
                        <input type="file" name="file_path" class="form-control" accept="application/pdf">
                    </div>
                    <div class="mb-4">
                        <h5 class="mb-1">Subtotal: <span id="subtotalLabel" class="text-muted">Rp 0</span></h5>
                        <h5 class="mb-1">Diskon ({{ old('diskon_persen', $po->diskon_persen ?? 0) }}%): <span id="diskonLabel" class="text-muted">Rp 0</span></h5>
                        <h5 class="mb-1">PPN ({{ old('ppn_persen', $po->ppn_persen ?? 0) }}%): <span id="ppnLabel" class="text-muted">Rp 0</span></h5>
                        <h5 class="mb-1">Grand Total (sebelum UM): <span id="grandTotal" class="text-primary">Rp 0</span></h5>
                        <h5 class="mb-1">Uang Muka Dipakai: <span id="uangMukaLabel" class="text-info">Rp 0</span></h5>
                        <h5 class="fw-bold">Total Tagihan Setelah UM: <span id="netTotal" class="text-primary">Rp 0</span></h5>
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

    const umInput = document.getElementById('uangMukaDipakai');
    const umDipakai = parseFloat(umInput?.value || 0);
    // Set max UM = min(Sisa UM, Grand Total)
    const selected = document.getElementById('uangMukaSelect')?.options[document.getElementById('uangMukaSelect')?.selectedIndex];
    const sisa = selected?.dataset?.sisa ? parseFloat(selected.dataset.sisa) : Infinity;
    const maxUm = Math.min(isFinite(sisa) ? sisa : Number.MAX_VALUE, grandTotal);
    if (umInput) {
        if (isFinite(maxUm)) umInput.max = maxUm; else umInput.removeAttribute('max');
    }
    const netTotal = Math.max(0, grandTotal - (parseFloat(umInput?.value || 0)));

    document.getElementById('subtotalLabel').innerText = formatRupiah(subtotal);
    document.getElementById('diskonLabel').innerText = formatRupiah(diskonRp);
    document.getElementById('ppnLabel').innerText = formatRupiah(ppnRp);
    document.getElementById('grandTotal').innerText = formatRupiah(grandTotal);
    document.getElementById('uangMukaLabel').innerText = formatRupiah(umDipakai);
    document.getElementById('netTotal').innerText = formatRupiah(netTotal);
}

document.addEventListener('input', function (e) {
    if (
        e.target.name?.includes('[qty]') ||
        e.target.name?.includes('[harga]') ||
        e.target.id === 'diskon-global' ||
        e.target.id === 'ppn-global' ||
        e.target.id === 'uangMukaDipakai'
    ) {
        hitungGrandTotal();
    }
});

document.addEventListener('DOMContentLoaded', hitungGrandTotal);

// Uang Muka handlers
document.getElementById('uangMukaSelect').addEventListener('change', function () {
    const selected = this.options[this.selectedIndex];
    const nominal = selected.dataset.nominal;
    const sisa = selected.dataset.sisa;
    const info = document.getElementById('uangMukaInfo');
    const inputDipakai = document.getElementById('uangMukaDipakai');
    
    if (nominal) {
        document.getElementById('uangMukaNominal').textContent = parseInt(nominal).toLocaleString('id-ID');
        document.getElementById('uangMukaSisa').textContent = parseInt(sisa).toLocaleString('id-ID');
        // set max UM jadi min(sisa, grand total)
        const grandTotalText = document.getElementById('grandTotal')?.innerText?.replace(/[^0-9]/g,'');
        const gt = parseFloat(grandTotalText) || 0;
        inputDipakai.max = Math.min(parseFloat(sisa) || 0, gt || Number.MAX_VALUE);
        info.style.display = 'block';
    } else {
        info.style.display = 'none';
        inputDipakai.value = '';
        inputDipakai.max = '';
    }

    hitungGrandTotal();
});
</script>
@endpush

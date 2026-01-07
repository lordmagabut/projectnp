@extends('layout.master')

@section('content')
<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">Edit PO</h4>

                <form action="{{ route('po.update', $po->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label">Tanggal</label>
                            <input type="date" name="tanggal" class="form-control" value="{{ $po->tanggal }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">No. PO</label>
                            <input type="text" name="no_po" id="noPoField" class="form-control" value="{{ $po->no_po }}" readonly>
                            <small class="text-muted">Nomor saat ini: {{ $po->no_po }}. Akan menjadi: <span id="noPoPreview">{{ $previewNoPo ?? '—' }}</span></small>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Supplier</label>
                            <select name="id_supplier" class="form-select" required>
                                <option value="">-- Pilih Supplier --</option>
                                @foreach($suppliers as $s)
                                    <option value="{{ $s->id }}" {{ $s->id == $po->id_supplier ? 'selected' : '' }}>{{ $s->nama_supplier }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Proyek</label>
                            <select name="id_proyek" id="proyekSelect" class="form-select" required>
                                <option value="">-- Pilih Proyek --</option>
                                @foreach($proyek as $pr)
                                    <option 
                                        value="{{ $pr->id }}" 
                                        {{ $pr->id == $po->id_proyek ? 'selected' : '' }}
                                    >
                                        {{ $pr->nama_proyek }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <h5 class="mb-3">Detail Pesanan</h5>
                    <table class="table table-bordered table-sm align-middle" id="barang-table">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 15%;">Kode Item</th>
                                <th>Uraian</th>
                                <th style="width: 10%;">Qty</th>
                                <th style="width: 10%;">UOM</th>
                                <th style="width: 15%;">Harga</th>
                                <th style="width: 15%;">Total</th>
                                <th style="width: 10%;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="detail-barang">
                            @foreach($po->details as $index => $item)
                            <tr>
                                <td>
                                    <select name="items[{{ $index }}][kode_item]" class="form-select kode-item" required>
                                        <option value="">-- Pilih Barang --</option>
                                        @foreach($barang as $b)
                                            <option value="{{ $b->kode_barang }}" data-uraian="{{ $b->nama_barang }}" {{ $b->kode_barang == $item->kode_item ? 'selected' : '' }}>
                                                {{ $b->kode_barang }} - {{ $b->nama_barang }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input type="text" name="items[{{ $index }}][uraian]" class="form-control uraian" value="{{ $item->uraian }}" required></td>
                                <td><input type="number" step="0.01" name="items[{{ $index }}][qty]" class="form-control qty" min="0" value="{{ $item->qty }}" required></td>
                                <td><input type="text" name="items[{{ $index }}][uom]" class="form-control" value="{{ $item->uom }}" required></td>
                                <td><input type="number" name="items[{{ $index }}][harga]" class="form-control harga" min="0" value="{{ $item->harga }}" required></td>
                                <td class="text-end total-row">0</td>
                                <td><button type="button" class="btn btn-danger btn-sm remove-row">Hapus</button></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <button type="button" class="btn btn-success btn-sm mb-4" id="addRow">+ Tambah Item</button>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Diskon (%)</label>
                            <input type="number" name="diskon_persen" class="form-control" id="diskon-global" value="{{ $po->details->first()->diskon_persen ?? 0 }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">PPN (%)</label>
                            <input type="number" name="ppn_persen" class="form-control" id="ppn-global" value="{{ $po->details->first()->ppn_persen ?? 0 }}" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Keterangan</label>
                        <textarea name="keterangan" class="form-control" rows="3">{{ $po->keterangan }}</textarea>
                    </div>

                    <div class="mb-4">
                        <h5 class="mb-2">Ringkasan Perhitungan</h5>
                        <div class="border rounded p-3 bg-light">
                            <div class="d-flex justify-content-between mb-1">
                                <span>Subtotal</span>
                                <span id="subtotalDisplay">Rp 0</span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span>Diskon (Rp)</span>
                                <span id="diskonDisplay">Rp 0</span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span>PPN (Rp)</span>
                                <span id="ppnDisplay">Rp 0</span>
                            </div>
                            <div class="d-flex justify-content-between fw-bold border-top pt-2 mt-2">
                                <span>Grand Total</span>
                                <span id="grandTotal" class="text-primary">Rp 0</span>
                            </div>
                        </div>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    let index = {{ $po->details->count() }};

    function hitungTotal() {
        let diskon = parseFloat(document.getElementById('diskon-global').value) || 0;
        let ppn = parseFloat(document.getElementById('ppn-global').value) || 0;
        let grandSubtotal = 0;

        // Hitung subtotal dari semua items
        document.querySelectorAll('#detail-barang tr').forEach(row => {
            let qty = parseFloat(row.querySelector('.qty').value) || 0;
            let harga = parseFloat(row.querySelector('.harga').value) || 0;
            let subtotal = qty * harga;
            grandSubtotal += subtotal;
        });

        // Hitung diskon dari grand subtotal
        let totalDiskon = grandSubtotal * (diskon / 100);
        
        // Hitung PPN dari (grandSubtotal - diskon)
        let totalPPN = (grandSubtotal - totalDiskon) * (ppn / 100);
        
        // Grand Total = subtotal - diskon + ppn
        let grandTotal = (grandSubtotal - totalDiskon) + totalPPN;

        // Update tampilan di setiap row
        document.querySelectorAll('#detail-barang tr').forEach(row => {
            let qty = parseFloat(row.querySelector('.qty').value) || 0;
            let harga = parseFloat(row.querySelector('.harga').value) || 0;
            let subtotal = qty * harga;
            row.querySelector('.total-row').innerText = subtotal.toLocaleString('id-ID', {minimumFractionDigits: 2});
        });

        // Update ringkasan
        document.getElementById('subtotalDisplay').innerText = 'Rp ' + grandSubtotal.toLocaleString('id-ID', {minimumFractionDigits: 2});
        document.getElementById('diskonDisplay').innerText = 'Rp ' + totalDiskon.toLocaleString('id-ID', {minimumFractionDigits: 2});
        document.getElementById('ppnDisplay').innerText = 'Rp ' + totalPPN.toLocaleString('id-ID', {minimumFractionDigits: 2});
        document.getElementById('grandTotal').innerText = 'Rp ' + grandTotal.toLocaleString('id-ID', {minimumFractionDigits: 2});
    }

    document.getElementById('addRow').addEventListener('click', function () {
        let row = `
            <tr>
                <td>
                    <select name="items[${index}][kode_item]" class="form-select kode-item" required>
                        <option value="">-- Pilih Barang --</option>
                        @foreach($barang as $b)
                            <option value="{{ $b->kode_barang }}" data-uraian="{{ $b->nama_barang }}">{{ $b->kode_barang }} - {{ $b->nama_barang }}</option>
                        @endforeach
                    </select>
                </td>
                <td><input type="text" name="items[${index}][uraian]" class="form-control uraian" required></td>
                <td><input type="number" step="0.01" name="items[${index}][qty]" class="form-control qty" min="0" required></td>
                <td><input type="text" name="items[${index}][uom]" class="form-control" required></td>
                <td><input type="number" name="items[${index}][harga]" class="form-control harga" min="0" required></td>
                <td class="text-end total-row">0</td>
                <td><button type="button" class="btn btn-danger btn-sm remove-row">Hapus</button></td>
            </tr>`;
        document.getElementById('detail-barang').insertAdjacentHTML('beforeend', row);
        index++;
    });

    document.addEventListener('input', function (e) {
        if (e.target.classList.contains('qty') || e.target.classList.contains('harga') || e.target.id === 'diskon-global' || e.target.id === 'ppn-global') {
            hitungTotal();
        }

        if (e.target.classList.contains('kode-item')) {
            let uraian = e.target.options[e.target.selectedIndex].getAttribute('data-uraian');
            e.target.closest('tr').querySelector('.uraian').value = uraian;
        }
    });

    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('remove-row')) {
            e.target.closest('tr').remove();
            hitungTotal();
        }
    });

    window.addEventListener('load', function () {
        hitungTotal();
    });

    document.getElementById('proyekSelect').addEventListener('change', function () {
        let selectedOption = this.options[this.selectedIndex];
        let idPerusahaan = selectedOption.getAttribute('data-id-perusahaan');
        let namaPerusahaan = selectedOption.getAttribute('data-nama-perusahaan');

        document.getElementById('idPerusahaan').value = idPerusahaan || '';
        document.getElementById('namaPerusahaan').value = namaPerusahaan || '';
    });

    // Preview nomor PO dinamis saat tanggal berubah
    const tanggalInput = document.querySelector('input[name="tanggal"]');
    const noPoPreview = document.getElementById('noPoPreview');
    function updateNoPoPreview() {
        const tgl = tanggalInput.value || new Date().toISOString().slice(0,10);
        fetch(`{{ route('po.preview_no') }}?tanggal=${encodeURIComponent(tgl)}`)
            .then(r => r.json())
            .then(data => {
                if (data?.no_po) {
                    noPoPreview.textContent = data.no_po;
                }
            })
            .catch(() => {});
    }
    tanggalInput?.addEventListener('change', updateNoPoPreview);
</script>
@endsection

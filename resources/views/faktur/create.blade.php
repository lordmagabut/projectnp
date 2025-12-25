@extends('layout.master')

@section('content')
<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">Form Input Faktur</h4>

                <form action="{{ route('faktur.store') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">Tanggal Faktur</label>
                        <input type="date" name="tanggal" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Supplier</label>
                        <select name="id_supplier" id="supplierSelect" class="form-select" required>
                            <option value="">-- Pilih Supplier --</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}">{{ $supplier->nama_supplier }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Pesanan Pembelian (PO)</label>
                        <select name="id_po" id="poSelect" class="form-select" required>
                            <option value="">-- Pilih PO --</option>
                        </select>
                    </div>

                    <!-- Uang Muka Section -->
                    <div id="uangMukaSection" class="card bg-light p-3 mb-3" style="display:none;">
                        <h5 class="mb-3">Uang Muka Pembelian (Opsional)</h5>
                        <div class="mb-3">
                            <label class="form-label">Pilih Uang Muka</label>
                            <select name="uang_muka_id" id="uangMukaSelect" class="form-select">
                                <option value="">-- Tanpa Uang Muka --</option>
                            </select>
                        </div>
                        <div id="uangMukaInfo" class="alert alert-info" style="display:none;">
                            <small>
                                <strong>Nominal UM:</strong> Rp <span id="uangMukaNominal">0</span><br>
                                <strong>Sisa UM:</strong> Rp <span id="uangMukaSisa">0</span>
                            </small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Jumlah UM yang Dipakai (Rp)</label>
                            <input type="number" name="uang_muka_dipakai" id="uangMukaDipakai" class="form-control" 
                                   min="0" step="0.01" placeholder="0">
                            <small class="form-text text-muted">Kosongkan jika tidak menggunakan UM</small>
                        </div>
                    </div>

                    <div id="poDetailContainer" class="mt-4" style="display:none;">
                        <h5>Detail Barang</h5>
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Nama Barang</th>
                                    <th>Qty PO</th>
                                    <th>Qty Faktur</th>
                                    <th>Harga</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody id="poItemsTable">
                                {{-- Baris item akan dimuat melalui JavaScript --}}
                            </tbody>
                        </table>
                    </div>

                    <button type="submit" class="btn btn-primary mt-3">Simpan Faktur</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('custom-scripts')
<script>
    const routePoBySupplier = "{{ url('api/po-by-supplier') }}";
    const routePoDetail = "{{ url('api/po-detail') }}";
    const routeUangMukaBySupplier = "{{ url('api/uang-muka-by-supplier') }}";

    document.getElementById('supplierSelect').addEventListener('change', function () {
        const supplierId = this.value;
        const poSelect = document.getElementById('poSelect');
        const uangMukaSelect = document.getElementById('uangMukaSelect');
        const uangMukaSection = document.getElementById('uangMukaSection');
        
        poSelect.innerHTML = `<option value="">Memuat PO...</option>`;
        uangMukaSelect.innerHTML = `<option value="">-- Tanpa Uang Muka --</option>`;
        
        if (supplierId) {
            // Load PO
            fetch(`${routePoBySupplier}/${supplierId}`)
                .then(res => res.json())
                .then(data => {
                    poSelect.innerHTML = `<option value="">-- Pilih PO --</option>`;
                    data.forEach(po => {
                        poSelect.innerHTML += `<option value="${po.id}">${po.no_po} - ${po.tanggal}</option>`;
                    });
                });
            
            // Load Uang Muka
            fetch(`${routeUangMukaBySupplier}/${supplierId}`)
                .then(res => res.json())
                .then(data => {
                    uangMukaSelect.innerHTML = `<option value="">-- Tanpa Uang Muka --</option>`;
                    data.forEach(um => {
                        const sisa = um.nominal - um.nominal_digunakan;
                        uangMukaSelect.innerHTML += `<option value="${um.id}" data-nominal="${um.nominal}" data-sisa="${sisa}">${um.no_uang_muka} - Sisa: Rp ${sisa.toLocaleString('id-ID')}</option>`;
                    });
                    uangMukaSection.style.display = data.length > 0 ? 'block' : 'none';
                });
        } else {
            uangMukaSection.style.display = 'none';
        }
    });

    document.getElementById('uangMukaSelect').addEventListener('change', function () {
        const selected = this.options[this.selectedIndex];
        const nominal = selected.dataset.nominal;
        const sisa = selected.dataset.sisa;
        const info = document.getElementById('uangMukaInfo');
        const inputDipakai = document.getElementById('uangMukaDipakai');
        
        if (nominal) {
            document.getElementById('uangMukaNominal').textContent = parseInt(nominal).toLocaleString('id-ID');
            document.getElementById('uangMukaSisa').textContent = parseInt(sisa).toLocaleString('id-ID');
            inputDipakai.max = sisa;
            info.style.display = 'block';
        } else {
            info.style.display = 'none';
            inputDipakai.value = '';
            inputDipakai.max = '';
        }
    });

    document.getElementById('poSelect').addEventListener('change', function () {
        const poId = this.value;
        const container = document.getElementById('poDetailContainer');
        const tbody = document.getElementById('poItemsTable');
        tbody.innerHTML = '';
        if (poId) {
            fetch(`${routePoDetail}/${poId}`)
                .then(res => res.json())
                .then(data => {
                    data.forEach((item, index) => {
                        tbody.innerHTML += `
                            <tr>
                                <td>
                                    ${item.nama_barang}
                                    <input type="hidden" name="items[${index}][id_barang]" value="${item.id_barang}">
                                </td>
                                <td>${item.qty_po}</td>
                                <td>
                                    <input type="number" name="items[${index}][qty]" value="${item.qty_po}" class="form-control" required>
                                </td>
                                <td>
                                    Rp ${parseInt(item.harga).toLocaleString('id-ID')}
                                    <input type="hidden" name="items[${index}][harga]" value="${item.harga}">
                                </td>
                                <td>
                                    <span class="total">Rp ${(item.qty_po * item.harga).toLocaleString('id-ID')}</span>
                                </td>
                            </tr>`;
                    });
                    container.style.display = 'block';
                });
        } else {
            container.style.display = 'none';
        }
    });
</script>
@endpush

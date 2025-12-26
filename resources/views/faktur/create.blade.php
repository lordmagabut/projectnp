@extends('layout.master')

@section('content')
<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">Form Input Faktur</h4>

                <form action="{{ route('faktur.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">Nomor Faktur</label>
                        <input type="text" name="no_faktur" class="form-control bg-light" 
                               value="{{ $nomorFaktur }}" readonly>
                        <small class="text-muted">Nomor otomatis sistem</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tanggal Faktur</label>
                        <input type="date" name="tanggal" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">File Faktur (PDF)</label>
                        <input type="file" name="file_path" class="form-control" accept=".pdf">
                        <small class="text-muted">Opsional, maksimal 30MB</small>
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
                        <select name="po_id" id="poSelect" class="form-select" required>
                            <option value="">-- Pilih PO --</option>
                        </select>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Diskon Global (%)</label>
                            <input type="number" name="diskon_persen" class="form-control" value="0" min="0" max="100" step="0.01">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">PPN Global (%)</label>
                            <input type="number" name="ppn_persen" class="form-control" value="0" min="0" max="100" step="0.01">
                        </div>
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
                                    <th>Qty Tersedia</th>
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
                        poSelect.innerHTML += `<option value="${po.id}" data-ppn="${po.ppn_persen ?? 0}" data-diskon="${po.diskon_persen ?? 0}">${po.no_po} - ${po.tanggal}</option>`;
                    });
                });
            
            // Load Uang Muka
            fetch(`${routeUangMukaBySupplier}/${supplierId}`)
                .then(res => res.json())
                .then(data => {
                    console.log('Uang Muka Data:', data);
                    uangMukaSelect.innerHTML = `<option value="">-- Tanpa Uang Muka --</option>`;
                    if (data.length > 0) {
                        data.forEach(um => {
                            const nominal = parseFloat(um.nominal || 0);
                            const digunakan = parseFloat(um.nominal_digunakan || 0);
                            const sisa = um.sisa !== undefined ? parseFloat(um.sisa) : Math.max(0, nominal - digunakan);
                            const sisaLabel = isNaN(sisa) ? '0' : sisa.toLocaleString('id-ID');
                            uangMukaSelect.innerHTML += `<option value="${um.id}" data-nominal="${nominal}" data-sisa="${sisa}">${um.no_um || um.no_uang_muka || '-'} - Sisa: Rp ${sisaLabel}</option>`;
                        });
                    }
                    // Tampilkan section uang muka setelah supplier dipilih
                    uangMukaSection.style.display = 'block';
                })
                .catch(err => {
                    console.error('Error loading Uang Muka:', err);
                    uangMukaSection.style.display = 'block';
                });
        } else {
            uangMukaSection.style.display = 'none';
            // Reset diskon/PPN when supplier is cleared
            const diskonInput = document.querySelector('input[name="diskon_persen"]');
            const ppnInput = document.querySelector('input[name="ppn_persen"]');
            if (diskonInput) diskonInput.value = 0;
            if (ppnInput) ppnInput.value = 0;
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
        const selected = this.options[this.selectedIndex];
        const diskonInput = document.querySelector('input[name="diskon_persen"]');
        const ppnInput = document.querySelector('input[name="ppn_persen"]');
        if (selected) {
            const diskon = parseFloat(selected.dataset.diskon || 0);
            const ppn = parseFloat(selected.dataset.ppn || 0);
            if (diskonInput) diskonInput.value = diskon;
            if (ppnInput) ppnInput.value = ppn;
        }

        const container = document.getElementById('poDetailContainer');
        const tbody = document.getElementById('poItemsTable');
        tbody.innerHTML = '';
        if (poId) {
            fetch(`${routePoDetail}/${poId}`)
                .then(res => res.json())
                .then(data => {
                    if (data.details && data.details.length > 0) {
                        data.details.forEach((item, index) => {
                            tbody.innerHTML += `
                                <tr>
                                    <td>
                                        ${item.barang_nama}
                                        <input type="hidden" name="items[${index}][po_detail_id]" value="${item.id}">
                                        <input type="hidden" name="items[${index}][kode_item]" value="${item.barang_id}">
                                        <input type="hidden" name="items[${index}][uraian]" value="${item.barang_nama}">
                                        <input type="hidden" name="items[${index}][uom]" value="${item.satuan}">
                                    </td>
                                    <td>${item.qty_available}</td>
                                    <td>
                                        <input type="number" name="items[${index}][qty]" value="${item.qty_available}" 
                                               max="${item.qty_available}" min="0" step="0.01" class="form-control" required>
                                    </td>
                                    <td>
                                        Rp ${parseInt(item.harga).toLocaleString('id-ID')}
                                        <input type="hidden" name="items[${index}][harga]" value="${item.harga}">
                                    </td>
                                    <td>
                                        <span class="total">Rp ${(item.qty_available * item.harga).toLocaleString('id-ID')}</span>
                                    </td>
                                </tr>`;
                        });
                        container.style.display = 'block';
                    } else {
                        tbody.innerHTML = '<tr><td colspan="5" class="text-center">Tidak ada item yang tersedia untuk difaktur</td></tr>';
                        container.style.display = 'block';
                    }
                })
                .catch(err => {
                    console.error('Error loading PO details:', err);
                    tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Error memuat data PO</td></tr>';
                });
        } else {
            container.style.display = 'none';
        }
    });
</script>
@endpush

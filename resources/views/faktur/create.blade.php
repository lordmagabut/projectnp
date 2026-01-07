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

                    <!-- Multiple Penerimaan Selection -->
                    <div id="penerimaanSection" class="mt-4 p-3 border rounded bg-light" style="display:none;">
                        <h5 class="mb-3">
                            <i data-feather="package" style="width: 18px; height: 18px;"></i>
                            Pilih Penerimaan Barang (Bisa Lebih Dari Satu)
                        </h5>
                        <div id="penerimaanList" class="row">
                            <!-- Penerimaan items akan dimuat melalui JavaScript -->
                        </div>
                        <!-- Hidden input untuk penerimaan_ids -->
                        <input type="hidden" id="penerimaanIdsInput" name="penerimaan_ids" value="">
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
                        <h5>Detail Barang dari Penerimaan Terpilih</h5>
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Nama Barang</th>
                                    <th>Penerimaan</th>
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

                    <button type="submit" class="btn btn-primary mt-3" onclick="return validateFakturForm();">Simpan Faktur</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('custom-scripts')
<script>
    function validateFakturForm() {
        const supplierId = document.getElementById('supplierSelect').value;
        const poId = document.getElementById('poSelect').value;
        
        // Validasi supplier dan PO dipilih
        if (!supplierId) {
            alert('Pilih supplier terlebih dahulu');
            return false;
        }
        if (!poId) {
            alert('Pilih Pesanan Pembelian (PO) terlebih dahulu');
            return false;
        }

        // Validasi ada minimal 1 item dengan qty > 0 - cek input qty yang ada
        const qtyInputs = document.querySelectorAll('input[name*="items["][name*="][qty]"]');
        if (qtyInputs.length === 0) {
            alert('Pilih penerimaan barang dan tambahkan item');
            return false;
        }

        let hasValidItem = false;
        qtyInputs.forEach(input => {
            if (parseFloat(input.value || 0) > 0) {
                hasValidItem = true;
            }
        });

        if (!hasValidItem) {
            alert('Minimal harus ada 1 item dengan qty > 0');
            return false;
        }

        return true;
    }

    const routePoBySupplier = "{{ url('api/po-by-supplier') }}";
    const routePenerimaanByPo = "{{ url('api/penerimaan-by-po') }}";
    const routePenerimaanDetail = "{{ url('api/penerimaan-detail') }}";
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

        const penerimaanSection = document.getElementById('penerimaanSection');
        const penerimaanList = document.getElementById('penerimaanList');
        const container = document.getElementById('poDetailContainer');
        
        penerimaanList.innerHTML = '';
        
        if (poId) {
            // Load penerimaan untuk PO ini
            fetch(`${routePenerimaanByPo}/${poId}`)
                .then(res => res.json())
                .then(data => {
                    if (data.length > 0) {
                        penerimaanList.innerHTML = '';
                        data.forEach((penerimaan, index) => {
                            const tanggal = new Date(penerimaan.tanggal).toLocaleDateString('id-ID');
                            penerimaanList.innerHTML += `
                                <div class="col-md-6 mb-3">
                                    <div class="form-check p-3 border rounded cursor-pointer hover-bg" style="cursor: pointer;">
                                        <input class="form-check-input penerimaan-checkbox" type="checkbox" 
                                               name="penerimaan_ids[]" value="${penerimaan.id}" id="penerimaan${penerimaan.id}"
                                               data-po-id="${poId}">
                                        <label class="form-check-label w-100" for="penerimaan${penerimaan.id}">
                                            <strong>${penerimaan.no_penerimaan}</strong><br>
                                            <small class="text-muted">Tanggal: ${tanggal}</small><br>
                                            <small class="text-muted">Status: <span class="badge bg-success">Approved</span></small>
                                        </label>
                                    </div>
                                </div>`;
                        });
                        penerimaanSection.style.display = 'block';
                    } else {
                        penerimaanList.innerHTML = '<div class="col-12"><div class="alert alert-info">Tidak ada penerimaan yang approved untuk PO ini</div></div>';
                        penerimaanSection.style.display = 'block';
                    }
                })
                .catch(err => {
                    console.error('Error loading penerimaan:', err);
                    penerimaanList.innerHTML = '<div class="col-12"><div class="alert alert-danger">Error memuat penerimaan</div></div>';
                });
        } else {
            penerimaanSection.style.display = 'none';
            container.style.display = 'none';
        }
    });

    // Load barang ketika penerimaan dipilih (trigger saat ada perubahan checkbox)
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('penerimaan-checkbox')) {
            // Update hidden input dengan selected penerimaan IDs
            const selectedIds = Array.from(document.querySelectorAll('.penerimaan-checkbox:checked'))
                .map(cb => cb.value);
            document.getElementById('penerimaanIdsInput').value = selectedIds.join(',');
            
            loadPenerimaanDetails();
        }
    });

    function loadPenerimaanDetails() {
        const poId = document.getElementById('poSelect').value;
        const selectedPenerimaanIds = Array.from(document.querySelectorAll('.penerimaan-checkbox:checked'))
            .map(cb => cb.value);
        
        const container = document.getElementById('poDetailContainer');
        const tbody = document.getElementById('poItemsTable');
        
        if (selectedPenerimaanIds.length === 0) {
            container.style.display = 'none';
            tbody.innerHTML = '';
            return;
        }

        // Load details dari semua penerimaan yang dipilih
        const csrfToken = document.querySelector('input[name="_token"]').value;
        
        fetch(`${routePenerimaanDetail}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                penerimaan_ids: selectedPenerimaanIds
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.details && data.details.length > 0) {
                tbody.innerHTML = '';
                let rowIndex = 0;
                
                // Group by po_detail untuk merge rows dengan item yang sama
                const groupedByPoDetail = {};
                data.details.forEach(item => {
                    if (!groupedByPoDetail[item.po_detail_id]) {
                        groupedByPoDetail[item.po_detail_id] = [];
                    }
                    groupedByPoDetail[item.po_detail_id].push(item);
                });

                Object.keys(groupedByPoDetail).forEach(poDetailId => {
                    const items = groupedByPoDetail[poDetailId];
                    const firstItem = items[0];
                    const totalQtyAvailable = items.reduce((sum, item) => sum + parseFloat(item.qty_available || 0), 0);

                    tbody.innerHTML += `
                        <tr>
                            <td>
                                ${firstItem.barang_nama}
                                <input type="hidden" name="items[${rowIndex}][po_detail_id]" value="${firstItem.po_detail_id}">
                                <input type="hidden" name="items[${rowIndex}][kode_item]" value="${firstItem.barang_id}">
                                <input type="hidden" name="items[${rowIndex}][uraian]" value="${firstItem.barang_nama}">
                                <input type="hidden" name="items[${rowIndex}][uom]" value="${firstItem.satuan}">
                            </td>
                            <td>
                                <small>${items.map(item => item.no_penerimaan).join(', ')}</small>
                            </td>
                            <td>${totalQtyAvailable}</td>
                            <td>
                                <input type="number" name="items[${rowIndex}][qty]" value="${totalQtyAvailable}" 
                                       max="${totalQtyAvailable}" min="0" step="0.01" class="form-control item-qty" required>
                            </td>
                            <td>
                                Rp ${parseInt(firstItem.harga).toLocaleString('id-ID')}
                                <input type="hidden" name="items[${rowIndex}][harga]" value="${firstItem.harga}">
                            </td>
                            <td>
                                <span class="total">Rp ${(totalQtyAvailable * firstItem.harga).toLocaleString('id-ID')}</span>
                            </td>
                        </tr>`;
                    rowIndex++;
                });

                container.style.display = 'block';
                updateTotalCalculation();
            } else {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center">Tidak ada item yang belum difaktur dari penerimaan terpilih</td></tr>';
                container.style.display = 'block';
            }
        })
        .catch(err => {
            console.error('Error loading details:', err);
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Error memuat data</td></tr>';
        });
    }

    // Update total calculation
    function updateTotalCalculation() {
        document.querySelectorAll('.item-qty').forEach(input => {
            input.addEventListener('change', function() {
                const row = this.closest('tr');
                const harga = parseFloat(row.querySelector('input[name*="[harga]"]').value);
                const qty = parseFloat(this.value || 0);
                const total = (qty * harga).toLocaleString('id-ID');
                row.querySelector('.total').textContent = `Rp ${total}`;
            });
        });
    }
</script>
@endpush

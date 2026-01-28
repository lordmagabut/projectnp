@extends('layout.master')

@push('plugin-styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
{{-- Font Awesome untuk ikon --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
{{-- Animate.css untuk animasi (opsional) --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
<style>
    /* Kustomisasi tambahan untuk tampilan */
    .card {
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        border: none;
    }
    .card-header {
        background-color: #f8f9fa; /* Warna latar belakang header kartu */
        border-bottom: 1px solid #e9ecef;
        border-top-left-radius: 12px;
        border-top-right-radius: 12px;
        padding: 1.25rem 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .form-label {
        font-weight: 600;
        color: #495057;
    }
    .form-control, .form-select {
        border-radius: 8px;
        border: 1px solid #ced4da;
        padding: 0.75rem 1rem;
    }
    .form-control:focus, .form-select:focus {
        border-color: #80bdff;
        box-shadow: 0 0 0 0.25rem rgba(0, 123, 255, 0.25);
    }
    .btn {
        border-radius: 8px;
        padding: 0.75rem 1.25rem;
        font-weight: 600;
        transition: all 0.2s ease-in-out;
    }
    .btn-primary {
        background-color: #007bff;
        border-color: #007bff;
    }
    .btn-primary:hover {
        background-color: #0056b3;
        border-color: #0056b3;
    }
    .btn-secondary {
        background-color: #6c757d;
        border-color: #6c757d;
    }
    .btn-secondary:hover {
        background-color: #5a6268;
        border-color: #545b62;
    }
    .btn-success {
        background-color: #28a745;
        border-color: #28a745;
    }
    .btn-success:hover {
        background-color: #218838;
        border-color: #1e7e34;
    }
    .alert {
        border-radius: 8px;
        display: flex;
        align-items: center;
        padding: 1rem 1.25rem;
        margin-bottom: 1.5rem;
    }
    .alert .fa-solid {
        margin-right: 10px;
        font-size: 1.25rem;
    }
    .table thead th {
        background-color: #e9ecef;
        color: #495057;
        font-weight: 600;
        border-bottom: 2px solid #dee2e6;
    }
    .table tbody tr:hover {
        background-color: #f2f2f2;
    }
</style>
@endpush

@section('content')
<div class="card animate__animated animate__fadeInDown">
    <div class="card-header">
        <h4 class="card-title mb-0"><i class="fas fa-calculator me-2"></i> Tambah Analisa Harga Satuan Pekerjaan</h4>
        <a href="{{ route('ahsp.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill">
            <i class="fas fa-arrow-left me-1"></i> Kembali ke Daftar
        </a>
    </div>
    <div class="card-body">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show animate__animated animate__fadeIn" role="alert">
                <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show animate__animated animate__fadeIn" role="alert">
                <i class="fas fa-times-circle me-2"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <form action="{{ route('ahsp.store') }}" method="POST" id="ahsp-form">
            @csrf

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Kode Pekerjaan (Auto-generated)</label>
                    @php
                        $kodeValue = $nextKode ?? 'AHSP-00001';
                    @endphp
                    <input type="text" class="form-control" value="{{ $kodeValue }}" readonly style="background-color: #e9ecef; font-weight: 600; color: #0d6efd;">
                    <small class="text-muted d-block mt-1">Kode akan di-assign otomatis dengan format AHSP-nnnnn</small>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Nama Pekerjaan <span class="text-danger">*</span></label>
                    <input type="text" name="nama_pekerjaan" class="form-control @error('nama_pekerjaan') is-invalid @enderror" value="{{ old('nama_pekerjaan') }}" required>
                    @error('nama_pekerjaan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-2">
                    <label class="form-label">Satuan <span class="text-danger">*</span></label>
                    <input type="text" name="satuan" class="form-control @error('satuan') is-invalid @enderror" value="{{ old('satuan') }}" required>
                    @error('satuan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-2">
                    <label class="form-label">Kategori <span class="text-danger">*</span></label>
                    <select name="kategori_id" class="form-select @error('kategori_id') is-invalid @enderror">
                        <option value="">- Pilih -</option>
                        @foreach($kategoris as $k)
                            <option value="{{ $k->id }}" {{ old('kategori_id') == $k->id ? 'selected' : '' }}>{{ $k->nama }}</option>
                        @endforeach
                    </select>
                    @error('kategori_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            <hr class="my-4">

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="m-0"><i class="fas fa-list-alt me-2"></i> Komponen Material / Upah</h6>
                <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="location.reload()" title="Reload halaman untuk melihat data material/jasa terbaru">
                    <i class="fas fa-sync-alt me-1"></i> Refresh Dropdown
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered" id="item-table">
                    <thead>
                        <tr>
                            <th style="width: 12%">Tipe</th>
                            <th style="width: 25%">Item</th>
                            <th style="width: 8%">Satuan</th>
                            <th style="width: 10%">Koefisien</th>
                            <th style="width: 12%" class="text-end">Harga Satuan</th>
                            <th style="width: 10%" class="text-end">Subtotal</th>
                            <th style="width: 8%" class="text-center">Diskon %</th>
                            <th style="width: 8%" class="text-center">PPN %</th>
                            <th style="width: 12%" class="text-end">Final</th>
                            <th style="width: 5%"></th>
                        </tr>
                    </thead>
                    <tbody id="item-body">
                        {{-- Baris akan ditambahkan via JS --}}
                    </tbody>
                </table>
            </div>

            <button type="button" class="btn btn-sm btn-success mb-3 rounded-pill" onclick="addItemRow()">
                <i class="fas fa-plus me-1"></i> Tambah Baris
            </button>

            <div class="row justify-content-end mt-4">
                <div class="col-md-6">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong>Total Harga Sebenarnya:</strong>
                        <span id="total-harga-sebenarnya" class="fw-bold fs-5">Rp 0</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <strong>Total Harga Pembulatan:</strong>
                        <span id="total-harga-pembulatan" class="fw-bold fs-5 text-primary">Rp 0</span>
                    </div>
                </div>
            </div>


            {{-- Input Hidden untuk menyimpan total pembulatan --}}
            <input type="hidden" name="total_harga_pembulatan" id="hidden-total-harga-pembulatan">

            <div class="d-flex justify-content-end mt-4">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="fas fa-save me-1"></i> Simpan Analisa
                </button>
                <a href="{{ route('ahsp.index') }}" class="btn btn-secondary">
                    <i class="fas fa-times-circle me-1"></i> Batal
                </a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('custom-scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    const materials = @json($materials);
    const upahs = @json($upahs);

    window.formatRupiah = function(value) {
        return 'Rp ' + Number(value).toLocaleString('id-ID');
    }

    window.roundUpToNearestThousand = function(value) {
        return Math.ceil(value / 1000) * 1000;
    }

    window.route_hsd_material_create = '{{ route('hsd-material.create') }}';
    window.route_hsd_upah_create = '{{ route('hsd-upah.create') }}';

    window.initSelect2 = function(container) {
        $(container).find('.item-dropdown').select2({
            placeholder: 'Pilih item',
            allowClear: true,
            width: '100%',
            dropdownParent: $(container)
        });
    }

    window.addItemRow = function() {
        const tbody = document.getElementById('item-body');
        const rowIndex = tbody.children.length;
        const row = document.createElement('tr');

        row.innerHTML = `
            <td>
                <select name="items[${rowIndex}][tipe]" class="form-select tipe-select">
                    <option value="material">Material</option>
                    <option value="upah">Upah</option>
                </select>
            </td>
            <td>
                <div class="d-flex align-items-center gap-1">
                    <select name="items[${rowIndex}][referensi_id]" class="form-select item-dropdown" style="flex:1;min-width:120px">
                        ${materials.map(m => `<option value="${m.id}" data-harga="${m.harga_satuan}" data-satuan="${m.satuan}">${m.nama}</option>`).join('')}
                    </select>
                    <a href="${window.route_hsd_material_create}" target="_blank" class="btn btn-sm btn-success px-2 py-1" title="Tambah Material Baru"><i class="fas fa-plus"></i> M</a>
                    <a href="${window.route_hsd_upah_create}" target="_blank" class="btn btn-sm btn-primary px-2 py-1" title="Tambah Jasa/Upah Baru"><i class="fas fa-plus"></i> J</a>
                </div>
            </td>
            <td class="satuan text-center">-</td>
            <td>
                <input type="number" name="items[${rowIndex}][koefisien]" class="form-control koefisien-input" step="0.0001" value="0">
            </td>
            <td class="harga-satuan text-end">Rp 0</td>
            <td class="subtotal text-end">Rp 0</td>
            <td>
                <input type="number" name="items[${rowIndex}][diskon_persen]" class="form-control form-control-sm diskon-input text-center" step="0.01" value="0" min="0" max="100" placeholder="0">
            </td>
            <td>
                <input type="number" name="items[${rowIndex}][ppn_persen]" class="form-control form-control-sm ppn-input text-center" step="0.01" value="0" min="0" max="100" placeholder="0">
            </td>
            <td class="subtotal-final text-end">Rp 0</td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-danger rounded" onclick="removeRow(this)">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </td>
        `;

        tbody.appendChild(row);
        feather.replace(); // Refresh feather icons
        window.initSelect2(row); // Inisialisasi Select2 untuk baris baru
        // Panggil updateSubtotal untuk baris baru dengan koefisien inputnya
        window.updateSubtotal(row.querySelector('.koefisien-input'));
    }

    $(document).ready(function() {
        const itemTableBody = $('#item-body');

        // Event listener untuk perubahan dropdown Tipe (Material/Upah)
        itemTableBody.on('change', '.tipe-select', function() {
            const select = this;
            const row = $(select).closest('tr');
            const itemDropdown = row.find('.item-dropdown');
            const tipe = select.value;

            const options = (tipe === 'material' ? materials : upahs)
                .map(item => `<option value="${item.id}" data-harga="${item.harga_satuan}" data-satuan="${item.satuan}">${item.nama || item.jenis_pekerja}</option>`)
                .join('');

            if (itemDropdown.data('select2')) {
                itemDropdown.select2('destroy');
            }
            itemDropdown.html(options);
            window.initSelect2(row);
            window.updateSubtotal(row.find('.koefisien-input')[0]);
        });

        // Event listener untuk perubahan dropdown Item (Material/Upah)
        itemTableBody.on('change', '.item-dropdown', function() {
            const select = this;
            const row = $(select).closest('tr');
            const selected = select.selectedOptions[0];
            const harga = parseFloat(selected?.dataset?.harga || 0);
            const satuan = selected?.dataset?.satuan || '-';

            row.find('.satuan').text(satuan);
            row.find('.harga-satuan').text(window.formatRupiah(harga));

            window.updateSubtotal(row.find('.koefisien-input')[0]);
        });

        // Event listener untuk perubahan input Koefisien
        itemTableBody.on('input', '.koefisien-input', function() {
            window.updateSubtotal(this);
        });

        // Event listener untuk perubahan Diskon
        itemTableBody.on('input', '.diskon-input', function() {
            window.updateSubtotal($(this).closest('tr').find('.koefisien-input')[0]);
        });

        // Event listener untuk perubahan PPN
        itemTableBody.on('input', '.ppn-input', function() {
            window.updateSubtotal($(this).closest('tr').find('.koefisien-input')[0]);
        });

        // Panggil addItemRow() saat DOM siap untuk menginisialisasi satu baris awal
        window.addItemRow();
    });

    window.updateSubtotal = function(input) {
        const row = $(input).closest('tr');
        const selected = row.find('.item-dropdown')[0].selectedOptions[0];
        const harga = parseFloat(selected?.dataset?.harga || 0);
        const satuan = selected?.dataset?.satuan || '-';
        const koef = parseFloat(input.value || 0);
        const subtotal = harga * koef;
        
        // Ambil diskon dan ppn
        const diskonPersen = parseFloat(row.find('.diskon-input').val() || 0);
        const ppnPersen = parseFloat(row.find('.ppn-input').val() || 0);
        
        // Hitung diskon nominal
        const diskonNominal = subtotal * (diskonPersen / 100);
        
        // Hitung subtotal setelah diskon
        const subtotalSetelahDiskon = subtotal - diskonNominal;
        
        // Hitung ppn nominal
        const ppnNominal = subtotalSetelahDiskon * (ppnPersen / 100);
        
        // Hitung final
        const subtotalFinal = subtotalSetelahDiskon + ppnNominal;

        row.find('.satuan').text(satuan);
        row.find('.harga-satuan').text(window.formatRupiah(harga));
        row.find('.subtotal').text(window.formatRupiah(subtotal));
        row.find('.subtotal-final').text(window.formatRupiah(subtotalFinal));
        window.updateTotalHarga();
    }

    window.updateTotalHarga = function() {
        let totalSebenarnya = 0;
        document.querySelectorAll('#item-body tr').forEach(row => {
            const subtotalFinalText = row.querySelector('.subtotal-final').innerText;
            const subtotalFinalValue = parseFloat(subtotalFinalText.replace('Rp ', '').replace(/\./g, '').replace(/,/g, '.') || 0);
            totalSebenarnya += subtotalFinalValue;
        });

        const totalPembulatan = window.roundUpToNearestThousand(totalSebenarnya);

        document.getElementById('total-harga-sebenarnya').innerText = window.formatRupiah(totalSebenarnya);
        document.getElementById('total-harga-pembulatan').innerText = window.formatRupiah(totalPembulatan);

        document.getElementById('hidden-total-harga-pembulatan').value = totalPembulatan;
    }

    window.removeRow = function(button) {
        $(button).closest('tr').remove();
        window.updateTotalHarga();
    }
</script>
@endpush
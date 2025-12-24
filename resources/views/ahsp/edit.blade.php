@extends('layout.master')

@push('plugin-styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
<style>
    .card{border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,.08);border:none}
    .card-header{background:#f8f9fa;border-bottom:1px solid #e9ecef;border-top-left-radius:12px;border-top-right-radius:12px;padding:1.25rem 1.5rem;display:flex;justify-content:space-between;align-items:center}
    .form-label{font-weight:600;color:#495057}
    .form-control,.form-select{border-radius:8px;border:1px solid #ced4da;padding:.75rem 1rem}
    .form-control:focus,.form-select:focus{border-color:#80bdff;box-shadow:0 0 0 .25rem rgba(0,123,255,.25)}
    .btn{border-radius:8px;padding:.75rem 1.25rem;font-weight:600;transition:all .2s}
    .btn-primary{background:#007bff;border-color:#007bff}
    .btn-primary:hover{background:#0056b3;border-color:#0056b3}
    .btn-secondary{background:#6c757d;border-color:#6c757d}
    .btn-secondary:hover{background:#5a6268;border-color:#545b62}
    .btn-success{background:#28a745;border-color:#28a745}
    .btn-success:hover{background:#218838;border-color:#1e7e34}
    .alert{border-radius:8px;display:flex;align-items:center;padding:1rem 1.25rem;margin-bottom:1.5rem}
    .alert .fa-solid{margin-right:10px;font-size:1.25rem}
    .table thead th{background:#e9ecef;color:#495057;font-weight:600;border-bottom:2px solid #dee2e6}
    .table tbody tr:hover{background:#f2f2f2}
</style>
@endpush

@section('content')
<div class="card animate__animated animate__fadeInDown">
    <div class="card-header">
        <div class="d-flex align-items-center gap-2">
            <h4 class="card-title mb-0"><i class="fas fa-calculator me-2"></i> Edit Analisa Harga Satuan Pekerjaan</h4>
            {{-- BADGE STATUS --}}
            @php $isDraft = strtolower($ahsp->status ?? 'draft') === 'draft'; @endphp
            <span class="badge {{ $isDraft ? 'bg-warning text-dark' : 'bg-success' }} rounded-pill">
                Status: {{ strtoupper($ahsp->status ?? 'draft') }}
            </span>
        </div>
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

        {{-- INFO: Kalkulasi Ulang hanya saat DRAFT --}}
        <div class="alert {{ $isDraft ? 'alert-info' : 'alert-secondary' }} animate__animated animate__fadeIn mb-4">
            <i class="fa-solid fa-info-circle"></i>
            @if($isDraft)
                Mode <strong>Draft</strong>: Anda dapat menekan <em>Kalkulasi Ulang</em> untuk menarik harga terbaru Material/Upah, menghitung ulang subtotal & total. Klik <strong>Perbarui Analisa</strong> untuk menyimpan.
            @else
                AHSP tidak dalam status <strong>Draft</strong>. Tombol <em>Kalkulasi Ulang</em> dinonaktifkan.
            @endif
        </div>

        <form action="{{ route('ahsp.update', $ahsp->id) }}" method="POST" id="ahsp-form" data-is-draft="{{ $isDraft ? '1' : '0' }}">
            @csrf
            @method('PUT')

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Kode Pekerjaan <span class="text-danger">*</span></label>
                    <input type="text" name="kode_pekerjaan" class="form-control @error('kode_pekerjaan') is-invalid @enderror" value="{{ old('kode_pekerjaan', $ahsp->kode_pekerjaan) }}" required>
                    @error('kode_pekerjaan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Nama Pekerjaan <span class="text-danger">*</span></label>
                    <input type="text" name="nama_pekerjaan" class="form-control @error('nama_pekerjaan') is-invalid @enderror" value="{{ old('nama_pekerjaan', $ahsp->nama_pekerjaan) }}" required>
                    @error('nama_pekerjaan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-2">
                    <label class="form-label">Satuan <span class="text-danger">*</span></label>
                    <input type="text" name="satuan" class="form-control @error('satuan') is-invalid @enderror" value="{{ old('satuan', $ahsp->satuan) }}" required>
                    @error('satuan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-2">
                    <label class="form-label">Kategori <span class="text-danger">*</span></label>
                    <select name="kategori_id" class="form-select @error('kategori_id') is-invalid @enderror">
                        <option value="">- Pilih -</option>
                        @foreach($kategoris as $k)
                            <option value="{{ $k->id }}" {{ old('kategori_id', $ahsp->kategori_id) == $k->id ? 'selected' : '' }}>{{ $k->nama }}</option>
                        @endforeach
                    </select>
                    @error('kategori_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            <hr class="my-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h6 class="m-0"><i class="fas fa-list-alt me-2"></i> Komponen Material / Upah</h6>

                <div class="d-flex gap-2">
                    {{-- TOMBOL KALKULASI ULANG (aktif hanya draft) --}}
                    <button type="button" id="btn-recalc" class="btn btn-sm {{ $isDraft ? 'btn-warning' : 'btn-outline-secondary' }} rounded-pill"
                            {{ $isDraft ? '' : 'disabled' }}>
                        <i class="fas fa-rotate me-1"></i> Kalkulasi Ulang (Draft)
                    </button>

                    {{-- TOMBOL REFRESH DROPDOWN --}}
                    <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="location.reload()" title="Reload halaman untuk melihat data material/jasa terbaru">
                        <i class="fas fa-sync-alt me-1"></i> Refresh Dropdown
                    </button>
                </div>
            </div>

            <div class="table-responsive mt-3">
                <table class="table table-bordered" id="item-table">
                    <thead>
                        <tr>
                            <th style="width: 15%">Tipe</th>
                            <th style="width: 35%">Item</th>
                            <th style="width: 10%">Satuan</th>
                            <th style="width: 15%">Koefisien</th>
                            <th style="width: 15%" class="text-end">Harga Satuan</th>
                            <th style="width: 15%" class="text-end">Subtotal</th>
                            <th style="width: 5%"></th>
                        </tr>
                    </thead>
                    <tbody id="item-body">
                        @foreach($ahsp->details as $i => $d)
                        <tr>
                            <td>
                                <select name="items[{{ $i }}][tipe]" class="form-select tipe-select">
                                    <option value="material" {{ $d->tipe == 'material' ? 'selected' : '' }}>Material</option>
                                    <option value="upah" {{ $d->tipe == 'upah' ? 'selected' : '' }}>Upah</option>
                                </select>
                            </td>
                            <td class="d-flex align-items-center gap-1">
                                <select name="items[{{ $i }}][referensi_id]" class="form-select item-dropdown" style="width:85%">
                                    @php
                                        $list = $d->tipe === 'material' ? $materials : $upahs;
                                    @endphp
                                    @foreach($list as $item)
                                        <option value="{{ $item->id }}"
                                            data-harga="{{ $item->harga_satuan }}"
                                            data-satuan="{{ $item->satuan }}"
                                            {{ $item->id == $d->referensi_id ? 'selected' : '' }}>
                                            {{ $item->nama ?? $item->jenis_pekerja }}
                                        </option>
                                    @endforeach
                                </select>
                                <a href="{{ route('hsd-material.create') }}" target="_blank" class="btn btn-sm btn-success px-2 py-1" title="Tambah Material Baru" onclick="window.saveFormToLocalStorage()">
                                    <i class="fas fa-plus"></i> M
                                </a>
                                <a href="{{ route('hsd-upah.create') }}" target="_blank" class="btn btn-sm btn-primary px-2 py-1" title="Tambah Jasa/Upah Baru" onclick="window.saveFormToLocalStorage()">
                                    <i class="fas fa-plus"></i> J
                                </a>
                            </td>
                            <td class="satuan text-center">
                                @php
                                    $currentSatuan = '-';
                                    if ($d->tipe === 'material') {
                                        $foundItem = $materials->firstWhere('id', $d->referensi_id);
                                        $currentSatuan = $foundItem->satuan ?? '-';
                                    } else {
                                        $foundItem = $upahs->firstWhere('id', $d->referensi_id);
                                        $currentSatuan = $foundItem->satuan ?? '-';
                                    }
                                @endphp
                                {{ $currentSatuan }}
                            </td>
                            <td>
                                <input type="number" name="items[{{ $i }}][koefisien]" class="form-control koefisien-input"
                                    step="0.0001" value="{{ old('items.'.$i.'.koefisien', $d->koefisien) }}">
                            </td>
                            <td class="harga-satuan text-end">
                                Rp {{ number_format(old('items.'.$i.'.harga_satuan', $d->harga_satuan), 0, ',', '.') }}
                            </td>
                            <td class="subtotal text-end">
                                Rp {{ number_format(old('items.'.$i.'.subtotal', $d->subtotal), 0, ',', '.') }}
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-danger rounded" onclick="removeRow(this)">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </td>

                            {{-- Hidden inputs untuk commit ke backend --}}
                            <input type="hidden" name="items[{{ $i }}][harga_satuan_detail]"
                                   value="{{ old('items.'.$i.'.harga_satuan_detail', $d->harga_satuan) }}">
                            <input type="hidden" name="items[{{ $i }}][subtotal_detail]"
                                   value="{{ old('items.'.$i.'.subtotal_detail', $d->subtotal) }}">
                        </tr>
                        @endforeach
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
                        <span id="total-harga-sebenarnya" class="fw-bold fs-5">
                            Rp {{ number_format($ahsp->total_harga ?? 0, 0, ',', '.') }}
                        </span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <strong>Total Harga Pembulatan:</strong>
                        <span id="total-harga-pembulatan" class="fw-bold fs-5 text-primary">
                            Rp {{ number_format($ahsp->total_harga_pembulatan ?? 0, 0, ',', '.') }}
                        </span>
                    </div>
                </div>
            </div>

            <input type="hidden" name="total_harga_pembulatan" id="hidden-total-harga-pembulatan"
                   value="{{ old('total_harga_pembulatan', $ahsp->total_harga_pembulatan) }}">

            <div class="d-flex justify-content-end mt-4">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="fas fa-save me-1"></i> Perbarui Analisa
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
    const upahs     = @json($upahs);

    // Buat peta cepat id->data untuk recalc
    const materialMap = Object.fromEntries((materials || []).map(m => [String(m.id), m]));
    const upahMap     = Object.fromEntries((upahs || []).map(u => [String(u.id), u]));

    window.route_hsd_material_create = '{{ route('hsd-material.create') }}';
    window.route_hsd_upah_create = '{{ route('hsd-upah.create') }}';

    // Simpan data form + baris items ke localStorage sebelum reload
    window.saveFormToLocalStorage = function() {
        const formData = new FormData(document.getElementById('ahsp-form'));
        const data = Object.fromEntries(formData);
        
        // Simpan juga data baris items dari DOM
        const items = [];
        document.querySelectorAll('#item-body tr').forEach((row, idx) => {
            const tipeEl = row.querySelector('.tipe-select');
            const itemDropdownEl = row.querySelector('.item-dropdown');
            const koefEl = row.querySelector('.koefisien-input');
            
            if (tipeEl && itemDropdownEl && koefEl) {
                items.push({
                    tipe: tipeEl.value,
                    referensi_id: itemDropdownEl.value,
                    koefisien: koefEl.value,
                    satuan: row.querySelector('.satuan')?.textContent || '-',
                    harga_satuan: row.querySelector('.harga-satuan')?.textContent || 'Rp 0',
                    subtotal: row.querySelector('.subtotal')?.textContent || 'Rp 0'
                });
            }
        });
        
        data.items = items;
        localStorage.setItem('ahsp-form-backup', JSON.stringify(data));
    }

    // Restore data form + baris items dari localStorage
    window.restoreFormFromLocalStorage = function() {
        const backup = localStorage.getItem('ahsp-form-backup');
        if (backup) {
            const data = JSON.parse(backup);
            const items = data.items || [];
            delete data.items;
            
            // Restore form fields
            Object.keys(data).forEach(key => {
                const el = document.querySelector(`[name="${key}"]`);
                if (el) el.value = data[key];
            });
            
            // Restore baris items
            if (items.length > 0) {
                const tbody = document.getElementById('item-body');
                tbody.innerHTML = '';
                
                items.forEach((item, idx) => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>
                            <select name="items[${idx}][tipe]" class="form-select tipe-select">
                                <option value="material" ${item.tipe === 'material' ? 'selected' : ''}>Material</option>
                                <option value="upah" ${item.tipe === 'upah' ? 'selected' : ''}>Upah</option>
                            </select>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-1">
                                <select name="items[${idx}][referensi_id]" class="form-select item-dropdown" style="flex:1;min-width:120px">
                                    ${(item.tipe === 'material' ? materials : upahs).map(m => `<option value="${m.id}" data-harga="${m.harga_satuan}" data-satuan="${m.satuan}" ${m.id == item.referensi_id ? 'selected' : ''}>${m.nama || m.jenis_pekerja}</option>`).join('')}
                                </select>
                                <a href="${window.route_hsd_material_create}" target="_blank" class="btn btn-sm btn-success px-2 py-1" title="Tambah Material Baru" onclick="window.saveFormToLocalStorage()"><i class="fas fa-plus"></i> M</a>
                                <a href="${window.route_hsd_upah_create}" target="_blank" class="btn btn-sm btn-primary px-2 py-1" title="Tambah Jasa/Upah Baru" onclick="window.saveFormToLocalStorage()"><i class="fas fa-plus"></i> J</a>
                            </div>
                        </td>
                        <td class="satuan text-center">${item.satuan}</td>
                        <td>
                            <input type="number" name="items[${idx}][koefisien]" class="form-control koefisien-input" step="0.0001" value="${item.koefisien}">
                        </td>
                        <td class="harga-satuan text-end">${item.harga_satuan}</td>
                        <td class="subtotal text-end">${item.subtotal}</td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-danger rounded" onclick="removeRow(this)">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </td>
                        <input type="hidden" name="items[${idx}][harga_satuan_detail]" value="${item.harga_satuan_detail || 0}">
                        <input type="hidden" name="items[${idx}][subtotal_detail]" value="${item.subtotal_detail || 0}">
                    `;
                    tbody.appendChild(row);
                    window.initSelect2(row);
                });
                
                window.updateTotalHarga();
            }
            
            localStorage.removeItem('ahsp-form-backup');
        }
    }

    // Saat halaman load, restore form data jika ada di localStorage
    window.addEventListener('load', function() {
        window.restoreFormFromLocalStorage();
        
        // Auto-save setiap 2 detik saat ada perubahan
        setInterval(() => window.saveFormToLocalStorage(), 2000);
        
        // Save juga sebelum reload/close tab
        window.addEventListener('beforeunload', () => {
            window.saveFormToLocalStorage();
        });
    });

    window.formatRupiah = function(value){ return 'Rp ' + Number(value||0).toLocaleString('id-ID'); }
    window.roundUpToNearestThousand = function(value){ return Math.ceil((value||0) / 1000) * 1000; }

    window.initSelect2 = function(container){
        $(container).find('.item-dropdown').select2({
            placeholder: 'Pilih item',
            allowClear: true,
            width: '100%',
            dropdownParent: $(container)
        });
    }

    window.addItemRow = function(){
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
                <select name="items[${rowIndex}][referensi_id]" class="form-select item-dropdown">
                    ${(materials||[]).map(m => `<option value="${m.id}" data-harga="${m.harga_satuan}" data-satuan="${m.satuan}">${m.nama}</option>`).join('')}
                </select>
            </td>
            <td class="satuan text-center">-</td>
            <td>
                <input type="number" name="items[${rowIndex}][koefisien]" class="form-control koefisien-input" step="0.0001" value="0">
            </td>
            <td class="harga-satuan text-end">Rp 0</td>
            <td class="subtotal text-end">Rp 0</td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-danger rounded" onclick="removeRow(this)">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </td>
            <input type="hidden" name="items[${rowIndex}][harga_satuan_detail]" value="0">
            <input type="hidden" name="items[${rowIndex}][subtotal_detail]" value="0">
        `;

        tbody.appendChild(row);
        feather.replace?.();
        window.initSelect2(row);
        window.updateSubtotal(row.querySelector('.koefisien-input'));
    }

    $(document).ready(function(){
        const itemTableBody = $('#item-body');

        // init select2 existing rows
        itemTableBody.find('.item-dropdown').each(function(){ window.initSelect2($(this).closest('tr')); });

        // change tipe -> refresh options
        itemTableBody.on('change', '.tipe-select', function(){
            const row = $(this).closest('tr');
            const itemDropdown = row.find('.item-dropdown');
            const tipe = this.value;

            const list = (tipe === 'material' ? materials : upahs) || [];
            const options = list.map(item => `<option value="${item.id}" data-harga="${item.harga_satuan}" data-satuan="${item.satuan}">${item.nama || item.jenis_pekerja}</option>`).join('');

            if (itemDropdown.data('select2')) itemDropdown.select2('destroy');
            itemDropdown.html(options);
            window.initSelect2(row);
            window.updateSubtotal(row.find('.koefisien-input')[0]);
        });

        // change item -> update harga/satuan/subtotal
        itemTableBody.on('change', '.item-dropdown', function(){
            const row = $(this).closest('tr');
            const selected = this.selectedOptions[0];
            const harga = parseFloat(selected?.dataset?.harga || 0);
            const satuan = selected?.dataset?.satuan || '-';

            row.find('.satuan').text(satuan);
            row.find('.harga-satuan').text(window.formatRupiah(harga));
            window.updateSubtotal(row.find('.koefisien-input')[0]);
        });

        // input koef -> calc subtotal
        itemTableBody.on('input', '.koefisien-input', function(){ window.updateSubtotal(this); });

        // first init totals
        window.updateTotalHarga();

        // ====== KALKULASI ULANG (hanya draft) ======
        $('#btn-recalc').on('click', function(){
            const isDraft = $('#ahsp-form').data('is-draft') === 1 || $('#ahsp-form').data('is-draft') === '1';
            if(!isDraft) return;

            // loop rows: tarik harga terbaru by referensi_id dan tipe
            $('#item-body tr').each(function(){
                const row = $(this);
                const tipe = row.find('.tipe-select').val();
                const sel = row.find('.item-dropdown')[0];
                const referensiId = sel?.value ? String(sel.value) : null;
                if(!referensiId) return;

                let latest = null;
                if(tipe === 'material') latest = materialMap[referensiId] || null;
                else latest = upahMap[referensiId] || null;

                const koefInput = row.find('.koefisien-input')[0];
                const koef = parseFloat(koefInput?.value || 0);

                const hargaBaru = parseFloat(latest?.harga_satuan || 0);
                const satuanBaru = latest?.satuan || '-';
                const subtotalBaru = hargaBaru * koef;

                // tampilkan
                row.find('.satuan').text(satuanBaru);
                row.find('.harga-satuan').text(window.formatRupiah(hargaBaru));
                row.find('.subtotal').text(window.formatRupiah(subtotalBaru));

                // update hidden fields agar tersimpan saat submit
                row.find('input[name$="[harga_satuan_detail]"]').val(hargaBaru);
                row.find('input[name$="[subtotal_detail]"]').val(subtotalBaru);
            });

            window.updateTotalHarga();
        });
    });

    window.updateSubtotal = function(input){
        const row = $(input).closest('tr');
        const selected = row.find('.item-dropdown')[0]?.selectedOptions?.[0];
        const harga = parseFloat(selected?.dataset?.harga || 0);
        const satuan = selected?.dataset?.satuan || '-';
        const koef  = parseFloat(input.value || 0);
        const subtotal = harga * koef;

        row.find('.satuan').text(satuan);
        row.find('.harga-satuan').text(window.formatRupiah(harga));
        row.find('.subtotal').text(window.formatRupiah(subtotal));

        // sinkronkan hidden fields
        row.find('input[name$="[harga_satuan_detail]"]').val(harga);
        row.find('input[name$="[subtotal_detail]"]').val(subtotal);

        window.updateTotalHarga();
    }

    window.updateTotalHarga = function(){
        let totalSebenarnya = 0;
        document.querySelectorAll('#item-body tr').forEach(row => {
            const txt = row.querySelector('.subtotal')?.innerText || 'Rp 0';
            const val = parseFloat(txt.replace('Rp ','').replace(/\./g,'').replace(/,/g,'.')) || 0;
            totalSebenarnya += val;
        });
        const totalPembulatan = window.roundUpToNearestThousand(totalSebenarnya);

        document.getElementById('total-harga-sebenarnya').innerText  = window.formatRupiah(totalSebenarnya);
        document.getElementById('total-harga-pembulatan').innerText  = window.formatRupiah(totalPembulatan);
        document.getElementById('hidden-total-harga-pembulatan').value = totalPembulatan;
    }

    window.removeRow = function(button){
        $(button).closest('tr').remove();
        window.updateTotalHarga();
    }
</script>
@endpush

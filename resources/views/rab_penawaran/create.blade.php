@extends('layout.master')

@push('plugin-styles')
    {{-- Pastikan hanya ada satu link untuk CSS Select2 di sini --}}
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    {{-- Tambahkan CSS kustom Anda di sini --}}
@endpush

@section('content')
<div class="card shadow-sm animate__animated animate__fadeIn">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h4 class="m-0 d-flex align-items-center"><i data-feather="file-text" class="me-2"></i> Buat Penawaran Baru</h4>
        <a href="{{ route('proyek.show', $proyek->id) }}" class="btn btn-light btn-sm d-inline-flex align-items-center">
            <i data-feather="arrow-left" class="me-1"></i> Kembali ke Proyek
        </a>
    </div>
    <div class="card-body p-3 p-md-4">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <form action="{{ route('proyek.penawaran.store', $proyek->id) }}" method="POST">
            @csrf

            <div class="mb-4 p-4 border rounded bg-light">
                <h5 class="mb-3 text-primary">Informasi Penawaran</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="nama_penawaran" class="form-label">Nama Penawaran <span class="text-danger">*</span></label>
                        <input type="text" name="nama_penawaran" id="nama_penawaran" class="form-control @error('nama_penawaran') is-invalid @enderror" value="{{ old('nama_penawaran') }}" required>
                        @error('nama_penawaran') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="tanggal_penawaran" class="form-label">Tanggal Penawaran <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal_penawaran" id="tanggal_penawaran" class="form-control @error('tanggal_penawaran') is-invalid @enderror" value="{{ old('tanggal_penawaran', date('Y-m-d')) }}" required>
                        @error('tanggal_penawaran') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>

            {{-- Bagian Baru: Muat dari RAB Dasar yang Ada --}}
            <div class="mb-4 p-4 border rounded bg-light">
                <h5 class="mb-3 text-primary">Muat dari RAB Dasar yang Ada (Opsional)</h5>
                <div class="row g-3 align-items-end">
                    <div class="col-md-8">
                        <label for="load_rab_header_select" class="form-label">Pilih Header RAB Dasar Spesifik</label>
                        <select id="load_rab_header_select" class="form-select">
                            <option value="">-- Pilih RAB Dasar --</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="button" class="btn btn-info w-100 mb-2" id="load-rab-button">Muat Header Ini</button>
                        <button type="button" class="btn btn-primary w-100" id="load-all-rab-button">Muat Seluruh RAB Dasar Proyek Ini</button>
                    </div>
                </div>
                <small class="text-muted mt-2">Memuat RAB dasar akan mengisi form di bawah ini. Anda masih bisa menambah/mengedit item setelah dimuat.</small>
            </div>

            <h5 class="mt-4 mb-3 text-primary">Bagian-bagian Pekerjaan (Sections)</h5>
            <div id="sections-container">
                {{-- Sections akan ditambahkan di sini via JavaScript --}}
                {{-- Satu section default akan ditambahkan oleh JS jika tidak ada preload data --}}
            </div>
            <button type="button" class="btn btn-success mt-3" id="add-section">Tambah Bagian Pekerjaan</button>

            <hr class="my-4">

            <div class="mb-4 p-4 border rounded bg-light text-end">
                <h5 class="mb-3 text-primary">Ringkasan Penawaran</h5>
                <div class="row justify-content-end">
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Total Bruto:</span>
                            <span class="fw-bold text-success" id="total-bruto">Rp 0</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <label for="discount_percentage" class="form-label mb-0">Diskon (%)</label>
                            <input type="number" step="0.01" name="discount_percentage" id="discount_percentage" class="form-control w-25 text-end" value="{{ old('discount_percentage', 0) }}">
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Jumlah Diskon:</span>
                            <span class="fw-bold text-danger" id="discount-amount">Rp 0</span>
                        </div>
                        <div class="d-flex justify-content="between mb-2">
                            <span class="fs-5">Total Akhir Penawaran:</span>
                            <span class="fs-5 fw-bold text-primary" id="final-total">Rp 0</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end mt-4">
                <button type="submit" class="btn btn-primary btn-lg shadow-sm">
                    <i data-feather="save" class="me-1"></i> Simpan Penawaran
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('custom-scripts')
{{-- PENTING: jQuery harus dimuat PERTAMA --}}
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
{{-- PENTING: Select2 JS harus dimuat SETELAH jQuery dan SEBELUM skrip kustom Anda --}}
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://unpkg.com/feather-icons"></script>

<script>
    feather.replace();

    let sectionCounter = 0; // Untuk melacak indeks section secara global
    
    // Fungsi format Rupiah
    function formatRupiah(value) {
        return 'Rp ' + Number(value).toLocaleString('id-ID');
    }

    // Fungsi untuk menginisialisasi Select2 pada elemen baru
    function initSelect2(element, type, sectionId = null) {
        if (type === 'rab-header-select') {
            $(element).select2({
                placeholder: '-- Pilih Header RAB --',
                allowClear: true,
                width: '100%'
            });
        } else if (type === 'load-rab-header-select') {
            $(element).select2({
                placeholder: '-- Pilih RAB Dasar --',
                allowClear: true,
                width: '100%',
                ajax: {
                    url: '{{ route("proyek.penawaran.searchRabHeaders", $proyek->id) }}',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            search: params.term
                        };
                    },
                    processResults: function (data) {
                        return {
                            results: data.map(item => ({
                                id: item.id,
                                text: item.text,
                                area: item.area, // Tambahkan area dari header
                                spesifikasi: item.spesifikasi // Tambahkan spesifikasi dari header
                            }))
                        };
                    },
                    cache: true
                }
            });

            // Event listener untuk perubahan nilai Select2 pada load_rab_header_select
            $(element).on('select2:select', function (e) {
                const data = e.params.data;
                // Isi input area dan spesifikasi utama jika kosong
                if (!$('#area').val()) {
                    $('#area').val(data.area || '');
                }
                if (!$('#spesifikasi').val()) {
                    $('#spesifikasi').val(data.spesifikasi || '');
                }
            });

            $(element).on('select2:clear', function (e) {
                // Tidak perlu reset area/spesifikasi utama di sini, karena bisa jadi sudah diisi dari sumber lain
            });

        } else if (type === 'rab-detail-select') {
            $(element).select2({
                placeholder: '-- Pilih Detail RAB --',
                allowClear: true,
                width: '100%',
                ajax: {
                    url: '{{ route("proyek.penawaran.searchRabDetails", $proyek->id) }}',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            search: params.term,
                            rab_header_id: sectionId // Kirim ID header RAB dasar dari section ini
                        };
                    },
                    processResults: function (data) {
                        console.log('Data received from searchRabDetails (Select2):', data); // DEBUGGING: Log data yang diterima
                        return {
                            results: data.map(item => ({
                                id: item.id,
                                text: item.text,
                                harga_satuan: item.harga_satuan,
                                ahsp_id: item.ahsp_id,
                                area: item.area, // Sertakan area dari RabDetail
                                spesifikasi: item.spesifikasi // Sertakan spesifikasi dari RabDetail
                            }))
                        };
                    },
                    cache: true
                }
            });

            // Event listener untuk perubahan nilai Select2
            $(element).on('select2:select', function (e) {
                const data = e.params.data;
                const row = $(this).closest('tr');
                
                console.log('Selected RAB Detail data (Select2):', data); // DEBUGGING: Log data item yang dipilih

                const areaInput = row.find('input[name$="[area]"]');
                const spesifikasiTextarea = row.find('textarea[name$="[spesifikasi]"]');

                console.log('Area input element found (Select2):', areaInput.length > 0 ? areaInput[0] : 'Not found');
                console.log('Spesifikasi textarea element found (Select2):', spesifikasiTextarea.length > 0 ? spesifikasiTextarea[0] : 'Not found');

                // Set data ke input/span yang relevan
                row.find('input[name$="[kode]"]').val(data.text.split(' - ')[0]); // Ambil kode dari text
                row.find('input[name$="[deskripsi]"]').val(data.text.split(' - ')[1].split(' (')[0]); // Ambil deskripsi
                row.find('input[name$="[satuan]"]').val(data.text.match(/\((.*?)\)/)[1]); // Ambil satuan
                
                // Set data area dan spesifikasi ke kolom baru
                console.log('Attempting to set Area to (Select2):', data.area || '');
                if (areaInput.length > 0) {
                    areaInput[0].value = data.area || ''; // Set value directly on native DOM element
                    areaInput.trigger('input'); // Trigger input event
                    areaInput.trigger('change'); // Trigger change event
                    areaInput.focus(); // Try focusing to force re-render
                    areaInput.blur(); // Then blur
                }

                console.log('Attempting to set Spesifikasi to (Select2):', data.spesifikasi || '');
                if (spesifikasiTextarea.length > 0) {
                    spesifikasiTextarea[0].value = data.spesifikasi || ''; // Set value directly on native DOM element
                    spesifikasiTextarea.trigger('input'); // Trigger input event
                    spesifikasiTextarea.trigger('change'); // Trigger change event
                    spesifikasiTextarea.focus(); // Try focusing to force re-render
                    spesifikasiTextarea.blur(); // Then blur
                }

                console.log('Area input value after setting (immediate, Select2):', areaInput.val());
                console.log('Spesifikasi textarea value after setting (immediate, Select2):', spesifikasiTextarea.val());

                // Simpan harga dasar di data atribut untuk perhitungan
                row.data('harga-satuan-dasar', data.harga_satuan);

                // Panggil updateItemRowCalculations
                updateItemRowCalculations(row);
            });

            $(element).on('select2:clear', function (e) {
                console.log('Select2 Clear event triggered for RAB Detail select.'); // DEBUGGING: Add this log
                const row = $(this).closest('tr');
                row.find('input[name$="[kode]"]').val('');
                row.find('input[name$="[deskripsi]"]').val('');
                row.find('input[name$="[volume]"]').val(1);
                row.find('input[name$="[satuan]"]').val('');
                row.find('input[name$="[area]"]').val(''); // Clear area
                row.find('textarea[name$="[spesifikasi]"]').val(''); // Clear spesifikasi
                row.data('harga-satuan-dasar', 0);
                updateItemRowCalculations(row);
            });
        }
    }

    // Fungsi untuk menghitung ulang harga penawaran dan total item
// Fungsi untuk menghitung ulang harga penawaran dan total item
function updateItemRowCalculations(row) {
    const hargaDasar = parseFloat(row.data('harga-satuan-dasar') || 0);
    const volume = parseFloat(row.find('input[name$="[volume]"]').val() || 0);
    
    const sectionCard = row.closest('.section-card');
    // Ambil persentase Profit dan Overhead
    const profitPercentage = parseFloat(sectionCard.find('input[name$="[profit_percentage]"]').val() || 0);
    const overheadPercentage = parseFloat(sectionCard.find('input[name$="[overhead_percentage]"]').val() || 0);

    // ===============================================
    // PERUBAHAN LOGIKA PERHITUNGAN DIMULAI DI SINI
    // ===============================================
    
    // Total persentase margin yang ditargetkan (diasumsikan sebagai persentase dari Harga Penawaran)
    const totalMarginPercentage = (profitPercentage + overheadPercentage) / 100; // Contoh: (15 + 10) / 100 = 0.25

    // Persentase Harga Dasar relatif terhadap Harga Penawaran
    const penyebut = 1 - totalMarginPercentage; // Contoh: 1 - 0.25 = 0.75 (75%)

    let hargaPenawaran = 0;
    
    if (penyebut > 0) {
        // Harga Penawaran = Harga Dasar / (1 - Total Margin)
        hargaPenawaran = hargaDasar / penyebut;
    } else {
        // Jika total margin 100% atau lebih, Harga Penawaran tidak terdefinisi atau tak hingga
        hargaPenawaran = 0; 
        console.error("Total Profit + Overhead mencapai 100% atau lebih. Harga Penawaran tidak valid.");
    }
    
    // ===============================================
    // PERHITUNGAN LANJUTAN
    // ===============================================

    const totalItem = hargaPenawaran * volume;

    // Output ke elemen HTML
    row.find('.harga-penawaran').text(formatRupiah(hargaPenawaran));
    row.find('.total-item').text(formatRupiah(totalItem));
    
    updateTotals(); // Perbarui total keseluruhan
}
// ... sisa skrip lainnya ...

    // Fungsi untuk memperbarui semua total
    function updateTotals() {
        let totalBruto = 0;
        // Menggunakan jQuery .each() untuk iterasi dan memastikan 'this' adalah objek jQuery
        $('.section-card').each(function() {
            const sectionCard = $(this); // Bungkus dengan jQuery
            sectionCard.find('.item-table tbody tr').each(function() {
                const row = $(this); // Bungkus dengan jQuery
                const totalItemText = row.find('.total-item').text();
                // Pastikan replace bekerja dengan benar untuk format Rupiah
                const totalItemValue = parseFloat(totalItemText.replace('Rp ', '').replace(/\./g, '').replace(/,/g, '.') || 0);
                totalBruto += totalItemValue;
            });
        });

        const discountPercentage = parseFloat($('#discount_percentage').val() || 0);
        const discountAmount = (totalBruto * discountPercentage) / 100;
        const finalTotal = totalBruto - discountAmount;

        $('#total-bruto').text(formatRupiah(totalBruto));
        $('#discount-amount').text(formatRupiah(discountAmount));
        $('#final-total').text(formatRupiah(finalTotal));
    }

    // Fungsi untuk menambahkan baris item baru
    function addItemRow(sectionCard, itemData = null) {
        const itemsBody = sectionCard.find('.items-body');
        const currentSectionIndex = sectionCard.data('section-index');
        const currentItemIndex = itemsBody.find('tr').length; // Ambil jumlah baris yang sudah ada

        const newRowHtml = `
            <tr>
                <td>${currentItemIndex + 1}</td>
                <td>
                    <select name="sections[${currentSectionIndex}][items][${currentItemIndex}][rab_detail_id]" class="form-select rab-detail-select" required>
                        <option value="">-- Pilih Detail RAB --</option>
                    </select>
                </td>
                <td><input type="text" name="sections[${currentSectionIndex}][items][${currentItemIndex}][kode]" class="form-control form-control-sm" value="" required></td>
                <td><input type="text" name="sections[${currentSectionIndex}][items][${currentItemIndex}][deskripsi]" class="form-control form-control-sm" value="" required></td>
                <td><input type="number" step="0.0001" name="sections[${currentSectionIndex}][items][${currentItemIndex}][volume]" class="form-control form-control-sm" value="1" required></td>
                <td><input type="text" name="sections[${currentSectionIndex}][items][${currentItemIndex}][satuan]" class="form-control form-control-sm" value="" required></td>
                <td><input type="text" name="sections[${currentSectionIndex}][items][${currentItemIndex}][area]" class="form-control form-control-sm" value=""></td>
                <td><textarea name="sections[${currentSectionIndex}][items][${currentItemIndex}][spesifikasi]" rows="1" class="form-control form-control-sm"></textarea></td>
                <td><span class="harga-penawaran">Rp 0</span></td>
                <td><span class="total-item">Rp 0</span></td>
                <td><button type="button" class="btn btn-sm btn-danger remove-item"><i data-feather="x"></i></button></td>
            </tr>
        `;
        const newRow = $(newRowHtml); // Buat elemen jQuery dari HTML string
        itemsBody.append(newRow);
        
        // Pre-fill item data if provided
        if (itemData) {
            console.log(`Preloading item ${currentItemIndex + 1} with data:`, itemData); // DEBUGGING: Log preloaded item data
            newRow.find('input[name$="[kode]"]').val(itemData.kode);
            newRow.find('input[name$="[deskripsi]"]').val(itemData.deskripsi);
            newRow.find('input[name$="[volume]"]').val(itemData.volume);
            newRow.find('input[name$="[satuan]"]').val(itemData.satuan);
            
            // Set area and spesifikasi for preloaded items
            const areaInput = newRow.find('input[name$="[area]"]');
            const spesifikasiTextarea = newRow.find('textarea[name$="[spesifikasi]"]');

            console.log('Attempting to set preloaded Area to:', itemData.area || '');
            if (areaInput.length > 0) {
                areaInput[0].value = itemData.area || '';
                areaInput.trigger('input');
                areaInput.trigger('change');
            }

            console.log('Attempting to set preloaded Spesifikasi to:', itemData.spesifikasi || '');
            if (spesifikasiTextarea.length > 0) {
                spesifikasiTextarea[0].value = itemData.spesifikasi || '';
                spesifikasiTextarea.trigger('input');
                spesifikasiTextarea.trigger('change');
            }
            console.log('Preloaded Area value after setting:', areaInput.val());
            console.log('Preloaded Spesifikasi value after setting:', spesifikasiTextarea.val());


            newRow.data('harga-satuan-dasar', itemData.harga_satuan_dasar);

            // Set rab_detail_id for the select2
            const rabDetailSelect = newRow.find('.rab-detail-select');
            // Tambahkan opsi yang dipilih secara manual untuk Select2
            const option = new Option(itemData.kode + ' - ' + itemData.deskripsi + ' (' + itemData.satuan + ')', itemData.rab_detail_id, true, true);
            rabDetailSelect.append(option).trigger('change');
        }

        const newSelect = newRow.find('.rab-detail-select');
        // Inisialisasi Select2 untuk item baru. Pastikan rab_header_id dari section induknya terkirim.
        initSelect2(newSelect, 'rab-detail-select', sectionCard.find('.rab-header-select').val()); 
        
        // Tambahkan event listener untuk input volume
        newRow.find('input[name$="[volume]"]').on('input', function() {
            updateItemRowCalculations($(this).closest('tr'));
        });

        // Hitung awal untuk item yang baru ditambahkan (penting untuk preloaded data)
        updateItemRowCalculations(newRow); 
    }

    // Fungsi untuk menambahkan section baru
    function addSection(sectionData = null) {
        const sectionsContainer = $('#sections-container');
        const currentSectionIndex = sectionCounter++; // Gunakan counter global
        
        const newSectionHtml = `
            <div class="card mb-3 section-card animate__animated animate__fadeInUp" data-section-index="${currentSectionIndex}">
                <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                    Section #${currentSectionIndex + 1}
                    <button type="button" class="btn btn-danger btn-sm remove-section"><i data-feather="trash-2"></i> Hapus Section</button>
                </div>
                <div class="card-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Pilih Header RAB Dasar <span class="text-danger">*</span></label>
                            <select name="sections[${currentSectionIndex}][rab_header_id]" class="form-select rab-header-select" required>
                                <option value="">-- Pilih Header RAB --</option>
                                @foreach($flatRabHeaders as $header)
                                    <option value="{{ $header['id'] }}">{{ $header['text'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Profit (%) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="sections[${currentSectionIndex}][profit_percentage]" class="form-control" value="0" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Overhead (%) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="sections[${currentSectionIndex}][overhead_percentage]" class="form-control" value="0" required>
                        </div>
                    </div>

                    <h6>Item Pekerjaan dalam Section ini</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm item-table">
                            <thead>
                                <tr>
                                    <th style="width: 5%">#</th>
                                    <th style="width: 15%">Item RAB Dasar</th> {{-- Mengurangi lebar untuk mengakomodasi kolom baru --}}
                                    <th style="width: 8%">Kode</th>
                                    <th style="width: 15%">Deskripsi</th>
                                    <th style="width: 8%">Volume</th>
                                    <th style="width: 8%">Satuan</th>
                                    <th style="width: 10%">Area</th>        {{-- Kolom baru --}}
                                    <th style="width: 15%">Spesifikasi</th> {{-- Kolom baru --}}
                                    <th style="width: 8%">Harga Penawaran</th>
                                    <th style="width: 8%">Total Item</th>
                                    <th style="width: 5%"></th>
                                </tr>
                            </thead>
                            <tbody class="items-body">
                                {{-- Initial item row for new section --}}
                            </tbody>
                        </table>
                    </div>
                    <button type="button" class="btn btn-sm btn-info mt-2 add-item"><i data-feather="plus"></i> Tambah Item</button>
                </div>
            </div>
        `;
        const newSectionCard = $(newSectionHtml); // Buat elemen jQuery dari HTML string
        sectionsContainer.append(newSectionCard);
        feather.replace(); // Refresh feather icons for new elements

        // Pre-fill section data if provided
        if (sectionData) {
            console.log(`Preloading section ${currentSectionIndex + 1} with data:`, sectionData); // DEBUGGING: Log preloaded section data
            newSectionCard.find('input[name$="[profit_percentage]"]').val(sectionData.profit_percentage);
            newSectionCard.find('input[name$="[overhead_percentage]"]').val(sectionData.overhead_percentage);
            
            // Set rab_header_id for the select element
            const rabHeaderSelect = newSectionCard.find('.rab-header-select');
            // Tambahkan opsi yang dipilih secara manual untuk Select2
            const option = new Option(sectionData.deskripsi, sectionData.rab_header_id, true, true);
            rabHeaderSelect.append(option).trigger('change');
            initSelect2(rabHeaderSelect, 'rab-header-select'); // Inisialisasi Select2 untuk header baru

            // Add items to this section
            if (sectionData.items && sectionData.items.length > 0) {
                sectionData.items.forEach(item => {
                    addItemRow(newSectionCard, item);
                });
            }

            // Recursively add children sections
            if (sectionData.children_sections && sectionData.children_sections.length > 0) {
                sectionData.children_sections.forEach(childSection => {
                    addSection(childSection); // Recursive call
                });
            }

        } else {
            // If no section data, initialize with a blank Select2 and one empty item row
            initSelect2(newSectionCard.find('.rab-header-select'), 'rab-header-select');
            // Untuk section yang baru ditambahkan secara manual, kita tetap ingin ada satu baris item
            addItemRow(newSectionCard); 
        }
        
        // Event listeners for profit/overhead changes
        newSectionCard.find('input[name$="[profit_percentage]"], input[name$="[overhead_percentage]"]').on('input', function() {
            newSectionCard.find('.item-table tbody tr').each(function() {
                updateItemRowCalculations($(this));
            });
        });
        updateTotals(); // Perbarui total setelah menambahkan section baru
    }

    // Event listener untuk tombol "Tambah Bagian Pekerjaan"
    $('#add-section').on('click', function() {
        addSection(); // Tambah section kosong baru
    });

    // Event listener untuk tombol "Hapus Section" (delegated)
    $('#sections-container').on('click', '.remove-section', function() {
        $(this).closest('.section-card').remove();
        updateTotals(); // Perbarui total setelah menghapus section
        // Re-index section numbers
        $('#sections-container .section-card').each(function(index) {
            $(this).data('section-index', index);
            $(this).find('.card-header').contents().filter(function() {
                return this.nodeType === 3; // Node type 3 is text node
            }).each(function() {
                this.nodeValue = `Section #${index + 1}`;
            });
            // Update name attributes for all inputs/selects inside
            $(this).find('[name^="sections["]').each(function() {
                const oldName = $(this).attr('name');
                const newName = oldName.replace(/sections\[\d+\]/, `sections[${index}]`);
                $(this).attr('name', newName);
            });
        });
        sectionCounter = $('#sections-container .section-card').length; // Reset counter based on actual sections
    });

    // Event listener untuk tombol "Tambah Item" (delegated)
    $('#sections-container').on('click', '.add-item', function() {
        addItemRow($(this).closest('.section-card'));
    });

    // Event listener untuk tombol "Hapus Item" (delegated)
    $('#sections-container').on('click', '.remove-item', function() {
        $(this).closest('tr').remove();
        updateTotals(); // Perbarui total setelah menghapus item
    });

    // Event listener untuk perubahan diskon
    $('#discount_percentage').on('input', updateTotals);

    // Inisialisasi Select2 untuk "Muat RAB Dasar"
    $(document).ready(function() {
        initSelect2($('#load_rab_header_select'), 'load-rab-header-select');

        // Event listener untuk tombol "Muat Header Ini"
        $('#load-rab-button').on('click', function() {
            const selectedRabHeaderId = $('#load_rab_header_select').val();
            if (selectedRabHeaderId) {
                // Redirect to the same page with a query parameter
                window.location.href = `{{ route('proyek.penawaran.create', $proyek->id) }}?load_rab_header_id=${selectedRabHeaderId}`;
            } else {
                alert('Silakan pilih RAB Dasar untuk dimuat.');
            }
        });

        // Event listener untuk tombol "Muat Seluruh RAB Dasar Proyek Ini"
        $('#load-all-rab-button').on('click', function() {
            // Redirect to the same page with a new query parameter
            window.location.href = `{{ route('proyek.penawaran.create', $proyek->id) }}?load_all_rab=true`;
        });

        // Cek apakah ada data RAB yang di-preload dari controller
        const preloadedRabData = @json($preloadedRabData ?? []); 
        if (preloadedRabData && preloadedRabData.length > 0) {
            console.log('Preloaded RAB Data received:', preloadedRabData); // DEBUGGING: Log seluruh preloaded data
            // Clear existing default section if it was added
            $('#sections-container').empty();
            sectionCounter = 0; // Reset counter for preloaded data

            // Iterate through each preloaded section (top-level headers)
            preloadedRabData.forEach(section => {
                addSection(section); // Add each as a new section
            });

            // Populate main area and spesifikasi from the first preloaded section if they are empty
            if (preloadedRabData[0] && preloadedRabData[0].area && !$('#area').val()) {
                $('#area').val(preloadedRabData[0].area);
            }
            if (preloadedRabData[0] && preloadedRabData[0].spesifikasi && !$('#spesifikasi').val()) {
                $('#spesifikasi').val(preloadedRabData[0].spesifikasi);
            }

        } else {
            // Tambah satu section default saat halaman dimuat jika tidak ada preloaded data
            addSection();
        }
        updateTotals(); // Hitung total awal
    });
</script>
@endpush

@extends('layout.master')

@push('plugin-styles')
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush

@section('content')
<div class="card shadow-sm animate__animated animate__fadeIn">
  <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
    <h4 class="m-0 d-flex align-items-center">
      <i data-feather="file-text" class="me-2"></i> Edit Penawaran: {{ $penawaran->nama_penawaran }}
    </h4>
    <div class="d-flex gap-2">
      <a href="{{ route('proyek.penawaran.show', ['proyek'=>$proyek->id, 'penawaran'=>$penawaran->id]) }}" class="btn btn-light btn-sm d-inline-flex align-items-center">
        <i data-feather="arrow-left" class="me-1"></i> Kembali ke Detail
      </a>
      <a href="{{ route('proyek.show', $proyek->id) }}" class="btn btn-outline-light btn-sm d-inline-flex align-items-center">
        <i data-feather="arrow-left" class="me-1"></i> Kembali ke Proyek
      </a>
    </div>
  </div>

  <div class="card-body p-3 p-md-4">
    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if(session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif

    <form action="{{ route('proyek.penawaran.update', ['proyek'=>$proyek->id, 'penawaran'=>$penawaran->id]) }}" method="POST">
      @csrf
      @method('PUT')

      {{-- Informasi Penawaran --}}
      <div class="mb-4 p-4 border rounded bg-light">
        <h5 class="mb-3 text-primary">Informasi Penawaran</h5>
        <div class="row g-3">
          <div class="col-md-6">
            <label for="nama_penawaran" class="form-label">Nama Penawaran <span class="text-danger">*</span></label>
            <input type="text" name="nama_penawaran" id="nama_penawaran"
                   class="form-control @error('nama_penawaran') is-invalid @enderror"
                   value="{{ old('nama_penawaran', $penawaran->nama_penawaran) }}" required>
            @error('nama_penawaran') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-md-6">
            <label for="tanggal_penawaran" class="form-label">Tanggal Penawaran <span class="text-danger">*</span></label>
            <input type="date" name="tanggal_penawaran" id="tanggal_penawaran"
                   class="form-control @error('tanggal_penawaran') is-invalid @enderror"
                   value="{{ old('tanggal_penawaran', $penawaran->tanggal_penawaran ? \Carbon\Carbon::parse($penawaran->tanggal_penawaran)->format('Y-m-d') : date('Y-m-d')) }}" required>
            @error('tanggal_penawaran') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>

          {{-- Tambahan agar tidak ke-null saat update (controller memang mengupdate ini) --}}
          <div class="col-md-6">
            <label for="area" class="form-label">Area</label>
            <input type="text" name="area" id="area"
                   class="form-control @error('area') is-invalid @enderror"
                   value="{{ old('area', $penawaran->area) }}">
            @error('area') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-md-6">
            <label for="spesifikasi" class="form-label">Spesifikasi</label>
            <textarea name="spesifikasi" id="spesifikasi" rows="1"
                      class="form-control @error('spesifikasi') is-invalid @enderror">{{ old('spesifikasi', $penawaran->spesifikasi) }}</textarea>
            @error('spesifikasi') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
        </div>
      </div>

      {{-- Muat dari RAB Dasar (opsional, untuk menambah section baru) --}}
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
        <small class="text-muted mt-2 d-block">Memuat RAB dasar akan menambah section/item ke bawah. Anda tetap bisa ubah sebelum menyimpan.</small>
      </div>

      {{-- Sections --}}
      <h5 class="mt-4 mb-3 text-primary">Bagian-bagian Pekerjaan (Sections)</h5>
      <div id="sections-container"></div>
      <button type="button" class="btn btn-success mt-3" id="add-section">
        <i data-feather="plus" class="me-1"></i> Tambah Bagian Pekerjaan
      </button>

      <hr class="my-4">

      {{-- Ringkasan --}}
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
              <input type="number" step="0.01" name="discount_percentage" id="discount_percentage"
                     class="form-control w-25 text-end"
                     value="{{ old('discount_percentage', $penawaran->discount_percentage ?? 0) }}">
            </div>
            <div class="d-flex justify-content-between mb-2">
              <span>Jumlah Diskon:</span>
              <span class="fw-bold text-danger" id="discount-amount">Rp 0</span>
            </div>
            <div class="d-flex justify-content-between mb-2">
              <span class="fs-5">Total Akhir Penawaran:</span>
              <span class="fs-5 fw-bold text-primary" id="final-total">Rp 0</span>
            </div>
          </div>
        </div>
      </div>

      <div class="d-flex justify-content-end mt-4">
        <button type="submit" class="btn btn-primary btn-lg shadow-sm">
          <i data-feather="save" class="me-1"></i> Simpan Perubahan
        </button>
      </div>
    </form>
  </div>
</div>
@endsection

@push('custom-scripts')
{{-- jQuery dulu --}}
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
{{-- Lalu Select2 --}}
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://unpkg.com/feather-icons"></script>

{{-- Siapkan data sections+items yang sudah ada untuk prefill --}}
@php
  $sectionsForJs = $penawaran->sections->map(function($s){
      return [
          'rab_header_id'       => $s->rab_header_id,
          'deskripsi'           => optional($s->rabHeader)->kode . ' - ' . optional($s->rabHeader)->deskripsi,
          'profit_percentage'   => (float) $s->profit_percentage,
          'overhead_percentage' => (float) $s->overhead_percentage,
          'items' => $s->items->map(function($i){
              return [
                  'rab_detail_id'        => $i->rab_detail_id,
                  'kode'                 => $i->kode,
                  'deskripsi'            => $i->deskripsi,
                  'volume'               => (float) $i->volume,
                  'satuan'               => $i->satuan,
                  'harga_satuan_dasar'   => (float) ($i->harga_satuan_dasar ?? 0),
                  // Jika tabel item belum punya kolom ini, biarkan null
                  'area'                 => $i->area ?? null,
                  'spesifikasi'          => $i->spesifikasi ?? null,
              ];
          })->values(),
      ];
  })->values();
@endphp

<script>
  feather.replace();

  let sectionCounter = 0;

  function formatRupiah(value) { return 'Rp ' + Number(value || 0).toLocaleString('id-ID'); }

  function initSelect2(element, type, sectionHeaderId = null) {
    if (type === 'rab-header-select') {
      $(element).select2({ placeholder: '-- Pilih Header RAB --', allowClear: true, width:'100%' });
    } else if (type === 'load-rab-header-select') {
      $(element).select2({
        placeholder: '-- Pilih RAB Dasar --', allowClear: true, width:'100%',
        ajax: {
          url: '{{ route("proyek.penawaran.searchRabHeaders", $proyek->id) }}',
          dataType: 'json', delay: 250,
          data: params => ({ search: params.term }),
          processResults: data => ({ results: data.map(item => ({ id:item.id, text:item.text, area:item.area, spesifikasi:item.spesifikasi })) }),
          cache: true
        }
      }).on('select2:select', function(e){
        const d = e.params.data;
        if (!$('#area').val()) $('#area').val(d.area || '');
        if (!$('#spesifikasi').val()) $('#spesifikasi').val(d.spesifikasi || '');
      });
    } else if (type === 'rab-detail-select') {
      $(element).select2({
        placeholder: '-- Pilih Detail RAB --', allowClear: true, width:'100%',
        ajax: {
          url: '{{ route("proyek.penawaran.searchRabDetails", $proyek->id) }}',
          dataType: 'json', delay: 250,
          data: params => ({ search: params.term, rab_header_id: sectionHeaderId }),
          processResults: data => ({
            results: data.map(item => ({
              id:item.id, text:item.text, harga_satuan:item.harga_satuan,
              ahsp_id:item.ahsp_id, area:item.area, spesifikasi:item.spesifikasi
            }))
          }),
          cache:true
        }
      }).on('select2:select', function(e){
        const d = e.params.data;
        const row = $(this).closest('tr');
        row.find('input[name$="[kode]"]').val(d.text.split(' - ')[0] || '');
        const descPart = d.text.split(' - ')[1] || '';
        row.find('input[name$="[deskripsi]"]').val(descPart.split(' (')[0] || '');
        const satuanMatch = d.text.match(/\((.*?)\)/);
        row.find('input[name$="[satuan]"]').val(satuanMatch ? satuanMatch[1] : '');

        const areaInput = row.find('input[name$="[area]"]');
        const spekInput = row.find('textarea[name$="[spesifikasi]"]');
        if (areaInput.length) areaInput.val(d.area || '');
        if (spekInput.length) spekInput.val(d.spesifikasi || '');

        row.data('harga-satuan-dasar', d.harga_satuan || 0);
        updateItemRowCalculations(row);
      }).on('select2:clear', function(){
        const row = $(this).closest('tr');
        row.find('input[name$="[kode]"], input[name$="[deskripsi]"], input[name$="[satuan]"], input[name$="[area]"]').val('');
        row.find('input[name$="[volume]"]').val(1);
        row.find('textarea[name$="[spesifikasi]"]').val('');
        row.data('harga-satuan-dasar', 0);
        updateItemRowCalculations(row);
      });
    }
  }

  function updateItemRowCalculations(row) {
    const hargaDasar = parseFloat(row.data('harga-satuan-dasar') || 0);
    const volume = parseFloat(row.find('input[name$="[volume]"]').val() || 0);
    const sectionCard = row.closest('.section-card');
    const profit = parseFloat(sectionCard.find('input[name$="[profit_percentage]"]').val() || 0);
    const overhead = parseFloat(sectionCard.find('input[name$="[overhead_percentage]"]').val() || 0);
    const koef = 1 + (profit/100) + (overhead/100);
    const hargaPenawaran = hargaDasar * koef;
    const totalItem = hargaPenawaran * volume;
    row.find('.harga-penawaran').text(formatRupiah(hargaPenawaran));
    row.find('.total-item').text(formatRupiah(totalItem));
    updateTotals();
  }

  function updateTotals() {
    let totalBruto = 0;
    $('.section-card .item-table tbody tr').each(function(){
      const text = $(this).find('.total-item').text();
      const num = parseFloat(text.replace('Rp ', '').replace(/\./g,'').replace(/,/g,'.')) || 0;
      totalBruto += num;
    });
    const discPct = parseFloat($('#discount_percentage').val() || 0);
    const discAmt = totalBruto * discPct/100;
    const finalTotal = totalBruto - discAmt;
    $('#total-bruto').text(formatRupiah(totalBruto));
    $('#discount-amount').text(formatRupiah(discAmt));
    $('#final-total').text(formatRupiah(finalTotal));
  }

  function addItemRow(sectionCard, itemData = null) {
    const itemsBody = sectionCard.find('.items-body');
    const sectionIndex = sectionCard.data('section-index');
    const itemIndex = itemsBody.find('tr').length;

    const html = `
      <tr>
        <td>${itemIndex+1}</td>
        <td>
          <select name="sections[${sectionIndex}][items][${itemIndex}][rab_detail_id]"
                  class="form-select rab-detail-select" required>
            <option value="">-- Pilih Detail RAB --</option>
          </select>
        </td>
        <td><input type="text" name="sections[${sectionIndex}][items][${itemIndex}][kode]" class="form-control form-control-sm" required></td>
        <td><input type="text" name="sections[${sectionIndex}][items][${itemIndex}][deskripsi]" class="form-control form-control-sm" required></td>
        <td><input type="number" step="0.0001" name="sections[${sectionIndex}][items][${itemIndex}][volume]" class="form-control form-control-sm" value="1" required></td>
        <td><input type="text" name="sections[${sectionIndex}][items][${itemIndex}][satuan]" class="form-control form-control-sm" required></td>
        <td><input type="text" name="sections[${sectionIndex}][items][${itemIndex}][area]" class="form-control form-control-sm"></td>
        <td><textarea name="sections[${sectionIndex}][items][${itemIndex}][spesifikasi]" rows="1" class="form-control form-control-sm"></textarea></td>
        <td><span class="harga-penawaran">Rp 0</span></td>
        <td><span class="total-item">Rp 0</span></td>
        <td><button type="button" class="btn btn-sm btn-danger remove-item"><i data-feather="x"></i></button></td>
      </tr>`;
    const $row = $(html).appendTo(itemsBody);
    const $select = $row.find('.rab-detail-select');
    initSelect2($select, 'rab-detail-select', sectionCard.find('.rab-header-select').val());

    // Prefill jika ada
    if (itemData) {
      $row.find('input[name$="[kode]"]').val(itemData.kode || '');
      $row.find('input[name$="[deskripsi]"]').val(itemData.deskripsi || '');
      $row.find('input[name$="[volume]"]').val(itemData.volume ?? 1);
      $row.find('input[name$="[satuan]"]').val(itemData.satuan || '');
      $row.find('input[name$="[area]"]').val(itemData.area || '');
      $row.find('textarea[name$="[spesifikasi]"]').val(itemData.spesifikasi || '');
      $row.data('harga-satuan-dasar', itemData.harga_satuan_dasar || 0);

      // Pasang option terpilih untuk Select2
      if (itemData.rab_detail_id) {
        const optText = `${itemData.kode || ''} - ${itemData.deskripsi || ''} (${itemData.satuan || ''})`;
        const option = new Option(optText, itemData.rab_detail_id, true, true);
        $select.append(option).trigger('change');
      }
    }

    // Event volume
    $row.find('input[name$="[volume]"]').on('input', () => updateItemRowCalculations($row));
    feather.replace();
    updateItemRowCalculations($row);
  }

  function addSection(sectionData = null) {
    const container = $('#sections-container');
    const idx = sectionCounter++;
    const html = `
      <div class="card mb-3 section-card animate__animated animate__fadeInUp" data-section-index="${idx}">
        <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
          Section #${idx+1}
          <button type="button" class="btn btn-danger btn-sm remove-section"><i data-feather="trash-2"></i> Hapus Section</button>
        </div>
        <div class="card-body">
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label">Pilih Header RAB Dasar <span class="text-danger">*</span></label>
              <select name="sections[${idx}][rab_header_id]" class="form-select rab-header-select" required>
                <option value="">-- Pilih Header RAB --</option>
                @foreach($flatRabHeaders as $h)
                  <option value="{{ $h['id'] }}">{{ $h['text'] }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Profit (%) <span class="text-danger">*</span></label>
              <input type="number" step="0.01" name="sections[${idx}][profit_percentage]" class="form-control" value="0" required>
            </div>
            <div class="col-md-3">
              <label class="form-label">Overhead (%) <span class="text-danger">*</span></label>
              <input type="number" step="0.01" name="sections[${idx}][overhead_percentage]" class="form-control" value="0" required>
            </div>
          </div>

          <h6>Item Pekerjaan dalam Section ini</h6>
          <div class="table-responsive">
            <table class="table table-bordered table-sm item-table">
              <thead>
                <tr>
                  <th style="width:5%">#</th>
                  <th style="width:15%">Item RAB Dasar</th>
                  <th style="width:8%">Kode</th>
                  <th style="width:15%">Deskripsi</th>
                  <th style="width:8%">Volume</th>
                  <th style="width:8%">Satuan</th>
                  <th style="width:10%">Area</th>
                  <th style="width:15%">Spesifikasi</th>
                  <th style="width:8%">Harga Penawaran</th>
                  <th style="width:8%">Total Item</th>
                  <th style="width:5%"></th>
                </tr>
              </thead>
              <tbody class="items-body"></tbody>
            </table>
          </div>

          <button type="button" class="btn btn-sm btn-info mt-2 add-item">
            <i data-feather="plus"></i> Tambah Item
          </button>
        </div>
      </div>`;
    const $card = $(html).appendTo(container);
    feather.replace();

    // Init header select2
    const $headerSelect = $card.find('.rab-header-select');
    initSelect2($headerSelect, 'rab-header-select');

    if (sectionData) {
      $headerSelect.val(sectionData.rab_header_id).trigger('change');
      $card.find('input[name$="[profit_percentage]"]').val(sectionData.profit_percentage ?? 0);
      $card.find('input[name$="[overhead_percentage]"]').val(sectionData.overhead_percentage ?? 0);

      // Items existing
      if (Array.isArray(sectionData.items)) {
        sectionData.items.forEach(it => addItemRow($card, it));
      }
    } else {
      addItemRow($card); // 1 row default
    }

    // Recalc saat profit/overhead berubah
    $card.find('input[name$="[profit_percentage]"], input[name$="[overhead_percentage]"]')
      .on('input', () => {
        $card.find('.item-table tbody tr').each(function(){ updateItemRowCalculations($(this)); });
      });

    updateTotals();
  }

  // Listeners global
  $('#add-section').on('click', () => addSection());
  $('#sections-container').on('click', '.add-item', function(){ addItemRow($(this).closest('.section-card')); });
  $('#sections-container').on('click', '.remove-item', function(){ $(this).closest('tr').remove(); updateTotals(); });

  // Hapus section + reindex
  $('#sections-container').on('click', '.remove-section', function(){
    $(this).closest('.section-card').remove();
    updateTotals();
    $('#sections-container .section-card').each(function(i){
      $(this).attr('data-section-index', i);
      $(this).find('.card-header').contents().filter(function(){ return this.nodeType===3; })
        .each(function(){ this.nodeValue = `Section #${i+1}`; });
      $(this).find('[name^="sections["]').each(function(){
        const oldName = $(this).attr('name');
        $(this).attr('name', oldName.replace(/sections\[\d+\]/, `sections[${i}]`));
      });
    });
    sectionCounter = $('#sections-container .section-card').length;
  });

  // Diskon
  $('#discount_percentage').on('input', updateTotals);

  // Inisialisasi pertama kali
  $(document).ready(function(){
    // Select2 untuk picker "muat RAB dasar"
    initSelect2($('#load_rab_header_select'), 'load-rab-header-select');

    // Tombol "Muat Header Ini"
    $('#load-rab-button').on('click', function(){
      const hdrId = $('#load_rab_header_select').val();
      if (!hdrId) return alert('Silakan pilih RAB Dasar.');
      // Tambahkan 1 section baru dengan header terpilih (tanpa reload halaman)
      addSection({ rab_header_id: parseInt(hdrId), deskripsi: '', profit_percentage: 0, overhead_percentage: 0, items: [] });
      // Set nilai header di section terakhir
      const $last = $('#sections-container .section-card').last();
      $last.find('.rab-header-select').val(hdrId).trigger('change');
    });

    // Tombol "Muat Seluruh RAB ..." → biar aman kita arahkan ke halaman create jika ingin import massal
    $('#load-all-rab-button').on('click', function(){
      window.location.href = `{{ route('proyek.penawaran.create', $proyek->id) }}?load_all_rab=true`;
    });

    // Prefill dari penawaran yang ada
    const preloaded = @json($sectionsForJs);
    $('#sections-container').empty();
    sectionCounter = 0;

    if (Array.isArray(preloaded) && preloaded.length) {
      preloaded.forEach(sec => addSection(sec));
    } else {
      addSection(); // kalau kosong, kasih satu section default
    }

    // Hitung total awal (berdasarkan perhitungan client-side)
    updateTotals();
  });
</script>
@endpush

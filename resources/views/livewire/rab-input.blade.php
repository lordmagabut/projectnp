<div>
  {{-- Flash messages --}}
  @if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show animate__animated animate__fadeIn" role="alert">
      <i class="fas fa-times-circle me-2"></i> {{ session('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif
  @if (session('success'))
    <div class="alert alert-success alert-dismissible fade show animate__animated animate__fadeIn" role="alert">
      <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  {{-- Panduan: Cara mengisi form ini --}}
  <div class="card mb-4 animate__animated animate__fadeIn">
    <div class="card-header d-flex align-items-center justify-content-between bg-light">
      <h6 class="mb-0">
        <i class="fas fa-question-circle me-2 text-primary"></i>
        Cara mengisi form ini
      </h6>
      <button class="btn btn-sm btn-outline-secondary" type="button"
              data-bs-toggle="collapse" data-bs-target="#howToFill"
              aria-expanded="false" aria-controls="howToFill">
        Tampilkan / Sembunyikan
      </button>
    </div>
    <div id="howToFill" class="collapse">
      <div class="card-body">
        <div class="row g-4">
          <div class="col-12 col-lg-6">
            <h6 class="text-primary mb-2"><i class="fas fa-sitemap me-1"></i> A. Buat Induk / Sub-Induk</h6>
            <ol class="mb-0 ps-3">
              <li>Isi <strong>Deskripsi Header</strong> (mis. “Pekerjaan Persiapan”).</li>
              <li>Jika header ini anak dari header lain, pilih di <strong>Pilih Induk</strong>.
                Jika dibiarkan kosong, header menjadi level tertinggi.</li>
              <li>Klik <strong>Tambah Header</strong>. Kode akan dibuat otomatis sesuai struktur.</li>
            </ol>
            <small class="text-muted d-block mt-2">
              Catatan: Header tidak bisa dihapus jika masih memiliki sub-header atau detail.
            </small>
          </div>

          <div class="col-12 col-lg-6">
            <h6 class="text-primary mb-2"><i class="fas fa-file-alt me-1"></i> B. Input Baris RAB Detail</h6>
            <ol class="mb-0 ps-3">
              <li>Pilih <strong>Sub-Induk (Header)</strong> tempat detail akan ditempatkan.</li>
              <li>Pilih <strong>AHSP</strong> (ketik kode/nama untuk mencari).</li>
              <li>Isi <strong>Volume</strong> (gunakan titik untuk desimal, mis. <code>12.5</code>).</li>
              <li>Kolom <strong>Satuan</strong>, <strong>Harga Material</strong>, <strong>Harga Jasa</strong>,
                  dan <strong>Harga Gabungan</strong> akan terisi otomatis dari AHSP.</li>
              <li>Tuliskan <strong>Deskripsi Detail</strong> (wajib), lalu (opsional) isi
                  <strong>Area</strong> dan <strong>Spesifikasi</strong>.</li>
              <li>Setelah semua benar, klik <strong>Tambah Detail</strong>.</li>
            </ol>
            <small class="text-muted d-block mt-2">
              Tips: Tombol <em>Tambah Detail</em> berada di paling bawah agar Anda meninjau data terlebih dahulu.
            </small>
          </div>
        </div>

        <hr>

        <div class="row g-4">
          <div class="col-12 col-lg-6">
            <h6 class="text-primary mb-2"><i class="fas fa-calculator me-1"></i> C. Perhitungan</h6>
            <ul class="mb-0 ps-3">
              <li><strong>Harga Material / Jasa</strong> diambil dari AHSP (tanpa pembulatan tambahan).</li>
              <li><strong>Harga Gabungan</strong> = Material + Jasa.</li>
              <li><strong>Total</strong> = Harga Gabungan × Volume.</li>
            </ul>
          </div>
          <div class="col-12 col-lg-6">
            <h6 class="text-primary mb-2"><i class="fas fa-exclamation-triangle me-1"></i> D. Validasi & Kesalahan Umum</h6>
            <ul class="mb-0 ps-3">
              <li>Pastikan Header, AHSP, Deskripsi, dan Volume sudah terisi sebelum menambah detail.</li>
              <li>Jika AHSP tidak muncul, cek bahwa <em>kategori</em> Header sesuai dengan kategori AHSP.</li>
              <li>Gunakan tanda titik (<code>.</code>) untuk angka desimal pada volume.</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>


  {{-- Header RAB Baru --}}
  <div class="card mb-4 animate__animated animate__fadeInUp animate__faster">
    <div class="card-header bg-primary text-white">
      <h5 class="mb-0"><i class="fas fa-sitemap me-2"></i> Buat Induk / Sub-Induk RAB Baru</h5>
    </div>
    <div class="card-body">
      <form class="row g-3" wire:submit.prevent="tambahHeader">
        <div class="col-md-6">
          <label for="newHeaderDescription" class="form-label">Deskripsi Header <span class="text-danger">*</span></label>
          <input type="text" id="newHeaderDescription" wire:model="newHeader.deskripsi" class="form-control @error('newHeader.deskripsi') is-invalid @enderror" placeholder="Contoh: Pekerjaan Persiapan">
          @error('newHeader.deskripsi') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-4">
          <label for="newHeaderParent" class="form-label">Pilih Induk (Opsional)</label>
          <select id="newHeaderParent" wire:model="newHeader.parent_id" class="form-select @error('newHeader.parent_id') is-invalid @enderror">
            <option value="">-- Induk Level Tertinggi --</option>
            @foreach($flatHeaders as $flatHeader)
              <option value="{{ $flatHeader['id'] }}">{{ $flatHeader['display_name'] }}</option>
            @endforeach
          </select>
          @error('newHeader.parent_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-2 d-flex align-items-end">
          <button type="submit" class="btn btn-success w-100" wire:loading.attr="disabled">
            <span wire:loading.remove><i class="fas fa-plus-circle me-1"></i> Tambah Header</span>
            <span wire:loading class="spinner-border spinner-border-sm me-2"></span>
          </button>
        </div>
      </form>
    </div>
  </div>

  {{-- Input RAB Detail --}}
<div class="card mb-4 shadow-sm animate__animated animate__fadeInUp animate__faster rab-input-tidy">
  <div class="card-header bg-info text-white d-flex align-items-center">
    <h5 class="mb-0"><i class="fas fa-file-alt me-2"></i> Input Baris RAB Detail</h5>
  </div>

    <div class="card-body">
      <form wire:submit.prevent="tambahDetail">

      {{-- BARIS 1: HEADER / AHSP / VOLUME (sejajar) --}}
  <div class="row g-3 align-items-end">
    {{-- HEADER --}}
    <div class="col-12 col-lg-4">
      <label class="form-label mb-1">Sub-Induk (Header) <span class="text-danger">*</span></label>
      <select wire:model.live="newItem.header_id"
              class="form-select @error('newItem.header_id') is-invalid @enderror">
        <option value="">-- Pilih --</option>
        @foreach($flatHeaders as $flatHeader)
          <option value="{{ $flatHeader['id'] }}">{{ $flatHeader['display_name'] }}</option>
        @endforeach
      </select>
      @error('newItem.header_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    {{-- AHSP (Select2) --}}
    @php $ahspSelectId = 'ahsp-select-'.$proyek_id.'-'.$kategori_id; @endphp
    <div class="col-12 col-lg-5" wire:ignore>
      <label class="form-label mb-1">AHSP <span class="text-danger">*</span></label>
      <select id="{{ $ahspSelectId }}" class="form-select" data-placeholder="Ketik kode/nama AHSP..."></select>
      {{-- hidden sinkron ke Livewire + error ditaruh di kolom yang sama supaya tidak bikin baris baru --}}
      <input type="hidden" wire:model="newItem.ahsp_id">
      @error('newItem.ahsp_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
    </div>

    {{-- VOLUME (sebaris) --}}
    <div class="col-12 col-lg-3">
      <label class="form-label mb-1">Volume <span class="text-danger">*</span></label>
      <input type="number" step="0.001"
            wire:model="newItem.volume"
            class="form-control @error('newItem.volume') is-invalid @enderror">
      @error('newItem.volume') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
  </div>


      <hr class="my-4">

      {{-- PREVIEW SATUAN & HARGA --}}
      <div class="row g-3">
        <div class="col-12 col-md-3">
          <label class="form-label mb-1">Satuan</label>
          <input type="text" class="form-control" value="{{ $newItem['satuan'] ?? '' }}" readonly>
        </div>

        <div class="col-12 col-md-3">
          <label class="form-label mb-1">Harga Satuan Material</label>
          <div class="input-group">
            <span class="input-group-text">Rp</span>
            <input type="text" class="form-control text-end"
                   value="{{ number_format((float)($newItem['harga_material'] ?? 0), 0, ',', '.') }}" readonly>
          </div>
        </div>

        <div class="col-12 col-md-3">
          <label class="form-label mb-1">Harga Satuan Jasa</label>
          <div class="input-group">
            <span class="input-group-text">Rp</span>
            <input type="text" class="form-control text-end"
                   value="{{ number_format((float)($newItem['harga_upah'] ?? 0), 0, ',', '.') }}" readonly>
          </div>
        </div>

        <div class="col-12 col-md-3">
          <label class="form-label mb-1">Harga Satuan (Gabungan)</label>
          <div class="input-group">
            <span class="input-group-text">Rp</span>
            <input type="text" class="form-control text-end"
                   value="{{ number_format((float)($newItem['harga_satuan'] ?? 0), 0, ',', '.') }}" readonly>
          </div>
          <small class="text-muted">Diambil dari AHSP (tanpa pembulatan tambahan).</small>
        </div>
      </div>

      <hr class="my-4">

      {{-- DESKRIPSI & SPESIFIKASI (spesifikasi di bawah deskripsi) --}}
      <div class="row g-3">
        <div class="col-12">
          <label class="form-label mb-1">Deskripsi Detail <span class="text-danger">*</span></label>
          <textarea wire:model="newItem.deskripsi"
                    class="form-control @error('newItem.deskripsi') is-invalid @enderror"
                    rows="2" placeholder="Detail pekerjaan: Contoh: Pasang keramik lantai"></textarea>
          @error('newItem.deskripsi') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-12 col-md-6">
          <label class="form-label mb-1">Area (Opsional)</label>
          <input type="text" wire:model="newItem.area"
                 class="form-control @error('newItem.area') is-invalid @enderror"
                 placeholder="Contoh: Lantai 1, Kamar Mandi">
          @error('newItem.area') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-12 col-md-6">
          <label class="form-label mb-1">Spesifikasi (Opsional)</label>
          <textarea wire:model="newItem.spesifikasi"
                    class="form-control @error('newItem.spesifikasi') is-invalid @enderror"
                    rows="2" placeholder="Contoh: Keramik 30x30, merek A"></textarea>
          @error('newItem.spesifikasi') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
      </div>

      {{-- TOMBOL AKSI (dipindah ke paling bawah) --}}
      <div class="mt-4 d-flex justify-content-end">
        @php
          $btnDisabled = empty($newItem['header_id']) || empty($newItem['ahsp_id']) || empty($newItem['deskripsi']) || (float)($newItem['volume'] ?? 0) <= 0;
        @endphp
        <button type="submit" class="btn btn-primary px-4"
        @if($btnDisabled) disabled @endif
        wire:loading.attr="disabled">
          <span wire:loading.remove><i class="fas fa-plus-square me-1"></i> Tambah Detail</span>
          <span wire:loading class="spinner-border spinner-border-sm me-2"></span>
        </button>
      </div>

    </form>
  </div>
</div>


  <style>
    .rab-input-tidy .form-label{ font-weight:600; }
    .rab-input-tidy .input-group-text{ min-width:48px; justify-content:center; }
    .rab-input-tidy .card-header{ border-bottom:0; }
    .rab-input-tidy .card-body{ padding-top:1.25rem; padding-bottom:1.25rem; }
  </style>

  {{-- List & Grand Total --}}
  <div class="row">
    <div class="col-md-12">
      @if($headers->isEmpty())
        <div class="card animate__animated animate__fadeInUp animate__faster">
          <div class="card-body text-center py-5">
            <i class="fas fa-info-circle fa-3x text-muted mb-3"></i>
            <p class="lead text-muted">Belum ada Header RAB untuk kategori ini.</p>
            <p class="text-muted">Silakan tambahkan header baru di bagian atas.</p>
          </div>
        </div>
      @else
        @foreach($headers as $header)
          @include('livewire.partials.rab-header-card', ['header' => $header, 'level' => 0])
        @endforeach
      @endif

      <div class="card mt-3 animate__animated animate__fadeInUp animate__faster">
        <div class="card-body d-flex justify-content-between align-items-center bg-light">
          <h5 class="mb-0 text-dark"><i class="fas fa-money-bill-wave me-2"></i> Grand Total Proyek</h5>
          <span class="fw-bold fs-4 text-primary">Rp {{ number_format($projectGrandTotal, 0, ',', '.') }}</span>
        </div>
      </div>
    </div>
  </div>

  @push('custom-scripts')
  {{-- jQuery sudah disediakan layout. Pastikan Select2 JS/CSS sudah dipush di layout/halaman. --}}
  <script>
    (function () {
      const elId = @json($ahspSelectId);

      function initAhspSelect() {
        const $el = $('#'+elId);
        if (!$el.length) return;

        // Destroy sebelum re-init agar tidak duplikat handler
        if ($el.hasClass('select2-hidden-accessible')) {
          $el.off('select2:select select2:open');
          $el.select2('destroy');
        }

        $el.select2({
          theme: 'bootstrap-5',
          width: '100%',
          dropdownParent: $el.closest('.card-body'),
          placeholder: $el.data('placeholder') || 'Ketik kode/nama AHSP...',
          allowClear: false,
          minimumInputLength: 0,
          ajax: {
            url: '{{ route('ahsp.search') }}',
            dataType: 'json',
            delay: 150,
            data: function (params) {
              const hasHeader = !!@this.get('newItem.header_id');
              const cat = hasHeader ? @this.get('selectedHeaderCategoryId') : @this.get('kategori_id');
              return { search: params.term || '', kategori_id: cat || null };
            },
            processResults: function (data) {
              // Ambil hanya field yang aman untuk preview; HINDARI harga di sini.
              const rows = (data?.results || data || []).map(x => ({
                id: x.id,
                text: x.text,
                satuan: x.satuan
              }));
              return { results: rows };
            },
            cache: true
          },
          templateResult: function (item) {
            if (!item.id) return item.text;
            // Tanpa badge harga agar tidak misleading (kita tidak pakai pembulatan)
            return $(`
              <div>
                <div class="fw-semibold">${item.text}</div>
                <small class="text-muted">Satuan: ${item.satuan ?? '-'}</small>
              </div>
            `);
          },
          templateSelection: (item) => item.text || item.id
        });

        // Prefetch saat dibuka supaya daftar muncul lebih cepat
        $el.on('select2:open', function() {
          try { $el.data('select2').trigger('query', { term: '' }); } catch(e){}
          const $s = $('.select2-container--open .select2-search__field');
          if ($s.length) {
            $s.val(' ').trigger('input');
            setTimeout(() => $s.val('').trigger('input'), 0);
          }
        });

        // Sinkronkan ke Livewire — JANGAN set harga dari JS
        $el.on('select2:select', function(e){
          const d = e.params?.data || {};
          @this.set('newItem.ahsp_id', d.id, true);
          @this.set('newItem.satuan', d.satuan ?? '', true);
          // Jangan override deskripsi/spesifikasi agar input manual tetap tersimpan
        });

        // Preselect bila ada nilai existing
        const current = @json($newItem['ahsp_id'] ?? '');
        if (current && !$el.find(`option[value="${current}"]`).length) {
          $.get('{{ route('ahsp.search') }}', { id: current }, function (res) {
            const r = (res?.results || res || [])[0];
            if (r) $el.append(new Option(r.text, r.id, true, true)).trigger('change');
          });
        } else if (!current) {
          // Jika state Livewire kosong, kosongkan Select2 juga
          $el.val(null).trigger('change');
        }
      }

      function boot() {
        if (typeof $ === 'undefined' || !$.fn.select2) return;
        initAhspSelect();
      }

      if (document.readyState !== 'loading') boot();
      else document.addEventListener('DOMContentLoaded', boot);

      // Re-init setelah DOM Livewire dimorph
      if (window.Livewire?.hook) {
        Livewire.hook('morph.updated', () => initAhspSelect());
      } else {
        // Kompat v2
        document.addEventListener('livewire:load', function () {
          if (window.Livewire?.hook) {
            Livewire.hook('message.processed', () => initAhspSelect());
          }
        });
      }
    })();
  </script>
@endpush

</div>

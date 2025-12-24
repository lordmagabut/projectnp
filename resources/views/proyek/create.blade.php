{{-- resources/views/pages/apps/proyek/create.blade.php --}}
@extends('layout.master')

@section('content')
<div class="row">
  <div class="col-lg-12 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <h4 class="card-title mb-4">Form Input Proyek</h4>

        @if($errors->any())
          <div class="alert alert-danger">
            <ul class="mb-0">
              @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
          </div>
        @endif

        <form action="{{ route('proyek.store') }}" method="POST" enctype="multipart/form-data">
          @csrf

          {{-- ========================
               Data Proyek Utama
               ======================== --}}
          <div class="mb-3">
            <label class="form-label">Nama Proyek</label>
            <input type="text" name="nama_proyek" class="form-control" value="{{ old('nama_proyek') }}" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Pemberi Kerja</label>
            <select name="pemberi_kerja_id" class="form-select" required>
              <option value="">-- Pilih Pemberi Kerja --</option>
              @foreach($pemberiKerja as $pk)
                <option value="{{ $pk->id }}" {{ old('pemberi_kerja_id')==$pk->id?'selected':'' }}>{{ $pk->nama_pemberi_kerja }}</option>
              @endforeach
            </select>
          </div>

          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">No SPK</label>
              <input type="text" name="no_spk" class="form-control" value="{{ old('no_spk') }}" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Nilai SPK (tanpa Rp)</label>
              <input type="text" name="nilai_spk" class="form-control" value="{{ old('nilai_spk') }}" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">File SPK (PDF)</label>
              <input type="file" name="file_spk" class="form-control" accept="application/pdf">
            </div>
          </div>

          <div class="row g-3 mt-1">
            <div class="col-md-4">
              <label class="form-label">Jenis Proyek</label>
              <select name="jenis_proyek" class="form-select" required>
                @php $jp = old('jenis_proyek','kontraktor'); @endphp
                <option value="kontraktor" {{ $jp==='kontraktor'?'selected':'' }}>Kontraktor</option>
                <option value="cost and fee" {{ $jp==='cost and fee'?'selected':'' }}>Cost and Fee</option>
                <option value="office" {{ $jp==='office'?'selected':'' }}>Office</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Tanggal Mulai</label>
              <input type="date" name="tanggal_mulai" class="form-control" value="{{ old('tanggal_mulai') }}">
            </div>
            <div class="col-md-4">
              <label class="form-label">Tanggal Selesai</label>
              <input type="date" name="tanggal_selesai" class="form-control" value="{{ old('tanggal_selesai') }}">
            </div>
          </div>

          <div class="row g-3 mt-1">
            <div class="col-md-4">
              <label class="form-label">Status</label>
              @php $st = old('status','perencanaan'); @endphp
              <select name="status" class="form-select" required>
                <option value="perencanaan" {{ $st==='perencanaan'?'selected':'' }}>Perencanaan</option>
                <option value="berjalan" {{ $st==='berjalan'?'selected':'' }}>Berjalan</option>
                <option value="selesai" {{ $st==='selesai'?'selected':'' }}>Selesai</option>
              </select>
            </div>
            <div class="col-md-8">
              <label class="form-label">Lokasi</label>
              <input type="text" name="lokasi" class="form-control" value="{{ old('lokasi') }}" required>
            </div>
          </div>

          {{-- ========================
               Profil Pajak Proyek
               ======================== --}}
          <hr class="my-4">
          <h5 class="mb-3">Profil Pajak Proyek</h5>
          {{-- pakai namespace tax[...] agar mudah diproses di controller --}}
          <input type="hidden" name="tax[aktif]" value="1">

          <div class="row g-3">
            <div class="col-md-3">
              <label class="form-label">Kena PPN?</label>
              @php $is = old('tax.is_taxable', 1); @endphp
              <select class="form-select" name="tax[is_taxable]" id="tax_is_taxable">
                <option value="0" {{ (int)$is===0?'selected':'' }}>Tidak</option>
                <option value="1" {{ (int)$is===1?'selected':'' }}>Ya</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Mode PPN</label>
              @php $md = old('tax.ppn_mode','exclude'); @endphp
              <select class="form-select" name="tax[ppn_mode]" id="tax_ppn_mode">
                <option value="exclude" {{ $md==='exclude'?'selected':'' }}>Exclude (PPN ditambahkan)</option>
                <option value="include" {{ $md==='include'?'selected':'' }}>Include (harga sudah PPN)</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Tarif PPN (%)</label>
              <input type="number" step="0.001" min="0" class="form-control" name="tax[ppn_rate]" id="tax_ppn_rate" value="{{ old('tax.ppn_rate', 11.000) }}">
            </div>
            <div class="col-md-3">
              <label class="form-label">Pembulatan</label>
              @php $rd = old('tax.rounding','HALF_UP'); @endphp
              <select class="form-select" name="tax[rounding]" id="tax_rounding">
                <option value="HALF_UP" {{ $rd==='HALF_UP'?'selected':'' }}>HALF_UP (standar rupiah)</option>
                <option value="FLOOR" {{ $rd==='FLOOR'?'selected':'' }}>FLOOR (turun)</option>
                <option value="CEIL"  {{ $rd==='CEIL'?'selected':'' }}>CEIL (naik)</option>
              </select>
            </div>
          </div>

          <div class="row g-3 mt-1">
            <div class="col-md-3">
              <label class="form-label">Potong PPh?</label>
              @php $ap = old('tax.apply_pph', 0); @endphp
              <select class="form-select" name="tax[apply_pph]" id="tax_apply_pph">
                <option value="0" {{ (int)$ap===0?'selected':'' }}>Tidak</option>
                <option value="1" {{ (int)$ap===1?'selected':'' }}>Ya</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Tarif PPh (%)</label>
              <input type="number" step="0.001" min="0" class="form-control" name="tax[pph_rate]" id="tax_pph_rate" value="{{ old('tax.pph_rate', 2.000) }}">
            </div>
            <div class="col-md-3">
              <label class="form-label">Dasar PPh</label>
              @php $pb = old('tax.pph_base', 'dpp'); @endphp
              <select class="form-select" name="tax[pph_base]" id="tax_pph_base">
                <option value="dpp" {{ $pb==='dpp'?'selected':'' }}>DPP (Subtotal tanpa PPN)</option>
                <option value="subtotal" {{ $pb==='subtotal'?'selected':'' }}>Subtotal (sebelum PPN)</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Mulai Berlaku</label>
              <input type="date" class="form-control" name="tax[effective_from]" value="{{ old('tax.effective_from') }}">
            </div>
          </div>

          <div class="row g-3 mt-1">
            <div class="col-md-3">
              <label class="form-label">Berakhir</label>
              <input type="date" class="form-control" name="tax[effective_to]" value="{{ old('tax.effective_to') }}">
            </div>
            <div class="col-md-9">
              <label class="form-label">Opsi Tambahan (JSON)</label>
              <textarea class="form-control" name="tax[extra_options]" rows="2" placeholder='{"dp_requires_fp": true}'>{{ old('tax.extra_options') }}</textarea>
              <small class="text-muted">Opsional. Biarkan kosong jika tidak perlu.</small>
            </div>
          </div>

          {{-- Sumber DPP PPh (baru) --}}
          <div class="row g-3 mt-1">
            <div class="col-md-4">
              <label class="form-label">Sumber DPP PPh</label>
              @php $src = old('tax.pph_dpp_source','jasa'); @endphp
              <select class="form-select" id="tax_pph_dpp_source" name="tax[pph_dpp_source]">
                <option value="jasa" {{ $src==='jasa'?'selected':'' }}>Jasa saja</option>
                <option value="material_jasa" {{ $src==='material_jasa'?'selected':'' }}>Material + Jasa</option>
              </select>
              <small class="text-muted">Menentukan apakah PPh dihitung dari DPP Jasa saja atau gabungan Material+Jasa.</small>
            </div>
          </div>

          <div class="alert alert-light mt-3">
            <div class="small text-muted">Catatan:</div>
            <ul class="small mb-0">
              <li><strong>Include PPN</strong>: subtotal yang Anda input sudah <em>termasuk</em> PPN; sistem akan memisahkan DPP otomatis.</li>
              <li><strong>Exclude PPN</strong>: PPN dihitung dan ditambahkan di akhir dari DPP.</li>
              <li><strong>PPh</strong> dipotong dari DPP (default) atau Subtotal sesuai pilihan.</li>
            </ul>
          </div>

          {{-- Aksi --}}
          <div class="mt-4">
            <button type="submit" class="btn btn-primary">Simpan</button>
            <a href="{{ route('proyek.index') }}" class="btn btn-secondary">Kembali</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

@push('custom-scripts')
<script>
(function(){
  const isTaxable = document.getElementById('tax_is_taxable');
  const applyPph  = document.getElementById('tax_apply_pph');
  
  // Ambil elemen input/select
  const ppnRateField = document.getElementById('tax_ppn_rate');
  const ppnModeField = document.getElementById('tax_ppn_mode');
  const pphRateField = document.getElementById('tax_pph_rate');
  const pphBaseField = document.getElementById('tax_pph_base');
  const pphDppSourceField = document.getElementById('tax_pph_dpp_source');

  function togglePpn(){
    const en = isTaxable.value === '1';
    if (!en) {
      // Jika "Tidak", paksa nilai menjadi 0 sebelum di-disable
      if(ppnRateField) ppnRateField.value = 0;
    }
    
    [ppnRateField, ppnModeField].forEach(el => {
      if(el) el.disabled = !en;
    });
  }

  function togglePph(){
    const en = applyPph.value === '1';
    if (!en) {
      // Jika "Tidak", paksa nilai menjadi 0 sebelum di-disable
      if(pphRateField) pphRateField.value = 0;
    }

    [pphRateField, pphBaseField, pphDppSourceField].forEach(el => {
      if(el) el.disabled = !en;
    });
  }

  // Event Listeners
  isTaxable && isTaxable.addEventListener('change', togglePpn);
  applyPph && applyPph.addEventListener('change', togglePph);
  
  // Jalankan saat halaman pertama kali dimuat
  togglePpn();
  togglePph();
})();
</script>
@endpush
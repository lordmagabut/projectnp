{{-- resources/views/proyek_tax_profiles/create.blade.php --}}
@extends('layout.master')

@section('content')
<form method="POST" action="{{ route('proyek-tax-profiles.store') }}" class="needs-validation" novalidate>
  @csrf
  <div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
      <h5 class="mb-2 mb-md-0">Tambah Profil Pajak Proyek</h5>
      <a href="{{ route('proyek-tax-profiles.index') }}" class="btn btn-light btn-sm">Kembali</a>
    </div>

    <div class="card-body">
      @if($errors->any())
        <div class="alert alert-danger">
          <div class="fw-semibold mb-1">Gagal menyimpan:</div>
          <ul class="mb-0">
            @foreach($errors->all() as $e)
              <li>{{ $e }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Proyek</label>
          <select class="form-select" name="proyek_id" required id="proyek_id">
            <option value="">— Pilih Proyek —</option>
            @foreach($proyekList as $prj)
              <option value="{{ $prj->id }}" {{ old('proyek_id')==$prj->id?'selected':'' }}>
                {{ $prj->nama_proyek }} (#{{ $prj->id }})
              </option>
            @endforeach
          </select>
          <div class="invalid-feedback">Proyek wajib dipilih.</div>
        </div>

        <div class="col-md-3">
          <label class="form-label">Kena PPN?</label>
          <select class="form-select" name="is_taxable" id="is_taxable">
            <option value="0" {{ old('is_taxable',0)==0?'selected':'' }}>Tidak</option>
            <option value="1" {{ old('is_taxable',0)==1?'selected':'' }}>Ya</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Mode PPN</label>
          <select class="form-select" name="ppn_mode" id="ppn_mode">
            @php $mode = old('ppn_mode','exclude'); @endphp
            <option value="exclude" {{ $mode==='exclude'?'selected':'' }}>Exclude (PPN ditambahkan)</option>
            <option value="include" {{ $mode==='include'?'selected':'' }}>Include (harga sudah PPN)</option>
          </select>
        </div>

        <div class="col-md-3">
          <label class="form-label">Tarif PPN (%)</label>
          <input type="number" step="0.001" min="0" class="form-control" name="ppn_rate" id="ppn_rate"
                 value="{{ old('ppn_rate', 11.000) }}">
        </div>

        <div class="col-md-3">
          <label class="form-label">Potong PPh?</label>
          <select class="form-select" name="apply_pph" id="apply_pph">
            <option value="0" {{ old('apply_pph',0)==0?'selected':'' }}>Tidak</option>
            <option value="1" {{ old('apply_pph',0)==1?'selected':'' }}>Ya</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Tarif PPh (%)</label>
          <input type="number" step="0.001" min="0" class="form-control" name="pph_rate" id="pph_rate"
                 value="{{ old('pph_rate', 2.000) }}">
        </div>
        <div class="col-md-3">
          <label class="form-label">Dasar PPh</label>
          @php $pb = old('pph_base','dpp'); @endphp
          <select class="form-select" name="pph_base" id="pph_base">
            <option value="dpp" {{ $pb==='dpp'?'selected':'' }}>DPP (Subtotal tanpa PPN)</option>
            <option value="subtotal" {{ $pb==='subtotal'?'selected':'' }}>Subtotal (sebelum PPN)</option>
          </select>
        </div>

        <div class="col-md-3">
          <label class="form-label">Pembulatan</label>
          @php $rd = old('rounding','HALF_UP'); @endphp
          <select class="form-select" name="rounding" id="rounding">
            <option value="HALF_UP" {{ $rd==='HALF_UP'?'selected':'' }}>HALF_UP (standar rupiah)</option>
            <option value="FLOOR" {{ $rd==='FLOOR'?'selected':'' }}>FLOOR (turun)</option>
            <option value="CEIL" {{ $rd==='CEIL'?'selected':'' }}>CEIL (naik)</option>
          </select>
        </div>

        <div class="col-md-3">
          <label class="form-label">Mulai Berlaku</label>
          <input type="date" class="form-control" name="effective_from" value="{{ old('effective_from') }}">
        </div>
        <div class="col-md-3">
          <label class="form-label">Berakhir</label>
          <input type="date" class="form-control" name="effective_to" value="{{ old('effective_to') }}">
        </div>

        <div class="col-md-3">
          <label class="form-label">Status</label>
          <select class="form-select" name="aktif">
            @php $ak = old('aktif',1); @endphp
            <option value="1" {{ $ak==1?'selected':'' }}>Aktif</option>
            <option value="0" {{ $ak==0?'selected':'' }}>Nonaktif</option>
          </select>
          <div class="form-text">Hanya boleh satu profil <em>Aktif</em> per proyek. Jika pilih Aktif, profil lain akan dinonaktifkan otomatis.</div>
        </div>

        <div class="col-12">
          <label class="form-label">Opsi Tambahan (JSON)</label>
          <textarea class="form-control" name="extra_options" rows="3" placeholder='{"dp_requires_fp": true}'>{{ old('extra_options') }}</textarea>
          <div class="form-text">Opsional. Biarkan kosong jika tidak perlu.</div>
        </div>
      </div>
    </div>

    <div class="card-footer d-flex justify-content-end gap-2">
      <button type="submit" class="btn btn-primary">Simpan</button>
      <a href="{{ route('proyek-tax-profiles.index') }}" class="btn btn-light">Batal</a>
    </div>
  </div>
</form>

@push('custom-scripts')
<script>
(function(){
  const isTaxable = document.getElementById('is_taxable');
  const applyPph  = document.getElementById('apply_pph');
  const ppnFields = ['ppn_mode','ppn_rate'].map(id=>document.getElementById(id));
  const pphFields = ['pph_rate','pph_base'].map(id=>document.getElementById(id));

  function togglePpn(){
    const en = isTaxable.value === '1';
    ppnFields.forEach(el=>{ el.disabled = !en; });
  }
  function togglePph(){
    const en = applyPph.value === '1';
    pphFields.forEach(el=>{ el.disabled = !en; });
  }
  isTaxable.addEventListener('change', togglePpn);
  applyPph.addEventListener('change', togglePph);
  togglePpn();
  togglePph();
})();
</script>
@endpush
@endsection
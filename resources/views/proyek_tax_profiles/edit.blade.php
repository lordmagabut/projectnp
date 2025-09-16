@extends('layout.master')
<div class="col-md-3">
<label class="form-label">Kena PPN?</label>
@php $is = old('is_taxable', (int)$profile->is_taxable); @endphp
<select class="form-select" name="is_taxable" id="is_taxable">
<option value="0" {{ $is===0?'selected':'' }}>Tidak</option>
<option value="1" {{ $is===1?'selected':'' }}>Ya</option>
</select>
</div>
<div class="col-md-3">
<label class="form-label">Mode PPN</label>
@php $md = old('ppn_mode', $profile->ppn_mode); @endphp
<select class="form-select" name="ppn_mode" id="ppn_mode">
<option value="exclude" {{ $md==='exclude'?'selected':'' }}>Exclude (PPN ditambahkan)</option>
<option value="include" {{ $md==='include'?'selected':'' }}>Include (harga sudah PPN)</option>
</select>
</div>


<div class="col-md-3">
<label class="form-label">Tarif PPN (%)</label>
<input type="number" step="0.001" min="0" class="form-control" name="ppn_rate" id="ppn_rate"
value="{{ old('ppn_rate', $profile->ppn_rate) }}">
</div>


<div class="col-md-3">
<label class="form-label">Potong PPh?</label>
@php $ap = old('apply_pph', (int)$profile->apply_pph); @endphp
<select class="form-select" name="apply_pph" id="apply_pph">
<option value="0" {{ $ap===0?'selected':'' }}>Tidak</option>
<option value="1" {{ $ap===1?'selected':'' }}>Ya</option>
</select>
</div>
<div class="col-md-3">
<label class="form-label">Tarif PPh (%)</label>
<input type="number" step="0.001" min="0" class="form-control" name="pph_rate" id="pph_rate"
value="{{ old('pph_rate', $profile->pph_rate) }}">
</div>
<div class="col-md-3">
<label class="form-label">Dasar PPh</label>
@php $pb = old('pph_base', $profile->pph_base); @endphp
<select class="form-select" name="pph_base" id="pph_base">
<option value="dpp" {{ $pb==='dpp'?'selected':'' }}>DPP (Subtotal tanpa PPN)</option>
<option value="subtotal" {{ $pb==='subtotal'?'selected':'' }}>Subtotal (sebelum PPN)</option>
</select>
</div>


<div class="col-md-3">
<label class="form-label">Pembulatan</label>
@php $rd = old('rounding', $profile->rounding); @endphp
<select class="form-select" name="rounding" id="rounding">
<option value="HALF_UP" {{ $rd==='HALF_UP'?'selected':'' }}>HALF_UP (standar rupiah)</option>
<option value="FLOOR" {{ $rd==='FLOOR'?'selected':'' }}>FLOOR (turun)</option>
<option value="CEIL" {{ $rd==='CEIL'?'selected':'' }}>CEIL (naik)</option>
</select>
</div>


<div class="col-md-3">
<label class="form-label">Mulai Berlaku</label>
<input type="date" class="form-control" name="effective_from" value="{{ old('effective_from', optional($profile->effective_from)->format('Y-m-d')) }}">
</div>
<div class="col-md-3">
<label class="form-label">Berakhir</label>
<input type="date" class="form-control" name="effective_to" value="{{ old('effective_to', optional($profile->effective_to)->format('Y-m-d')) }}">
</div>


<div class="col-md-3">
<label class="form-label">Status</label>
@php $ak = old('aktif', (int)$profile->aktif); @endphp
<select class="form-select" name="aktif">
<option value="1" {{ $ak===1?'selected':'' }}>Aktif</option>
<option value="0" {{ $ak===0?'selected':'' }}>Nonaktif</option>
</select>
<div class="form-text">Memilih <em>Aktif</em> akan menonaktifkan profil aktif lain pada proyek ini.</div>
</div>


<div class="col-12">
<label class="form-label">Opsi Tambahan (JSON)</label>
<textarea class="form-control" name="extra_options" rows="3" placeholder='{"dp_requires_fp": true}'>{{ old('extra_options', is_array($profile->extra_options) ? json_encode($profile->extra_options) : $profile->extra_options) }}</textarea>
<div class="form-text">Opsional. Biarkan kosong jika tidak perlu.</div>
</div>
</div>
</div>


<div class="card-footer d-flex justify-content-end gap-2">
<button type="submit" class="btn btn-primary">Simpan Perubahan</button>
<a href="{{ route('proyek-tax-profiles.index') }}" class="btn btn-light">Batal</a>
</div>
</div>
</form>


@push('custom-scripts')
<script>
(function(){
const isTaxable = document.getElementById('is_taxable');
const applyPph = document.getElementById('apply_pph');
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
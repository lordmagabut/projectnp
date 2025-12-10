@extends('layout.master')

@section('content')
@php
  // ... (kode php sebelumnya tetap sama)
  $tax = optional($proyek->taxProfileAktif);
  $tglMulai   = old('tanggal_mulai', $proyek->tanggal_mulai ? \Carbon\Carbon::parse($proyek->tanggal_mulai)->format('Y-m-d') : '');
  $tglSelesai = old('tanggal_selesai', $proyek->tanggal_selesai ? \Carbon\Carbon::parse($proyek->tanggal_selesai)->format('Y-m-d') : '');
@endphp

<div class="row">
  <div class="col-lg-12 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <h4 class="card-title mb-4">Edit Proyek</h4>

        {{-- Error handling tetap sama --}}
        @if($errors->any())
          <div class="alert alert-danger">
            <ul class="mb-0">
              @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
          </div>
        @endif

        <form action="{{ route('proyek.update', $proyek->id) }}" method="POST" enctype="multipart/form-data">
          @csrf
          @method('PUT')

          {{-- Nama Proyek & Pemberi Kerja (Tetap sama) --}}
          <div class="mb-3">
            <label class="form-label">Nama Proyek</label>
            <input type="text" name="nama_proyek" class="form-control" value="{{ old('nama_proyek', $proyek->nama_proyek) }}" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Pemberi Kerja</label>
            <select name="pemberi_kerja_id" class="form-select" required>
              @foreach($pemberiKerja as $pk)
                <option value="{{ $pk->id }}" {{ old('pemberi_kerja_id', $proyek->pemberi_kerja_id) == $pk->id ? 'selected' : '' }}>
                  {{ $pk->nama_pemberi_kerja }}
                </option>
              @endforeach
            </select>
          </div>

          {{-- No SPK / Nilai / File SPK (Tetap sama) --}}
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">No SPK</label>
              <input type="text" name="no_spk" class="form-control" value="{{ old('no_spk', $proyek->no_spk) }}" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Nilai SPK (tanpa Rp)</label>
              <input type="text" name="nilai_spk" class="form-control" value="{{ old('nilai_spk', $proyek->nilai_spk) }}" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">File SPK (PDF, Max 10MB)</label><br>
              @if($proyek->file_spk)
                <a href="{{ asset('storage/' . $proyek->file_spk) }}" target="_blank">Lihat File Lama</a><br>
              @endif
              <input type="file" name="file_spk" class="form-control mt-1" accept="application/pdf">
            </div>
          </div>

          {{-- ========================================== --}}
          {{--       FITUR BARU: GAMBAR KERJA             --}}
          {{-- ========================================== --}}
          <div class="row g-3 mt-1">
            <div class="col-md-12">
                <label class="form-label fw-bold">File Gambar Kerja (Analisa)</label>
                
                {{-- Tampilkan file lama jika ada --}}
                @if($proyek->file_gambar_kerja)
                    <div class="mb-2 p-2 border rounded bg-light d-flex align-items-center">
                        <span class="me-2 text-muted small">File saat ini:</span>
                        <a href="{{ asset('storage/' . $proyek->file_gambar_kerja) }}" target="_blank" class="btn btn-sm btn-info text-white">
                            <i class="mdi mdi-eye"></i> Lihat Gambar
                        </a>
                    </div>
                @endif

                {{-- Input File Baru --}}
                <input type="file" name="file_gambar_kerja" class="form-control" accept=".pdf, .jpg, .jpeg, image/jpeg, application/pdf">
                <div class="form-text text-muted">
                    Format: <strong>PDF</strong> atau <strong>JPG</strong>. Maksimal ukuran upload sesuai konfigurasi server. 
                    <br><i>Kosongkan jika tidak ingin mengganti file.</i>
                </div>
            </div>
          </div>
          {{-- ========================================== --}}


          {{-- Nilai Penawaran / Diskon / Kontrak (Tetap sama) --}}
          <div class="row g-3 mt-1">
            <div class="col-md-4">
              <label class="form-label">Nilai Penawaran (Total dari RAB)</label>
              <input type="text" class="form-control" value="{{ number_format($proyek->nilai_penawaran ?? 0, 0, ',', '.') }}" readonly>
            </div>
            <div class="col-md-4">
              <label class="form-label">Diskon RAB / Pembulatan (Rp)</label>
              <input type="number" step="0.01" name="diskon_rab" id="diskon_rab" class="form-control" value="{{ old('diskon_rab', $proyek->diskon_rab ?? 0) }}">
            </div>
            <div class="col-md-4">
              <label class="form-label">Nilai Kontrak (Penawaran - Diskon)</label>
              <input type="text" id="nilai_kontrak_display" class="form-control" value="{{ number_format($proyek->nilai_kontrak ?? 0, 0, ',', '.') }}" readonly>
              <input type="hidden" name="nilai_kontrak" id="nilai_kontrak_hidden" value="{{ old('nilai_kontrak', $proyek->nilai_kontrak ?? 0) }}">
            </div>
          </div>

          {{-- Jenis / Tanggal / Status / Lokasi (Tetap sama) --}}
          <div class="row g-3 mt-1">
            <div class="col-md-4">
              <label class="form-label">Jenis Proyek</label>
              <select name="jenis_proyek" class="form-select" required>
                <option value="kontraktor"  {{ old('jenis_proyek', $proyek->jenis_proyek) == 'kontraktor'  ? 'selected' : '' }}>Kontraktor</option>
                <option value="cost and fee"{{ old('jenis_proyek', $proyek->jenis_proyek) == 'cost and fee'? 'selected' : '' }}>Cost and Fee</option>
                <option value="office"      {{ old('jenis_proyek', $proyek->jenis_proyek) == 'office'      ? 'selected' : '' }}>Office</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Tanggal Mulai</label>
              <input type="date" name="tanggal_mulai" class="form-control" value="{{ $tglMulai }}">
            </div>
            <div class="col-md-4">
              <label class="form-label">Tanggal Selesai</label>
              <input type="date" name="tanggal_selesai" class="form-control" value="{{ $tglSelesai }}">
            </div>
          </div>

          <div class="row g-3 mt-1">
            <div class="col-md-4">
              <label for="status" class="form-label">Status</label>
              <select name="status" class="form-select" required>
                @php $statusVal = old('status', $proyek->status ?? 'perencanaan'); @endphp
                <option value="perencanaan" {{ $statusVal=='perencanaan' ? 'selected' : '' }}>Perencanaan</option>
                <option value="berjalan"    {{ $statusVal=='berjalan'    ? 'selected' : '' }}>Berjalan</option>
                <option value="selesai"     {{ $statusVal=='selesai'     ? 'selected' : '' }}>Selesai</option>
              </select>
            </div>
            <div class="col-md-8">
              <label class="form-label">Lokasi</label>
              <input type="text" name="lokasi" class="form-control" value="{{ old('lokasi', $proyek->lokasi) }}" required>
            </div>
          </div>

          {{-- Profil Pajak Proyek (Tetap sama) --}}
          <hr class="my-4">
          <h5 class="mb-3">Profil Pajak Proyek</h5>
          <input type="hidden" name="tax[aktif]" value="1">

          <div class="row g-3">
            <div class="col-md-3">
              <label class="form-label">Kena PPN?</label>
              @php $is = old('tax.is_taxable', (int)($tax->is_taxable ?? 1)); @endphp
              <select class="form-select" name="tax[is_taxable]" id="tax_is_taxable">
                <option value="0" {{ (int)$is===0?'selected':'' }}>Tidak</option>
                <option value="1" {{ (int)$is===1?'selected':'' }}>Ya</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Mode PPN</label>
              @php $md = old('tax.ppn_mode', $tax->ppn_mode ?? 'exclude'); @endphp
              <select class="form-select" name="tax[ppn_mode]" id="tax_ppn_mode">
                <option value="exclude" {{ $md==='exclude'?'selected':'' }}>Exclude (PPN ditambahkan)</option>
                <option value="include" {{ $md==='include'?'selected':'' }}>Include (harga sudah PPN)</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Tarif PPN (%)</label>
              <input type="number" step="0.001" min="0" class="form-control" name="tax[ppn_rate]" id="tax_ppn_rate" value="{{ old('tax.ppn_rate', $tax->ppn_rate ?? 11.000) }}">
            </div>
            <div class="col-md-3">
              <label class="form-label">Pembulatan</label>
              @php $rd = old('tax.rounding', $tax->rounding ?? 'HALF_UP'); @endphp
              <select class="form-select" name="tax[rounding]" id="tax_rounding">
                <option value="HALF_UP" {{ $rd==='HALF_UP'?'selected':'' }}>HALF_UP (standar rupiah)</option>
                <option value="FLOOR"   {{ $rd==='FLOOR'  ?'selected':'' }}>FLOOR (turun)</option>
                <option value="CEIL"    {{ $rd==='CEIL'   ?'selected':'' }}>CEIL (naik)</option>
              </select>
            </div>
          </div>

          <div class="row g-3 mt-1">
            <div class="col-md-3">
              <label class="form-label">Potong PPh?</label>
              @php $ap = old('tax.apply_pph', (int)($tax->apply_pph ?? 0)); @endphp
              <select class="form-select" name="tax[apply_pph]" id="tax_apply_pph">
                <option value="0" {{ (int)$ap===0?'selected':'' }}>Tidak</option>
                <option value="1" {{ (int)$ap===1?'selected':'' }}>Ya</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Tarif PPh (%)</label>
              <input type="number" step="0.001" min="0" class="form-control" name="tax[pph_rate]" id="tax_pph_rate" value="{{ old('tax.pph_rate', $tax->pph_rate ?? 2.000) }}">
            </div>
            <div class="col-md-3">
              <label class="form-label">Dasar PPh</label>
              @php $pb = old('tax.pph_base', $tax->pph_base ?? 'dpp'); @endphp
              <select class="form-select" name="tax[pph_base]" id="tax_pph_base">
                <option value="dpp"      {{ $pb==='dpp'     ?'selected':'' }}>DPP (Subtotal tanpa PPN)</option>
                <option value="subtotal" {{ $pb==='subtotal'?'selected':'' }}>Subtotal (sebelum PPN)</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Mulai Berlaku</label>
              <input type="date" class="form-control" name="tax[effective_from]" value="{{ old('tax.effective_from', $tax->effective_from ? \Carbon\Carbon::parse($tax->effective_from)->format('Y-m-d') : '') }}">
            </div>
          </div>

          <div class="row g-3 mt-1">
            <div class="col-md-3">
              <label class="form-label">Berakhir</label>
              <input type="date" class="form-control" name="tax[effective_to]" value="{{ old('tax.effective_to', $tax->effective_to ? \Carbon\Carbon::parse($tax->effective_to)->format('Y-m-d') : '') }}">
            </div>
            <div class="col-md-9">
              <label class="form-label">Opsi Tambahan (JSON)</label>
              <textarea class="form-control" name="tax[extra_options]" rows="2" placeholder='{"dp_requires_fp": true}'>{{ old('tax.extra_options', is_array($tax->extra_options ?? null) ? json_encode($tax->extra_options) : ($tax->extra_options ?? '')) }}</textarea>
              <small class="text-muted">Opsional. Biarkan kosong jika tidak perlu.</small>
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

          <div class="mt-4">
            <button type="submit" class="btn btn-primary">Update</button>
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
  // Script lama tetap sama
  (function(){
    const diskonEl = document.getElementById('diskon_rab');
    if(!diskonEl) return;
    const penawaran = {{ (int)($proyek->nilai_penawaran ?? 0) }};
    const disp = document.getElementById('nilai_kontrak_display');
    const hid  = document.getElementById('nilai_kontrak_hidden');
    function recalc(){
      const diskon = parseFloat(diskonEl.value)||0;
      const kontrak = penawaran - diskon;
      if(disp) disp.value = (kontrak<0?0:kontrak).toLocaleString('id-ID');
      if(hid)  hid.value  = (kontrak<0?0:kontrak);
    }
    diskonEl.addEventListener('input', recalc);
    recalc();
  })();

  (function(){
    const isTaxable = document.getElementById('tax_is_taxable');
    const applyPph  = document.getElementById('tax_apply_pph');
    const ppnFields = ['tax_ppn_mode','tax_ppn_rate'].map(id=>document.getElementById(id));
    const pphFields = ['tax_pph_rate','tax_pph_base'].map(id=>document.getElementById(id));

    function togglePpn(){
      const en = isTaxable && isTaxable.value === '1';
      ppnFields.forEach(el=> el && (el.disabled = !en));
    }
    function togglePph(){
      const en = applyPph && applyPph.value === '1';
      pphFields.forEach(el=> el && (el.disabled = !en));
    }
    isTaxable && isTaxable.addEventListener('change', togglePpn);
    applyPph && applyPph.addEventListener('change', togglePph);
    togglePpn();
    togglePph();
  })();
</script>
@endpush
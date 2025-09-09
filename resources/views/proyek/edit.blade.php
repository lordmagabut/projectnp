@extends('layout.master')

@section('content')
<div class="row">
  <div class="col-lg-12 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <h4 class="card-title mb-4">Edit Proyek</h4>
        <form action="{{ route('proyek.update', $proyek->id) }}" method="POST" enctype="multipart/form-data">
          @csrf
          @method('PUT')

          {{-- Nama Proyek --}}
          <div class="mb-3">
            <label class="form-label">Nama Proyek</label>
            <input type="text" name="nama_proyek" class="form-control" value="{{ $proyek->nama_proyek }}" required>
          </div>

          {{-- Pemberi Kerja --}}
          <div class="mb-3">
            <label class="form-label">Pemberi Kerja</label>
            <select name="pemberi_kerja_id" class="form-select" required>
              @foreach($pemberiKerja as $pk)
                <option value="{{ $pk->id }}" {{ $proyek->pemberi_kerja_id == $pk->id ? 'selected' : '' }}>
                  {{ $pk->nama_pemberi_kerja }}
                </option>
              @endforeach
            </select>
          </div>

          {{-- No SPK --}}
          <div class="mb-3">
            <label class="form-label">No SPK</label>
            <input type="text" name="no_spk" class="form-control" value="{{ $proyek->no_spk }}" required>
          </div>

          {{-- Nilai SPK --}}
          <div class="mb-3">
            <label class="form-label">Nilai SPK (tanpa Rp)</label>
            <input type="text" name="nilai_spk" class="form-control" value="{{ $proyek->nilai_spk }}" required>
          </div>

          {{-- Nilai Penawaran --}}
          <div class="mb-3">
            <label class="form-label">Nilai Penawaran (Total dari RAB)</label>
            <input type="text" class="form-control" value="{{ number_format($proyek->nilai_penawaran, 0, ',', '.') }}" readonly>
          </div>

          {{-- Diskon / Pembulatan --}}
          <div class="mb-3">
            <label class="form-label">Diskon RAB / Pembulatan (Rp)</label>
            <input type="number" step="0.01" name="diskon_rab" id="diskon_rab" class="form-control" value="{{ $proyek->diskon_rab ?? 0 }}">
          </div>

          {{-- Nilai Kontrak --}}
          <div class="mb-3">
            <label class="form-label">Nilai Kontrak (Penawaran - Diskon)</label>
            <input type="text" id="nilai_kontrak_display" class="form-control" value="{{ number_format($proyek->nilai_kontrak, 0, ',', '.') }}" readonly>
          </div>

          {{-- Hidden nilai kontrak (untuk simpan ke DB) --}}
          <input type="hidden" name="nilai_kontrak" id="nilai_kontrak_hidden" value="{{ $proyek->nilai_kontrak }}">

          {{-- File SPK --}}
          <div class="mb-3">
            <label class="form-label">File SPK (PDF, Max 10MB)</label><br>
            @if($proyek->file_spk)
              <a href="{{ asset('storage/' . $proyek->file_spk) }}" target="_blank">Lihat File Lama</a><br><br>
            @endif
            <input type="file" name="file_spk" class="form-control">
            <small class="text-muted">Kosongkan jika tidak ingin mengganti file.</small>
          </div>

          {{-- Jenis Proyek --}}
          <div class="mb-3">
            <label class="form-label">Jenis Proyek</label>
            <select name="jenis_proyek" class="form-select" required>
              <option value="kontraktor" {{ $proyek->jenis_proyek == 'kontraktor' ? 'selected' : '' }}>Kontraktor</option>
              <option value="cost and fee" {{ $proyek->jenis_proyek == 'cost and fee' ? 'selected' : '' }}>Cost and Fee</option>
              <option value="office" {{ $proyek->jenis_proyek == 'office' ? 'selected' : '' }}>Office</option>
            </select>
          </div>

          {{-- Tanggal --}}
          <div class="mb-3">
              <label for="tanggal_mulai">Tanggal Mulai</label>
              <input type="date" name="tanggal_mulai" class="form-control" value="{{ $proyek->tanggal_mulai }}">
          </div>
          <div class="mb-3">
              <label for="tanggal_selesai">Tanggal Selesai</label>
              <input type="date" name="tanggal_selesai" class="form-control" value="{{ $proyek->tanggal_selesai }}">
          </div>

          {{-- Status --}}
          <div class="mb-3">
              <label for="status">Status</label>
              <select name="status" class="form-select" required>
                  <option value="perencanaan" {{ $proyek->status == 'perencanaan' ? 'selected' : '' }}>Perencanaan</option>
                  <option value="berjalan" {{ $proyek->status == 'berjalan' ? 'selected' : '' }}>Berjalan</option>
                  <option value="selesai" {{ $proyek->status == 'selesai' ? 'selected' : '' }}>Selesai</option>
              </select>
          </div>

          {{-- Lokasi --}}
          <div class="mb-3">
              <label for="lokasi">Lokasi</label>
              <input type="text" name="lokasi" class="form-control" value="{{ $proyek->lokasi }}" required>
          </div>

          {{-- Aksi --}}
          <button type="submit" class="btn btn-primary">Update</button>
          <a href="{{ route('proyek.index') }}" class="btn btn-secondary">Kembali</a>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

@push('custom-scripts')
<script>
  // Hitung nilai kontrak saat diskon diubah
  document.getElementById('diskon_rab').addEventListener('input', function () {
    const penawaran = {{ $proyek->nilai_penawaran ?? 0 }};
    const diskon = parseFloat(this.value) || 0;
    const kontrak = penawaran - diskon;

    document.getElementById('nilai_kontrak_display').value = kontrak.toLocaleString('id-ID');
    document.getElementById('nilai_kontrak_hidden').value = kontrak;
  });
</script>
@endpush

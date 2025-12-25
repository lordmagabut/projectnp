@extends('layout.master')

@push('plugin-styles')
  <link href="{{ asset('assets/plugins/select2/select2.min.css') }}" rel="stylesheet" />
@endpush

@section('content')
<nav class="page-breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ url('/') }}">Akunting</a></li>
    <li class="breadcrumb-item"><a href="{{ route('opening-balance.index', ['id_perusahaan' => $selectedPerusahaanId]) }}">Saldo Awal</a></li>
    <li class="breadcrumb-item active" aria-current="page">Tambah</li>
  </ol>
</nav>

<div class="row">
  <div class="col-md-8 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <h6 class="card-title">Input Saldo Awal COA</h6>

        @if ($errors->any())
          <div class="alert alert-danger">
            <ul class="mb-0">
              @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
          </div>
        @endif

        <form action="{{ route('opening-balance.store') }}" method="POST">
          @csrf

          <div class="mb-3">
            <label class="form-label">Perusahaan <span class="text-danger">*</span></label>
            <input type="hidden" name="id_perusahaan" value="{{ $selectedPerusahaanId }}">
            <input type="text" class="form-control" disabled value="{{ $perusahaans->find($selectedPerusahaanId)->nama_perusahaan ?? '' }}">
          </div>

          <div class="mb-3">
            <label class="form-label">Akun COA <span class="text-danger">*</span></label>
            <select name="coa_id" class="form-select select2" required data-width="100%">
              <option value="">-- Pilih COA --</option>
              <optgroup label="Aset">
                @foreach($coas->filter(fn($c) => $c->tipe == 'Aset') as $coa)
                  <option value="{{ $coa->id }}" {{ old('coa_id') == $coa->id ? 'selected' : '' }}>
                    {{ $coa->no_akun }} - {{ $coa->nama_akun }}
                  </option>
                @endforeach
              </optgroup>
              <optgroup label="Liabilitas">
                @foreach($coas->filter(fn($c) => $c->tipe == 'Liabilitas') as $coa)
                  <option value="{{ $coa->id }}" {{ old('coa_id') == $coa->id ? 'selected' : '' }}>
                    {{ $coa->no_akun }} - {{ $coa->nama_akun }}
                  </option>
                @endforeach
              </optgroup>
              <optgroup label="Ekuitas">
                @foreach($coas->filter(fn($c) => $c->tipe == 'Ekuitas') as $coa)
                  <option value="{{ $coa->id }}" {{ old('coa_id') == $coa->id ? 'selected' : '' }}>
                    {{ $coa->no_akun }} - {{ $coa->nama_akun }}
                  </option>
                @endforeach
              </optgroup>
              <optgroup label="Lainnya">
                @foreach($coas->filter(fn($c) => !in_array($c->tipe, ['Aset', 'Liabilitas', 'Ekuitas'])) as $coa)
                  <option value="{{ $coa->id }}" {{ old('coa_id') == $coa->id ? 'selected' : '' }}>
                    {{ $coa->no_akun }} - {{ $coa->nama_akun }}
                  </option>
                @endforeach
              </optgroup>
            </select>
            @error('coa_id')
              <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label class="form-label">Tanggal Saldo Awal <span class="text-danger">*</span></label>
                <input type="date" name="tanggal" class="form-control" value="{{ old('tanggal', now()->format('Y-m-d')) }}" required>
                @error('tanggal')
                  <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label class="form-label">Saldo Awal (Rp) <span class="text-danger">*</span></label>
                <input type="number" name="saldo_awal" class="form-control" step="0.01" placeholder="0" value="{{ old('saldo_awal') }}" required>
                <small class="text-muted d-block mt-1">Positif = Debit, Negatif = Kredit</small>
                @error('saldo_awal')
                  <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
              </div>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Keterangan</label>
            <textarea name="keterangan" class="form-control" rows="2" placeholder="Misal: Saldo awal tahun buku 2025">{{ old('keterangan') }}</textarea>
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">
              <i class="link-icon" data-feather="save"></i> Simpan Saldo Awal
            </button>
            <a href="{{ route('opening-balance.index', ['id_perusahaan' => $selectedPerusahaanId]) }}" class="btn btn-secondary">
              <i class="link-icon" data-feather="x"></i> Batal
            </a>
          </div>

        </form>

      </div>
    </div>
  </div>

  <div class="col-md-4">
    <div class="card">
      <div class="card-body">
        <h6 class="card-title">Informasi</h6>
        <p class="text-muted mb-3">
          <strong>Saldo Awal</strong> adalah saldo awal akun pada tanggal dimulainya periode akuntansi.
        </p>
        <ul class="text-muted small">
          <li>✓ Positif = Saldo Debit (untuk akun Aset, Beban, HPP)</li>
          <li>✓ Negatif = Saldo Kredit (untuk akun Liabilitas, Pendapatan, Ekuitas)</li>
          <li>✓ Sistem auto-generate jurnal offset ke Laba Ditahan</li>
          <li>✓ Setiap saldo awal hanya bisa dibuat sekali per akun per tanggal</li>
        </ul>
      </div>
    </div>
  </div>
</div>

@endsection

@push('plugin-scripts')
  <script src="{{ asset('assets/plugins/select2/select2.min.js') }}"></script>
@endpush

@push('custom-scripts')
<script>
  $(function() {
    if ($('.select2').length) {
      $('.select2').select2();
    }
    if (feather) {
      feather.replace();
    }
  });
</script>
@endpush

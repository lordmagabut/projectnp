@extends('layout.master')

@section('content')
<div class="row">
  <div class="col-md-12">
    <h4 class="mb-4">Laporan Neraca - {{ $nama_perusahaan }}</h4>

    <form method="GET" class="row g-3 mb-4">
      <div class="col-md-3">
        <label>Perusahaan</label>
        <select name="id_perusahaan" class="form-select" onchange="this.form.submit()">
          @foreach($perusahaans as $p)
            <option value="{{ $p->id }}" {{ $selectedPerusahaanId == $p->id ? 'selected' : '' }}>
              {{ $p->nama_perusahaan }}
            </option>
          @endforeach
        </select>
      </div>
      <div class="col-md-3">
        <label>Tanggal Awal</label>
        <input type="date" name="tanggal_awal" class="form-control" value="{{ $tanggalAwal }}">
      </div>
      <div class="col-md-3">
        <label>Tanggal Akhir</label>
        <input type="date" name="tanggal_akhir" class="form-control" value="{{ $tanggalAkhir }}">
      </div>
      <div class="col-md-2 d-grid">
        <label class="invisible">_</label>
        <button class="btn btn-primary">Tampilkan</button>
      </div>
    </form>

    <div class="table-responsive">
      <table class="table table-sm table-bordered align-middle">
        <thead class="table-light">
          <tr>
            <th>Akun</th>
            <th class="text-end">Saldo</th>
          </tr>
        </thead>
        <tbody>
          @include('laporan.partials._coa_tree_rows', ['tree' => $coaTree])
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection

@push('custom-scripts')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.toggle-collapse').forEach(function (toggle) {
      toggle.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.getElementById(this.dataset.target);
        if (target) {
          target.classList.toggle('d-none');
          this.querySelector('.arrow').classList.toggle('rotate');
        }
      });
    });
  });
</script>
<style>
  .arrow {
    display: inline-block;
    transition: transform 0.2s ease;
  }
  .arrow.rotate {
    transform: rotate(90deg);
  }
</style>
@endpush

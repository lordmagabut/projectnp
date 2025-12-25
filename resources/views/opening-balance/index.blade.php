@extends('layout.master')

@section('content')
<nav class="page-breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ url('/') }}">Akunting</a></li>
    <li class="breadcrumb-item active" aria-current="page">Saldo Awal</li>
  </ol>
</nav>

<div class="row">
  <div class="col-md-12 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h6 class="card-title mb-0">Daftar Saldo Awal COA</h6>
          <a href="{{ route('opening-balance.create', ['id_perusahaan' => $selectedPerusahaanId]) }}" class="btn btn-primary btn-sm">
            <i class="link-icon" data-feather="plus"></i> Tambah Saldo Awal
          </a>
        </div>

        <!-- Filter -->
        <div class="mb-3">
          <label class="form-label">Pilih Perusahaan:</label>
          <form method="GET" action="{{ route('opening-balance.index') }}" class="d-flex gap-2">
            <select name="id_perusahaan" class="form-select" onchange="this.form.submit()">
              @foreach($perusahaans as $p)
                <option value="{{ $p->id }}" {{ $selectedPerusahaanId == $p->id ? 'selected' : '' }}>
                  {{ $p->nama_perusahaan }}
                </option>
              @endforeach
            </select>
          </form>
        </div>

        @if(session('success'))
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        @endif

        @if(session('error'))
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        @endif

        <!-- Table -->
        <div class="table-responsive">
          <table class="table table-bordered table-hover">
            <thead class="table-light">
              <tr>
                <th>Tanggal</th>
                <th>No. Akun</th>
                <th>Nama Akun</th>
                <th class="text-end">Saldo Awal</th>
                <th>Keterangan</th>
                <th width="120">Aksi</th>
              </tr>
            </thead>
            <tbody>
              @forelse($openingBalances as $ob)
                <tr>
                  <td>{{ $ob->tanggal->format('d/m/Y') }}</td>
                  <td>{{ $ob->coa->no_akun }}</td>
                  <td>{{ $ob->coa->nama_akun }}</td>
                  <td class="text-end {{ $ob->saldo_awal < 0 ? 'text-danger' : '' }}">
                    <strong>{{ number_format($ob->saldo_awal, 2, ',', '.') }}</strong>
                  </td>
                  <td>{{ $ob->keterangan }}</td>
                  <td>
                    <form action="{{ route('opening-balance.destroy', $ob->id) }}" method="POST" style="display:inline;">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Hapus saldo awal ini?')">
                        <i class="link-icon" data-feather="trash-2"></i> Hapus
                      </button>
                    </form>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="6" class="text-center text-muted">Belum ada saldo awal</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

      </div>
    </div>
  </div>
</div>
@endsection

@push('custom-scripts')
<script>
  $(function() {
    if (feather) {
      feather.replace();
    }
  });
</script>
@endpush

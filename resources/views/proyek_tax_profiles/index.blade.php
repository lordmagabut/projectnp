{{-- resources/views/proyek_tax_profiles/index.blade.php --}}
@extends('layout.master')

@section('content')
<div class="card shadow-sm">
  <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
    <h5 class="mb-2 mb-md-0">Profil Pajak per Proyek</h5>
    <div class="d-flex gap-2">
      <a href="{{ route('proyek-tax-profiles.create') }}" class="btn btn-primary btn-sm">
        <i class="fa fa-plus me-1"></i> Tambah Profil
      </a>
    </div>
  </div>
  <div class="card-body">
    @if(session('success'))
      <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="table-responsive">
      <table class="table table-bordered table-hover align-middle">
        <thead class="table-light">
          <tr>
            <th style="min-width:220px">Proyek</th>
            <th>PPN?</th>
            <th>Mode PPN</th>
            <th>Tarif PPN</th>
            <th>PPh?</th>
            <th>Tarif PPh</th>
            <th>Dasar PPh</th>
            <th>Pembulatan</th>
            <th>Efektif</th>
            <th>Aktif</th>
            <th style="width:110px">Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse($profiles as $p)
            <tr>
              <td>
                <div class="fw-semibold">{{ $p->proyek->nama_proyek ?? '—' }}</div>
                <div class="text-muted small">#{{ $p->proyek_id }}</div>
              </td>
              <td>
                <span class="badge {{ $p->is_taxable ? 'bg-success' : 'bg-secondary' }}">{{ $p->is_taxable ? 'Ya' : 'Tidak' }}</span>
              </td>
              <td>{{ strtoupper($p->ppn_mode) }}</td>
              <td>{{ rtrim(rtrim(number_format($p->ppn_rate, 3), '0'), '.') }}%</td>
              <td>
                <span class="badge {{ $p->apply_pph ? 'bg-success' : 'bg-secondary' }}">{{ $p->apply_pph ? 'Ya' : 'Tidak' }}</span>
              </td>
              <td>{{ rtrim(rtrim(number_format($p->pph_rate, 3), '0'), '.') }}%</td>
              <td class="text-uppercase">{{ $p->pph_base }}</td>
              <td class="text-uppercase">{{ $p->rounding }}</td>
              <td class="small">
                {{ $p->effective_from?->format('d/m/Y') ?? '—' }}
                @if($p->effective_to)
                  — {{ $p->effective_to->format('d/m/Y') }}
                @endif
              </td>
              <td>
                @if($p->aktif)
                  <span class="badge bg-primary">Aktif</span>
                @else
                  <span class="badge bg-light text-muted">Nonaktif</span>
                @endif
              </td>
              <td>
                <a href="{{ route('proyek-tax-profiles.edit', $p->id) }}" class="btn btn-sm btn-warning">Edit</a>
              </td>
            </tr>
          @empty
            <tr><td colspan="11" class="text-center text-muted">Belum ada data.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if(method_exists($profiles, 'links'))
      <div class="mt-3">{{ $profiles->links() }}</div>
    @endif
  </div>
</div>
@endsection
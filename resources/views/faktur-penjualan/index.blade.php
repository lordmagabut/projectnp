@extends('layout.master')

@section('content')
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Faktur Penjualan</h5>
    <span class="text-muted">Auto-dibuat dari Sertifikat Pembayaran</span>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-striped mb-0">
        <thead>
          <tr>
            <th>No</th>
            <th>Tanggal</th>
            <th>Proyek</th>
            <th>Sertifikat</th>
            <th>Subtotal</th>
            <th>PPN</th>
            <th>Total</th>
            <th>Status Bayar</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse($fakturs as $faktur)
            <tr>
              <td>{{ $faktur->no_faktur }}</td>
              <td>{{ optional($faktur->tanggal)->format('d/m/Y') }}</td>
              <td>{{ optional($faktur->proyek)->nama_proyek ?? '-' }}</td>
              <td>
                @if($faktur->sertifikat_pembayaran_id)
                  <a href="{{ route('sertifikat.show', $faktur->sertifikat_pembayaran_id) }}" class="text-decoration-underline">Sertifikat #{{ $faktur->sertifikat_pembayaran_id }}</a>
                @else
                  <span class="text-muted">-</span>
                @endif
              </td>
              <td>Rp {{ number_format($faktur->subtotal, 2, ',', '.') }}</td>
              <td>Rp {{ number_format($faktur->total_ppn, 2, ',', '.') }}</td>
              <td><strong>Rp {{ number_format($faktur->total, 2, ',', '.') }}</strong></td>
              <td><span class="badge bg-secondary text-uppercase">{{ $faktur->status_pembayaran ?? 'belum' }}</span></td>
              <td>
                <div class="btn-group btn-group-sm" role="group">
                  <a class="btn btn-outline-primary" href="{{ route('faktur-penjualan.show', $faktur->id) }}" title="Detail">
                    <i class="fas fa-eye"></i>
                  </a>
                  @if($faktur->status === 'draft')
                    <a class="btn btn-outline-warning" href="{{ route('faktur-penjualan.edit', $faktur->id) }}" title="Edit">
                      <i class="fas fa-edit"></i>
                    </a>
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="9" class="text-center text-muted py-4">Belum ada faktur penjualan.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="p-3">
      {{ $fakturs->links() }}
    </div>
  </div>
</div>
@endsection

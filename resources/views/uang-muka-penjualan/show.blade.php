@extends('layout.master')

@section('content')
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Detail Uang Muka Penjualan</h5>
    <div class="d-flex gap-2">
      <a href="{{ route('uang-muka-penjualan.edit', $um->id) }}" class="btn btn-primary btn-sm">Edit</a>
      <form action="{{ route('uang-muka-penjualan.destroy', $um->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus UM ini?');">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-danger btn-sm" {{ $um->nominal_digunakan > 0 ? 'disabled' : '' }}>Hapus</button>
      </form>
      <a href="{{ route('uang-muka-penjualan.index') }}" class="btn btn-secondary btn-sm">Kembali</a>
    </div>
  </div>
  <div class="card-body">
    <table class="table table-borderless">
      <tr>
        <th style="width:220px">Nomor Bukti</th>
        <td>{{ $um->nomor_bukti ?? '-' }}</td>
      </tr>
      <tr>
        <th>Tanggal</th>
        <td>{{ optional($um->tanggal)->format('d/m/Y') }}</td>
      </tr>
      <tr>
        <th>Proyek</th>
        <td>{{ $um->proyek->nama_proyek ?? '-' }}</td>
      </tr>
      <tr>
        <th>Sales Order</th>
        <td>{{ optional($um->salesOrder)->nomor ?? '-' }}</td>
      </tr>
      <tr>
        <th>Nominal</th>
        <td>Rp {{ number_format($um->nominal, 2, ',', '.') }}</td>
      </tr>
      <tr>
        <th>Nominal Digunakan</th>
        <td>Rp {{ number_format($um->nominal_digunakan, 2, ',', '.') }}</td>
      </tr>
      <tr>
        <th>Sisa Uang Muka</th>
        <td><strong>Rp {{ number_format($um->getSisaUangMuka(), 2, ',', '.') }}</strong></td>
      </tr>
      <tr>
        <th>Status</th>
        <td>
          @if($um->status == 'diterima')
            <span class="badge bg-success">Diterima</span>
          @elseif($um->status == 'sebagian')
            <span class="badge bg-warning">Sebagian</span>
          @else
            <span class="badge bg-info">Lunas</span>
          @endif
        </td>
      </tr>
      <tr>
        <th>Metode Pembayaran</th>
        <td>{{ $um->metode_pembayaran ?? '-' }}</td>
      </tr>
      <tr>
        <th>Keterangan</th>
        <td>{{ $um->keterangan ?? '-' }}</td>
      </tr>
      <tr>
        <th>Dibuat Oleh</th>
        <td>{{ optional($um->creator)->name ?? '-' }} ({{ optional($um->created_at)->format('d/m/Y H:i') }})</td>
      </tr>
      <tr>
        <th>Terakhir Diubah</th>
        <td>{{ optional($um->updated_at)->format('d/m/Y H:i') }}</td>
      </tr>
    </table>

    <div class="mt-4">
      <h6>Riwayat Penggunaan</h6>
      <div class="alert alert-info">
        <p><strong>Nominal Awal:</strong> Rp {{ number_format($um->nominal, 2, ',', '.') }}</p>
        <p><strong>Telah Digunakan:</strong> Rp {{ number_format($um->nominal_digunakan, 2, ',', '.') }}</p>
        <p><strong>Sisa Tersedia:</strong> Rp {{ number_format($um->getSisaUangMuka(), 2, ',', '.') }}</p>
      </div>
    </div>

    <div class="mt-4 d-flex gap-2">
      <a href="{{ route('uang-muka-penjualan.index') }}" class="btn btn-secondary">Kembali</a>
      <a href="{{ route('uang-muka-penjualan.edit', $um->id) }}" class="btn btn-primary">Edit</a>
    </div>
  </div>
</div>
@endsection

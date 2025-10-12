@extends('layout.master')

@section('content')
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Detail Sertifikat Pembayaran</h5>
    <a href="{{ route('sertifikat.cetak', $sp->id) }}" class="btn btn-primary">Cetak PDF</a>
  </div>
  <div class="card-body">
    <table class="table table-borderless">
      <tr>
        <th style="width:220px">Nomor Sertifikat</th>
        <td>{{ $sp->nomor ?? '-' }}</td>
      </tr>
      <tr>
        <th>Tanggal</th>
        <td>{{ \Carbon\Carbon::parse($sp->tanggal)->format('d/m/Y') }}</td>
      </tr>
      <tr>
        <th>Nomor BAPP</th>
        <td>{{ optional($sp->bapp)->nomor_bapp ?? '-' }}</td>
      </tr>
      <tr>
        <th>Proyek</th>
        <td>{{ optional(optional($sp->bapp)->proyek)->nama_proyek ?? '-' }}</td>
      </tr>
      <tr>
        <th>Termin / Progress</th>
        <td>Ke-{{ $sp->termin_ke }} — {{ rtrim(rtrim(number_format($sp->persen_progress,4,',','.'),'0'),',') }}%</td>
      </tr>
      <tr>
        <th>WO Material</th>
        <td>Rp {{ number_format($sp->nilai_wo_material, 2, ',', '.') }}</td>
      </tr>
      <tr>
        <th>WO Upah</th>
        <td>Rp {{ number_format($sp->nilai_wo_jasa, 2, ',', '.') }}</td>
      </tr>
      <tr>
        <th>Nilai WO Total</th>
        <td>Rp {{ number_format($sp->nilai_wo_total, 2, ',', '.') }}</td>
      </tr>
      <tr>
        <th>Total Dibayar (sebelum PPN)</th>
        <td>Rp {{ number_format($sp->total_dibayar, 2, ',', '.') }}</td>
      </tr>
      <tr>
        <th>PPN {{ rtrim(rtrim(number_format($sp->ppn_persen,2,',','.'),'0'),',') }}%</th>
        <td>Rp {{ number_format($sp->ppn_nilai, 2, ',', '.') }}</td>
      </tr>
      <tr>
        <th>Total Tagihan</th>
        <td><strong>Rp {{ number_format($sp->total_tagihan, 2, ',', '.') }}</strong></td>
      </tr>
    </table>

    <div class="mt-3">
      <a href="{{ route('sertifikat.index') }}" class="btn btn-secondary">Kembali</a>
      <a href="{{ route('sertifikat.cetak', $sp->id) }}" class="btn btn-outline-primary">Cetak PDF</a>
    </div>
  </div>
</div>
@endsection

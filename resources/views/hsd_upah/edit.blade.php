@extends('layout.master')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="card-title">Edit Upah / Tukang</h4>
        <a href="{{ route('hsd-upah.index') }}" class="btn btn-sm btn-secondary">← Kembali</a>
    </div>
    <div class="card-body">
        <form action="{{ route('hsd-upah.update', $upah->id) }}" method="POST">
            @csrf @method('PUT')

            <div class="mb-3">
                <label for="kode" class="form-label">Kode</label>
                <input type="text" name="kode" id="kode" class="form-control" required value="{{ old('kode', $upah->kode) }}">
            </div>

            <div class="mb-3">
                <label for="jenis_pekerja" class="form-label">Jenis Pekerja</label>
                <input type="text" name="jenis_pekerja" id="jenis_pekerja" class="form-control" required value="{{ old('jenis_pekerja', $upah->jenis_pekerja) }}">
            </div>

            <div class="mb-3">
                <label for="satuan" class="form-label">Satuan</label>
                <input type="text" name="satuan" id="satuan" class="form-control" required value="{{ old('satuan', $upah->satuan) }}">
            </div>

            <div class="mb-3">
                <label for="harga_satuan" class="form-label">Harga Satuan</label>
                <input type="number" name="harga_satuan" id="harga_satuan" class="form-control" step="0.01" required value="{{ old('harga_satuan', $upah->harga_satuan) }}">
            </div>

            <div class="mb-3">
                <label for="sumber" class="form-label">Sumber Perubahan Harga</label>
                <input type="text" name="sumber" id="sumber" class="form-control" placeholder="Contoh: update gaji UMR, tukang harian, dsb" value="{{ old('sumber') }}">
                <div class="form-text">Opsional. Diisi jika harga berubah.</div>
            </div>

            <div class="mb-3">
                <label for="keterangan" class="form-label">Keterangan</label>
                <textarea name="keterangan" id="keterangan" class="form-control">{{ old('keterangan', $upah->keterangan) }}</textarea>
            </div>

            <button type="submit" class="btn btn-success">Update</button>
        </form>
    </div>
</div>

{{-- Riwayat Perubahan Harga --}}
@if($upah->histories->count())
<div class="card mt-4">
    <div class="card-header">
        <h5 class="card-title">Riwayat Perubahan Harga</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 15%">Tanggal</th>
                        <th style="width: 25%">Harga (Lama → Baru)</th>
                        <th style="width: 35%">Sumber</th>
                        <th style="width: 25%">Diupdate Oleh</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($upah->histories->sortByDesc('tanggal_berlaku') as $history)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($history->tanggal_berlaku)->format('d-m-Y') }}</td>
                        <td>
                            Rp {{ number_format($history->harga_satuan, 0, ',', '.') }}
                            @if($history->harga_baru)
                                → Rp {{ number_format($history->harga_baru, 0, ',', '.') }}
                            @endif
                        </td>
                        <td>{{ $history->sumber ?? '-' }}</td>
                        <td>{{ $history->updatedBy->username ?? '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif
@endsection

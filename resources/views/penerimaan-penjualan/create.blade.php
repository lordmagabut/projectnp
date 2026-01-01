@extends('layout.master')

@section('title', 'Buat Penerimaan Penjualan')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1>Buat Penerimaan Pembayaran Penjualan</h1>
        </div>
    </div>

    @if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle"></i> <strong>Terjadi Kesalahan</strong>
        <ul class="mb-0 mt-2">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Form Penerimaan Pembayaran</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('penerimaan-penjualan.store') }}" method="POST">
                        @csrf
                        @php
                            $initialRows = old('items');
                            if (!$initialRows) {
                                $initialRows = [
                                    [
                                        'faktur_penjualan_id' => $selectedFakturId,
                                        'nominal' => null,
                                        'pph_dipotong' => 0,
                                        'keterangan_pph' => null,
                                    ],
                                ];
                            }
                        @endphp

                        <div class="mb-3">
                            <label class="form-label">Pembayaran untuk Faktur (1 pemberi kerja)</label>
                            <div class="table-responsive">
                                <table class="table table-bordered align-middle" id="items-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 38%">Faktur Penjualan</th>
                                            <th style="width: 20%">Nominal</th>
                                            <th style="width: 18%">PPh Dipotong</th>
                                            <th style="width: 18%">Ket. PPh</th>
                                            <th style="width: 6%" class="text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($initialRows as $idx => $row)
                                        <tr class="item-row">
                                            <td>
                                                <select name="items[{{ $idx }}][faktur_penjualan_id]" class="form-control" required>
                                                    <option value="">-- Pilih Faktur --</option>
                                                    @foreach ($fakturPenjualan as $faktur)
                                                        @php
                                                            $sumDetail = \App\Models\PenerimaanPenjualanDetail::where('faktur_penjualan_id', $faktur->id)
                                                                ->whereHas('penerimaan', function ($q) { $q->whereIn('status', ['draft', 'approved']); })
                                                                ->sum('nominal');
                                                            $legacySum = \App\Models\PenerimaanPenjualan::where('faktur_penjualan_id', $faktur->id)
                                                                ->whereDoesntHave('details')
                                                                ->whereIn('status', ['draft', 'approved'])
                                                                ->sum('nominal');
                                                            $sisa = $faktur->total - ($sumDetail + $legacySum);
                                                        @endphp
                                                        <option value="{{ $faktur->id }}" @selected($row['faktur_penjualan_id'] == $faktur->id)>
                                                            {{ $faktur->no_faktur }} ({{ $faktur->perusahaan->nama_perusahaan ?? 'N/A' }}) - Sisa: Rp {{ number_format($sisa, 2, ',', '.') }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <div class="input-group">
                                                    <span class="input-group-text">Rp</span>
                                                    <input type="number" name="items[{{ $idx }}][nominal]" class="form-control text-right" step="0.01" value="{{ $row['nominal'] }}" required>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="input-group">
                                                    <span class="input-group-text">Rp</span>
                                                    <input type="number" name="items[{{ $idx }}][pph_dipotong]" class="form-control text-right" step="0.01" value="{{ $row['pph_dipotong'] ?? 0 }}">
                                                </div>
                                            </td>
                                            <td>
                                                <input type="text" name="items[{{ $idx }}][keterangan_pph]" class="form-control" maxlength="100" value="{{ $row['keterangan_pph'] ?? '' }}">
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(this)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="5">
                                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="addRow()">
                                                    <i class="fas fa-plus"></i> Tambah Faktur
                                                </button>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            <small class="text-muted">Semua faktur harus milik pemberi kerja/perusahaan yang sama. Sistem akan menolak jika berbeda.</small>
                            @error('items')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="tanggal" class="form-label">
                                Tanggal <span class="text-danger">*</span>
                            </label>
                            <input type="date" name="tanggal" id="tanggal" 
                                   class="form-control @error('tanggal') is-invalid @enderror"
                                   value="{{ old('tanggal', now()->format('Y-m-d')) }}"
                                   required>
                            @error('tanggal')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="metode_pembayaran" class="form-label">
                                Metode Pembayaran <span class="text-danger">*</span>
                            </label>
                            <select name="metode_pembayaran" id="metode_pembayaran" 
                                    class="form-control @error('metode_pembayaran') is-invalid @enderror"
                                    required>
                                <option value="">-- Pilih Metode --</option>
                                <option value="Tunai" @selected(old('metode_pembayaran') === 'Tunai')>Tunai</option>
                                <option value="Transfer" @selected(old('metode_pembayaran') === 'Transfer')>Transfer Bank</option>
                                <option value="Cek" @selected(old('metode_pembayaran') === 'Cek')>Cek</option>
                                <option value="Giro" @selected(old('metode_pembayaran') === 'Giro')>Giro</option>
                                <option value="Kartu Kredit" @selected(old('metode_pembayaran') === 'Kartu Kredit')>Kartu Kredit</option>
                            </select>
                            @error('metode_pembayaran')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="keterangan" class="form-label">Keterangan</label>
                            <textarea name="keterangan" id="keterangan" 
                                      class="form-control @error('keterangan') is-invalid @enderror"
                                      rows="3">{{ old('keterangan') }}</textarea>
                            @error('keterangan')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Simpan
                            </button>
                            <a href="{{ route('penerimaan-penjualan.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Batal
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card bg-light">
                <div class="card-header">
                    <h5 class="card-title mb-0">Informasi</h5>
                </div>
                <div class="card-body">
                    <p><strong>Petunjuk Pengisian:</strong></p>
                    <ul class="small">
                        <li>Pilih faktur penjualan yang akan menerima pembayaran</li>
                        <li>Isi tanggal penerimaan pembayaran</li>
                        <li>Masukkan nominal pembayaran yang diterima</li>
                        <li>Pilih metode pembayaran</li>
                        <li>Tambahkan keterangan jika diperlukan</li>
                        <li>Data akan disimpan dengan status Draft</li>
                        <li>Butuh persetujuan untuk bisa difinalisasi</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

<script>
    let rowIndex = {{ count($initialRows) }};

    function addRow() {
        const tbody = document.querySelector('#items-table tbody');
        const template = `
            <tr class="item-row">
                <td>
                    <select name="items[__INDEX__][faktur_penjualan_id]" class="form-control" required>
                        <option value="">-- Pilih Faktur --</option>
                        @foreach ($fakturPenjualan as $faktur)
                            @php
                                $sumDetail = \App\Models\PenerimaanPenjualanDetail::where('faktur_penjualan_id', $faktur->id)
                                    ->whereHas('penerimaan', function ($q) { $q->whereIn('status', ['draft', 'approved']); })
                                    ->sum('nominal');
                                $legacySum = \App\Models\PenerimaanPenjualan::where('faktur_penjualan_id', $faktur->id)
                                    ->whereDoesntHave('details')
                                    ->whereIn('status', ['draft', 'approved'])
                                    ->sum('nominal');
                                $sisa = $faktur->total - ($sumDetail + $legacySum);
                            @endphp
                            <option value="{{ $faktur->id }}">{{ $faktur->no_faktur }} ({{ $faktur->perusahaan->nama_perusahaan ?? 'N/A' }}) - Sisa: Rp {{ number_format($sisa, 2, ',', '.') }}</option>
                        @endforeach
                    </select>
                </td>
                <td>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="number" name="items[__INDEX__][nominal]" class="form-control text-right" step="0.01" required>
                    </div>
                </td>
                <td>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="number" name="items[__INDEX__][pph_dipotong]" class="form-control text-right" step="0.01" value="0">
                    </div>
                </td>
                <td>
                    <input type="text" name="items[__INDEX__][keterangan_pph]" class="form-control" maxlength="100">
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(this)">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `.replace(/__INDEX__/g, rowIndex);
        rowIndex++;
        tbody.insertAdjacentHTML('beforeend', template);
    }

    function removeRow(btn) {
        const row = btn.closest('tr');
        const tbody = document.querySelector('#items-table tbody');
        if (tbody.querySelectorAll('tr').length <= 1) {
            return;
        }
        row.remove();
    }
</script>

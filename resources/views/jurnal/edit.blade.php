@extends('layout.master')

@section('content')
<div class="row">
    <div class="col-md-12">
        <h4 class="mb-4">Edit Jurnal Umum</h4>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @elseif(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <form action="{{ route('jurnal.update', $jurnal->id) }}" method="POST">
            @csrf
            @method('PUT')

            {{-- Input Perusahaan --}}
            <div class="mb-3">
                <label class="form-label">Perusahaan</label>
                @if($perusahaans->count() > 1)
                    <select name="id_perusahaan" class="form-control" required>
                        <option value="">-- Pilih Perusahaan --</option>
                        @foreach($perusahaans as $perusahaan)
                            <option value="{{ $perusahaan->id }}" {{ $jurnal->id_perusahaan == $perusahaan->id ? 'selected' : '' }}>
                                {{ $perusahaan->nama_perusahaan }}
                            </option>
                        @endforeach
                    </select>
                @else
                <input type="hidden" name="id_perusahaan" value="{{ $jurnal->id_perusahaan }}">
                <input type="text" class="form-control" value="{{ $jurnal->perusahaan->nama_perusahaan ?? 'Tidak ditemukan' }}" disabled>

                @endif
            </div>

            <div class="mb-3">
                <label>Tanggal</label>
                <input type="date" name="tanggal" class="form-control" value="{{ old('tanggal', $jurnal->tanggal) }}" required>
            </div>

            <div class="mb-3">
                <label>Keterangan</label>
                <textarea name="keterangan" class="form-control" rows="2">{{ old('keterangan', $jurnal->keterangan) }}</textarea>
            </div>

            <table class="table table-bordered" id="jurnal-table">
                <thead>
                    <tr>
                        <th>Akun</th>
                        <th>Debit</th>
                        <th>Kredit</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($jurnal->details as $index => $detail)
                    <tr>
                        <td>
                            <select name="rows[{{ $index }}][coa_id]" class="form-select" required>
                                <option value="">-- Pilih Akun --</option>
                                @foreach($coa as $c)
                                    <option value="{{ $c->id }}" {{ $detail->coa_id == $c->id ? 'selected' : '' }}>
                                        {{ $c->no_akun }} - {{ $c->nama_akun }}
                                    </option>
                                @endforeach
                            </select>
                        </td>
                        <td><input type="number" name="rows[{{ $index }}][debit]" step="0.01" class="form-control" value="{{ $detail->debit }}"></td>
                        <td><input type="number" name="rows[{{ $index }}][kredit]" step="0.01" class="form-control" value="{{ $detail->kredit }}"></td>
                        <td><button type="button" class="btn btn-danger btn-sm remove-row">Hapus</button></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <button type="button" class="btn btn-secondary btn-sm mb-3" id="add-row">+ Tambah Baris</button>

            <div class="text-end">
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
    let index = {{ $jurnal->details->count() }};

    document.getElementById('add-row').addEventListener('click', function () {
        let row = `
        <tr>
            <td>
                <select name="rows[${index}][coa_id]" class="form-select" required>
                    <option value="">-- Pilih Akun --</option>
                    @foreach($coa as $c)
                        <option value="{{ $c->id }}">{{ $c->no_akun }} - {{ $c->nama_akun }}</option>
                    @endforeach
                </select>
            </td>
            <td><input type="number" name="rows[${index}][debit]" step="0.01" class="form-control"></td>
            <td><input type="number" name="rows[${index}][kredit]" step="0.01" class="form-control"></td>
            <td><button type="button" class="btn btn-danger btn-sm remove-row">Hapus</button></td>
        </tr>`;
        document.querySelector('#jurnal-table tbody').insertAdjacentHTML('beforeend', row);
        index++;
    });

    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('remove-row')) {
            e.target.closest('tr').remove();
        }
    });
</script>
@endsection

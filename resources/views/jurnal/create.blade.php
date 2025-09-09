@extends('layout.master')

@section('content')
<div class="row">
    <div class="col-md-12">
        <h4 class="mb-4">Input Jurnal Umum</h4>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @elseif(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <form action="{{ route('jurnal.store') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label class="form-label">Perusahaan</label>
                <select name="id_perusahaan" class="form-control" required>
                    <option value="">-- Pilih Perusahaan --</option>
                    @foreach($perusahaans as $perusahaan)
                        <option value="{{ $perusahaan->id }}">{{ $perusahaan->nama_perusahaan }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <label>Tanggal</label>
                <input type="date" name="tanggal" class="form-control" required>
            </div>

            <div class="mb-3">
                <label>Keterangan</label>
                <textarea name="keterangan" class="form-control" rows="2"></textarea>
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
                    <tr>
                        <td>
                            <select name="rows[0][coa_id]" class="form-select" required>
                                <option value="">-- Pilih Akun --</option>
                                @foreach($coa as $c)
                                    <option value="{{ $c->id }}">{{ $c->no_akun }} - {{ $c->nama_akun }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td><input type="number" name="rows[0][debit]" step="0.01" class="form-control"></td>
                        <td><input type="number" name="rows[0][kredit]" step="0.01" class="form-control"></td>
                        <td><button type="button" class="btn btn-danger btn-sm remove-row">Hapus</button></td>
                    </tr>
                </tbody>
            </table>

            <button type="button" class="btn btn-secondary btn-sm mb-3" id="add-row">+ Tambah Baris</button>

            <div class="text-end">
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
    let index = 1;
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

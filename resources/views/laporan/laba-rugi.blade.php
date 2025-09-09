@extends('layout.master')

@section('content')
<div class="row">
    <div class="col-md-12">
        <h4 class="mb-4">Laporan Laba Rugi</h4>

        <form method="GET" class="row g-3 mb-4">
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
                <button class="btn btn-primary" type="submit">Tampilkan</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Kategori</th>
                        <th>No Akun</th>
                        <th>Nama Akun</th>
                        <th class="text-end">Saldo</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $totalPendapatan = 0;
                        $totalBeban = 0;
                    @endphp

                    @foreach($data->groupBy('tipe') as $tipe => $rows)
                        <tr class="table-secondary fw-bold">
                            <td colspan="4">{{ strtoupper($tipe) }}</td>
                        </tr>
                        @foreach($rows as $row)
                            <tr>
                                <td></td>
                                <td>{{ $row['no_akun'] }}</td>
                                <td>{{ $row['nama_akun'] }}</td>
                                <td class="text-end">{{ number_format($row['saldo'], 0, ',', '.') }}</td>
                            </tr>

                            @php
                                if ($tipe == 'Pendapatan') $totalPendapatan += $row['saldo'];
                                if ($tipe == 'Beban') $totalBeban += $row['saldo'];
                            @endphp
                        @endforeach
                    @endforeach

                    <tr class="fw-bold table-light">
                        <td colspan="3" class="text-end">Total Pendapatan</td>
                        <td class="text-end">{{ number_format($totalPendapatan, 0, ',', '.') }}</td>
                    </tr>
                    <tr class="fw-bold table-light">
                        <td colspan="3" class="text-end">Total Beban</td>
                        <td class="text-end">{{ number_format($totalBeban, 0, ',', '.') }}</td>
                    </tr>
                    <tr class="fw-bold table-success">
                        <td colspan="3" class="text-end">Laba Bersih</td>
                        <td class="text-end">{{ number_format($labaBersih, 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

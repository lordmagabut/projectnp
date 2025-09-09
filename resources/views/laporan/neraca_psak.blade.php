@extends('layout.master')

@section('content')
<div class="row">
    <div class="col-md-12">
        <h4 class="mb-4">Laporan Neraca - {{ $nama_perusahaan }}</h4>

        <form method="GET" class="row g-3 mb-4">
            <div class="col-md-3">
                <label>Perusahaan</label>
                <select name="id_perusahaan" class="form-select">
                    @foreach($perusahaans as $p)
                        <option value="{{ $p->id }}" {{ $selectedPerusahaanId == $p->id ? 'selected' : '' }}>
                            {{ $p->nama_perusahaan }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label>Tanggal Awal</label>
                <input type="date" name="tanggal_awal" class="form-control" value="{{ $tanggalAwal }}">
            </div>
            <div class="col-md-3">
                <label>Tanggal Akhir</label>
                <input type="date" name="tanggal_akhir" class="form-control" value="{{ $tanggalAkhir }}">
            </div>
            <div class="col-md-2 d-grid">
                <label class="invisible">Tampilkan</label>
                <button class="btn btn-primary">Tampilkan</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width: 60%">Akun</th>
                        <th class="text-end" style="width: 40%">Saldo (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        function renderSection($tree, $kategori) {
                            $total = 0;
                            foreach ($tree as $row) {
                                if ($row['tipe'] === $kategori) {
                                    $indent = $row['parent_id'] ? 20 : 0;
                                    echo '<tr>';
                                    echo '<td style="padding-left: ' . $indent . 'px;">';
                                    echo $row['no_akun'] . ' - ' . $row['nama_akun'];
                                    echo '</td>';
                                    echo '<td class="text-end">' . number_format($row['saldo'], 0, ',', '.') . '</td>';
                                    echo '</tr>';
                                    $total += $row['saldo'];
                                }
                                if (!empty($row['children'])) {
                                    $total += renderSection($row['children'], $kategori);
                                }
                            }
                            return $total;
                        }

                        $totalAset = renderSection($coaTree, 'Aset Lancar') + renderSection($coaTree, 'Aset Tidak Lancar');
                        $totalLiabilitas = renderSection($coaTree, 'Kewajiban Jangka Pendek') + renderSection($coaTree, 'Kewajiban Jangka Panjang');
                        $totalEkuitas = renderSection($coaTree, 'Ekuitas');
                    @endphp

                    <tr class="table-secondary fw-bold">
                        <td colspan="2">ASET</td>
                    </tr>
                    @php renderSection($coaTree, 'Aset Lancar'); @endphp
                    @php renderSection($coaTree, 'Aset Tidak Lancar'); @endphp
                    <tr class="fw-bold table-light">
                        <td class="text-end">Total Aset</td>
                        <td class="text-end">{{ number_format($totalAset, 0, ',', '.') }}</td>
                    </tr>

                    <tr class="table-secondary fw-bold">
                        <td colspan="2">LIABILITAS</td>
                    </tr>
                    @php renderSection($coaTree, 'Kewajiban Jangka Pendek'); @endphp
                    @php renderSection($coaTree, 'Kewajiban Jangka Panjang'); @endphp

                    <tr class="table-secondary fw-bold">
                        <td colspan="2">EKUITAS</td>
                    </tr>
                    @php renderSection($coaTree, 'Ekuitas'); @endphp

                    <tr class="fw-bold table-light">
                        <td class="text-end">Total Liabilitas + Ekuitas</td>
                        <td class="text-end">{{ number_format($totalLiabilitas + $totalEkuitas, 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

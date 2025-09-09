@extends('layout.master')

@section('content')
<div class="row">
    <div class="col-lg-12">
        <h4 class="mb-3">Kurva-S Proyek</h4>
        <form method="POST" action="{{ route('projectTask.generateUlang', request()->route('proyek_id')) }}" onsubmit="return confirm('Generate ulang akan menghapus rencana mingguan lama dan menghitung ulang berdasarkan tanggal mulai. Lanjutkan?')">
            @csrf
            <button type="submit" class="btn btn-warning mb-3">
                🔁 Generate Ulang Rencana Mingguan
            </button>
        </form>
        @php
            // Hitung total rencana per minggu (seluruh task)
            $mingguan_total = array_fill(1, $durasi_minggu, 0);
            foreach ($tasks as $task) {
                foreach ($task->rencanaMingguan as $rencana) {
                    $mingguan_total[$rencana->minggu_ke] += $rencana->bobot_mingguan;
                }
            }

            // Filter hanya minggu yang memiliki nilai > 0
            $minggu_aktif = collect($mingguan_total)
                ->filter(fn($val) => $val > 0)
                ->keys()
                ->values()
                ->all();
        @endphp

        <div class="table-responsive mb-5">
            <table class="table table-bordered text-center align-middle">
                <thead class="table-light">
                    <tr>
                        <th rowspan="2">Kode</th>
                        <th rowspan="2">Deskripsi</th>
                        @foreach ($minggu_aktif as $m)
                            <th>M{{ $m }}</th>
                        @endforeach
                        <th rowspan="2">Total (%)</th>
                    </tr>
                    <tr>
                        @foreach ($minggu_aktif as $m)
                            <th>{{ $m }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($tasks as $task)
                        @php $total = 0; @endphp
                        <tr>
                            <td>{{ $task->kode }}</td>
                            <td class="text-start">{{ $task->deskripsi }}</td>
                            @foreach ($minggu_aktif as $m)
                                @php
                                    $rencana = $task->rencanaMingguan->firstWhere('minggu_ke', $m);
                                    $nilai = $rencana->bobot_mingguan ?? 0;
                                    $total += $nilai;
                                @endphp
                                <td>{{ $nilai > 0 ? number_format($nilai, 2) : '' }}</td>
                            @endforeach
                            <td><strong>{{ number_format($total, 2) }}</strong></td>
                        </tr>
                    @endforeach
                    <tr class="table-secondary fw-bold">
                        <td colspan="2">Total Rencana (%)</td>
                        @foreach ($minggu_aktif as $m)
                            <td>{{ number_format($mingguan_total[$m], 2) }}</td>
                        @endforeach
                        <td>{{ number_format(array_sum($mingguan_total), 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <h5 class="mb-3">Grafik Kurva-S (Akumulasi Rencana)</h5>
        <canvas id="kurvaSChart" height="100"></canvas>
    </div>
</div>
@endsection

@push('custom-scripts')

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const labels = {!! json_encode(array_map(fn($m) => 'M'.$m, $minggu_aktif)) !!};
    const dataRencana = [];
    let akumulatif = 0;

    @foreach ($minggu_aktif as $m)
        akumulatif += {{ $mingguan_total[$m] }};
        dataRencana.push(+(akumulatif.toFixed(2)));
    @endforeach

    const ctx = document.getElementById('kurvaSChart').getContext('2d');
    if (dataRencana.length > 0) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Akumulasi Rencana',
                    data: dataRencana,
                    borderWidth: 2,
                    fill: false,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        suggestedMax: 100
                    }
                }
            }
        });
    }
</script>
@endpush

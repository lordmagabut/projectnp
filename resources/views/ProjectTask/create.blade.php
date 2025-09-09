@extends('layout.master')

@section('content')
<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card p-4">
            <form method="POST" action="{{ route('projectTask.store') }}">
                @csrf
                @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
                @endif
                <input type="hidden" name="proyek_id" value="{{ $proyek->id }}">
                <h5>Proyek: {{ $proyek->nama_proyek }}</h5>
                @if(isset($totalMinggu) && isset($proyekStart))
                <div class="card mt-4">
                    <div class="card-body">
                        <h6>Referensi Minggu Proyek ({{ $proyekStart->format('d M Y') }} s/d {{ $proyekEnd->format('d M Y') }})</h6>
                        <div style="overflow-x: auto;">
                            <table class="table table-bordered table-sm text-center w-auto" style="min-width: max-content;">
                                <thead>
                                    <tr>
                                        <th class="sticky-col bg-light">Minggu</th>
                                        @for ($i = 1; $i <= $totalMinggu; $i++)
                                            <th>{{ $i }}</th>
                                        @endfor
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="sticky-col bg-light">Tanggal Mulai</td>
                                        @for ($i = 1; $i <= $totalMinggu; $i++)
                                            @php
                                                $startDate = \Carbon\Carbon::parse($proyekStart)->addWeeks($i - 1)->startOfWeek();
                                            @endphp
                                            <td>{{ $startDate->format('d-m-Y') }}</td>
                                        @endfor
                                    </tr>
                                    <tr>
                                        <td class="sticky-col bg-light">Tanggal Akhir</td>
                                        @for ($i = 1; $i <= $totalMinggu; $i++)
                                            @php
                                                $endDate = \Carbon\Carbon::parse($proyekStart)->addWeeks($i - 1)->endOfWeek();
                                            @endphp
                                            <td>{{ $endDate->format('d-m-Y') }}</td>
                                        @endfor
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @endif

                <table class="table table-bordered" id="task-table">
                    <thead>
                        <tr>
                            <th>WBS</th>
                            <th>WBS Induk</th>
                            <th>Deskripsi</th>
                            <th>Bobot (%)</th>
                            <th>Minggu ke-</th>
                            <th>Durasi (minggu)</th>
                            <th><button type="button" class="btn btn-sm btn-success" id="add-row">+</button></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><input name="tasks[0][kode]" class="form-control" /></td>

                            <td>
                                <select name="tasks[0][parent_id]" class="form-select">
                                    <option value="">-- Tidak Ada --</option>
                                    @foreach ($existingTasks as $t)
                                        <option value="{{ $t->id }}">{{ $t->kode }} - {{ $t->deskripsi }}</option>
                                    @endforeach
                                </select>
                            </td>

                            <td><input name="tasks[0][deskripsi]" class="form-control" required /></td>
                            <td><input name="tasks[0][bobot]" type="number" step="0.0001" class="form-control"/></td>
                            <td><input name="tasks[0][minggu_ke]" type="number" class="form-control" /></td>
                            <td><input name="tasks[0][durasi]" type="number" class="form-control"/></td>
                            <td><button type="button" class="btn btn-sm btn-danger remove-row">-</button></td>
                        </tr>
                    </tbody>
                </table>

                <button type="submit" class="btn btn-primary mt-3">Simpan Semua</button>
            </form>
        </div>
    </div>
</div>
@endsection
@push('custom-scripts')
<style>
    .sticky-col {
        position: sticky;
        left: 0;
        z-index: 2;
        background-color: #f8f9fa;
    }

    .sticky-col:first-child {
        z-index: 3;
    }
</style>
<script>
let rowIdx = 1;
document.getElementById('add-row').addEventListener('click', function() {
    const tbody = document.querySelector('#task-table tbody');
    const newRow = tbody.rows[0].cloneNode(true);

    newRow.querySelectorAll('input, select').forEach((el) => {
        el.name = el.name.replace(/\d+/, rowIdx);
        if (el.tagName === 'SELECT') {
            el.selectedIndex = 0;
        } else {
            el.value = '';
        }
    });

    tbody.appendChild(newRow);
    rowIdx++;
});

document.addEventListener('click', function(e) {
    if (e.target.classList.contains('remove-row')) {
        if (document.querySelectorAll('#task-table tbody tr').length > 1) {
            e.target.closest('tr').remove();
        }
    }
});
</script>
@endpush


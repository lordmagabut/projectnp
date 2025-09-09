@extends('layout.master')

@section('content')
<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card p-4">
            <form method="POST" action="{{ route('projectTask.store') }}">
                @csrf

                <div class="mb-3">
                    <label class="form-label">Proyek</label>
                    <select name="proyek_id" class="form-select" required>
                        <option value="">-- Pilih Proyek --</option>
                        @foreach ($proyekList as $proyek)
                            <option value="{{ $proyek->id }}">{{ $proyek->nama_proyek }}</option>
                        @endforeach
                    </select>
                </div>

                <table class="table table-bordered" id="task-table">
                    <thead>
                        <tr>
                            <th>WBS</th>
                            <th>WBS Induk</th>
                            <th>Deskripsi</th>
                            <th>Bobot (%)</th>
                            <th>Tanggal Mulai</th>
                            <th>Durasi (minggu)</th>
                            <th><button type="button" class="btn btn-sm btn-success" id="add-row">+</button></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            @if($proyekStart)
                                <p>Mulai: {{ $proyekStart->format('d-m-Y') }}</p>
                            @endif

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
                            <td><input name="tasks[0][tanggal_mulai]" type="date" class="form-control"/></td>
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
@endsection

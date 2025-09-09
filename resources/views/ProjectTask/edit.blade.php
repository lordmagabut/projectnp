@extends('layout.master')

@section('content')
<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card p-4">
            <h4 class="mb-3">Edit Task Proyek: {{ $proyek->nama_proyek }}</h4>

            <form method="POST" action="{{ route('projectTask.update', $task->id) }}">
                @csrf
                @method('PUT')

                <input type="hidden" name="proyek_id" value="{{ $proyek->id }}">

                @if(session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">WBS</label>
                        <input name="kode" class="form-control" value="{{ old('kode', $task->kode) }}" />
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">WBS Induk</label>
                        <select name="parent_id" class="form-select">
                            <option value="">-- Tidak Ada --</option>
                            @foreach ($existingTasks as $t)
                                <option value="{{ $t->id }}" {{ $task->parent_id == $t->id ? 'selected' : '' }}>{{ $t->kode }} - {{ $t->deskripsi }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Deskripsi</label>
                        <input name="deskripsi" class="form-control" value="{{ old('deskripsi', $task->deskripsi) }}" required />
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Bobot (%)</label>
                        <input name="bobot" type="number" step="0.0001" class="form-control" value="{{ old('bobot', $task->bobot) }}" />
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Minggu ke-</label>
                        <input name="minggu_ke" value="{{ old('minggu_ke', $task->minggu_ke) }}" class="form-control">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Durasi (minggu)</label>
                        <input name="durasi" type="number" class="form-control" value="{{ old('durasi', $task->durasi) }}" />
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Update</button>
                <a href="{{ route('proyek.show', $proyek->id) }}" class="btn btn-secondary">Kembali</a>
            </form>

            {{-- Referensi Minggu --}}
            @if(isset($totalMinggu) && isset($proyekStart))
                <div class="card mt-4">
                    <div class="card-body">
                        <h6>Referensi Minggu Proyek ({{ $proyekStart->format('d M Y') }} s/d {{ $proyekEnd->format('d M Y') }})</h6>
                        <div style="overflow-x: auto;">
                            <table class="table table-sm table-bordered table-striped w-auto" style="min-width: max-content;">
                                <thead>
                                    <tr class="text-center">
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
        </div>
    </div>
</div>

{{-- Sticky column CSS --}}
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
@endsection

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
                <div class="mb-3">
                    <label class="form-label">WBS</label>
                    <input name="kode" class="form-control" value="{{ old('kode', $task->kode) }}" />
                </div>

                <div class="mb-3">
                    <label class="form-label">WBS Induk</label>
                    <select name="parent_id" class="form-select">
                        <option value="">-- Tidak Ada --</option>
                        @foreach ($existingTasks as $t)
                            <option value="{{ $t->id }}" {{ $task->parent_id == $t->id ? 'selected' : '' }}>{{ $t->kode }} - {{ $t->deskripsi }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Deskripsi</label>
                    <input name="deskripsi" class="form-control" value="{{ old('deskripsi', $task->deskripsi) }}" required />
                </div>

                <div class="mb-3">
                    <label class="form-label">Bobot (%)</label>
                    <input name="bobot" type="number" step="0.0001" class="form-control" value="{{ old('bobot', $task->bobot) }}" />
                </div>

                <div class="mb-3">
                    <label class="form-label">Minggu ke-</label>
                    <input name="minggu_ke" value="{{ old('minggu_ke', $task->minggu_ke) }}" class="form-control">
                </div>

                <div class="mb-3">
                    <label class="form-label">Durasi (minggu)</label>
                    <input name="durasi" type="number" class="form-control" value="{{ old('durasi', $task->durasi) }}" />
                </div>

                <button type="submit" class="btn btn-primary">Update</button>
                <a href="{{ route('proyek.show', $proyek->id) }}" class="btn btn-secondary">Kembali</a>
            </form>
        </div>
    </div>
</div>
@endsection

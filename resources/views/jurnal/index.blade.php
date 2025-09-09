@extends('layout.master')

@push('plugin-styles')
<link rel="stylesheet" href="{{ asset('assets/plugins/datatables-net-bs5/dataTables.bootstrap5.css') }}">
<link href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap5.min.css" rel="stylesheet" />
@endpush

@section('content')
<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="card-title">Daftar Jurnal Umum</h4>
                    @if(auth()->user()->buat_jurnal == 1)
                        <a href="{{ route('jurnal.create') }}" class="btn btn-sm btn-success">Tambah Jurnal</a>
                    @endif
                </div>

                <form method="GET" class="row g-3 align-items-end mb-4">
                    <div class="col-md-4">
                        <label class="form-label">Perusahaan</label>
                        <select name="id_perusahaan" class="form-select">
                            <option value="">-- Semua Perusahaan --</option>
                            @foreach($perusahaans as $p)
                                <option value="{{ $p->id }}" {{ $request->id_perusahaan == $p->id ? 'selected' : '' }}>
                                    {{ $p->nama_perusahaan }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    @php
                        $defaultAwal = now()->startOfYear()->format('Y-m-d');
                        $defaultAkhir = now()->endOfMonth()->format('Y-m-d');
                    @endphp

                    <div class="col-md-3">
                        <label class="form-label">Tanggal Awal</label>
                        <input type="date" name="tanggal_awal" class="form-control" value="{{ $request->tanggal_awal ?? $defaultAwal }}">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Tanggal Akhir</label>
                        <input type="date" name="tanggal_akhir" class="form-control" value="{{ $request->tanggal_akhir ?? $defaultAkhir }}">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Akun (COA)</label>
                        <select name="coa_id" class="form-select">
                            <option value="">-- Semua Akun --</option>
                            @foreach($coaList as $coa)
                                <option value="{{ $coa->id }}" {{ $request->coa_id == $coa->id ? 'selected' : '' }}>
                                    {{ $coa->no_akun }} - {{ $coa->nama_akun }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                </form>

                <div class="table-responsive">
                    <table id="dataTableJurnal" class="table table-hover table-striped align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal</th>
                                <th>No Jurnal</th>
                                <th>Keterangan</th>
                                <th>Akun</th>
                                <th class="text-end">Debit</th>
                                <th class="text-end">Kredit</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $totalDebit = 0;
                                $totalKredit = 0;
                                $renderedJurnalIds = [];
                            @endphp

                            @foreach($jurnalDetails as $row)
                                @php
                                    $totalDebit += $row->debit;
                                    $totalKredit += $row->kredit;
                                @endphp
                                <tr>
                                    <td>{{ $row->jurnal->tanggal }}</td>
                                    <td>{{ $row->jurnal->no_jurnal }}</td>
                                    <td>{{ $row->jurnal->keterangan }}</td>
                                    <td>{{ $row->coa->no_akun }} - {{ $row->coa->nama_akun }}</td>
                                    <td class="text-end">{{ number_format($row->debit, 0, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format($row->kredit, 0, ',', '.') }}</td>
                                    <td class="text-center">
                                        @if(!in_array($row->jurnal_id, $renderedJurnalIds))
                                        @if(auth()->user()->edit_jurnal == 1)
                                            <a href="{{ route('jurnal.edit', $row->jurnal_id) }}" class="btn btn-sm btn-primary me-1">Edit</a>
                                        @endif
                                        @if(auth()->user()->hapus_jurnal == 1)
                                            <form action="{{ route('jurnal.destroy', $row->jurnal_id) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus jurnal ini?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                            </form>
                                        @endif
                                            @php $renderedJurnalIds[] = $row->jurnal_id; @endphp
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="4" class="text-end">Total</th>
                                <th class="text-end">{{ number_format($totalDebit, 0, ',', '.') }}</th>
                                <th class="text-end">{{ number_format($totalKredit, 0, ',', '.') }}</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('plugin-scripts')
<script src="{{ asset('assets/plugins/datatables-net/jquery.dataTables.js') }}"></script>
<script src="{{ asset('assets/plugins/datatables-net-bs5/dataTables.bootstrap5.js') }}"></script>
@endpush

@push('custom-scripts')
<script>
    $(document).ready(function() {
        $('#dataTableJurnal').DataTable({
            responsive: true,
            ordering: false,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json'
            }
        });
    });
</script>
@endpush

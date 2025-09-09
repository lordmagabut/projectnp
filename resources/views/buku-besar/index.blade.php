@extends('layout.master')

@section('content')
<div class="row mb-3">
    <div class="col-md-12">
        <h4 class="mb-3">Buku Besar</h4>

        <form method="GET" class="row g-3 align-items-end mb-4">
            <div class="col-md-4">
                <label class="form-label">Perusahaan</label>
                <select name="id_perusahaan" class="form-select">
                    <option value="">-- Semua Perusahaan --</option>
                    @foreach($perusahaans as $p)
                        <option value="{{ $p->id }}" {{ $selectedPerusahaanId == $p->id ? 'selected' : '' }}>
                            {{ $p->nama_perusahaan }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Akun (COA)</label>
                <select name="coa_id" class="form-select">
                    <option value="">-- Pilih Akun --</option>
                    @foreach($coaList as $coa)
                        <option value="{{ $coa->id }}" {{ $selectedCoaId == $coa->id ? 'selected' : '' }}>
                            {{ $coa->no_akun }} - {{ $coa->nama_akun }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label">Tanggal Awal</label>
                <input type="date" name="tanggal_awal" value="{{ $tanggalAwal }}" class="form-control">
            </div>

            <div class="col-md-2">
                <label class="form-label">Tanggal Akhir</label>
                <input type="date" name="tanggal_akhir" value="{{ $tanggalAkhir }}" class="form-control">
            </div>

            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
        </form>

        @if($data && count($data) > 0)
            <div class="table-responsive">
                <table id="bukuBesarTable" class="table table-hover table-striped align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Tanggal</th>
                            <th>No Jurnal</th>
                            <th>Keterangan</th>
                            <th class="text-end">Debit</th>
                            <th class="text-end">Kredit</th>
                            <th class="text-end">Saldo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $saldo = 0; @endphp
                        @foreach($data as $row)
                            @php
                                $saldo += ($row->debit - $row->kredit);
                            @endphp
                            <tr>
                                <td>{{ $row->jurnal->tanggal }}</td>
                                <td>
                                    <a href="#" class="text-decoration-none"
                                       onclick="showJurnalDetail({{ $row->jurnal->id }})">
                                        {{ $row->jurnal->no_jurnal }}
                                    </a>
                                </td>
                                <td>{{ $row->jurnal->keterangan }}</td>
                                <td class="text-end">{{ number_format($row->debit, 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($row->kredit, 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($saldo, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @elseif($selectedCoaId)
            <div class="alert alert-warning">Tidak ada transaksi untuk akun yang dipilih dalam rentang waktu ini.</div>
        @endif
    </div>
</div>

{{-- Modal container --}}
<div id="modalContainer"></div>
@endsection

@push('plugin-styles')
<link rel="stylesheet" href="{{ asset('assets/plugins/datatables-net-bs5/dataTables.bootstrap5.css') }}">
@endpush

@push('plugin-scripts')
<script src="{{ asset('assets/plugins/datatables-net/jquery.dataTables.js') }}"></script>
<script src="{{ asset('assets/plugins/datatables-net-bs5/dataTables.bootstrap5.js') }}"></script>
@endpush

@push('custom-scripts')
<script>
    $(document).ready(function () {
        $('#bukuBesarTable').DataTable({
            ordering: false,
            searching: false,
            paging: false,
            info: false,
            responsive: true,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json'
            }
        });
    });

    function showJurnalDetail(jurnalId) {
        // Optional: Tampilkan spinner atau loading di modal
        $('#modalContainer').html('<div class="text-center p-4">Memuat...</div>');

        $.get("{{ url('/jurnal/detail') }}/" + jurnalId, function(response) {

            // Buat modal baru
            let html = `
            <div class="modal fade" id="jurnalModal" tabindex="-1" aria-labelledby="jurnalModalLabel" aria-hidden="true">
              <div class="modal-dialog modal-lg">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title">Detail Jurnal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body">
                    <p><strong>No Jurnal:</strong> ${response.jurnal.no_jurnal}</p>
                    <p><strong>Tanggal:</strong> ${response.jurnal.tanggal}</p>
                    <p><strong>Perusahaan:</strong> ${response.jurnal.perusahaan?.nama_perusahaan || '-'}</p>
                    <p><strong>Keterangan:</strong> ${response.jurnal.keterangan}</p>

                    <table class="table table-bordered mt-3">
                        <thead class="table-light">
                            <tr>
                                <th>Akun</th>
                                <th class="text-end">Debit</th>
                                <th class="text-end">Kredit</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${response.details.map(row => `
                                <tr>
                                    <td>${row.akun}</td>
                                    <td class="text-end">${row.debit}</td>
                                    <td class="text-end">${row.kredit}</td>
                                </tr>`).join('')}
                        </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>`;

            $('#modalContainer').html(html);
            new bootstrap.Modal(document.getElementById('jurnalModal')).show();
        });
    }
</script>
@endpush

@extends('layout.master')

@push('plugin-styles')
<link href="{{ asset('assets/plugins/datatables-net-bs5/dataTables.bootstrap5.css') }}" rel="stylesheet" />
<link href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap5.min.css" rel="stylesheet" />
@endpush

@section('content')
<div class="row">
  <div class="col-12 grid-margin stretch-card">
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
          <h4 class="card-title m-0">Daftar RAB Penawaran</h4>
          <div class="d-flex gap-2">
            <input type="text" id="globalSearch" class="form-control form-control-sm" placeholder="Cari penawaran...">
            @can('buat penawaran')
              <a href="{{ route('proyek.penawaran.create', $proyek->id) }}" class="btn btn-primary btn-sm">
                <i data-feather="plus" class="me-1"></i> Tambah Penawaran
              </a>
            @endcan
          </div>
        </div>

        <div class="table-responsive">
          <table id="tPenawaran" class="table table-hover align-middle display nowrap" style="width:100%">
            <thead>
              <tr>
                <th style="width:50px">#</th>
                <th>Nama/No Penawaran</th> {{-- opsional ganti label --}}
                <th>Proyek</th>
                <th>Tanggal</th>
                <th class="text-end">Total</th>
                <th>Status</th>
                <th class="text-end" style="width:90px">Aksi</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>

      </div>
    </div>
  </div>
</div>

{{-- Modal Hapus --}}
<div class="modal fade" id="modalDelete" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form id="formDelete" method="POST">
      @csrf
      @method('DELETE')
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Hapus Penawaran</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
        </div>
        <div class="modal-body">
          <p class="mb-0">Yakin ingin menghapus penawaran <span id="deleteLabel" class="fw-semibold"></span> ? Tindakan ini tidak dapat dibatalkan.</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-danger">Hapus</button>
        </div>
      </div>
    </form>
  </div>
</div>
@endsection

@push('plugin-scripts')
<script src="{{ asset('assets/plugins/datatables-net/jquery.dataTables.js') }}"></script>
<script src="{{ asset('assets/plugins/datatables-net-bs5/dataTables.bootstrap5.js') }}"></script>
<script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap5.min.js"></script>
@endpush

@push('custom-scripts')
<script>
  const rupiah = (num) => {
    if (num === null || num === undefined) return 'Rp 0';
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(num);
  };

  $(function () {
    const table = $('#tPenawaran').DataTable({
      processing: true,
      serverSide: true,
      responsive: true,
      lengthChange: true,
      pageLength: 10,
      order: [[3, 'desc']], // sort tanggal desc
      ajax: { // FIX: jangan duplikasi
        url: "{{ route('proyek.penawaran.data', $proyek->id) }}",
        type: "GET",
      },
      columns: [
        { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable:false, searchable:false },
        { data: 'kode', name: 'nama_penawaran', // alias di controller
          render: function(data, type, row){
            return `<a href="{{ url('proyek/'.$proyek->id.'/penawaran') }}/${row.id}" class="text-decoration-none fw-semibold">${data ?? '-'}</a>`;
          }
        },
        { data: 'proyek_nama', name: 'proyek_nama' },
        { data: 'tanggal_penawaran', name: 'tanggal_penawaran',
          render: function(d){
            if(!d) return '-';
            const date = new Date(d);
            const dd = String(date.getDate()).padStart(2,'0');
            const mm = String(date.getMonth()+1).padStart(2,'0');
            const yy = date.getFullYear();
            return `${dd}-${mm}-${yy}`;
          }
        },
        { data: 'total', name: 'final_total_penawaran', className:'text-end',
          render: function(d){ return rupiah(d); }
        },
        { data: 'status', name: 'status',
          render: function(s){
            const map = { 'draft':'badge bg-secondary', 'final':'badge bg-success', 'revisi':'badge bg-warning text-dark', 'ditolak':'badge bg-danger' };
            const cls = map[(s||'').toLowerCase()] || 'badge bg-light text-dark';
            return `<span class="${cls} text-uppercase">${s ?? 'DRAFT'}</span>`;
          }
        },
        { data: null, orderable:false, searchable:false, className:'text-end',
          render: function(_, __, row){
            const base = `{{ url('proyek/'.$proyek->id.'/penawaran') }}/${row.id}`;
            return `
              <div class="dropdown text-end">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">Aksi</button>
                <ul class="dropdown-menu dropdown-menu-end">
                  <li><a class="dropdown-item" href="${base}"><i data-feather="eye" class="me-2"></i>Lihat</a></li>
                  <li><a class="dropdown-item" href="${base}/edit"><i data-feather="edit-2" class="me-2"></i>Edit</a></li>
                  <li><a class="dropdown-item" target="_blank" href="${base}/pdf"><i data-feather="printer" class="me-2"></i>PDF</a></li>
                  <li><hr class="dropdown-divider"></li>
                  <li><a href="#" class="dropdown-item text-danger btn-delete" data-url="${base}" data-label="${row.kode ?? '-'}"><i data-feather="trash-2" class="me-2"></i>Hapus</a></li>
                </ul>
              </div>
            `;
          }
        }
      ],
      drawCallback: function(){
        if (window.feather) window.feather.replace();
      }
    });

    // Global search
    $('#globalSearch').on('keyup', function(){
      table.search(this.value).draw();
    });

    // Hapus (modal)
    $(document).on('click', '.btn-delete', function(e){
      e.preventDefault();
      const url = $(this).data('url');
      const label = $(this).data('label');
      $('#formDelete').attr('action', url);
      $('#deleteLabel').text(label);
      const modal = new bootstrap.Modal(document.getElementById('modalDelete'));
      modal.show();
    });
  });
</script>
@endpush

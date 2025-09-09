<div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
  <h5 class="m-0 d-flex align-items-center">
    <i data-feather="dollar-sign" class="me-2"></i> RAB Penawaran
  </h5>
  <div class="d-flex gap-2">
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
        <th>Nama/No Penawaran</th>
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

{{-- Modal Hapus (khusus penawaran) --}}
<div class="modal fade" id="modalDeletePenawaran" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form id="formDeletePenawaran" method="POST">
      @csrf
      @method('DELETE')
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Hapus Penawaran</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
        </div>
        <div class="modal-body">
          <p class="mb-0">Yakin ingin menghapus penawaran <span id="deleteLabelPenawaran" class="fw-semibold"></span>? Tindakan ini tidak dapat dibatalkan.</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-danger">Hapus</button>
        </div>
      </div>
    </form>
  </div>
</div>

@push('custom-scripts')
<script>
(function() {
  const rupiah = (num) => {
    if (num === null || num === undefined) return 'Rp 0';
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(num);
  };

  // Inisialisasi DataTable (sekali saja)
  let tablePenawaran = $('#tPenawaran').DataTable({
    processing: true,
    serverSide: true,
    responsive: true,
    lengthChange: true,
    pageLength: 10,
    order: [[3, 'desc']], // sort tanggal desc
    ajax: {
      url: "{{ route('proyek.penawaran.data', $proyek->id) }}",
      type: "GET",
    },
    columns: [
      { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable:false, searchable:false },
      { data: 'kode', name: 'nama_penawaran',
        render: function(data, type, row){
          const url = `{{ url('proyek/'.$proyek->id.'/penawaran') }}/${row.id}`;
          return `<a href="${url}" class="text-decoration-none fw-semibold">${data ?? '-'}</a>`;
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
          const base = `{{ url('proyek/'.$proyek->id.'/penawaran/') }}/${row.id}`;
          return `
            <div class="dropdown text-end">
              <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">Aksi</button>
              <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="${base}"><i data-feather="eye" class="me-2"></i>Lihat</a></li>
                @can('edit penawaran')
                <li><a class="dropdown-item" href="${base}/edit"><i data-feather="edit-2" class="me-2"></i>Edit</a></li>
                @endcan
                <li><a class="dropdown-item" target="_blank" href="${base}/pdf"><i data-feather="printer" class="me-2"></i>PDF</a></li>
                @can('hapus penawaran')
                <li><hr class="dropdown-divider"></li>
                <li><a href="#" class="dropdown-item text-danger btn-delete-penawaran" data-url="${base}" data-label="${row.kode ?? '-'}"><i data-feather="trash-2" class="me-2"></i>Hapus</a></li>
                @endcan
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

  // Adjust kolom saat tab Penawaran ditampilkan (agar layout tidak pecah)
  document.querySelector('a#rabpenawaran-tab')?.addEventListener('shown.bs.tab', function () {
    tablePenawaran.columns.adjust().responsive.recalc();
  });

  // Pencarian global khusus penawaran
  $('#penawaranSearch').on('keyup', function(){
    tablePenawaran.search(this.value).draw();
  });

  // Hapus (modal)
  $(document).on('click', '.btn-delete-penawaran', function(e){
    e.preventDefault();
    const url = $(this).data('url');
    const label = $(this).data('label');
    $('#formDeletePenawaran').attr('action', url);
    $('#deleteLabelPenawaran').text(label);
    const modal = new bootstrap.Modal(document.getElementById('modalDeletePenawaran'));
    modal.show();
  });
})();
</script>
@endpush

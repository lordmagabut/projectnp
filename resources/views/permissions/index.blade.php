@extends('layout.master')

@push('plugin-styles')
  {{-- DataTables CSS --}}
  <link href="{{ asset('assets/plugins/datatables-net-bs5/dataTables.bootstrap5.css') }}" rel="stylesheet" />
  <link href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap5.min.css" rel="stylesheet" />
@endpush

@section('content')
<div class="row">
  <div class="col-lg-12 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
          <div>
            <h4 class="card-title mb-1">Manajemen Izin</h4>
            <p class="card-description mb-0">Daftar semua izin yang terdaftar dalam sistem.</p>
          </div>
          @can('manage permissions')
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#permissionCreateModal">
              <i class="btn-icon-prepend" data-feather="plus"></i> Tambah Izin Baru
            </button>
          @endcan
        </div>

        @if(session('success'))
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        @endif
        @if(session('error'))
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        @endif

        <div class="table-responsive">
          <table id="permissionsDataTable" class="table table-hover align-middle display nowrap" style="width:100%">
            <thead>
              <tr>
                <th>Nama Izin</th>
                <th class="text-end">Aksi</th>
              </tr>
            </thead>
            <tbody>
              @forelse($permissions as $permission)
                <tr>
                  <td>{{ $permission->name }}</td>
                  <td class="text-end">
                    @can('manage permissions')
                      <div class="btn-group">
                        <button
                          type="button"
                          class="btn btn-outline-primary btn-sm open-edit-permission"
                          data-id="{{ $permission->id }}"
                          data-name="{{ $permission->name }}"
                          data-bs-toggle="modal"
                          data-bs-target="#permissionEditModal">
                          <i data-feather="edit"></i> Edit
                        </button>

                        <div class="dropdown ms-1">
                          <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" id="dropdown-{{ $permission->id }}" data-bs-toggle="dropdown" aria-expanded="false">
                            Lainnya
                          </button>
                          <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdown-{{ $permission->id }}">
                            <li>
                              <form action="{{ route('permissions.destroy', $permission->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus izin ini? Ini akan memengaruhi peran dan pengguna yang menggunakan izin ini.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="dropdown-item text-danger">
                                  <i class="me-2" data-feather="trash"></i> Hapus
                                </button>
                              </form>
                            </li>
                          </ul>
                        </div>
                      </div>
                    @endcan
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="2" class="text-center">Tidak ada izin yang ditemukan.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

      </div>
    </div>
  </div>
</div>

{{-- ===================== MODALS ===================== --}}

{{-- Create Permission Modal --}}
<div class="modal fade" id="permissionCreateModal" tabindex="-1" aria-labelledby="permissionCreateModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form action="{{ route('permissions.store') }}" method="POST" id="createPermissionForm">
        @csrf
        <input type="hidden" name="form" value="create">
        <div class="modal-header">
          <h5 class="modal-title" id="permissionCreateModalLabel">Buat Izin Baru</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="create-permission-name" class="form-label">Nama Izin <span class="text-danger">*</span></label>
            <input type="text"
                   class="form-control @error('name') {{ old('form')==='create' ? 'is-invalid' : '' }} @enderror"
                   id="create-permission-name"
                   name="name"
                   value="{{ old('form')==='create' ? old('name') : '' }}"
                   placeholder="Contoh: create articles, view dashboard"
                   required>
            @error('name')
              @if(old('form')==='create')
                <div class="invalid-feedback">{{ $message }}</div>
              @endif
            @enderror
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">Simpan Izin</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Edit Permission Modal (single reusable) --}}
<div class="modal fade" id="permissionEditModal" tabindex="-1" aria-labelledby="permissionEditModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      {{-- action akan di-set dinamis via JS; gunakan template URL berikut --}}
      <form method="POST" id="editPermissionForm" action="{{ route('permissions.update', '__id__') }}">
        @csrf
        @method('PUT')
        <input type="hidden" name="form" value="edit">
        <input type="hidden" name="permission_id" id="edit-permission-id" value="{{ old('form')==='edit' ? old('permission_id') : '' }}">
        <div class="modal-header">
          <h5 class="modal-title" id="permissionEditModalLabel">Edit Izin</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="edit-permission-name" class="form-label">Nama Izin <span class="text-danger">*</span></label>
            <input type="text"
                   class="form-control @error('name') {{ old('form')==='edit' ? 'is-invalid' : '' }} @enderror"
                   id="edit-permission-name"
                   name="name"
                   value="{{ old('form')==='edit' ? old('name') : '' }}"
                   required>
            @error('name')
              @if(old('form')==='edit')
                <div class="invalid-feedback">{{ $message }}</div>
              @endif
            @enderror
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">Perbarui Izin</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('plugin-scripts')
  {{-- jQuery (pastikan tidak dobel di layout) --}}
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
  {{-- DataTables JS --}}
  <script src="{{ asset('assets/plugins/datatables-net/jquery.dataTables.js') }}"></script>
  <script src="{{ asset('assets/plugins/datatables-net-bs5/dataTables.bootstrap5.js') }}"></script>
  <script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
  <script src="https://cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap5.min.js"></script>
@endpush

@push('custom-scripts')
<script>
  $(function() {
    // DataTables
    $('#permissionsDataTable').DataTable({
      aLengthMenu: [[10, 25, 50, 100, -1],[10, 25, 50, 100, "All"]],
      iDisplayLength: 10,
      language: {
        search: "",
        zeroRecords: "Tidak ada data izin yang ditemukan",
        info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ izin",
        infoEmpty: "Menampilkan 0 sampai 0 dari 0 izin",
        infoFiltered: "(difilter dari _MAX_ total izin)",
        lengthMenu: "Tampilkan _MENU_ izin",
        paginate: { first: "Pertama", last: "Terakhir", next: "Berikutnya", previous: "Sebelumnya" }
      },
      responsive: true,
      autoWidth: false
    });
    $('#permissionsDataTable_filter input').attr('placeholder', 'Cari Izin...');

    // Feather icons
    if (typeof feather !== 'undefined') { feather.replace(); }

    // ========= Edit Modal handler =========
    const editModal = document.getElementById('permissionEditModal');
    const editForm  = document.getElementById('editPermissionForm');
    const nameInput = document.getElementById('edit-permission-name');
    const idInput   = document.getElementById('edit-permission-id');
    const routeTemplate = editForm.getAttribute('action'); // e.g. /permissions/__id__

    // Open from table button
    document.querySelectorAll('.open-edit-permission').forEach(btn => {
      btn.addEventListener('click', function() {
        const id   = this.dataset.id;
        const name = this.dataset.name;

        idInput.value = id;
        nameInput.value = name;

        // set form action by replacing __id__
        editForm.setAttribute('action', routeTemplate.replace('__id__', id));
      });
    });

    // ========= Re-open modal on validation error (old input) =========
    @if($errors->any())
      @if(old('form') === 'create')
        const createModal = new bootstrap.Modal(document.getElementById('permissionCreateModal'));
        createModal.show();
      @elseif(old('form') === 'edit' && old('permission_id'))
        // Rehydrate edit form with old values
        const oldId   = @json(old('permission_id'));
        const oldName = @json(old('name'));

        idInput.value = oldId;
        nameInput.value = oldName ?? '';

        editForm.setAttribute('action', routeTemplate.replace('__id__', oldId));

        const showEditModal = new bootstrap.Modal(editModal);
        showEditModal.show();
      @endif
    @endif
  });
</script>
@endpush

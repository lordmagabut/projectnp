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
            <h4 class="card-title mb-1">Manajemen Peran</h4>
            <p class="mb-0">Daftar semua peran dalam sistem.</p>
          </div>

          @can('manage roles')
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#roleCreateModal">
              <i data-feather="plus"></i> Tambah Peran Baru
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
          <table id="rolesDataTable" class="table table-hover align-middle display nowrap" style="width:100%">
            <thead>
              <tr>
                <th>Nama Peran</th>
                <th>Izin Terkait</th>
                <th class="text-end">Aksi</th>
              </tr>
            </thead>
            <tbody>
              @forelse($roles as $role)
                <tr>
                  <td>{{ $role->name }}</td>
                  <td>
                    @forelse($role->permissions as $permission)
                      <span class="badge bg-info mb-1">{{ $permission->name }}</span>
                    @empty
                      <span class="text-muted">Tidak ada izin</span>
                    @endforelse
                  </td>
                  <td class="text-end">
                    @can('manage roles')
                      <div class="btn-group">
                        <button
                          type="button"
                          class="btn btn-outline-primary btn-sm open-edit-role"
                          data-id="{{ $role->id }}"
                          data-name="{{ $role->name }}"
                          data-permissions='@json($role->permissions->pluck("name"))'
                          data-bs-toggle="modal"
                          data-bs-target="#roleEditModal">
                          <i data-feather="edit"></i> Edit
                        </button>

                        <div class="dropdown ms-1">
                          <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" id="dropdown-{{ $role->id }}" data-bs-toggle="dropdown" aria-expanded="false">
                            Lainnya
                          </button>
                          <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdown-{{ $role->id }}">
                            <li>
                              <form action="{{ route('roles.destroy', $role->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus peran ini?')">
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
                  <td colspan="3" class="text-center">Tidak ada peran yang ditemukan.</td>
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

{{-- Create Role Modal --}}
<div class="modal fade" id="roleCreateModal" tabindex="-1" aria-labelledby="roleCreateModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form action="{{ route('roles.store') }}" method="POST" id="createRoleForm">
        @csrf
        <input type="hidden" name="form" value="create">

        <div class="modal-header">
          <h5 class="modal-title" id="roleCreateModalLabel">Buat Peran Baru</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
        </div>

        <div class="modal-body">
          <div class="mb-3">
            <label for="create-role-name" class="form-label">Nama Peran <span class="text-danger">*</span></label>
            <input type="text"
                   class="form-control @error('name') {{ old('form')==='create' ? 'is-invalid' : '' }} @enderror"
                   id="create-role-name"
                   name="name"
                   value="{{ old('form')==='create' ? old('name') : '' }}"
                   placeholder="Contoh: admin, editor, user"
                   required>
            @error('name')
              @if(old('form')==='create')
                <div class="invalid-feedback">{{ $message }}</div>
              @endif
            @enderror
          </div>

          <div class="mb-2">
            <label class="form-label">Pilih Izin untuk Peran Ini:</label>
            <div class="row">
              @forelse($permissions as $permission)
                <div class="col-md-6 col-lg-4 mb-2">
                  <div class="form-check">
                    <input class="form-check-input"
                           type="checkbox"
                           name="permissions[]"
                           value="{{ $permission->name }}"
                           id="create-permission-{{ $permission->id }}"
                           {{ (old('form')==='create' && in_array($permission->name, old('permissions', []))) ? 'checked' : '' }}>
                    <label class="form-check-label" for="create-permission-{{ $permission->id }}">
                      {{ $permission->name }}
                    </label>
                  </div>
                </div>
              @empty
                <div class="col-12">
                  <p class="text-muted mb-0">Tidak ada izin yang tersedia.</p>
                </div>
              @endforelse
            </div>
            @error('permissions')
              @if(old('form')==='create')
                <div class="text-danger small mt-1">{{ $message }}</div>
              @endif
            @enderror
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">Simpan Peran</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Edit Role Modal (reusable) --}}
<div class="modal fade" id="roleEditModal" tabindex="-1" aria-labelledby="roleEditModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      {{-- action di-set dinamis via JS; gunakan template URL berikut --}}
      <form method="POST" id="editRoleForm" action="{{ route('roles.update', '__id__') }}">
        @csrf
        @method('PUT')
        <input type="hidden" name="form" value="edit">
        <input type="hidden" name="role_id" id="edit-role-id" value="{{ old('form')==='edit' ? old('role_id') : '' }}">

        <div class="modal-header">
          <h5 class="modal-title" id="roleEditModalLabel">Edit Peran</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
        </div>

        <div class="modal-body">
          <div class="mb-3">
            <label for="edit-role-name" class="form-label">Nama Peran <span class="text-danger">*</span></label>
            <input type="text"
                   class="form-control @error('name') {{ old('form')==='edit' ? 'is-invalid' : '' }} @enderror"
                   id="edit-role-name"
                   name="name"
                   value="{{ old('form')==='edit' ? old('name') : '' }}"
                   required>
            @error('name')
              @if(old('form')==='edit')
                <div class="invalid-feedback">{{ $message }}</div>
              @endif
            @enderror
          </div>

          <div class="mb-2">
            <label class="form-label">Pilih Izin untuk Peran Ini:</label>
            <div class="row">
              @forelse($permissions as $permission)
                <div class="col-md-6 col-lg-4 mb-2">
                  <div class="form-check">
                    <input class="form-check-input"
                           type="checkbox"
                           name="permissions[]"
                           value="{{ $permission->name }}"
                           id="edit-permission-{{ $permission->id }}">
                    <label class="form-check-label" for="edit-permission-{{ $permission->id }}">
                      {{ $permission->name }}
                    </label>
                  </div>
                </div>
              @empty
                <div class="col-12">
                  <p class="text-muted mb-0">Tidak ada izin yang tersedia.</p>
                </div>
              @endforelse
            </div>
            @error('permissions')
              @if(old('form')==='edit')
                <div class="text-danger small mt-1">{{ $message }}</div>
              @endif
            @enderror
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">Perbarui Peran</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('plugin-scripts')
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
  <script src="{{ asset('assets/plugins/datatables-net/jquery.dataTables.js') }}"></script>
  <script src="{{ asset('assets/plugins/datatables-net-bs5/dataTables.bootstrap5.js') }}"></script>
  <script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
  <script src="https://cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap5.min.js"></script>
@endpush

@push('custom-scripts')
<script>
  $(function() {
    // DataTables
    $('#rolesDataTable').DataTable({
      aLengthMenu: [[10,25,50,100,-1],[10,25,50,100,"All"]],
      iDisplayLength: 10,
      language: {
        search: "",
        zeroRecords: "Tidak ada data peran yang ditemukan",
        info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ peran",
        infoEmpty: "Menampilkan 0 sampai 0 dari 0 peran",
        infoFiltered: "(difilter dari _MAX_ total peran)",
        lengthMenu: "Tampilkan _MENU_ peran",
        paginate: { first: "Pertama", last: "Terakhir", next: "Berikutnya", previous: "Sebelumnya" }
      },
      responsive: true,
      autoWidth: false
    });
    $('#rolesDataTable_filter input').attr('placeholder', 'Cari Peran...');

    // Feather icons
    if (typeof feather !== 'undefined') { feather.replace(); }

    // ========= Edit Modal handler =========
    const editModal   = document.getElementById('roleEditModal');
    const editForm    = document.getElementById('editRoleForm');
    const nameInput   = document.getElementById('edit-role-name');
    const idInput     = document.getElementById('edit-role-id');
    const routeTmpl   = editForm.getAttribute('action'); // /roles/__id__

    function setEditPermissions(permArray) {
      // reset
      $('#editRoleForm input[name="permissions[]"]').prop('checked', false);
      // check
      (permArray || []).forEach(function(p){
        $('#editRoleForm input[name="permissions[]"]').filter(function(){ return this.value === p; }).prop('checked', true);
      });
    }

    // Open from table button
    document.querySelectorAll('.open-edit-role').forEach(btn => {
      btn.addEventListener('click', function() {
        const id   = this.dataset.id;
        const name = this.dataset.name;
        let perms  = [];

        try { perms = JSON.parse(this.dataset.permissions || '[]'); } catch(e) { perms = []; }

        idInput.value = id;
        nameInput.value = name;
        setEditPermissions(perms);

        // set form action
        editForm.setAttribute('action', routeTmpl.replace('__id__', id));
      });
    });

    // ========= Re-open modal on validation error =========
    @if($errors->any())
      @if(old('form') === 'create')
        const createModal = new bootstrap.Modal(document.getElementById('roleCreateModal'));
        createModal.show();
      @elseif(old('form') === 'edit' && old('role_id'))
        const showEditModal = new bootstrap.Modal(editModal);
        const oldId   = @json(old('role_id'));
        const oldName = @json(old('name'));
        const oldPerms = @json(old('permissions', []));

        idInput.value = oldId;
        nameInput.value = oldName ?? '';
        setEditPermissions(oldPerms);
        editForm.setAttribute('action', routeTmpl.replace('__id__', oldId));
        showEditModal.show();
      @endif
    @endif
  });
</script>
@endpush

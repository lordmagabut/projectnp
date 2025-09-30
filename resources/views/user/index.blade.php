@extends('layout.master')

@push('plugin-styles')
  <link href="{{ asset('assets/plugins/datatables-net-bs5/dataTables.bootstrap5.css') }}" rel="stylesheet" />
  <link href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap5.min.css" rel="stylesheet" />
@endpush

@section('content')
<div class="row">
  <div class="col-lg-12 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <h4 class="card-title">Manajemen Pengguna</h4>
        <p class="card-description">Daftar semua pengguna dalam sistem dan peran yang mereka miliki.</p>

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

        @can('manage users')
          <a href="{{ route('user.create') }}" class="btn btn-primary mb-3">
            <i class="btn-icon-prepend" data-feather="plus"></i> Tambah Pengguna Baru
          </a>
        @endcan

        <div class="table-responsive">
          <table id="usersDataTable" class="table table-hover align-middle display nowrap" style="width:100%">
            <thead>
              <tr>
                <th>Username</th>
                <th>Peran (Roles)</th>
                <th class="text-end">Aksi</th>
              </tr>
            </thead>
            <tbody>
              @forelse($users as $user)
                <tr>
                  <td>{{ $user->username }}</td>
                  <td>
                    @forelse($user->getRoleNames() as $roleName)
                      <span class="badge bg-primary text-light me-1">{{ $roleName }}</span>
                    @empty
                      <span class="text-muted">Tidak ada peran</span>
                    @endforelse
                  </td>
                  <td class="text-end">
                    @can('manage users')
                      <div class="btn-group">
                        <button type="button"
                                class="btn btn-outline-info btn-sm open-user-logs"
                                data-url="{{ route('user.logs', $user->id) }}"
                                data-username="{{ $user->username }}"
                                data-bs-toggle="modal"
                                data-bs-target="#userLogsModal">
                          <i data-feather="activity"></i> Aktivitas
                        </button>

                        <div class="dropdown ms-1">
                          <button class="btn btn-primary btn-sm dropdown-toggle" type="button" id="dropdownUserActions-{{ $user->id }}" data-bs-toggle="dropdown" aria-expanded="false">
                            Aksi
                          </button>
                          <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownUserActions-{{ $user->id }}">
                            <li>
                              <a class="dropdown-item" href="{{ route('users.editRoles', $user->id) }}">
                                <i class="me-2" data-feather="tool"></i> Kelola Peran
                              </a>
                            </li>
                            <li>
                              <a class="dropdown-item" href="{{ route('user.edit', $user->id) }}">
                                <i class="me-2" data-feather="edit"></i> Edit Pengguna
                              </a>
                            </li>
                            <li>
                              <form action="{{ route('user.destroy', $user->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pengguna ini?')">
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
                  <td colspan="3" class="text-center">Tidak ada pengguna yang ditemukan.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

      </div>
    </div>
  </div>
</div>

{{-- ============ MODAL: LOG USER ============ --}}
<div class="modal fade" id="userLogsModal" tabindex="-1" aria-labelledby="userLogsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          Log Aktivitas — <span id="userLogsModalTitle">Username</span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body" id="userLogsModalBody">
        <div class="py-4 text-center text-muted">
          <div class="spinner-border me-2" role="status" aria-hidden="true"></div> Memuat data...
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
      </div>
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
    // DataTables utama
    $('#usersDataTable').DataTable({
      aLengthMenu: [[10,25,50,100,-1],[10,25,50,100,"All"]],
      iDisplayLength: 10,
      language: {
        search: "",
        zeroRecords: "Tidak ada data pengguna yang ditemukan",
        info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ pengguna",
        infoEmpty: "Menampilkan 0 sampai 0 dari 0 pengguna",
        infoFiltered: "(difilter dari _MAX_ total pengguna)",
        lengthMenu: "Tampilkan _MENU_ pengguna",
        paginate: { first: "Pertama", last: "Terakhir", next: "Berikutnya", previous: "Sebelumnya" }
      },
      responsive: true,
      autoWidth: false
    });
    $('#usersDataTable_filter input').attr('placeholder', 'Cari Pengguna...');

    if (typeof feather !== 'undefined') { feather.replace(); }

    // Loader modal log
    $(document).on('click', '.open-user-logs', function(){
      const url = $(this).data('url');
      const uname = $(this).data('username') || 'User';
      $('#userLogsModalTitle').text(uname);
      $('#userLogsModalBody').html(`<div class="py-4 text-center text-muted"><div class="spinner-border me-2" role="status"></div> Memuat data...</div>`);
      $.get(url)
        .done(function(html){
          $('#userLogsModalBody').html(html);
        })
        .fail(function(xhr){
          let msg = 'Gagal memuat data.';
          if (xhr?.responseText) msg += `<div class="small text-muted mt-2">${xhr.responseText}</div>`;
          $('#userLogsModalBody').html(`<div class="alert alert-danger mb-0" role="alert">${msg}</div>`);
        });
    });
  });
</script>
@endpush

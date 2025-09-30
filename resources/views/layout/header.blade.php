<nav class="navbar">
  <a href="#" class="sidebar-toggler">
    <i data-feather="menu"></i>
  </a>

  <div class="navbar-content">
    <ul class="navbar-nav">

      @auth
        @php
          $u = auth()->user();
          $displayName = $u?->name ?? $u?->username ?? 'User';
          $displayEmail = $u?->email ?? '—';

          $emailHash = $u?->email ? md5(strtolower(trim($u->email))) : null;
          $avatar30 = $emailHash
            ? "https://www.gravatar.com/avatar/{$emailHash}?s=30&d=mp"
            : 'https://via.placeholder.com/30x30';
          $avatar80 = $emailHash
            ? "https://www.gravatar.com/avatar/{$emailHash}?s=80&d=mp"
            : 'https://via.placeholder.com/80x80';
        @endphp

        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="profileDropdown" role="button"
             data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <img class="wd-30 ht-30 rounded-circle" src="{{ $avatar30 }}" alt="{{ $displayName }}">
          </a>

          <div class="dropdown-menu p-0" aria-labelledby="profileDropdown">
            <div class="d-flex flex-column align-items-center border-bottom px-5 py-3">
              <div class="mb-3">
                <img class="wd-80 ht-80 rounded-circle" src="{{ $avatar80 }}" alt="{{ $displayName }}">
              </div>
              <div class="text-center">
                <p class="tx-16 fw-bolder mb-0">{{ $displayName }}</p>
                <p class="tx-12 text-muted mb-0">{{ $displayEmail }}</p>
              </div>
            </div>

            <ul class="list-unstyled p-1">
              <li>
                <a href="{{ url('/general/profile') }}" class="dropdown-item py-2 d-flex align-items-center">
                  <i class="me-2 icon-md" data-feather="user"></i>
                  <span>Profile</span>
                </a>
              </li>
              <li>
                <a href="{{ url('/general/profile/edit') }}" class="dropdown-item py-2 d-flex align-items-center">
                  <i class="me-2 icon-md" data-feather="edit"></i>
                  <span>Edit Profile</span>
                </a>
              </li>
              <li>
                <form action="{{ route('logout') }}" method="POST" class="d-inline">
                  @csrf
                  <button type="submit" class="dropdown-item py-2 d-flex align-items-center text-danger">
                    <i class="me-2 icon-md" data-feather="log-out"></i>
                    <span>Log Out</span>
                  </button>
                </form>
              </li>
            </ul>
          </div>
        </li>
      @else
        <li class="nav-item">
          <a class="nav-link" href="{{ route('login') }}">Login</a>
        </li>
      @endauth

    </ul>
  </div>
</nav>

@push('custom-scripts')
<script>
  if (typeof feather !== 'undefined') { feather.replace(); }
</script>
@endpush

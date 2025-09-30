<nav class="sidebar">
  <div class="sidebar-header">
    <a href="#" class="sidebar-brand">
      Noble<span>UI</span>
    </a>
    <div class="sidebar-toggler not-active">
      <span></span>
      <span></span>
      <span></span>
    </div>
  </div>
  <div class="sidebar-body">
    <ul class="nav">
      <li class="nav-item nav-category">Main</li>
      <li class="nav-item {{ active_class(['/']) }}">
        <a href="{{ url('/') }}" class="nav-link">
          <i class="link-icon" data-feather="box"></i>
          <span class="link-title">Beranda Proyek</span>
        </a>
      </li>

      <li class="nav-item nav-category">Setting</li>
      @can('manage perusahaan')
      <li class="nav-item {{ active_class(['perusahaan/*', 'template-dokumen/*']) }}"> {{-- Sesuaikan active_class dengan rute yang benar --}}
        <a class="nav-link" data-bs-toggle="collapse" href="#perusahaanSettings" role="button" aria-expanded="{{ is_active_route(['perusahaan/*', 'template-dokumen/*']) }}" aria-controls="perusahaanSettings">
          <i class="link-icon" data-feather="settings"></i>
          <span class="link-title">Perusahaan</span> {{-- Ubah label agar lebih jelas --}}
          <i class="link-arrow" data-feather="chevron-down"></i>
        </a>
        <div class="collapse {{ show_class(['perusahaan/*', 'template-dokumen/*']) }}" id="perusahaanSettings"> {{-- ID UNIK BARU --}}
          <ul class="nav sub-menu">
            <li class="nav-item">
              <a href="{{ url('/perusahaan') }}" class="nav-link {{ active_class(['perusahaan']) }}">Profil Perusahaan</a>
            </li>
            <li class="nav-item">
              <a href="{{ url('/template-dokumen') }}" class="nav-link {{ active_class(['template-dokumen']) }}">Template Dokumen</a> {{-- Ubah URL ini ke rute yang benar --}}
            </li>
          </ul>
        </div>
      </li>
      @endcan

      @can('manage users')
      <li class="nav-item {{ active_class(['user/*', 'roles/*', 'permissions/*']) }}"> {{-- Sesuaikan active_class dengan rute yang benar --}}
        <a class="nav-link" data-bs-toggle="collapse" href="#userSettings" role="button" aria-expanded="{{ is_active_route(['user/*', 'roles/*', 'permissions/*']) }}" aria-controls="userSettings">
          <i class="link-icon" data-feather="users"></i> {{-- Ganti ikon agar lebih sesuai --}}
          <span class="link-title">User</span> {{-- Ubah label agar lebih jelas --}}
          <i class="link-arrow" data-feather="chevron-down"></i>
        </a>
        <div class="collapse {{ show_class(['user/*', 'roles/*', 'permissions/*']) }}" id="userSettings"> {{-- ID UNIK BARU --}}
          <ul class="nav sub-menu">
            <li class="nav-item">
              <a href="{{ url('/user') }}" class="nav-link {{ active_class(['user-manager']) }}">Daftar</a> {{-- Gunakan rute yang sudah kita definisikan --}}
            </li>
            <li class="nav-item">
              <a href="{{ url('/roles') }}" class="nav-link {{ active_class(['roles']) }}">Roles</a>
            </li>
            <li class="nav-item">
              <a href="{{ url('/permissions') }}" class="nav-link {{ active_class(['permissions']) }}">Izin</a>
            </li>
          </ul>
        </div>
      </li>
      @endcan

      <li class="nav-item nav-category">Master Data</li>
      @can('manage barang')
      <li class="nav-item {{ active_class(['barang/*']) }}">
        <a class="nav-link" data-bs-toggle="collapse" href="#barangMenu" role="button"
          aria-expanded="{{ is_active_route(['barang/*']) }}" aria-controls="barangMenu">
          <i class="link-icon" data-feather="package"></i>
          <span class="link-title">Barang</span>
          <i class="link-arrow" data-feather="chevron-down"></i>
        </a>
        <div class="collapse {{ show_class(['barang/*']) }}" id="barangMenu">
          <ul class="nav sub-menu">
            <li class="nav-item">
              <a href="{{ route('barang.index') }}" class="nav-link {{ active_class(['barang']) }}">Daftar Barang</a>
            </li>
          </ul>
        </div>
      </li>
      @endcan
      @can('manage coa')
      <li class="nav-item {{ active_class(['coa/*']) }}">
        <a class="nav-link" data-bs-toggle="collapse" href="#coaMenu" role="button"
          aria-expanded="{{ is_active_route(['coa/*']) }}" aria-controls="coaMenu">
          <i class="link-icon" data-feather="layers"></i>
          <span class="link-title">COA</span>
          <i class="link-arrow" data-feather="chevron-down"></i>
        </a>
        <div class="collapse {{ show_class(['coa/*']) }}" id="coaMenu">
          <ul class="nav sub-menu">
            <li class="nav-item">
              <a href="{{ route('coa.index') }}" class="nav-link {{ active_class(['coa']) }}">Daftar COA</a>
            </li>
          </ul>
        </div>
      </li>
      @endcan
      @can('manage supplier') 
      <li class="nav-item {{ active_class(['supplier/*']) }}">
        <a class="nav-link" data-bs-toggle="collapse" href="#supplierMenu" role="button"
          aria-expanded="{{ is_active_route(['supplier/*']) }}" aria-controls="supplierMenu">
          <i class="link-icon" data-feather="truck"></i>
          <span class="link-title">Supplier</span>
          <i class="link-arrow" data-feather="chevron-down"></i>
        </a>
        <div class="collapse {{ show_class(['supplier/*']) }}" id="supplierMenu">
          <ul class="nav sub-menu">
            <li class="nav-item">
              <a href="{{ route('supplier.index') }}" class="nav-link {{ active_class(['supplier']) }}">Daftar Supplier</a>
            </li>
          </ul>
        </div>
      </li>
      @endcan
      @can('manage pelanggan')
      <li class="nav-item {{ active_class(['pemberiKerja/*']) }}">
        <a class="nav-link" data-bs-toggle="collapse" href="#pemberiKerjaMenu" role="button"
          aria-expanded="{{ is_active_route(['pemberiKerja/*']) }}" aria-controls="pemberiKerjaMenu">
          <i class="link-icon" data-feather="briefcase"></i>
          <span class="link-title">Pemberi Kerja</span>
          <i class="link-arrow" data-feather="chevron-down"></i>
        </a>
        <div class="collapse {{ show_class(['pemberiKerja/*']) }}" id="pemberiKerjaMenu">
          <ul class="nav sub-menu">
            <li class="nav-item">
              <a href="{{ route('pemberiKerja.index') }}" class="nav-link {{ active_class(['pemberiKerja']) }}">Daftar Pemberi Kerja</a>
            </li>
          </ul>
        </div>
      </li>
      @endcan 
      <li class="nav-item nav-category">Operasional</li>
      <li class="nav-item {{ active_class(['proyek/*']) }}">
        <a class="nav-link" data-bs-toggle="collapse" href="#proyekMenu" role="button"
          aria-expanded="{{ is_active_route(['proyek/*']) }}" aria-controls="proyekMenu">
          <i class="link-icon" data-feather="folder"></i>
          <span class="link-title">Proyek</span>
          <i class="link-arrow" data-feather="chevron-down"></i>
        </a>
        <div class="collapse {{ show_class(['proyek/*']) }}" id="proyekMenu">
          <ul class="nav sub-menu">
            @can('manage proyek')
            <li class="nav-item">
              <a href="{{ route('proyek.index') }}"
                class="nav-link {{ active_class(['proyek']) }}">Daftar Proyek</a>
            </li>
            @endcan
            @can ('manage ahsp')
            <li class="nav-item">
              <a href="{{ route('ahsp.index') }}"
                class="nav-link {{ active_class(['proyek']) }}">AHSP</a>
            </li>
            @endcan
          </ul>
        </div>
      </li>

      <li class="nav-item {{ active_class(['po/*']) }}">
        <a class="nav-link" data-bs-toggle="collapse" href="#poMenu" role="button"
          aria-expanded="{{ is_active_route(['po/*']) }}" aria-controls="poMenu">
          <i class="link-icon" data-feather="shopping-cart"></i>
          <span class="link-title">Pembelian</span>
          <i class="link-arrow" data-feather="chevron-down"></i>
        </a>
        <div class="collapse {{ show_class(['po/*']) }}" id="poMenu">
          <ul class="nav sub-menu">
            <li class="nav-item">
              <a href="{{ route('po.index') }}"
                class="nav-link {{ active_class(['po']) }}">Daftar PO</a>
            </li>
          </ul>
        </div>
      </li>

      <li class="nav-item {{ active_class(['so/*']) }}">
        <a class="nav-link" data-bs-toggle="collapse" href="#soMenu" role="button"
          aria-expanded="{{ is_active_route(['so/*']) }}" aria-controls="soMenu">
          <i class="link-icon" data-feather="shopping-bag"></i>
          <span class="link-title">SO</span>
          <i class="link-arrow" data-feather="chevron-down"></i>
        </a>
        <div class="collapse {{ show_class(['so/*']) }}" id="soMenu">
          <ul class="nav sub-menu">
            <li class="nav-item">
              {{-- Jika modul Anda memakai nama route lain (mis. sales-orders.index), ganti di sini --}}
              <a href=""
                class="nav-link {{ active_class(['so']) }}">Daftar SO</a>
            </li>
          </ul>
        </div>
      </li>
      
      <li class="nav-item nav-category">Pages</li>
      <li class="nav-item {{ active_class(['general/*']) }}">
        <a class="nav-link" data-bs-toggle="collapse" href="#general" role="button" aria-expanded="{{ is_active_route(['general/*']) }}" aria-controls="general">
          <i class="link-icon" data-feather="book"></i>
          <span class="link-title">Special Pages</span>
          <i class="link-arrow" data-feather="chevron-down"></i>
        </a>
        <div class="collapse {{ show_class(['general/*']) }}" id="general">
          <ul class="nav sub-menu">
            <li class="nav-item">
              <a href="{{ url('/general/blank-page') }}" class="nav-link {{ active_class(['general/blank-page']) }}">Blank page</a>
            </li>
            <li class="nav-item">
              <a href="{{ url('/general/faq') }}" class="nav-link {{ active_class(['general/faq']) }}">Faq</a>
            </li>
            <li class="nav-item">
              <a href="{{ url('/general/invoice') }}" class="nav-link {{ active_class(['general/invoice']) }}">Invoice</a>
            </li>
            <li class="nav-item">
              <a href="{{ url('/general/profile') }}" class="nav-link {{ active_class(['general/profile']) }}">Profile</a>
            </li>
            <li class="nav-item">
              <a href="{{ url('/general/pricing') }}" class="nav-link {{ active_class(['general/pricing']) }}">Pricing</a>
            </li>
            <li class="nav-item">
              <a href="{{ url('/general/timeline') }}" class="nav-link {{ active_class(['general/timeline']) }}">Timeline</a>
            </li>
          </ul>
        </div>
      </li>
      <li class="nav-item {{ active_class(['auth/*']) }}">
        <a class="nav-link" data-bs-toggle="collapse" href="#auth" role="button" aria-expanded="{{ is_active_route(['auth/*']) }}" aria-controls="auth">
          <i class="link-icon" data-feather="unlock"></i>
          <span class="link-title">Authentication</span>
          <i class="link-arrow" data-feather="chevron-down"></i>
        </a>
        <div class="collapse {{ show_class(['auth/*']) }}" id="auth">
          <ul class="nav sub-menu">
            <li class="nav-item">
              <a href="{{ url('/auth/login') }}" class="nav-link {{ active_class(['auth/login']) }}">Login</a>
            </li>
            <li class="nav-item">
              <a href="{{ url('/auth/register') }}" class="nav-link {{ active_class(['auth/register']) }}">Register</a>
            </li>
          </ul>
        </div>
      </li>
      <li class="nav-item {{ active_class(['error/*']) }}">
        <a class="nav-link" data-bs-toggle="collapse" href="#error" role="button" aria-expanded="{{ is_active_route(['error/*']) }}" aria-controls="error">
          <i class="link-icon" data-feather="cloud-off"></i>
          <span class="link-title">Error</span>
          <i class="link-arrow" data-feather="chevron-down"></i>
        </a>
        <div class="collapse {{ show_class(['error/*']) }}" id="error">
          <ul class="nav sub-menu">
            <li class="nav-item">
              <a href="{{ url('/error/404') }}" class="nav-link {{ active_class(['error/404']) }}">404</a>
            </li>
            <li class="nav-item">
              <a href="{{ url('/error/500') }}" class="nav-link {{ active_class(['error/500']) }}">500</a>
            </li>
          </ul>
        </div>
      </li>
      <li class="nav-item nav-category">Docs</li>
      <li class="nav-item">
        <a href="https://www.nobleui.com/laravel/documentation/docs.html" target="_blank" class="nav-link">
          <i class="link-icon" data-feather="hash"></i>
          <span class="link-title">Documentation</span>
        </a>
      </li>
    </ul>
  </div>
</nav>

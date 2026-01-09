<nav class="sidebar">
  <style>
    /* Sidebar brand responsive tweaks */
    .sidebar-header .sidebar-brand { display:flex; align-items:center; }
    .sidebar-header .sidebar-brand img { height:36px; object-fit:contain; margin-right:8px; }
    .sidebar-header .brand-text { display:inline-block; max-width:120px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; font-size:14px; font-weight:600; line-height:1; }

    @media (max-width: 768px) {
      .sidebar-header .sidebar-brand { flex-direction:column; align-items:center; gap:4px; }
      .sidebar-header .sidebar-brand img { margin-right:0; }
      .sidebar-header .brand-text { max-width:160px; white-space:normal; text-align:center; font-size:13px; }
    }
  </style>
  <div class="sidebar-header d-flex align-items-center">
    <a href="{{ url('/') }}" class="sidebar-brand d-flex align-items-center">
      <img src="{{ company_logo_url($company) }}" alt="{{ $company->nama_perusahaan ?? 'Artista Group' }}" style="height:36px; object-fit:contain; margin-right:8px;">
      <span class="brand-text" title="{{ $company->nama_perusahaan ?? 'Artista Group' }}" style="display:inline-block; max-width:120px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; font-size:14px; font-weight:600; line-height:1;">{{ $company->nama_perusahaan ?? 'Artista Group' }}</span>
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
      <li class="nav-item {{ active_class(['perusahaan/*', 'template-dokumen/*', 'account-mapping*']) }}"> {{-- Sesuaikan active_class dengan rute yang benar --}}
        <a class="nav-link" data-bs-toggle="collapse" href="#perusahaanSettings" role="button" aria-expanded="{{ is_active_route(['perusahaan/*', 'template-dokumen/*', 'account-mapping*']) }}" aria-controls="perusahaanSettings">
          <i class="link-icon" data-feather="settings"></i>
          <span class="link-title">Perusahaan</span> {{-- Ubah label agar lebih jelas --}}
          <i class="link-arrow" data-feather="chevron-down"></i>
        </a>
        <div class="collapse {{ show_class(['perusahaan/*', 'template-dokumen/*', 'account-mapping*']) }}" id="perusahaanSettings"> {{-- ID UNIK BARU --}}
          <ul class="nav sub-menu">
            <li class="nav-item">
              <a href="{{ url('/perusahaan') }}" class="nav-link {{ active_class(['perusahaan']) }}">Profil Perusahaan</a>
            </li>
            <li class="nav-item">
              <a href="{{ url('/template-dokumen') }}" class="nav-link {{ active_class(['template-dokumen']) }}">Template Dokumen</a> {{-- Ubah URL ini ke rute yang benar --}}
            </li>
            <li class="nav-item">
              <a href="{{ route('account-mapping.index') }}" class="nav-link {{ active_class(['account-mapping']) }}">Mapping COA</a>
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
      @can('manage barang')
      <li class="nav-item {{ active_class(['datasync/*']) }}">
        <a class="nav-link" data-bs-toggle="collapse" href="#datasyncMenu" role="button"
          aria-expanded="{{ is_active_route(['datasync/*']) }}" aria-controls="datasyncMenu">
          <i class="link-icon" data-feather="database"></i>
          <span class="link-title">Data Sync</span>
          <i class="link-arrow" data-feather="chevron-down"></i>
        </a>
        <div class="collapse {{ show_class(['datasync/*']) }}" id="datasyncMenu">
          <ul class="nav sub-menu">
            <li class="nav-item">
              <a href="{{ route('datasync.index') }}" class="nav-link {{ active_class(['datasync']) }}">Sinkronisasi Data</a>
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

      <li class="nav-item {{ active_class(['po/*', 'penerimaan/*', 'retur/*', 'faktur/*', 'uang-muka-pembelian/*', 'pembayaran/*']) }}">
        <a class="nav-link" data-bs-toggle="collapse" href="#poMenu" role="button"
          aria-expanded="{{ is_active_route(['po/*', 'penerimaan/*', 'retur/*', 'faktur/*', 'uang-muka-pembelian/*', 'pembayaran/*']) }}" aria-controls="poMenu">
          <i class="link-icon" data-feather="shopping-cart"></i>
          <span class="link-title">Pembelian</span>
          <i class="link-arrow" data-feather="chevron-down"></i>
        </a>
        <div class="collapse {{ show_class(['po/*', 'penerimaan/*', 'retur/*', 'faktur/*', 'uang-muka-pembelian/*', 'pembayaran/*']) }}" id="poMenu">
          <ul class="nav sub-menu">
            <li class="nav-item">
              <a href="{{ route('po.index') }}"
                class="nav-link {{ active_class(['po']) }}">Pesanan Pembelian</a>
            </li>
            <li class="nav-item">
              <a href="{{ route('penerimaan.index') }}"
                class="nav-link {{ active_class(['penerimaan']) }}">Penerimaan Barang</a>
            </li>
            <li class="nav-item">
              <a href="{{ route('retur.index') }}"
                class="nav-link {{ active_class(['retur']) }}">Retur Pembelian</a>
            </li>
            <li class="nav-item">
              <a href="{{ route('faktur.index') }}"
                class="nav-link {{ active_class(['faktur']) }}">Faktur Pembelian</a>
            </li>
            <li class="nav-item">
              <a href="{{ route('uang-muka-pembelian.index') }}"
                class="nav-link {{ active_class(['uang-muka-pembelian']) }}">Uang Muka Pembelian</a>
            </li>
            <li class="nav-item">
              <a href="{{ route('pembayaran.index') }}"
                class="nav-link {{ active_class(['pembayaran']) }}">Pembayaran</a>
            </li>
          </ul>
        </div>
      </li>

      <li class="nav-item {{ active_class(['so/*', 'uang-muka-penjualan*']) }}">
        <a class="nav-link" data-bs-toggle="collapse" href="#soMenu" role="button"
          aria-expanded="{{ is_active_route(['so/*', 'uang-muka-penjualan*', 'faktur-penjualan*']) }}" aria-controls="soMenu">
          <i class="link-icon" data-feather="shopping-bag"></i>
          <span class="link-title">Penjualan</span>
          <i class="link-arrow" data-feather="chevron-down"></i>
        </a>
        <div class="collapse {{ show_class(['so/*', 'uang-muka-penjualan*', 'faktur-penjualan*', 'penerimaan-penjualan*']) }}" id="soMenu">
          <ul class="nav sub-menu">
            <li class="nav-item">
              <a href="{{ route('so.index') }}"
                class="nav-link {{ active_class(['so']) }}">Daftar SO</a>
            </li>
            <li class="nav-item">
              <a href="{{ route('uang-muka-penjualan.index') }}"
                class="nav-link {{ active_class(['uang-muka-penjualan*']) }}">Uang Muka</a>
            </li>
            <li class="nav-item">
              <a href="{{ route('faktur-penjualan.index') }}"
                class="nav-link {{ active_class(['faktur-penjualan*']) }}">Faktur Penjualan</a>
            </li>
            <li class="nav-item">
              <a href="{{ route('penerimaan-penjualan.index') }}"
                class="nav-link {{ active_class(['penerimaan-penjualan*']) }}">Penerimaan Penjualan</a>
            </li>
          </ul>
        </div>
      </li>
      
      <li class="nav-item nav-category">AKUNTING</li>
      <li class="nav-item {{ active_class(['saldo-awal*']) }}">
        <a href="{{ route('opening-balance.index') }}" class="nav-link {{ active_class(['saldo-awal*']) }}">
          <i class="link-icon" data-feather="edit"></i>
          <span class="link-title">Saldo Awal</span>
        </a>
      </li>

      {{-- ===== Laporan ===== --}}
      <li class="nav-item {{ active_class(['laporan/*', 'laporan/jurnal*', 'laporan/laba-rugi*', 'laporan/neraca*', 'laporan/buku-besar*', 'laporan/general-ledger*', 'saldo-awal*']) }}">
        <a class="nav-link" data-bs-toggle="collapse" href="#laporanMenu" role="button"
          aria-expanded="{{ is_active_route(['laporan/*','laporan/jurnal*','laporan/laba-rugi*','laporan/neraca*','laporan/buku-besar*','laporan/general-ledger*']) }}"
          aria-controls="laporanMenu">
          <i class="link-icon" data-feather="bar-chart-2"></i>
          <span class="link-title">Laporan</span>
          <i class="link-arrow" data-feather="chevron-down"></i>
        </a>
        <div class="collapse {{ show_class(['laporan/*','laporan/jurnal*','laporan/laba-rugi*','laporan/neraca*','laporan/buku-besar*','laporan/general-ledger*']) }}" id="laporanMenu">
          <ul class="nav sub-menu">
            <li class="nav-item">
              <a href="{{ url('/jurnal') }}" class="nav-link {{ active_class(['laporan/jurnal*']) }}">Jurnal</a>
                        <li class="nav-item">
                          <a href="{{ route('laporan.general-ledger') }}" class="nav-link {{ active_class(['laporan/general-ledger*']) }}">General Ledger</a>
                        </li>
            </li>
            <li class="nav-item">
              <a href="{{ url('/laporan/laba-rugi') }}" class="nav-link {{ active_class(['laporan/laba-rugi*']) }}">Laba Rugi</a>
            </li>
            <li class="nav-item">
              <a href="{{ url('/laporan/neraca') }}" class="nav-link {{ active_class(['laporan/neraca*']) }}">Neraca</a>
            </li>
            <li class="nav-item">
              <a href="{{ url('/buku-besar') }}" class="nav-link {{ active_class(['laporan/buku-besar*']) }}">Buku Besar</a>
            </li>
          </ul>
        </div>
      </li>

    </ul>
  </div>
</nav>

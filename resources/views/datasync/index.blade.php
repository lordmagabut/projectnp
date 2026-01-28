@extends('layout.master')

@section('content')
<div class="card shadow-sm">
  <div class="card-header bg-primary text-white d-flex align-items-center">
    <i data-feather="database" class="me-2"></i>
    <h4 class="mb-0">Sinkronisasi Data dari Database Eksternal</h4>
  </div>

  <div class="card-body">
    {{-- Database Configuration Section --}}
    <div class="card mb-4 border-primary">
      <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i data-feather="settings" class="me-2"></i>Konfigurasi Database Eksternal</h5>
        <button class="btn btn-sm btn-light" type="button" data-bs-toggle="collapse" data-bs-target="#configForm">
          <i data-feather="edit" class="me-1"></i> Edit
        </button>
      </div>
      <div class="collapse" id="configForm">
        <div class="card-body">
          <form id="formDbConfig">
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">Nama Konfigurasi</label>
                <input type="text" name="name" class="form-control" placeholder="e.g., Database Perusahaan A" required>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Host</label>
                <input type="text" name="host" class="form-control" placeholder="127.0.0.1 atau IP server" required>
              </div>
              <div class="col-md-3 mb-3">
                <label class="form-label">Port</label>
                <input type="text" name="port" class="form-control" value="3306" required>
              </div>
              <div class="col-md-9 mb-3">
                <label class="form-label">Nama Database</label>
                <input type="text" name="database" class="form-control" placeholder="nama_database" required>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" placeholder="root" required>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" placeholder="(kosongkan jika tidak ada)">
              </div>
              <div class="col-12 mb-3">
                <label class="form-label">Catatan</label>
                <textarea name="notes" class="form-control" rows="2" placeholder="Keterangan opsional"></textarea>
              </div>
            </div>
            <div class="d-flex gap-2">
              <button type="submit" class="btn btn-primary">
                <i data-feather="save" class="me-1"></i> Simpan Konfigurasi
              </button>
              <button type="button" class="btn btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#configForm">
                Batal
              </button>
            </div>
          </form>
        </div>
      </div>
      <div class="card-body" id="currentConfigDisplay">
        <p class="text-muted mb-0"><i data-feather="loader" class="me-2"></i>Memuat konfigurasi...</p>
      </div>
    </div>

    <div class="alert alert-info d-flex align-items-start">
      <i data-feather="info" class="me-2 mt-1"></i>
      <div>
        <strong>Informasi:</strong> Fitur ini memungkinkan Anda membandingkan dan menyalin data referensi
        (HSD Material, HSD Upah, AHSP) dari database eksternal yang memiliki struktur sama.
      </div>
    </div>

    <div class="alert alert-warning d-flex align-items-start">
      <i data-feather="alert-triangle" class="me-2 mt-1"></i>
      <div>
        <strong>Troubleshooting Koneksi:</strong>
        <ul class="mb-0 mt-2">
          <li><strong>Access Denied:</strong> Pastikan username dan password benar. Coba gunakan username dengan akses dari host manapun (<code>'root'@'%'</code>)</li>
          <li><strong>Host:</strong> Gunakan IP address (misal <code>192.168.1.100</code>) bukan nama komputer. Untuk localhost, gunakan <code>127.0.0.1</code></li>
          <li><strong>Port:</strong> Default MySQL adalah <code>3306</code>. Cek jika menggunakan port berbeda</li>
          <li><strong>Firewall:</strong> Pastikan port MySQL tidak diblock oleh firewall</li>
          <li><strong>MySQL User:</strong> User harus memiliki hak akses dari IP komputer ini. Jalankan: <code>GRANT ALL ON database_name.* TO 'user'@'%' IDENTIFIED BY 'password';</code></li>
        </ul>
      </div>
    </div>

    {{-- Test Connection --}}
    <div class="mb-4">
      <button id="btnTestConnection" class="btn btn-outline-primary">
        <i data-feather="wifi" class="me-1"></i> Test Koneksi Database Eksternal
      </button>
      <div id="connectionStatus" class="mt-2"></div>
    </div>

    {{-- Tabs --}}
    <ul class="nav nav-tabs mb-3" id="syncTabs" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link active" id="hsd-material-tab" data-bs-toggle="tab" data-bs-target="#hsd-material" type="button">
          <i data-feather="package" class="me-1"></i> HSD Material
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="hsd-upah-tab" data-bs-toggle="tab" data-bs-target="#hsd-upah" type="button">
          <i data-feather="users" class="me-1"></i> HSD Upah
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="ahsp-tab" data-bs-toggle="tab" data-bs-target="#ahsp" type="button">
          <i data-feather="layers" class="me-1"></i> AHSP
        </button>
      </li>
    </ul>

    {{-- Tab Content --}}
    <div class="tab-content" id="syncTabsContent">
      {{-- HSD Material --}}
      <div class="tab-pane fade show active" id="hsd-material" role="tabpanel">
        <button class="btn btn-primary mb-3" onclick="loadComparison('hsd-material')">
          <i data-feather="refresh-cw" class="me-1"></i> Muat Perbandingan
        </button>
        <div id="hsd-material-content"></div>
      </div>

      {{-- HSD Upah --}}
      <div class="tab-pane fade" id="hsd-upah" role="tabpanel">
        <button class="btn btn-primary mb-3" onclick="loadComparison('hsd-upah')">
          <i data-feather="refresh-cw" class="me-1"></i> Muat Perbandingan
        </button>
        <div id="hsd-upah-content"></div>
      </div>

      {{-- AHSP --}}
      <div class="tab-pane fade" id="ahsp" role="tabpanel">
        <button class="btn btn-primary mb-3" onclick="loadComparison('ahsp')">
          <i data-feather="refresh-cw" class="me-1"></i> Muat Perbandingan
        </button>
        <div id="ahsp-content"></div>
      </div>
    </div>
  </div>
</div>

<!-- Modal untuk Preview AHSP Details -->
<div class="modal fade" id="previewAhspModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          <i data-feather="eye" class="me-2"></i>Preview AHSP yang Akan Di-Sync
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="previewContent">
        <div class="text-center py-5">
          <div class="spinner-border" role="status"></div>
          <p class="mt-2">Memuat detail...</p>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-primary" id="confirmSyncBtn" onclick="confirmSync()">
          <i data-feather="check" class="me-1"></i>Lanjutkan Sync
        </button>
      </div>
    </div>
  </div>
</div>

{{-- Modal untuk preview AHSP yang berbeda --}}
<div class="modal fade" id="previewDifferentAhspModal" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          <i data-feather="eye" class="me-2"></i>Perbandingan Detail AHSP
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="previewDifferentContent">
        <div class="text-center py-5">
          <div class="spinner-border" role="status"></div>
          <p class="mt-2">Memuat detail...</p>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
        <button type="button" class="btn btn-primary" id="confirmDifferentSyncBtn" onclick="confirmDifferentSync()">
          <i data-feather="download" class="me-1"></i>Update dengan Data Eksternal
        </button>
      </div>
    </div>
  </div>
</div>

@endsection

@push('custom-scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
  feather.replace();

  // Event delegation for re-sync buttons
  document.addEventListener('click', function(e) {
    if (e.target.closest('.resync-btn')) {
      const btn = e.target.closest('.resync-btn');
      const type = btn.dataset.type;
      const id = btn.dataset.id;
      resyncItem(type, id);
    }
  });

  // Load current config on page load
  loadCurrentConfig();

  // Save config form
  document.getElementById('formDbConfig').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData);

    try {
      const res = await fetch('{{ route("datasync.save-config") }}', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify(data)
      });

      const result = await res.json();

      if (result.success) {
        alert('✅ ' + result.message);
        // Collapse form and reload current config
        bootstrap.Collapse.getInstance(document.getElementById('configForm')).hide();
        loadCurrentConfig();
      } else {
        alert('❌ Error: ' + result.message);
      }
    } catch (error) {
      alert('❌ Error: ' + error.message);
    }
  });

  // Test connection
  document.getElementById('btnTestConnection').addEventListener('click', async function() {
    const btn = this;
    const status = document.getElementById('connectionStatus');
    
    btn.disabled = true;
    status.innerHTML = '<div class="spinner-border spinner-border-sm" role="status"></div> Testing...';

    try {
      const res = await fetch('{{ route("datasync.test-connection") }}');
      const data = await res.json();

      if (data.success) {
        status.innerHTML = `<div class="alert alert-success mb-0"><i data-feather="check-circle" class="me-2"></i>${data.message}</div>`;
      } else {
        status.innerHTML = `<div class="alert alert-danger mb-0"><i data-feather="x-circle" class="me-2"></i>${data.message}</div>`;
      }
    } catch (error) {
      status.innerHTML = `<div class="alert alert-danger mb-0"><i data-feather="x-circle" class="me-2"></i>Error: ${error.message}</div>`;
    } finally {
      btn.disabled = false;
      feather.replace();
    }
  });
});

async function loadCurrentConfig() {
  const display = document.getElementById('currentConfigDisplay');
  
  try {
    const res = await fetch('{{ route("datasync.get-config") }}');
    const result = await res.json();

    if (result.success && result.data) {
      const cfg = result.data;
      display.innerHTML = `
        <div class="row">
          <div class="col-md-6">
            <dl class="row mb-0">
              <dt class="col-sm-4">Nama:</dt>
              <dd class="col-sm-8">${cfg.name}</dd>
              <dt class="col-sm-4">Host:</dt>
              <dd class="col-sm-8">${cfg.host}:${cfg.port}</dd>
              <dt class="col-sm-4">Database:</dt>
              <dd class="col-sm-8"><code>${cfg.database}</code></dd>
            </dl>
          </div>
          <div class="col-md-6">
            <dl class="row mb-0">
              <dt class="col-sm-4">Username:</dt>
              <dd class="col-sm-8">${cfg.username}</dd>
              <dt class="col-sm-4">Catatan:</dt>
              <dd class="col-sm-8">${cfg.notes || '—'}</dd>
            </dl>
          </div>
        </div>
      `;

      // Populate form for editing
      const form = document.getElementById('formDbConfig');
      form.elements['name'].value = cfg.name;
      form.elements['host'].value = cfg.host;
      form.elements['port'].value = cfg.port;
      form.elements['database'].value = cfg.database;
      form.elements['username'].value = cfg.username;
      form.elements['notes'].value = cfg.notes || '';
    } else {
      display.innerHTML = `
        <div class="alert alert-warning mb-0">
          <i data-feather="alert-triangle" class="me-2"></i>
          Belum ada konfigurasi database eksternal. Klik tombol <strong>Edit</strong> untuk menambahkan.
        </div>
      `;
      feather.replace();
    }
  } catch (error) {
    display.innerHTML = `
      <div class="alert alert-danger mb-0">
        <i data-feather="x-circle" class="me-2"></i>
        Error memuat konfigurasi: ${error.message}
      </div>
    `;
    feather.replace();
  }
}

async function loadComparison(type) {
  const contentDiv = document.getElementById(`${type}-content`);
  contentDiv.innerHTML = '<div class="text-center py-5"><div class="spinner-border" role="status"></div><p class="mt-2">Memuat data...</p></div>';

  const routes = {
    'hsd-material': '{{ route("datasync.compare-hsd-material") }}',
    'hsd-upah': '{{ route("datasync.compare-hsd-upah") }}',
    'ahsp': '{{ route("datasync.compare-ahsp") }}'
  };

  try {
    const res = await fetch(routes[type]);
    const result = await res.json();

    if (!result.success) throw new Error(result.message);

    renderComparison(type, result.data);
  } catch (error) {
    contentDiv.innerHTML = `<div class="alert alert-danger"><i data-feather="alert-circle" class="me-2"></i>Error: ${error.message}</div>`;
    feather.replace();
  }
}

function renderComparison(type, data) {
  const contentDiv = document.getElementById(`${type}-content`);
  
  let html = `
    <div class="accordion" id="accordion-${type}">
      <div class="accordion-item">
        <h2 class="accordion-header">
          <button class="accordion-button ${data.only_external.length === 0 ? 'collapsed' : ''}" type="button" data-bs-toggle="collapse" data-bs-target="#only-external-${type}">
            <span class="badge bg-primary me-2">${data.only_external.length}</span>
            Hanya Ada di Database Eksternal (Bisa Dicopy)
          </button>
        </h2>
        <div id="only-external-${type}" class="accordion-collapse collapse ${data.only_external.length > 0 ? 'show' : ''}" data-bs-parent="#accordion-${type}">
          <div class="accordion-body">
            ${renderOnlyExternal(type, data.only_external)}
          </div>
        </div>
      </div>

      <div class="accordion-item">
        <h2 class="accordion-header">
          <button class="accordion-button ${data.different.length === 0 ? 'collapsed' : ''}" type="button" data-bs-toggle="collapse" data-bs-target="#different-${type}">
            <span class="badge bg-warning text-dark me-2">${data.different.length}</span>
            Data Berbeda (Update Available)
          </button>
        </h2>
        <div id="different-${type}" class="accordion-collapse collapse ${data.different.length > 0 ? 'show' : ''}" data-bs-parent="#accordion-${type}">
          <div class="accordion-body">
            ${renderDifferent(type, data.different)}
          </div>
        </div>
      </div>

      <div class="accordion-item">
        <h2 class="accordion-header">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#only-local-${type}">
            <span class="badge bg-secondary me-2">${data.only_local.length}</span>
            Hanya Ada di Database Lokal
          </button>
        </h2>
        <div id="only-local-${type}" class="accordion-collapse collapse" data-bs-parent="#accordion-${type}">
          <div class="accordion-body">
            ${renderOnlyLocal(type, data.only_local)}
          </div>
        </div>
      </div>

      <div class="accordion-item">
        <h2 class="accordion-header">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#same-${type}">
            <span class="badge bg-success me-2">${data.same.length}</span>
            Data Sama
          </button>
        </h2>
        <div id="same-${type}" class="accordion-collapse collapse" data-bs-parent="#accordion-${type}">
          <div class="accordion-body">
            ${renderSame(type, data.same)}
          </div>
        </div>
      </div>
    </div>
  `;

  contentDiv.innerHTML = html;
  feather.replace();
}

function renderOnlyExternal(type, items) {
  if (items.length === 0) return '<p class="text-muted">Tidak ada data.</p>';

  const fields = getFieldsForType(type);
  let html = `
    <div class="d-flex justify-content-between mb-3">
      <button class="btn btn-sm btn-success" onclick="copySelected('${type}', 'only-external')">
        <i data-feather="download" class="me-1"></i> Copy yang Dipilih
      </button>
      <button class="btn btn-sm btn-outline-secondary" onclick="selectAllInSection('only-external-${type}')">
        <i data-feather="check-square" class="me-1"></i> Pilih Semua
      </button>
    </div>
    <div class="table-responsive">
      <table class="table table-sm table-hover">
        <thead class="table-light">
          <tr>
            <th><input type="checkbox" class="select-all" data-section="only-external-${type}"></th>
            ${fields.map(f => `<th>${f.label}</th>`).join('')}
          </tr>
        </thead>
        <tbody>
  `;

  items.forEach(item => {
    html += `<tr>
      <td><input type="checkbox" class="item-checkbox" data-id="${item.id}" data-section="only-external-${type}"></td>
      ${fields.map(f => `<td>${formatValue(item[f.key], f.type)}</td>`).join('')}
    </tr>`;
  });

  html += '</tbody></table></div>';
  return html;
}

function renderDifferent(type, pairs) {
  if (pairs.length === 0) return '<p class="text-muted">Tidak ada perbedaan.</p>';

  const fields = getFieldsForType(type);
  let html = `
    <div class="d-flex justify-content-between mb-3">
      <button class="btn btn-sm btn-warning text-dark" onclick="copySelected('${type}', 'different')">
        <i data-feather="download" class="me-1"></i> Update yang Dipilih
      </button>
      <button class="btn btn-sm btn-outline-secondary" onclick="selectAllInSection('different-${type}')">
        <i data-feather="check-square" class="me-1"></i> Pilih Semua
      </button>
    </div>
  `;

  pairs.forEach((pair, idx) => {
    // Tombol preview hanya untuk AHSP
    const previewBtn = type === 'ahsp' ? `
      <button class="btn btn-xs btn-info" title="Lihat Detail" onclick="showAhspDifferentPreview('${pair.external.id}', '${pair.local.id}')">
        <i data-feather="eye" style="width:14px;height:14px;"></i>
      </button>
    ` : '';

    html += `
      <div class="card mb-3">
        <div class="card-header d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center">
            <input type="checkbox" class="item-checkbox me-2" data-id="${pair.external.id}" data-section="different-${type}">
            <strong>${pair.local.kode || pair.local.kode_pekerjaan}</strong>
          </div>
          ${previewBtn}
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-6">
              <h6 class="text-muted">Lokal (Saat Ini)</h6>
              ${renderItemDetails(pair.local, fields)}
            </div>
            <div class="col-md-6">
              <h6 class="text-primary">Eksternal (Baru)</h6>
              ${renderItemDetails(pair.external, fields, pair.local)}
            </div>
          </div>
        </div>
      </div>
    `;
  });

  return html;
}

function renderOnlyLocal(type, items) {
  if (items.length === 0) return '<p class="text-muted">Tidak ada data.</p>';

  const fields = getFieldsForType(type);
  let html = '<div class="table-responsive"><table class="table table-sm"><thead class="table-light"><tr>';
  html += fields.map(f => `<th>${f.label}</th>`).join('');
  html += '</tr></thead><tbody>';

  items.forEach(item => {
    html += '<tr>' + fields.map(f => `<td>${formatValue(item[f.key], f.type)}</td>`).join('') + '</tr>';
  });

  html += '</tbody></table></div>';
  return html;
}

function renderSame(type, items) {
  if (items.length === 0) return '<p class="text-muted">Tidak ada data.</p>';

  const fields = getFieldsForType(type);
  let html = `
    <div class="alert alert-info mb-3">
      <i data-feather="info" class="me-2"></i>
      ${items.length} item sama persis antara database lokal dan eksternal. Gunakan tombol "Re-Sync" jika ingin memperbarui ulang.
    </div>
    <div class="table-responsive">
      <table class="table table-sm table-hover">
        <thead class="table-light">
          <tr>
            <th width="100">Aksi</th>
            ${fields.map(f => `<th>${f.label}</th>`).join('')}
          </tr>
        </thead>
        <tbody>
  `;

  items.forEach(item => {
    const itemId = item.id;
    html += `<tr>
      <td>
        <button class="btn btn-sm btn-outline-primary resync-btn" data-type="${type}" data-id="${itemId}" title="Re-Sync item ini">
          <i data-feather="refresh-cw" class="me-1"></i> Re-Sync
        </button>
      </td>
      ${fields.map(f => `<td>${formatValue(item[f.key], f.type)}</td>`).join('')}
    </tr>`;
  });

  html += '</tbody></table></div>';
  return html;
}

function renderItemDetails(item, fields, compareWith = null) {
  let html = '<dl class="row mb-0 small">';
  fields.forEach(f => {
    if (f.key === 'kode' || f.key === 'kode_pekerjaan') return;
    
    const isDiff = compareWith && item[f.key] != compareWith[f.key];
    const className = isDiff ? 'text-primary fw-bold' : '';
    
    html += `
      <dt class="col-sm-4">${f.label}:</dt>
      <dd class="col-sm-8 ${className}">${formatValue(item[f.key], f.type)}</dd>
    `;
  });
  html += '</dl>';
  return html;
}

function getFieldsForType(type) {
  const fieldMap = {
    'hsd-material': [
      {key: 'kode', label: 'Kode'},
      {key: 'nama', label: 'Nama'},
      {key: 'satuan', label: 'Satuan'},
      {key: 'harga_satuan', label: 'Harga', type: 'currency'},
      {key: 'updated_at', label: 'Update', type: 'date'}
    ],
    'hsd-upah': [
      {key: 'kode', label: 'Kode'},
      {key: 'jenis_pekerja', label: 'Jenis Pekerja'},
      {key: 'satuan', label: 'Satuan'},
      {key: 'harga_satuan', label: 'Harga', type: 'currency'},
      {key: 'updated_at', label: 'Update', type: 'date'}
    ],
    'ahsp': [
      {key: 'kode_pekerjaan', label: 'Kode'},
      {key: 'nama_pekerjaan', label: 'Nama Pekerjaan'},
      {key: 'satuan', label: 'Satuan'},
      {key: 'total_harga', label: 'Total Harga', type: 'currency'},
      {key: 'updated_at', label: 'Update', type: 'date'}
    ]
  };
  return fieldMap[type] || [];
}

function formatValue(value, type) {
  if (value === null || value === undefined) return '—';
  
  if (type === 'currency') {
    return 'Rp ' + parseFloat(value).toLocaleString('id-ID');
  }
  if (type === 'date') {
    return new Date(value).toLocaleDateString('id-ID');
  }
  return value;
}

function selectAllInSection(sectionId) {
  const section = document.getElementById(sectionId);
  const checkboxes = section.querySelectorAll('.item-checkbox');
  const allChecked = Array.from(checkboxes).every(cb => cb.checked);
  
  checkboxes.forEach(cb => cb.checked = !allChecked);
}

async function copySelected(type, section) {
  const checkboxes = document.querySelectorAll(`.item-checkbox[data-section="${section}-${type}"]:checked`);
  const ids = Array.from(checkboxes).map(cb => parseInt(cb.dataset.id));

  if (ids.length === 0) {
    alert('Pilih minimal satu item untuk dicopy.');
    return;
  }

  if (!confirm(`Copy ${ids.length} item yang dipilih?`)) return;

  const routes = {
    'hsd-material': '{{ route("datasync.copy-hsd-material") }}',
    'hsd-upah': '{{ route("datasync.copy-hsd-upah") }}',
    'ahsp': '{{ route("datasync.copy-ahsp") }}'
  };

  try {
    const res = await fetch(routes[type], {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': '{{ csrf_token() }}'
      },
      body: JSON.stringify({ ids })
    });

    const data = await res.json();

    if (data.success) {
      alert(data.message);
      loadComparison(type); // Reload
    } else {
      alert('Error: ' + data.message);
    }
  } catch (error) {
    alert('Error: ' + error.message);
  }
}

async function resyncItem(type, id) {
  // Hanya AHSP yang perlu preview detail
  if (type === 'ahsp') {
    await showAhspPreview(id);
  } else {
    // Material dan Upah langsung sync
    await performSync(type, id);
  }
}

async function showAhspPreview(id) {
  // Buka modal dan load preview
  const modal = new bootstrap.Modal(document.getElementById('previewAhspModal'));
  modal.show();

  const contentDiv = document.getElementById('previewContent');
  contentDiv.innerHTML = `
    <div class="text-center py-5">
      <div class="spinner-border" role="status"></div>
      <p class="mt-2">Memuat detail AHSP...</p>
    </div>
  `;

  // Store id untuk digunakan saat confirm sync
  window.pendingSyncId = id;
  window.pendingSyncType = 'ahsp';

  try {
    const res = await fetch('{{ route("datasync.get-ahsp-details") }}?id=' + id, {
      headers: {
        'X-CSRF-TOKEN': '{{ csrf_token() }}'
      }
    });

    const data = await res.json();

    if (data.success) {
      const externalHeader = data.external.header;
      const externalDetails = data.external.details;
      const existingHeader = data.existing.header;
      const existingDetails = data.existing.details;
      const hasExisting = data.hasExisting;

      let html = '';

      // Header comparison
      if (hasExisting) {
        html += `
          <div class="mb-3">
            <h6 class="fw-bold mb-2">
              <i data-feather="copy" class="me-1" style="width: 16px;"></i>
              Informasi Header
            </h6>
            <div class="row">
              <div class="col-md-6">
                <div class="card border-secondary">
                  <div class="card-header bg-light">
                    <strong>Data Lokal (Saat Ini)</strong>
                  </div>
                  <div class="card-body p-2 small">
                    <p class="mb-1"><strong>Kode:</strong> ${existingHeader.kode_pekerjaan}</p>
                    <p class="mb-1"><strong>Nama:</strong> ${existingHeader.nama_pekerjaan}</p>
                    <p class="mb-1"><strong>Satuan:</strong> ${existingHeader.satuan}</p>
                    <p class="mb-0"><strong>Total Harga:</strong> <br>Rp ${parseFloat(existingHeader.total_harga).toLocaleString('id-ID')}</p>
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="card border-primary">
                  <div class="card-header bg-light">
                    <strong>Data Eksternal (Baru)</strong>
                  </div>
                  <div class="card-body p-2 small">
                    <p class="mb-1"><strong>Kode:</strong> ${externalHeader.kode_pekerjaan}</p>
                    <p class="mb-1"><strong>Nama:</strong> ${externalHeader.nama_pekerjaan}</p>
                    <p class="mb-1"><strong>Satuan:</strong> ${externalHeader.satuan}</p>
                    <p class="mb-0"><strong>Total Harga:</strong> <br>Rp ${parseFloat(externalHeader.total_harga).toLocaleString('id-ID')}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        `;
      } else {
        html += `
          <div class="alert alert-info mb-3">
            <i data-feather="info" class="me-2" style="width: 16px;"></i>
            <strong>AHSP baru:</strong> Data ini belum ada di database lokal.
          </div>
          <div class="mb-3">
            <h6 class="fw-bold mb-2">Informasi Header (Baru)</h6>
            <div class="card bg-light">
              <div class="card-body p-3 small">
                <p class="mb-1"><strong>Kode:</strong> ${externalHeader.kode_pekerjaan}</p>
                <p class="mb-1"><strong>Nama:</strong> ${externalHeader.nama_pekerjaan}</p>
                <p class="mb-1"><strong>Satuan:</strong> ${externalHeader.satuan}</p>
                <p class="mb-0"><strong>Total Harga:</strong> Rp ${parseFloat(externalHeader.total_harga).toLocaleString('id-ID')}</p>
              </div>
            </div>
          </div>
        `;
      }

      // Details comparison
      html += `
        <div class="mb-3">
          <h6 class="fw-bold mb-2">
            <i data-feather="layers" class="me-1" style="width: 16px;"></i>
            Komponen Penyusun
          </h6>
      `;

      if (hasExisting && existingDetails.length > 0) {
        html += `
          <ul class="nav nav-tabs mb-3" role="tablist">
            <li class="nav-item" role="presentation">
              <button class="nav-link active" id="existing-tab" data-bs-toggle="tab" data-bs-target="#existing" type="button" role="tab">
                Lokal (${existingDetails.length})
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="external-tab" data-bs-toggle="tab" data-bs-target="#external" type="button" role="tab">
                Eksternal (${externalDetails.length})
              </button>
            </li>
          </ul>
          <div class="tab-content">
            <div class="tab-pane fade show active" id="existing" role="tabpanel">
              <div class="table-responsive">
                <table class="table table-sm table-bordered">
                  <thead class="table-light">
                    <tr>
                      <th width="70">Tipe</th>
                      <th>Kode</th>
                      <th>Nama</th>
                      <th width="60">Satuan</th>
                      <th width="80">Koefisien</th>
                      <th width="90">Harga</th>
                      <th width="90">Subtotal</th>
                      <th width="60">Diskon %</th>
                      <th width="80">PPN %</th>
                      <th width="100">Final</th>
                    </tr>
                  </thead>
                  <tbody>
        `;

        existingDetails.forEach(detail => {
          html += `
            <tr>
              <td><span class="badge ${detail.tipe === 'material' ? 'bg-info' : 'bg-warning text-dark'}">${detail.tipe === 'material' ? 'Mat' : 'Upah'}</span></td>
              <td><code class="small">${detail.source_kode}</code></td>
              <td><small>${detail.source_nama}</small></td>
              <td><small>${detail.satuan}</small></td>
              <td class="text-end"><small>${parseFloat(detail.koefisien).toLocaleString('id-ID', {maximumFractionDigits: 4})}</small></td>
              <td class="text-end"><small>Rp ${parseFloat(detail.harga_satuan).toLocaleString('id-ID')}</small></td>
              <td class="text-end"><small>Rp ${parseFloat(detail.subtotal).toLocaleString('id-ID')}</small></td>
              <td class="text-end"><small>${parseFloat(detail.diskon_persen).toLocaleString('id-ID', {maximumFractionDigits: 2})}%</small></td>
              <td class="text-end"><small>${parseFloat(detail.ppn_persen).toLocaleString('id-ID', {maximumFractionDigits: 2})}%</small></td>
              <td class="text-end"><small><strong>Rp ${parseFloat(detail.subtotal_final).toLocaleString('id-ID')}</strong></small></td>
            </tr>
          `;
        });

        html += `
                  </tbody>
                </table>
              </div>
            </div>
            <div class="tab-pane fade" id="external" role="tabpanel">
              <div class="table-responsive">
                <table class="table table-sm table-bordered">
                  <thead class="table-light">
                    <tr>
                      <th width="70">Tipe</th>
                      <th>Kode</th>
                      <th>Nama</th>
                      <th width="60">Satuan</th>
                      <th width="80">Koefisien</th>
                      <th width="90">Harga</th>
                      <th width="90">Subtotal</th>
                      <th width="60">Diskon %</th>
                      <th width="80">PPN %</th>
                      <th width="100">Final</th>
                    </tr>
                  </thead>
                  <tbody>
        `;

        externalDetails.forEach(detail => {
          html += `
            <tr>
              <td><span class="badge ${detail.tipe === 'material' ? 'bg-info' : 'bg-warning text-dark'}">${detail.tipe === 'material' ? 'Mat' : 'Upah'}</span></td>
              <td><code class="small">${detail.source_kode}</code></td>
              <td><small>${detail.source_nama}</small></td>
              <td><small>${detail.satuan}</small></td>
              <td class="text-end"><small>${parseFloat(detail.koefisien).toLocaleString('id-ID', {maximumFractionDigits: 4})}</small></td>
              <td class="text-end"><small>Rp ${parseFloat(detail.harga_satuan).toLocaleString('id-ID')}</small></td>
              <td class="text-end"><small>Rp ${parseFloat(detail.subtotal).toLocaleString('id-ID')}</small></td>
              <td class="text-end"><small>${parseFloat(detail.diskon_persen).toLocaleString('id-ID', {maximumFractionDigits: 2})}%</small></td>
              <td class="text-end"><small>${parseFloat(detail.ppn_persen).toLocaleString('id-ID', {maximumFractionDigits: 2})}%</small></td>
              <td class="text-end"><small><strong>Rp ${parseFloat(detail.subtotal_final).toLocaleString('id-ID')}</strong></small></td>
            </tr>
          `;
        });

        html += `
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        `;
      } else {
        html += `
          <div class="table-responsive">
            <table class="table table-sm table-bordered">
              <thead class="table-light">
                <tr>
                  <th width="70">Tipe</th>
                  <th>Kode</th>
                  <th>Nama</th>
                  <th width="60">Satuan</th>
                  <th width="80">Koefisien</th>
                  <th width="90">Harga</th>
                  <th width="90">Subtotal</th>
                  <th width="60">Diskon %</th>
                  <th width="80">PPN %</th>
                  <th width="100">Final</th>
                </tr>
              </thead>
              <tbody>
        `;

        externalDetails.forEach(detail => {
          html += `
            <tr>
              <td><span class="badge ${detail.tipe === 'material' ? 'bg-info' : 'bg-warning text-dark'}">${detail.tipe === 'material' ? 'Mat' : 'Upah'}</span></td>
              <td><code class="small">${detail.source_kode}</code></td>
              <td><small>${detail.source_nama}</small></td>
              <td><small>${detail.satuan}</small></td>
              <td class="text-end"><small>${parseFloat(detail.koefisien).toLocaleString('id-ID', {maximumFractionDigits: 4})}</small></td>
              <td class="text-end"><small>Rp ${parseFloat(detail.harga_satuan).toLocaleString('id-ID')}</small></td>
              <td class="text-end"><small>Rp ${parseFloat(detail.subtotal).toLocaleString('id-ID')}</small></td>
              <td class="text-end"><small>${parseFloat(detail.diskon_persen).toLocaleString('id-ID', {maximumFractionDigits: 2})}%</small></td>
              <td class="text-end"><small>${parseFloat(detail.ppn_persen).toLocaleString('id-ID', {maximumFractionDigits: 2})}%</small></td>
              <td class="text-end"><small><strong>Rp ${parseFloat(detail.subtotal_final).toLocaleString('id-ID')}</strong></small></td>
            </tr>
          `;
        });

        html += `
              </tbody>
            </table>
          </div>
        `;
      }

      html += `
        </div>

        <div class="alert alert-info mb-0">
          <i data-feather="info" class="me-2" style="width: 16px; height: 16px;"></i>
          <strong>Verifikasi:</strong> Pastikan semua data di atas sudah benar sebelum melanjutkan sync.
        </div>
      `;

      contentDiv.innerHTML = html;
      feather.replace();
    } else {
      contentDiv.innerHTML = `
        <div class="alert alert-danger mb-0">
          <i data-feather="alert-circle" class="me-2"></i>
          ${data.message || 'Gagal memuat detail AHSP'}
        </div>
      `;
    }
  } catch (error) {
    contentDiv.innerHTML = `
      <div class="alert alert-danger mb-0">
        <i data-feather="alert-circle" class="me-2"></i>
        Error: ${error.message}
      </div>
    `;
  }
}

async function confirmSync() {
  if (!window.pendingSyncId || !window.pendingSyncType) {
    alert('Data sync tidak valid');
    return;
  }

  const modal = bootstrap.Modal.getInstance(document.getElementById('previewAhspModal'));
  modal.hide();

  await performSync(window.pendingSyncType, window.pendingSyncId);
}

async function showAhspDifferentPreview(externalId, localId) {
  // Buka modal dan load preview untuk AHSP berbeda
  const modal = new bootstrap.Modal(document.getElementById('previewDifferentAhspModal'));
  modal.show();

  const contentDiv = document.getElementById('previewDifferentContent');
  contentDiv.innerHTML = `
    <div class="text-center py-5">
      <div class="spinner-border" role="status"></div>
      <p class="mt-2">Memuat detail perbandingan...</p>
    </div>
  `;

  // Store untuk digunakan saat confirm sync
  window.pendingDifferentExternalId = externalId;
  window.pendingDifferentLocalId = localId;

  try {
    const res = await fetch('{{ route("datasync.get-ahsp-details") }}?id=' + externalId, {
      headers: {
        'X-CSRF-TOKEN': '{{ csrf_token() }}'
      }
    });

    const data = await res.json();

    if (data.success) {
      const externalHeader = data.external.header;
      const externalDetails = data.external.details;
      const existingHeader = data.existing.header;
      const existingDetails = data.existing.details;
      const hasExisting = data.hasExisting;

      let html = '';

      // Alert info
      html += `
        <div class="alert alert-warning mb-3">
          <i data-feather="alert-circle" class="me-2" style="width: 16px;"></i>
          <strong>Data Berbeda:</strong> Pilih tombol Update di bawah untuk mengganti data lokal dengan data eksternal.
        </div>
      `;

      // Header comparison - side by side
      html += `
        <div class="mb-4">
          <h6 class="fw-bold mb-3">
            <i data-feather="copy" class="me-1" style="width: 16px;"></i>
            Informasi Header
          </h6>
          <div class="row">
            <div class="col-md-6">
              <div class="card border-secondary h-100">
                <div class="card-header bg-light">
                  <strong>Data Lokal (Saat Ini)</strong>
                </div>
                <div class="card-body p-2 small">
                  <p class="mb-1"><strong>Kode:</strong> ${existingHeader.kode_pekerjaan}</p>
                  <p class="mb-1"><strong>Nama:</strong> ${existingHeader.nama_pekerjaan}</p>
                  <p class="mb-1"><strong>Satuan:</strong> ${existingHeader.satuan}</p>
                  <p class="mb-0"><strong>Total Harga:</strong> <br>Rp <span class="fw-bold text-danger">${parseFloat(existingHeader.total_harga).toLocaleString('id-ID')}</span></p>
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="card border-success h-100">
                <div class="card-header bg-light">
                  <strong>Data Eksternal (Baru)</strong>
                </div>
                <div class="card-body p-2 small">
                  <p class="mb-1"><strong>Kode:</strong> ${externalHeader.kode_pekerjaan}</p>
                  <p class="mb-1"><strong>Nama:</strong> ${externalHeader.nama_pekerjaan}</p>
                  <p class="mb-1"><strong>Satuan:</strong> ${externalHeader.satuan}</p>
                  <p class="mb-0"><strong>Total Harga:</strong> <br>Rp <span class="fw-bold text-success">${parseFloat(externalHeader.total_harga).toLocaleString('id-ID')}</span></p>
                </div>
              </div>
            </div>
          </div>
        </div>
      `;

      // Details comparison - tabs
      html += `
        <div class="mb-3">
          <h6 class="fw-bold mb-2">
            <i data-feather="layers" class="me-1" style="width: 16px;"></i>
            Komponen Penyusun
          </h6>
          <ul class="nav nav-tabs mb-3" role="tablist">
            <li class="nav-item" role="presentation">
              <button class="nav-link active" id="diff-local-tab" data-bs-toggle="tab" data-bs-target="#diff-local" type="button" role="tab">
                Lokal (${existingDetails.length})
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="diff-external-tab" data-bs-toggle="tab" data-bs-target="#diff-external" type="button" role="tab">
                Eksternal (${externalDetails.length})
              </button>
            </li>
          </ul>
          <div class="tab-content">
            <div class="tab-pane fade show active" id="diff-local" role="tabpanel">
              <div class="table-responsive">
                <table class="table table-sm table-bordered">
                  <thead class="table-light">
                    <tr>
                      <th width="70">Tipe</th>
                      <th>Kode</th>
                      <th>Nama</th>
                      <th width="60">Satuan</th>
                      <th width="80">Koefisien</th>
                      <th width="90">Harga</th>
                      <th width="90">Subtotal</th>
                      <th width="60">Diskon %</th>
                      <th width="80">PPN %</th>
                      <th width="100">Final</th>
                    </tr>
                  </thead>
                  <tbody>
      `;

      existingDetails.forEach(detail => {
        html += `
          <tr>
            <td><span class="badge ${detail.tipe === 'material' ? 'bg-info' : 'bg-warning text-dark'}">${detail.tipe === 'material' ? 'Mat' : 'Upah'}</span></td>
            <td><code class="small">${detail.source_kode}</code></td>
            <td><small>${detail.source_nama}</small></td>
            <td><small>${detail.satuan}</small></td>
            <td class="text-end"><small>${parseFloat(detail.koefisien).toLocaleString('id-ID', {maximumFractionDigits: 4})}</small></td>
            <td class="text-end"><small>Rp ${parseFloat(detail.harga_satuan).toLocaleString('id-ID')}</small></td>
            <td class="text-end"><small>Rp ${parseFloat(detail.subtotal).toLocaleString('id-ID')}</small></td>
            <td class="text-end"><small>${parseFloat(detail.diskon_persen).toLocaleString('id-ID', {maximumFractionDigits: 2})}%</small></td>
            <td class="text-end"><small>${parseFloat(detail.ppn_persen).toLocaleString('id-ID', {maximumFractionDigits: 2})}%</small></td>
            <td class="text-end"><small><strong>Rp ${parseFloat(detail.subtotal_final).toLocaleString('id-ID')}</strong></small></td>
          </tr>
        `;
      });

      html += `
                  </tbody>
                </table>
              </div>
            </div>
            <div class="tab-pane fade" id="diff-external" role="tabpanel">
              <div class="table-responsive">
                <table class="table table-sm table-bordered">
                  <thead class="table-light">
                    <tr>
                      <th width="70">Tipe</th>
                      <th>Kode</th>
                      <th>Nama</th>
                      <th width="60">Satuan</th>
                      <th width="80">Koefisien</th>
                      <th width="90">Harga</th>
                      <th width="90">Subtotal</th>
                      <th width="60">Diskon %</th>
                      <th width="80">PPN %</th>
                      <th width="100">Final</th>
                    </tr>
                  </thead>
                  <tbody>
      `;

      externalDetails.forEach(detail => {
        html += `
          <tr>
            <td><span class="badge ${detail.tipe === 'material' ? 'bg-info' : 'bg-warning text-dark'}">${detail.tipe === 'material' ? 'Mat' : 'Upah'}</span></td>
            <td><code class="small">${detail.source_kode}</code></td>
            <td><small>${detail.source_nama}</small></td>
            <td><small>${detail.satuan}</small></td>
            <td class="text-end"><small>${parseFloat(detail.koefisien).toLocaleString('id-ID', {maximumFractionDigits: 4})}</small></td>
            <td class="text-end"><small>Rp ${parseFloat(detail.harga_satuan).toLocaleString('id-ID')}</small></td>
            <td class="text-end"><small>Rp ${parseFloat(detail.subtotal).toLocaleString('id-ID')}</small></td>
            <td class="text-end"><small>${parseFloat(detail.diskon_persen).toLocaleString('id-ID', {maximumFractionDigits: 2})}%</small></td>
            <td class="text-end"><small>${parseFloat(detail.ppn_persen).toLocaleString('id-ID', {maximumFractionDigits: 2})}%</small></td>
            <td class="text-end"><small><strong>Rp ${parseFloat(detail.subtotal_final).toLocaleString('id-ID')}</strong></small></td>
          </tr>
        `;
      });

      html += `
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <div class="alert alert-info mb-0">
          <i data-feather="info" class="me-2" style="width: 16px; height: 16px;"></i>
          <strong>Verifikasi:</strong> Pastikan perbandingan di atas sudah benar sebelum melanjutkan update.
        </div>
      `;

      contentDiv.innerHTML = html;
      feather.replace();
    } else {
      contentDiv.innerHTML = `
        <div class="alert alert-danger mb-0">
          <i data-feather="alert-circle" class="me-2"></i>
          ${data.message || 'Gagal memuat detail AHSP'}
        </div>
      `;
    }
  } catch (error) {
    contentDiv.innerHTML = `
      <div class="alert alert-danger mb-0">
        <i data-feather="alert-circle" class="me-2"></i>
        Error: ${error.message}
      </div>
    `;
  }
}

async function confirmDifferentSync() {
  if (!window.pendingDifferentExternalId) {
    alert('Data tidak valid');
    return;
  }

  const modal = bootstrap.Modal.getInstance(document.getElementById('previewDifferentAhspModal'));
  modal.hide();

  // Perform sync for different AHSP
  if (!confirm('Update AHSP ini dengan data eksternal?')) return;

  const route = '{{ route("datasync.resync-ahsp", ["id" => "__ID__"]) }}'.replace('__ID__', window.pendingDifferentExternalId);

  try {
    const res = await fetch(route, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': '{{ csrf_token() }}'
      }
    });

    const data = await res.json();

    if (data.success) {
      alert('✓ AHSP berhasil diupdate');
      loadComparison('ahsp'); // Reload
    } else {
      alert('Error: ' + data.message);
    }
  } catch (error) {
    alert('Error: ' + error.message);
  }
}

async function performSync(type, id) {
  if (!confirm('Re-sync item ini?')) return;

  const routeMap = {
    'hsd-material': '{{ route("datasync.resync-hsd-material", ["id" => "__ID__"]) }}',
    'hsd-upah': '{{ route("datasync.resync-hsd-upah", ["id" => "__ID__"]) }}',
    'ahsp': '{{ route("datasync.resync-ahsp", ["id" => "__ID__"]) }}'
  };

  const route = routeMap[type].replace('__ID__', id);

  // Cari button yang sesuai (jika dari modal, tidak ada button untuk diubah)
  const btn = document.querySelector(`.resync-btn[data-type="${type}"][data-id="${id}"]`);
  const originalHtml = btn ? btn.innerHTML : null;
  
  if (btn) {
    btn.disabled = true;
    btn.innerHTML = '<i data-feather="loader" class="spinner me-1"></i> Syncing...';
  }

  try {
    const res = await fetch(route, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': '{{ csrf_token() }}'
      }
    });

    const data = await res.json();

    if (data.success) {
      if (btn) {
        btn.className = 'btn btn-sm btn-outline-success resync-btn';
        btn.innerHTML = '<i data-feather="check" class="me-1"></i> Synced!';
        setTimeout(() => {
          btn.className = 'btn btn-sm btn-outline-primary resync-btn';
          btn.innerHTML = originalHtml;
          btn.disabled = false;
          feather.replace();
        }, 2000);
      }
      alert(data.message);
      // Reload comparison
      const tabType = type === 'ahsp' ? 'ahsp' : (type === 'hsd-material' ? 'hsd-material' : 'hsd-upah');
      loadComparison(tabType);
    } else {
      alert('Error: ' + data.message);
      if (btn) {
        btn.disabled = false;
        btn.innerHTML = originalHtml;
        feather.replace();
      }
    }
  } catch (error) {
    alert('Error: ' + error.message);
    if (btn) {
      btn.disabled = false;
      btn.innerHTML = originalHtml;
      feather.replace();
    }
  }
}
</script>
@endpush

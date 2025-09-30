<div class="mb-3">
  <div class="row g-2">
    <div class="col-auto"><strong>Last Login:</strong></div>
    <div class="col-auto">
      {{ optional($lastLogin)->created_at?->format('d M Y H:i') ?? '—' }}
      @if($lastLogin && $lastLogin->ip_address)
        <span class="text-muted"> (IP: {{ $lastLogin->ip_address }})</span>
      @endif
    </div>
    <div class="col ms-auto">
      <form method="get" class="d-flex gap-2 justify-content-end" onsubmit="return false;">
        <input type="text" class="form-control form-control-sm" id="logSearchInput" placeholder="Cari event/desc/ip/device...">
        <button class="btn btn-sm btn-outline-secondary" id="logSearchBtn">Cari</button>
      </form>
    </div>
  </div>
</div>

<div class="table-responsive">
  <table class="table table-sm table-hover align-middle mb-0" id="userLogsTable">
    <thead>
      <tr>
        <th style="width: 170px;">Waktu</th>
        <th>Event</th>
        <th>Deskripsi</th>
        <th style="width: 140px;">IP</th>
        <th>Device</th>
      </tr>
    </thead>
    <tbody>
      @forelse($logs as $log)
        <tr>
          <td>{{ $log->created_at->format('d M Y H:i:s') }}</td>
          <td>
            <span class="badge {{ $log->event === 'login' ? 'bg-success' : ($log->event === 'logout' ? 'bg-secondary' : 'bg-info') }}">
              {{ $log->event }}
            </span>
          </td>
          <td class="text-break">{{ $log->description ?: '—' }}</td>
          <td>{{ $log->ip_address ?: '—' }}</td>
          <td class="text-break">{{ $log->device_name ?: '—' }}</td>
        </tr>
      @empty
        <tr>
          <td colspan="5" class="text-center text-muted py-4">Belum ada aktivitas.</td>
        </tr>
      @endforelse
    </tbody>
  </table>
</div>

<script>
  // Pencarian ringan di dalam modal (client-side)
  (function(){
    const $rows = $('#userLogsTable tbody tr');
    $('#logSearchBtn').on('click', function(){
      const term = ($('#logSearchInput').val() || '').toLowerCase();
      $rows.each(function(){
        const text = $(this).text().toLowerCase();
        $(this).toggle(text.indexOf(term) > -1);
      });
    });
  })();
</script>

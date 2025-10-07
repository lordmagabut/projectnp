@php
  // Pakai timezone dari config (set di .env: APP_TIMEZONE=Asia/Jakarta)
  $tz = config('app.timezone', 'UTC');
  $lastLoginAt = optional($lastLogin)->created_at?->timezone($tz);
@endphp

<div class="mb-3">
  <div class="row g-2 align-items-center">
    <div class="col-auto"><strong>Last Login:</strong></div>
    <div class="col-auto">
      {{ $lastLoginAt ? $lastLoginAt->format('d M Y H:i') : '—' }}
      @if($lastLogin && $lastLogin->ip_address)
        <span class="text-muted"> (IP: {{ $lastLogin->ip_address }})</span>
      @endif
    </div>
    <div class="col ms-auto">
      <form class="d-flex gap-2 justify-content-end">
        <input type="text" class="form-control form-control-sm" id="logSearchInput" placeholder="Cari event/desc/ip/device...">
        <button type="button" class="btn btn-sm btn-outline-secondary" id="logSearchBtn">Cari</button>
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
        @php
          $created = optional($log->created_at)->timezone($tz);
          $badge = $log->event === 'login' ? 'bg-success' : ($log->event === 'logout' ? 'bg-secondary' : 'bg-info');
        @endphp
        <tr>
          <td>{{ $created ? $created->format('d M Y H:i:s') : '—' }}</td>
          <td>
            <span class="badge {{ $badge }}">{{ $log->event }}</span>
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
    function applyFilter() {
      const term = ($('#logSearchInput').val() || '').toLowerCase();
      $rows.each(function(){
        const text = $(this).text().toLowerCase();
        $(this).toggle(text.indexOf(term) > -1);
      });
    }
    $('#logSearchBtn').on('click', applyFilter);
    $('#logSearchInput').on('keyup', function(e){
      if (e.key === 'Enter') e.preventDefault();
      applyFilter();
    });
  })();
</script>

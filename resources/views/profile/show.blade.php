@extends('layout.master')

@section('content')
@php
  // Pakai timezone dari config (set di .env: APP_TIMEZONE=Asia/Jakarta)
  $tz = config('app.timezone', 'UTC');
  $email = $user->email ?? null;
  $hash  = $email ? md5(strtolower(trim($email))) : null;
  $avatar = $hash ? "https://www.gravatar.com/avatar/{$hash}?s=120&d=mp" : 'https://via.placeholder.com/120x120';
  $lastLoginAt = optional($lastLogin)->created_at?->timezone($tz);
@endphp

<div class="row">
  <div class="col-12 col-xl-4">
    <div class="card shadow-sm mb-3">
      <div class="card-body d-flex align-items-center">
        <img class="rounded-circle me-3" src="{{ $avatar }}" width="80" height="80" alt="avatar">
        <div>
          <h5 class="mb-1">{{ $user->name ?? $user->username }}</h5>
          <div class="text-muted small">{{ $user->email ?? '—' }}</div>
          <div class="mt-2">
            @forelse($user->roles as $r)
              <span class="badge bg-primary me-1 mb-1">{{ $r->name }}</span>
            @empty
              <span class="text-muted">Tidak ada peran</span>
            @endforelse
          </div>
        </div>
      </div>
    </div>

    <div class="card shadow-sm mb-3">
      <div class="card-header">
        <strong>Status Login</strong>
      </div>
      <div class="card-body">
        @auth
          <dl class="row mb-0">
            <dt class="col-5">Sedang Login</dt>
            <dd class="col-7">Ya</dd>
            <dt class="col-5">IP Sekarang</dt>
            <dd class="col-7">{{ $ip ?? '—' }}</dd>
            <dt class="col-5">Nama Komputer</dt>
            <dd class="col-7">{{ (!empty($hostname) && $hostname !== ($ip ?? null)) ? $hostname : '—' }}</dd>
            <dt class="col-5">User Agent</dt>
            <dd class="col-7 text-break">{{ $ua ?? '—' }}</dd>
          </dl>
        @else
          <dl class="row mb-0">
            <dt class="col-5">Sedang Login</dt>
            <dd class="col-7">Tidak</dd>
            <dt class="col-5">Last Login</dt>
            <dd class="col-7">
              {{ $lastLoginAt ? $lastLoginAt->format('d M Y H:i') : '—' }}
              @if($lastLogin && $lastLogin->ip_address)
                <div class="text-muted small">IP: {{ $lastLogin->ip_address }}</div>
              @endif
            </dd>
          </dl>
        @endauth
      </div>
    </div>
  </div>

  <div class="col-12 col-xl-8">
    <div class="card shadow-sm">
      <div class="card-header d-flex justify-content-between align-items-center">
        <strong>Log Aktivitas</strong>
        <form method="get" class="d-flex gap-2">
          <input type="text"
                 class="form-control form-control-sm"
                 name="q"
                 value="{{ request('q') }}"
                 placeholder="Cari event/desc/ip/device...">
          <button class="btn btn-sm btn-outline-secondary" type="submit">Cari</button>
        </form>
      </div>

      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table mb-0 align-middle">
            <thead>
              <tr>
                <th style="width: 170px;">Waktu</th>
                <th>Event</th>
                <th>Deskripsi</th>
                <th style="width: 160px;">IP</th>
                <th>Device</th>
              </tr>
            </thead>
            <tbody>
              @forelse($logs as $log)
                @php
                  $created = optional($log->created_at)->timezone($tz);
                  $badge = $log->event === 'login' ? 'bg-success'
                         : ($log->event === 'logout' ? 'bg-secondary' : 'bg-info');
                @endphp
                <tr>
                  <td>{{ $created ? $created->format('d M Y H:i:s') : '—' }}</td>
                  <td><span class="badge {{ $badge }}">{{ $log->event }}</span></td>
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
      </div>

      @if($logs instanceof \Illuminate\Contracts\Pagination\Paginator)
        <div class="card-footer">
          {{ $logs->withQueryString()->links() }}
        </div>
      @endif
    </div>
  </div>
</div>
@endsection

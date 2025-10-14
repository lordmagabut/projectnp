@extends('layout.master2')

@section('content')
<style>
  /* ======= White, minimal, NiceHash-like ======= */
  .auth-wrapper {
    min-height: calc(100vh - 80px); /* sesuaikan jika header/footer ada */
    display: flex; align-items: center; justify-content: center;
    background: #f6f7f9;
  }
  .auth-card {
    width: 100%;
    max-width: 420px;
    background: #fff;
    border: 1px solid #e6e8eb;
    border-radius: 16px;
    box-shadow: 0 6px 20px rgba(16,24,40,.04);
  }
  .auth-card .brand {
    display: flex; align-items: center; justify-content: center;
    gap: 10px; margin-bottom: 12px;
  }
  .brand img {
    height: 36px; width: auto;
  }
  .brand .brand-name {
    font-weight: 700; color: #111827; letter-spacing: .2px;
  }
  .auth-header {
    text-align: center;
    margin-bottom: 6px;
  }
  .auth-title {
    margin: 0;
    font-size: 22px;
    font-weight: 700;
    color: #111827;
  }
  .auth-subtitle {
    color: #6b7280;
    font-size: 14px;
    margin: 6px 0 0 0;
  }
  .auth-body {
    padding: 28px 28px 22px 28px;
  }
  .form-label {
    font-weight: 600; color: #374151; font-size: 13px;
  }
  .form-control {
    border-radius: 10px; padding: 10px 12px; border-color: #e5e7eb;
  }
  .form-control:focus {
    border-color: #94c0ff;
    box-shadow: 0 0 0 .2rem rgba(59,130,246,.15);
  }
  .btn-primary {
    width: 100%;
    border-radius: 10px;
    padding: 10px 14px;
    font-weight: 700;
    background: #2563eb;
    border-color: #2563eb;
  }
  .btn-primary:hover { background:#1f51bf; border-color:#1f51bf; }
  .helper-row {
    display:flex; align-items:center; justify-content:space-between; gap:8px;
    margin-top: -2px; margin-bottom: 10px;
  }
  .link-muted { color:#2563eb; text-decoration:none; font-weight:600; }
  .link-muted:hover { text-decoration: underline; }
  .divider {
    display:flex; align-items:center; gap:12px; margin: 16px 0 6px 0;
    color:#9ca3af; font-size:12px; font-weight:600;
  }
  .divider::before, .divider::after {
    content:""; flex:1; height:1px; background:#e5e7eb;
  }
  .alerts-wrap { margin-bottom: 14px; }
  .list-unstyled { margin: 0; }
  .input-with-toggle {
    position: relative;
  }
  .password-toggle {
    position:absolute; right:10px; top:50%;
    transform: translateY(-50%);
    border: none; background: transparent; padding: 6px;
    color:#6b7280; cursor:pointer;
  }
  .password-toggle:hover { color:#111827; }
  .auth-footer {
    padding: 0 28px 26px 28px;
    color:#6b7280; font-size:12px; text-align:center;
  }
  .auth-footer a { color:#6b7280; text-decoration: underline; }
  .top-pad {
    padding-top: clamp(16px, 4vh, 40px);
  }

  /* Small improvement for error list inside nice card */
  .alert { border-radius: 10px; }
</style>

<div class="auth-wrapper top-pad">
  <div class="auth-card">
    <div class="auth-body">

      {{-- Brand / Logo (opsional) --}}
      <div class="brand">
        {{-- Ganti logo sesuai aset Anda --}}
        <img src="{{ asset('assets/images/logo.png') }}" alt="Logo" onerror="this.style.display='none'">
        <span class="brand-name">Project Monitoring System</span>
      </div>

      <div class="auth-header">
        <h1 class="auth-title">SELAMAT DATANG</h1>
        <p class="auth-subtitle">Silakan masuk ke akun Anda</p>
      </div>

      {{-- Alerts --}}
      <div class="alerts-wrap">
        {{-- Pesan Error Session --}}
        @if(session('error'))
          <div class="alert alert-danger py-2 px-3 mb-2">{{ session('error') }}</div>
        @endif

        {{-- Validasi --}}
        @if($errors->any())
          <div class="alert alert-danger py-2 px-3">
            <ul class="mb-0 list-unstyled">
              @foreach($errors->all() as $error)
                <li>• {{ $error }}</li>
              @endforeach
            </ul>
          </div>
        @endif
      </div>

      {{-- Form Login --}}
      <form class="forms-sample" method="POST" action="{{ route('auth.login') }}" novalidate>
        @csrf

        <div class="mb-3">
          <label for="username" class="form-label">Username</label>
          <input
            type="text"
            class="form-control @error('username') is-invalid @enderror"
            id="username"
            name="username"
            placeholder="nama pengguna"
            value="{{ old('username') }}"
            required
            autofocus>
          @error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="mb-2 input-with-toggle">
          <label for="password" class="form-label">Password</label>
          <input
            type="password"
            class="form-control @error('password') is-invalid @enderror"
            id="password"
            name="password"
            placeholder="••••••••"
            required
            autocomplete="current-password">
          <button type="button" class="password-toggle" aria-label="Tampilkan password" onclick="togglePassword()">
            <i class="far fa-eye" id="eyeIcon"></i>
          </button>
          @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="helper-row">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="remember" name="remember" {{ old('remember') ? 'checked' : '' }}>
            <label class="form-check-label" for="remember">Ingat saya</label>
          </div>
          {{-- Ganti route jika punya halaman lupa password --}}
          {{-- <a class="link-muted" href="{{ route('password.request',[],false) }}">Lupa password?</a> --}}
        </div>

        <button type="submit" class="btn btn-primary">Login</button>

        {{-- Divider / Social placeholder (opsional) --}}
        <div class="divider"><span>atau</span></div>

        {{-- Tombol SSO/OAuth contoh (nonaktifkan jika tidak dipakai) --}}
        {{-- <a href="{{ route('oauth.google.redirect') }}" class="btn btn-outline-secondary w-100" style="border-radius:10px">
          <i class="fab fa-google me-2"></i> Masuk dengan Google
        </a> --}}
      </form>
    </div>

    <div class="auth-footer">
      <div>Dengan masuk, Anda menyetujui <a href="#">Ketentuan Layanan</a> dan <a href="#">Kebijakan Privasi</a>.</div>
    </div>
  </div>
</div>

{{-- Toggle password --}}
<script>
  function togglePassword(){
    const input = document.getElementById('password');
    const icon  = document.getElementById('eyeIcon');
    if(input.type === 'password'){
      input.type = 'text';
      icon.classList.remove('fa-eye'); icon.classList.add('fa-eye-slash');
    } else {
      input.type = 'password';
      icon.classList.remove('fa-eye-slash'); icon.classList.add('fa-eye');
    }
  }
</script>
@endsection

@extends('admin.layout')

@section('content')
<div class="login-page">
  <div class="login-card animate-slide-up">
    <div class="login-header">
      <div class="login-icon-box" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
          <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
        </svg>
      </div>
      <h1>Administrator sign in</h1>
      <p>Operations console for bookings, reservations &amp; payment reconciliation</p>
    </div>

    <form method="post" action="/login">
      @csrf

      <div class="form-group">
        <label for="email">Administrator Email</label>
        <div class="input-icon-wrapper">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect width="20" height="16" x="2" y="4" rx="2"/>
            <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
          </svg>
          <input
            id="email"
            name="email"
            type="email"
            required
            autocomplete="username"
            placeholder="admin@example.test"
            value="{{ old('email', 'admin@example.test') }}"
          >
        </div>
      </div>

      <div class="form-group">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
          <label for="password" style="margin-bottom: 0;">Password</label>
        </div>
        <div class="input-icon-wrapper">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 2l-2 2m-1-1l-3 3m0 0l-2 2m2-2l3-3m-3 3l-6 6a5 5 0 0 1-7-7l6-6m4 4l3-3"/>
          </svg>
          <input
            id="password"
            name="password"
            type="password"
            required
            autocomplete="current-password"
            placeholder="••••••••"
            value="password"
          >
        </div>
      </div>

      <button type="submit" class="btn btn-primary btn-lg" style="width: 100%; margin-top: 8px;">
        Sign in to Console
        <svg class="icon-arrow-right" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="5" y1="12" x2="19" y2="12"/>
          <polyline points="12 5 19 12 12 19"/>
        </svg>
      </button>
    </form>

    <div class="demo-credentials">
      <div class="demo-credentials-header">
        <span>Evaluation Credentials</span>
        <button type="button" onclick="fillDemo()" class="btn btn-secondary btn-sm" style="padding: 3px 10px; font-size: 11px;">
          Fill Demo
        </button>
      </div>
      <div>Email: <code>admin@example.test</code></div>
      <div style="margin-top: 3px;">Password: <code>password</code></div>
    </div>
  </div>
</div>

<script>
  function fillDemo() {
    const email = document.getElementById('email');
    const pwd = document.getElementById('password');
    email.value = 'admin@example.test';
    pwd.value = 'password';
    email.style.transition = 'box-shadow 0.3s ease';
    email.style.boxShadow = '0 0 0 4px rgba(16, 185, 129, 0.3)';
    pwd.style.boxShadow = '0 0 0 4px rgba(16, 185, 129, 0.3)';
    setTimeout(() => {
      email.style.boxShadow = '';
      pwd.style.boxShadow = '';
    }, 600);
  }
</script>
@endsection

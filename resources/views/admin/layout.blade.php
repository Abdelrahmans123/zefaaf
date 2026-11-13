<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Zefaaf · Operations & Bookings Console</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/css/admin.css">
  <style>
    /* Inline baseline fallback */
    :root { font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif; }
  </style>
</head>
<body>
  <header class="app-header">
    <div class="header-container">
      <a href="/admin/transactions" class="brand-wrapper">
        <div class="brand-emblem" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
          </svg>
        </div>
        <div class="brand-details">
          <span class="brand-name">
            Zefaaf
            <span class="brand-badge">Ops</span>
          </span>
          <span class="brand-sub">Bookings &amp; Financial Recovery</span>
        </div>
      </a>

      @auth
      <nav class="header-nav" aria-label="Operations Navigation">
        <a href="/admin/transactions" class="nav-link {{ request()->is('admin/transactions') && !request()->is('admin/transactions/create') ? 'active' : '' }}">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect width="20" height="14" x="2" y="5" rx="2"/>
            <line x1="2" x2="22" y1="10" y2="10"/>
          </svg>
          Transactions
        </a>
        <a href="/admin/transactions/create" class="nav-link {{ request()->is('admin/transactions/create') ? 'active' : '' }}">
          <svg class="icon-plus" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"/>
            <line x1="5" y1="12" x2="19" y2="12"/>
          </svg>
          New Booking
        </a>
      </nav>

      <div class="header-user">
        <div class="user-profile">
          <div class="user-avatar" title="{{ Auth::user()->name ?? 'Admin' }}">
            {{ strtoupper(substr(Auth::user()->name ?? 'AD', 0, 2)) }}
          </div>
          <div class="user-info">
            <span class="user-name">{{ Auth::user()->name ?? 'Administrator' }}</span>
            <span class="user-role">Operations Lead</span>
          </div>
        </div>

        <form method="post" action="/logout" style="margin: 0;">
          @csrf
          <button type="submit" class="btn btn-outline-light" title="Sign out of console">
            <svg class="icon-arrow-right" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
              <polyline points="16 17 21 12 16 7"/>
              <line x1="21" x2="9" y1="12" y2="12"/>
            </svg>
            Sign out
          </button>
        </form>
      </div>
      @endauth
    </div>
  </header>

  <main class="app-main">
    @if(session('message'))
      <div class="notice notice-success" role="alert">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
          <polyline points="22 4 12 14.01 9 11.01"/>
        </svg>
        <div style="flex: 1;">{{ session('message') }}</div>
      </div>
    @endif

    @if(isset($errors) && $errors->any())
      <div class="notice errors notice-error" role="alert">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10"/>
          <line x1="12" y1="8" x2="12" y2="12"/>
          <line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        <div style="flex: 1;">{{ $errors->first() }}</div>
      </div>
    @endif

    @yield('content')
  </main>

  <footer class="app-footer">
    <div class="footer-container">
      <div>
        <strong>Zefaaf</strong> &middot; Transaction Integrity &amp; Payment Recovery Engine
      </div>
      <div class="footer-status">
        <span class="footer-status-dot"></span>
        <span>Reconciler Service Active</span>
      </div>
    </div>
  </footer>

  <script>
    // Smooth ripple effect on button clicks
    document.addEventListener('click', function(e) {
      const btn = e.target.closest('.btn, button');
      if (!btn || btn.classList.contains('copy-btn')) return;

      const rect = btn.getBoundingClientRect();
      const circle = document.createElement('span');
      const diameter = Math.max(rect.width, rect.height);
      const radius = diameter / 2;

      circle.style.width = circle.style.height = `${diameter}px`;
      circle.style.left = `${e.clientX - rect.left - radius}px`;
      circle.style.top = `${e.clientY - rect.top - radius}px`;
      circle.classList.add('btn-ripple');

      const existing = btn.querySelector('.btn-ripple');
      if (existing) existing.remove();

      btn.appendChild(circle);
      setTimeout(() => circle.remove(), 600);
    });

    // Copy-to-clipboard functionality with animated checkmark badge
    function copyText(text, btn) {
      if (!navigator.clipboard) return;
      navigator.clipboard.writeText(text).then(function() {
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<span class="copied-badge">&check; Copied!</span>';
        setTimeout(function() {
          btn.innerHTML = originalHtml;
        }, 1600);
      });
    }
  </script>
</body>
</html>

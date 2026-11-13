@extends('admin.layout')

@section('content')
<div class="page-header animate-slide-up">
  <div class="page-title-group">
    <h1>Purchase transactions</h1>
    <p>Reservations, synchronized payments, and background financial recovery in one place.</p>
  </div>
  <a class="btn btn-primary btn-lg" href="/admin/transactions/create">
    <svg class="icon-plus" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <line x1="12" y1="5" x2="12" y2="19"/>
      <line x1="5" y1="12" x2="19" y2="12"/>
    </svg>
    Create purchase
  </a>
</div>

{{-- Metric Counters --}}
@php
  $totalOnPage = $transactions->count();
  $confirmedCount = $transactions->where('status', 'confirmed')->count();
  $pendingCount = $transactions->where('status', 'pending')->count();
  $retryCount = $transactions->where('reconcile_failures', '>', 0)->count();
@endphp

<div class="stats-grid">
  <div class="stat-card animate-slide-up animate-delay-1">
    <div class="stat-info">
      <span class="stat-label">Total Transactions</span>
      <span class="stat-value">{{ $transactions->total() }}</span>
    </div>
    <div class="stat-icon slate">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <rect width="20" height="14" x="2" y="5" rx="2"/>
        <line x1="2" x2="22" y1="10" y2="10"/>
      </svg>
    </div>
  </div>

  <div class="stat-card animate-slide-up animate-delay-2">
    <div class="stat-info">
      <span class="stat-label">Confirmed (Page)</span>
      <span class="stat-value" style="color: var(--brand-700);">{{ $confirmedCount }}</span>
    </div>
    <div class="stat-icon emerald">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
        <polyline points="22 4 12 14.01 9 11.01"/>
      </svg>
    </div>
  </div>

  <div class="stat-card animate-slide-up animate-delay-3">
    <div class="stat-info">
      <span class="stat-label">Pending / In-Flight</span>
      <span class="stat-value" style="color: var(--gold-600);">{{ $pendingCount }}</span>
    </div>
    <div class="stat-icon amber">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="10"/>
        <polyline points="12 6 12 12 16 14"/>
      </svg>
    </div>
  </div>

  <div class="stat-card animate-slide-up animate-delay-4">
    <div class="stat-info">
      <span class="stat-label">Awaiting Recovery</span>
      <span class="stat-value" style="color: #dc2626;">{{ $retryCount }}</span>
    </div>
    <div class="stat-icon rose">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M21.5 2v6h-6M2.5 22v-6h6M2 11.5a10 10 0 0 1 18.8-4.3M22 12.5a10 10 0 0 1-18.8 4.2"/>
      </svg>
    </div>
  </div>
</div>

{{-- Filter Toolbar --}}
<form method="get" action="/admin/transactions" class="filter-card animate-slide-up animate-delay-3">
  <div class="filter-group">
    <label for="status">Filter by Status:</label>
    <select name="status" id="status" class="filter-select">
      <option value="">All statuses</option>
      @foreach(['pending','confirmed','failed','cancelled','expired'] as $status)
        <option value="{{ $status }}" @selected(request('status') === $status)>
          {{ ucfirst($status) }}
        </option>
      @endforeach
    </select>
    <button type="submit" class="btn btn-secondary btn-sm">
      <svg class="icon-arrow-right" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
      </svg>
      Filter
    </button>
  </div>

  @if(request('status'))
    <div style="display: flex; align-items: center; gap: 8px;">
      <span style="font-size: 13px; color: var(--slate-500);">Filtered by: <strong>{{ ucfirst(request('status')) }}</strong></span>
      <a href="/admin/transactions" class="btn btn-secondary btn-sm" style="font-size: 11.5px; padding: 4px 8px;">
        ✕ Clear filter
      </a>
    </div>
  @endif
</form>

{{-- Data Table --}}
<div class="table-wrapper animate-slide-up animate-delay-4">
  <div class="table-responsive">
    <table>
      <thead>
        <tr>
          <th>Service / Transaction</th>
          <th>Customer</th>
          <th>Amount</th>
          <th>Status</th>
          <th>Created (UTC)</th>
          <th style="text-align: right;">Action</th>
        </tr>
      </thead>
      <tbody>
        @forelse($transactions as $b)
          <tr>
            <td>
              <a href="/admin/transactions/{{ $b->id }}" class="table-title">
                {{ $b->service->name }}
              </a>
              <div style="display: flex; align-items: center; gap: 6px; margin-top: 4px;">
                <span class="badge-mono" title="{{ $b->id }}">
                  {{ substr($b->id, 0, 8) }}&hellip;{{ substr($b->id, -4) }}
                </span>
                <button type="button" class="copy-btn" onclick="copyText('{{ $b->id }}', this)" title="Copy transaction UUID">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect width="14" height="14" x="8" y="8" rx="2" ry="2"/>
                    <path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/>
                  </svg>
                </button>
              </div>
            </td>

            <td>
              <div class="customer-cell">
                <div class="customer-avatar">
                  {{ strtoupper(substr($b->user->name ?? 'CU', 0, 2)) }}
                </div>
                <div class="customer-details">
                  <span class="customer-name">{{ $b->user->name }}</span>
                  <span class="customer-email">{{ $b->user->email }}</span>
                </div>
              </div>
            </td>

            <td>
              <div class="amount-display">
                {{ number_format($b->amount_minor / 100, 2) }}
                <span class="currency-tag">{{ $b->currency }}</span>
              </div>
            </td>

            <td>
              <span class="badge {{ $b->status }}">{{ $b->status }}</span>
              @if($b->reconcile_failures)
                <div>
                  <span class="badge-retry">
                    <svg class="icon-spin-hover" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M21.5 2v6h-6M2.5 22v-6h6M2 11.5a10 10 0 0 1 18.8-4.3M22 12.5a10 10 0 0 1-18.8 4.2"/>
                    </svg>
                    Retry scheduled ({{ $b->reconcile_failures }})
                  </span>
                </div>
              @endif
            </td>

            <td>
              <div style="font-weight: 500; font-size: 13px; color: var(--slate-800);">
                {{ $b->created_at }}
              </div>
            </td>

            <td style="text-align: right;">
              <a href="/admin/transactions/{{ $b->id }}" class="btn btn-secondary btn-sm">
                Details
                <svg class="icon-arrow-right" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <line x1="5" y1="12" x2="19" y2="12"/>
                  <polyline points="12 5 19 12 12 19"/>
                </svg>
              </a>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="6">
              <div class="empty-state">
                <div class="empty-state-icon">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"/>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                  </svg>
                </div>
                <h3>No transactions match this filter.</h3>
                <p>Try selecting a different status filter or create a new reservation for a customer.</p>
                @if(request('status'))
                  <a href="/admin/transactions" class="btn btn-secondary btn-sm">
                    Reset Filter
                  </a>
                @else
                  <a href="/admin/transactions/create" class="btn btn-primary btn-sm">
                    <svg class="icon-plus" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <line x1="12" y1="5" x2="12" y2="19"/>
                      <line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    Create First Purchase
                  </a>
                @endif
              </div>
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<div style="margin-top: 20px;">
  {{ $transactions->links() }}
</div>
@endsection

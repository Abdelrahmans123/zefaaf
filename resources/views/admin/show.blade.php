@extends('admin.layout')

@section('content')
<a href="/admin/transactions" class="breadcrumb-nav animate-slide-up">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
    <line x1="19" y1="12" x2="5" y2="12"/>
    <polyline points="12 19 5 12 12 5"/>
  </svg>
  Back to Transactions
</a>

{{-- Header Hero --}}
<div class="page-header animate-slide-up animate-delay-1" style="margin-bottom: 24px;">
  <div class="page-title-group">
    <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
      <h1>{{ $booking->service->name }}</h1>
      <span class="badge {{ $booking->status }}" style="font-size: 13px; padding: 5px 12px;">{{ $booking->status }}</span>
    </div>
    <div style="display: flex; align-items: center; gap: 8px; margin-top: 6px; flex-wrap: wrap;">
      <span class="badge-mono" style="font-size: 12px;">
        ID: {{ $booking->id }}
      </span>
      <button type="button" class="copy-btn" onclick="copyText('{{ $booking->id }}', this)" title="Copy transaction UUID">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <rect width="14" height="14" x="8" y="8" rx="2" ry="2"/>
          <path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/>
        </svg>
      </button>
      <span style="font-size: 13px; color: var(--slate-400);">&middot;</span>
      <span style="font-size: 13px; color: var(--slate-500);">Created {{ $booking->created_at }}</span>
    </div>
  </div>

  <div style="display: flex; align-items: center; gap: 10px;">
    <form method="post" action="/admin/transactions/{{ $booking->id }}/reconcile" style="margin: 0;">
      @csrf
      <button type="submit" class="btn btn-secondary">
        <svg class="icon-spin-hover" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M21.5 2v6h-6M2.5 22v-6h6M2 11.5a10 10 0 0 1 18.8-4.3M22 12.5a10 10 0 0 1-18.8 4.2"/>
        </svg>
        Synchronize payment
      </button>
    </form>
  </div>
</div>

<div class="show-layout">
  {{-- Main Left Column --}}
  <div style="display: flex; flex-direction: column; gap: 24px;">
    {{-- Key Highlight Stats --}}
    <div class="card animate-slide-up animate-delay-2" style="margin-bottom: 0;">
      <div class="card-header">
        <h2>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect width="20" height="14" x="2" y="5" rx="2"/>
            <line x1="2" x2="22" y1="10" y2="10"/>
          </svg>
          Transaction Specifications
        </h2>
      </div>

      <div class="card-body">
        <div class="details-grid">
          <div class="detail-item">
            <span class="detail-label">Transaction Reference</span>
            <div style="display: flex; align-items: center; gap: 6px;">
              <span class="detail-value mono">{{ $booking->id }}</span>
              <button type="button" class="copy-btn" onclick="copyText('{{ $booking->id }}', this)" title="Copy UUID">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <rect width="14" height="14" x="8" y="8" rx="2" ry="2"/>
                  <path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/>
                </svg>
              </button>
            </div>
          </div>

          <div class="detail-item">
            <span class="detail-label">Customer Account</span>
            <span class="detail-value">
              {{ $booking->user->name }} &middot; <span style="font-weight: 400; color: var(--slate-500);">{{ $booking->user->email }}</span>
            </span>
          </div>

          <div class="detail-item">
            <span class="detail-label">Payable Amount</span>
            <div class="detail-value" style="font-size: 18px; color: var(--brand-800);">
              {{ number_format($booking->amount_minor / 100, 2) }}
              <span style="font-size: 13px; font-weight: 600; color: var(--slate-500);">{{ $booking->currency }}</span>
            </div>
          </div>

          <div class="detail-item">
            <span class="detail-label">Created (UTC)</span>
            <span class="detail-value">{{ $booking->created_at }}</span>
          </div>

          <div class="detail-item">
            <span class="detail-label">Payment Deadline</span>
            <span class="detail-value">{{ $booking->expires_at }}</span>
          </div>

          <div class="detail-item">
            <span class="detail-label">Confirmed At</span>
            <span class="detail-value">
              @if($booking->confirmed_at)
                <span style="color: var(--brand-700); font-weight: 700;">&check; {{ $booking->confirmed_at }}</span>
              @else
                <span style="color: var(--slate-400);">&mdash;</span>
              @endif
            </span>
          </div>

          <div class="detail-item">
            <span class="detail-label">Cancellation Requested</span>
            <span class="detail-value">
              @if($booking->cancel_requested)
                <span class="badge pending" style="font-size: 11px;">Yes &middot; Pending</span>
              @else
                <span style="color: var(--slate-500);">No</span>
              @endif
            </span>
          </div>

          <div class="detail-item">
            <span class="detail-label">Recovery Attempts</span>
            <span class="detail-value">
              {{ $booking->reconcile_failures }}
              @if($booking->reconcile_failures > 0)
                <span class="badge-retry" style="margin-left: 6px;">Automatic Retry</span>
              @endif
            </span>
          </div>

          <div class="detail-item" style="grid-column: 1 / -1;">
            <span class="detail-label">Last Recovery Error</span>
            <div class="detail-value">
              @if($booking->last_error)
                <div style="background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 10px 14px; border-radius: var(--radius-sm); font-size: 13px; font-family: var(--font-mono); margin-top: 4px;">
                  {{ $booking->last_error }}
                </div>
              @else
                <span style="color: var(--slate-400);">&mdash; None</span>
              @endif
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- Payment Notifications Audit Trail --}}
    <div class="card animate-slide-up animate-delay-3" style="margin-bottom: 0;">
      <div class="card-header">
        <h2>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
            <polyline points="14 2 14 8 20 8"/>
            <line x1="16" y1="13" x2="8" y2="13"/>
            <line x1="16" y1="17" x2="8" y2="17"/>
            <polyline points="10 9 9 9 8 9"/>
          </svg>
          Payment notifications
        </h2>
        <span class="badge-mono">{{ $events->count() }} {{ Str::plural('event', $events->count()) }}</span>
      </div>

      <div class="table-responsive">
        <table>
          <thead>
            <tr>
              <th>Event</th>
              <th>Reported status</th>
              <th>Received (UTC)</th>
              <th>Processed (UTC)</th>
            </tr>
          </thead>
          <tbody>
            @forelse($events as $event)
              <tr>
                <td>
                  <span class="badge-mono">{{ $event->id }}</span>
                </td>
                <td>
                  <span class="badge {{ $event->reported_status }}">{{ $event->reported_status }}</span>
                </td>
                <td>
                  <span style="font-size: 13px; color: var(--slate-800);">{{ $event->received_at }}</span>
                </td>
                <td>
                  @if($event->processed_at)
                    <span style="font-size: 13px; color: var(--brand-700); font-weight: 500;">{{ $event->processed_at }}</span>
                  @else
                    <span class="badge-retry" style="color: #b45309; background: #fef3c7;">Awaiting recovery</span>
                  @endif
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="4">
                  <div style="text-align: center; padding: 32px 16px; color: var(--slate-400); font-size: 13.5px;">
                    No notifications received.
                  </div>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  {{-- Right Operations Sidebar --}}
  <div class="actions-panel">
    {{-- Admin Notes & Cancellation Form --}}
    <div class="card animate-slide-up animate-delay-2" style="margin-bottom: 0;">
      <div class="card-header">
        <h3>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
          </svg>
          Operations Management
        </h3>
      </div>

      <div class="card-body">
        <form method="post" action="/admin/transactions/{{ $booking->id }}">
          @csrf
          @method('PATCH')

          <div class="form-group">
            <label for="note">Admin Note</label>
            <textarea
              id="note"
              name="admin_note"
              maxlength="2000"
              rows="4"
              placeholder="Add audit or operational remarks for this booking..."
            >{{ $booking->admin_note }}</textarea>
            <div class="form-hint">Internal audit log only (max 2,000 characters).</div>
          </div>

          @if($booking->status === 'pending')
            <div class="form-group" style="padding: 12px; background: #fffbeb; border: 1px solid #fde68a; border-radius: var(--radius-sm);">
              <label class="checkbox-label" style="margin-bottom: 0;">
                <input type="checkbox" name="cancel" value="1">
                <span>Request cancellation</span>
              </label>
              <div style="font-size: 11.5px; color: #92400e; margin-top: 6px; margin-left: 27px;">
                Flags this pending booking for cancellation during the next reconciler cycle.
              </div>
            </div>
          @endif

          <button type="submit" class="btn btn-primary" style="width: 100%;">
            <svg class="icon-save" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
              <polyline points="17 21 17 13 7 13 7 21"/>
              <polyline points="7 3 7 8 15 8"/>
            </svg>
            Save changes
          </button>
        </form>
      </div>
    </div>

    {{-- Synchronization Action --}}
    <div class="card animate-slide-up animate-delay-3" style="margin-bottom: 0;">
      <div class="card-header">
        <h3>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21.5 2v6h-6M2.5 22v-6h6M2 11.5a10 10 0 0 1 18.8-4.3M22 12.5a10 10 0 0 1-18.8 4.2"/>
          </svg>
          Gateway Synchronization
        </h3>
      </div>
      <div class="card-body">
        <p style="font-size: 13px; color: var(--slate-600); line-height: 1.5; margin-bottom: 16px;">
          Polls authoritative payment provider state to confirm funds or release spot if expired.
        </p>
        <form method="post" action="/admin/transactions/{{ $booking->id }}/reconcile">
          @csrf
          <button type="submit" class="btn btn-secondary" style="width: 100%;">
            <svg class="icon-spin-hover" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M21.5 2v6h-6M2.5 22v-6h6M2 11.5a10 10 0 0 1 18.8-4.3M22 12.5a10 10 0 0 1-18.8 4.2"/>
            </svg>
            Synchronize payment
          </button>
        </form>
      </div>
    </div>

    {{-- Archival / Danger Zone --}}
    @if($booking->status !== 'pending')
      <div class="card animate-slide-up animate-delay-4" style="border-color: #fecaca; margin-bottom: 0;">
        <div class="card-header" style="background: #fef2f2; border-color: #fee2e2;">
          <h3 style="color: #991b1b;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="3 6 5 6 21 6"/>
              <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
            </svg>
            Archive Transaction
          </h3>
        </div>
        <div class="card-body">
          <p style="font-size: 12.5px; color: var(--slate-600); line-height: 1.5; margin-bottom: 16px;">
            Archiving removes this transaction from active listings while preserving immutable audit logs for compliance.
          </p>
          <form method="post" action="/admin/transactions/{{ $booking->id }}" onsubmit="return confirm('Are you sure you want to archive this transaction? Financial logs will be retained.');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-subtle-danger" style="width: 100%;">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="3 6 5 6 21 6"/>
                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
              </svg>
              Archive transaction
            </button>
          </form>
        </div>
      </div>
    @else
      <div class="animate-slide-up animate-delay-4" style="padding: 14px 16px; background: #f8fafc; border: 1px dashed var(--slate-300); border-radius: var(--radius-md); font-size: 12px; color: var(--slate-500); line-height: 1.5;">
        <strong style="color: var(--slate-700);">Archival Locked:</strong> Pending transactions cannot be archived until their payment outcome is definitively resolved.
      </div>
    @endif
  </div>
</div>
@endsection

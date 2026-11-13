@extends('admin.layout')

@section('content')
<div style="max-width: 860px; margin: 0 auto;">
  <a href="/admin/transactions" class="breadcrumb-nav animate-slide-up">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <line x1="19" y1="12" x2="5" y2="12"/>
      <polyline points="12 19 5 12 12 5"/>
    </svg>
    Back to Transactions
  </a>

  <div class="page-header animate-slide-up animate-delay-1" style="margin-bottom: 24px;">
    <div class="page-title-group">
      <h1>Create purchase</h1>
      <p>Reserve one spot for a customer. Payment is required to confirm the purchase.</p>
    </div>
  </div>

  <div style="display: grid; grid-template-columns: 1.8fr 1.2fr; gap: 24px; align-items: start;">
    {{-- Form Card --}}
    <div class="card animate-slide-up animate-delay-2" style="margin-bottom: 0;">
      <div class="card-header">
        <h2>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
            <circle cx="8.5" cy="7" r="4"/>
            <line x1="20" y1="8" x2="20" y2="14"/>
            <line x1="23" y1="11" x2="17" y2="11"/>
          </svg>
          Reservation Details
        </h2>
      </div>

      <div class="card-body">
        <form method="post" action="/admin/transactions">
          @csrf
          <input type="hidden" name="idempotency_key" value="{{ $key }}">

          <div class="form-group">
            <label for="user">Customer Account</label>
            <select id="user" name="user_id" required>
              <option value="" disabled selected>Select a registered customer...</option>
              @foreach($users as $u)
                <option value="{{ $u->id }}">{{ $u->name }} &mdash; {{ $u->email }}</option>
              @endforeach
            </select>
            <div class="form-hint">Choose which registered customer will hold this reservation.</div>
          </div>

          <div class="form-group">
            <label for="service">Select Service</label>
            <select id="service" name="service_id" required>
              <option value="" disabled selected>Select a booking service...</option>
              @foreach($services as $s)
                <option value="{{ $s->id }}">
                  {{ $s->name }} &mdash; {{ number_format($s->price_minor / 100, 2) }} {{ $s->currency }}
                  @if($s->capacity !== null)
                    ({{ $s->capacity }} spots max)
                  @else
                    (Unlimited capacity)
                  @endif
                </option>
              @endforeach
            </select>
            <div class="form-hint">Service capacity and booking window are enforced atomically.</div>
          </div>

          <div class="form-group" style="padding: 14px 16px; background: #f8fafc; border: 1px solid var(--slate-200); border-radius: var(--radius-sm);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
              <span style="font-size: 11.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--slate-500);">Idempotency Protection</span>
              <span class="badge-mono" style="font-size: 10.5px;">Auto-Generated</span>
            </div>
            <code style="font-family: var(--font-mono); font-size: 12px; color: var(--slate-700); word-break: break-all;">
              {{ $key }}
            </code>
            <div style="font-size: 11.5px; color: var(--slate-400); margin-top: 4px;">
              Guarantees zero duplicate bookings even under network retries or repeated submissions.
            </div>
          </div>

          <div style="display: flex; align-items: center; gap: 12px; margin-top: 24px;">
            <button type="submit" class="btn btn-primary btn-lg">
              <svg class="icon-arrow-right" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                <polyline points="22 4 12 14.01 9 11.01"/>
              </svg>
              Reserve spot
            </button>
            <a href="/admin/transactions" class="btn btn-secondary btn-lg">
              Cancel
            </a>
          </div>
        </form>
      </div>
    </div>

    {{-- Info Card --}}
    <div style="display: flex; flex-direction: column; gap: 16px;">
      <div class="card animate-slide-up animate-delay-3" style="margin-bottom: 0;">
        <div class="card-header">
          <h3>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10"/>
              <line x1="12" y1="16" x2="12" y2="12"/>
              <line x1="12" y1="8" x2="12.01" y2="8"/>
            </svg>
            How Reservations Work
          </h3>
        </div>
        <div class="card-body" style="font-size: 13px; line-height: 1.6; color: var(--slate-600);">
          <div style="display: flex; gap: 12px; margin-bottom: 14px;">
            <div style="width: 24px; height: 24px; border-radius: 50%; background: var(--brand-100); color: var(--brand-700); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 11px; flex-shrink: 0;">1</div>
            <div>
              <strong style="color: var(--slate-800);">Temporary Hold:</strong> Spot is reserved for 15 minutes while payment is pending.
            </div>
          </div>
          <div style="display: flex; gap: 12px; margin-bottom: 14px;">
            <div style="width: 24px; height: 24px; border-radius: 50%; background: var(--gold-100); color: var(--gold-700); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 11px; flex-shrink: 0;">2</div>
            <div>
              <strong style="color: var(--slate-800);">Automatic Expiry:</strong> If payment is unfulfilled, the reconciler releases the spot back to inventory.
            </div>
          </div>
          <div style="display: flex; gap: 12px;">
            <div style="width: 24px; height: 24px; border-radius: 50%; background: #dbeafe; color: #1e40af; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 11px; flex-shrink: 0;">3</div>
            <div>
              <strong style="color: var(--slate-800);">Confirmation:</strong> Webhook notification or synchronization locks spot consumption.
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

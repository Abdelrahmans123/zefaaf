<?php

namespace App\Http\Controllers;

use App\Actions\CancelBooking;
use App\Actions\ReserveBooking;
use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use App\Services\ReconciliationRunner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminController
{
    public function login(Request $r)
    {
        $credentials = $r->validate(['email' => 'required|email', 'password' => 'required|string']);
        if (! Auth::attempt([...$credentials, 'is_admin' => true])) {
            return back()->withErrors(['email' => 'Invalid administrator credentials.']);
        }
        $r->session()->regenerate();

        return redirect('/admin/transactions');
    }

    public function logout(Request $r)
    {
        Auth::logout();
        $r->session()->invalidate();
        $r->session()->regenerateToken();

        return redirect('/login');
    }

    public function index(Request $r)
    {
        $r->validate(['status' => 'nullable|in:pending,confirmed,failed,cancelled,expired']);
        $transactions = Booking::with(['user', 'service'])->whereNull('archived_at')->when($r->status, fn ($q) => $q->where('status', $r->status))->latest()->paginate(20)->withQueryString();

        return view('admin.index', compact('transactions'));
    }

    public function create()
    {
        return view('admin.create', ['users' => User::orderBy('name')->get(), 'services' => Service::orderBy('name')->get(), 'key' => (string) Str::uuid()]);
    }

    public function store(Request $r, ReserveBooking $service)
    {
        $data = $r->validate(['user_id' => 'required|exists:users,id', 'service_id' => 'required|exists:services,id', 'idempotency_key' => 'required|uuid']);
        $booking = $service->execute(User::findOrFail($data['user_id']), Service::findOrFail($data['service_id']), $data['idempotency_key']);

        return redirect('/admin/transactions/'.$booking->id);
    }

    public function show(Booking $booking)
    {
        return view('admin.show', ['booking' => $booking->load(['service', 'user']), 'events' => DB::table('payment_events')->where('booking_id', $booking->id)->orderByDesc('received_at')->get()]);
    }

    public function update(Request $r, Booking $booking, CancelBooking $service)
    {
        $data = $r->validate(['admin_note' => 'nullable|string|max:2000', 'cancel' => 'nullable|boolean']);
        $booking->update(['admin_note' => $data['admin_note'] ?? null]);
        if ($r->boolean('cancel')) {
            $service->execute($booking);
        }

        return back()->with('message', 'Transaction updated. Cancellation requests are processed by the reconciler.');
    }

    public function destroy(Booking $booking)
    {
        abort_if($booking->status === 'pending', 409, 'Resolve the payment before archiving this transaction.');
        $booking->update(['archived_at' => now()]);

        return redirect('/admin/transactions')->with('message', 'Transaction archived; its payment audit history is retained.');
    }

    public function reconcile(Booking $booking, ReconciliationRunner $reconciler)
    {
        $ok = $reconciler->run($booking);

        return back()->with('message', $ok ? 'Payment status synchronized.' : 'Provider unavailable; an automatic retry is scheduled.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Payment;

/**
 * Live Payments — Accounts-department-only. Shows payments as they come in
 * (cash/bank/cheque entries recorded by Accounts, and online submissions
 * made by students from their dashboard) so staff can see and verify
 * activity without digging through the ledger. The feed endpoint is polled
 * from the page every few seconds for a near-real-time view.
 */
class LivePaymentController extends Controller
{
    public function index()
    {
        $payments = Payment::with('voucher.student')
            ->latest('id')
            ->take(50)
            ->get();

        $pendingVerification = Payment::with('voucher.student')
            ->where('status', 'pending')
            ->latest('id')
            ->get();

        return view('accounts.live-payments', compact('payments', 'pendingVerification'));
    }

    /** JSON feed polled by the Live Payments page for near-real-time updates. */
    public function feed()
    {
        $payments = Payment::with('voucher.student')
            ->latest('id')
            ->take(50)
            ->get()
            ->map(fn ($p) => [
                'id'          => $p->id,
                'student'     => $p->voucher?->student?->name,
                'voucher_no'  => $p->voucher?->voucher_no,
                'amount'      => (float) $p->amount,
                'method'      => $p->method,
                'status'      => $p->status ?? 'verified',
                'paid_at'     => optional($p->paid_at)->diffForHumans(),
                'received_by' => $p->received_by,
            ]);

        return response()->json(['payments' => $payments]);
    }
}

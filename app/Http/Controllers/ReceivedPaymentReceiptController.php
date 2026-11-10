<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Setting;
use App\Models\Student;
use App\Services\StudentReceivedPaymentHistory;
use Illuminate\Http\Response;

/** Read-only, student-scoped reprint of exactly one received collection. */
class ReceivedPaymentReceiptController extends Controller
{
    public function show(Student $student, Payment $payment): Response
    {
        abort_unless($payment->voucher && (int) $payment->voucher->student_id === (int) $student->id,
            404);
        abort_unless($payment->isVerified() && $payment->method !== 'balance_credit', 404);

        $allocations = Payment::query()
            ->with('voucher')
            ->whereHas('voucher', fn ($query) => $query->where('student_id', $student->id))
            ->when(
                $payment->receipt_batch_id,
                fn ($query) => $query->where('receipt_batch_id', $payment->receipt_batch_id),
                fn ($query) => $query->whereKey($payment->id)
            )
            ->where(fn ($query) => $query->where('status', 'verified')->orWhereNull('status'))
            ->where('method', '!=', 'balance_credit')
            ->orderBy('id')
            ->get();

        // Protect against a stale or reversed payment ID, and never combine
        // rows belonging to a different student even if identifiers collide.
        abort_unless($allocations->contains('id', $payment->id), 404);
        $receipt = StudentReceivedPaymentHistory::forStudent($allocations, collect())->first();
        abort_unless($receipt !== null, 404);

        $student->loadMissing('program', 'schoolClass', 'academicYear');
        $institutionName = Setting::get('institution_name', 'Institute');

        return response()->view('payments.received-payment-receipt', compact('student', 'receipt', 'institutionName'))
            ->header('Cache-Control', 'private, no-store, max-age=0')
            ->header('X-Content-Type-Options', 'nosniff');
    }
}

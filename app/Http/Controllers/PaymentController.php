<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Student;
use App\Models\Voucher;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Services\StudentBalanceService;

class PaymentController extends Controller
{
    /**
     * Accounts directly records a payment it has received (cash/bank
     * transfer/cheque, or an online payment it is keying in itself). The
     * amount is bounded by the voucher's remaining balance — it can never
     * exceed what the defined fee structure/installment plan says is owed,
     * so there is no way to manually inflate or invent a challan amount
     * here; Accounts is only ever recording what came in against it.
     *
     * A single voucher number can bundle more than one fee head (e.g.
     * Tuition Fee + Examination Fee on the same installment) as separate
     * rows behind the scenes. Accounts only ever sees ONE voucher number
     * and ONE combined balance, so a payment here is applied as a
     * waterfall across every fee-head row in that voucher group — oldest
     * row first — until the amount is exhausted, exactly like a payment
     * taken against a student is applied across vouchers. Each row keeps
     * its own Payment record so the itemised history stays intact.
     */
    public function store(Request $request, Voucher $voucher)
    {
        return DB::transaction(function () use ($request, $voucher) {
            Student::whereKey($voucher->student_id)->lockForUpdate()->firstOrFail()->assertActiveEnrollment();
            $voucher = Voucher::whereKey($voucher->id)->lockForUpdate()->firstOrFail();
            // Once the selected installment reaches its due date, any unpaid
            // amount from earlier installments in the same fee plan is collected
            // with it. The old rows remain the accounting source of truth; this
            // payment waterfall settles those rows first and then the selected
            // installment, so carry-forward never duplicates the student's debt.
            $group = $voucher->paymentRowsWithCarryForward()
                ->filter(fn (Voucher $v) => $v->balance() > 0)
                ->values();
            // For a split challan, only its assigned arrears share may be
            // collected, even when earlier vouchers have a larger balance.
            $groupBalance = round($voucher->payableBalanceWithCarryForward(), 0);
            $carryForward = round($voucher->carryForwardBalance(), 0);

            $validated = $request->validate([
                'amount'       => 'required|integer|min:1|max:' . max(1, (int) $groupBalance),
                'method'       => 'required|in:cash,bank_transfer,cheque,online',
                'paid_at'      => 'nullable|date',
                'reference_no' => 'nullable|string|max:100',
                'notes'        => 'nullable|string',
                // Optional payment proof (screenshot/photo) for online, bank
                // transfer, or cheque payments — not required for cash.
                'proof'        => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
            ]);

            abort_if($groupBalance <= 0, 400, 'This voucher has no remaining balance to collect.');

            $proofPath = null;
            if ($request->hasFile('proof')) {
                $proofPath = $request->file('proof')->store('payments/proofs', 'public');
            }

            // One submitted collection may be allocated to several vouchers.
            $receiptBatchId = (string) Str::uuid();
            $remaining = (int) $validated['amount'];
            $firstPayment = null;
            $firstVoucher = null;
            $isFirstRow = true;
            $touched = [];

            $priorRemaining = (float) $voucher->carryForwardBalance();
            foreach ($group as $row) {
                if ($remaining <= 0) break;

                $isCurrent = $row->voucher_no === $voucher->voucher_no;
                $available = $isCurrent ? $row->balance() : min($row->balance(), $priorRemaining);
                if (! $isCurrent) $priorRemaining = max(0, round($priorRemaining - $available, 2));
                $portion = min($available, $remaining);
                if ($portion <= 0) continue;

                $payment = Payment::create([
                    'voucher_id'   => $row->id,
                    'amount'       => $portion,
                    'method'       => $validated['method'],
                    'paid_at'      => $validated['paid_at'] ?? now(),
                    'reference_no' => $validated['reference_no'] ?? null,
                    'received_by'  => auth()->user()->name,
                    'notes'        => $validated['notes'] ?? null,
                    'status'       => 'verified',
                    'receipt_batch_id' => $receiptBatchId,
                    // The one proof upload covers the whole collection. Attach it
                    // to the first row settled by the waterfall.
                    'proof_path'   => $isFirstRow ? $proofPath : null,
                ]);

                $row->increment('paid_amount', $portion);
                VoucherController::recalcStatus($row->fresh());

                if ($isFirstRow) {
                    $firstPayment = $payment;
                    $firstVoucher = $row;
                }
                $isFirstRow = false;

                if (! isset($touched[$row->voucher_no])) {
                    $touched[$row->voucher_no] = [
                        'voucher_no' => $row->voucher_no,
                        'title'      => $row->feeHead->name ?? $row->title,
                        'amount'     => 0,
                    ];
                }
                $touched[$row->voucher_no]['amount'] += $portion;
                $remaining -= $portion;
            }

            $this->refreshStudentFeeStatus($voucher->student);

            $touchedVouchers = array_values($touched);
            $touchedNos = implode(', ', array_column($touchedVouchers, 'voucher_no'));
            $auditNote = "Received payment of {$validated['amount']} from selected voucher {$voucher->voucher_no}; applied to {$touchedNos}";
            if ($carryForward > 0) {
                $auditNote .= " (included carried-forward balance {$carryForward})";
            }
            AuditLog::record('paid', 'Payment', $firstPayment?->id, $auditNote);

            $success = 'Payment recorded successfully.';
            if ($carryForward > 0) {
                $success .= ' Previous unpaid installment balance was settled first as carried-forward dues.';
            }

            // Prefer the selected voucher if it received an allocation.
            // The receipt displays that voucher number's cumulative verified paid amount,
            // not the latest split portion or the whole multi-voucher collection.
            $selectedWasTouched = isset($touched[$voucher->voucher_no]);
            $receiptVoucher = $selectedWasTouched ? $voucher : ($firstVoucher ?: $voucher);

            return redirect()->route('vouchers.receipt', $receiptVoucher)
                ->with('success', $success . ' Here is the receipt slip.');
        }, 3);
    }

    /**
     * Receive a payment directly against a student rather than a single
     * pre-selected voucher — used from the student's Accounts profile page
     * (e.g. a parent walks in and pays a lump sum). The amount is bounded
     * by the student's total outstanding balance across every unpaid,
     * partial, or overdue voucher combined, and is then applied as a
     * waterfall: oldest due date first, one voucher fully settled before
     * moving to the next, until the amount received is exhausted. Each
     * voucher touched gets its own Payment record (so per-voucher receipts
     * and history stay intact) and the student's overall fee/balance
     * status is recalculated the moment it's done.
     */
    public function storeForStudent(Request $request, Student $student)
    {
        return DB::transaction(function () use ($request, $student) {
            $student = Student::whereKey($student->id)->lockForUpdate()->firstOrFail();
            $student->assertActiveEnrollment();
            // Use the same authoritative fee-plan rows as the student balance
            // banner. If an older FeeAssignment was superseded for the same
            // semester/year, it must not receive money or inflate the balance.
            $outstanding = $student->authoritativeVouchers()
                ->reject(fn (Voucher $v) => $v->status === 'cancelled')
                ->filter(fn (Voucher $v) => $v->balance() > 0)
                ->sortBy(fn (Voucher $v) => optional($v->due_date)->timestamp ?? PHP_INT_MAX)
                ->values();

            $outstandingTotal = round($outstanding->sum(fn (Voucher $v) => $v->balance()), 0);

            $validated = $request->validate([
                'amount'       => 'required|integer|min:1|max:' . max(1, (int) $outstandingTotal),
                'method'       => 'required|in:cash,bank_transfer,cheque,online',
                'paid_at'      => 'nullable|date',
                'reference_no' => 'nullable|string|max:100',
                'notes'        => 'nullable|string',
                'proof'        => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
            ]);

            abort_if($outstandingTotal <= 0, 400, 'This student has no outstanding balance to receive payment against.');

            $proofPath = null;
            if ($request->hasFile('proof')) {
                $proofPath = $request->file('proof')->store('payments/proofs', 'public');
            }

            // One submitted collection may be allocated to several vouchers.
            $receiptBatchId = (string) Str::uuid();
            $remaining = (int) $validated['amount'];
            $touchedVouchers = [];
            $firstVoucher = null;
            $isFirstVoucher = true;

            foreach ($outstanding as $voucher) {
                if ($remaining <= 0) break;

                $portion = min($voucher->balance(), $remaining);
                if ($portion <= 0) continue;

                Payment::create([
                    'voucher_id'   => $voucher->id,
                    'amount'       => $portion,
                    'method'       => $validated['method'],
                    'paid_at'      => $validated['paid_at'] ?? now(),
                    'reference_no' => $validated['reference_no'] ?? null,
                    'received_by'  => auth()->user()->name,
                    'notes'        => $validated['notes'] ?? null,
                    'status'       => 'verified',
                    'receipt_batch_id' => $receiptBatchId,
                    // The one proof upload covers the whole payment, so it's
                    // attached to the first voucher it was applied against —
                    // the other touched vouchers stay linked to it through the
                    // same payment date/reference/notes.
                    'proof_path'   => $isFirstVoucher ? $proofPath : null,
                ]);

                $voucher->increment('paid_amount', $portion);
                VoucherController::recalcStatus($voucher->fresh());

                if ($isFirstVoucher) {
                    $firstVoucher = $voucher;
                }
                $isFirstVoucher = false;

                $touchedVouchers[] = ['voucher_no' => $voucher->voucher_no, 'title' => $voucher->title, 'amount' => $portion];
                $remaining -= $portion;
            }

            $this->refreshStudentFeeStatus($student);

            AuditLog::record(
                'paid',
                'Payment',
                null,
                "Received payment of {$validated['amount']} for {$student->name} ({$student->student_id}), applied to voucher(s): " . implode(', ', array_column($touchedVouchers, 'voucher_no'))
            );

            $successMsg = 'Payment of Rs.' . number_format($validated['amount'], 0) . " recorded and applied to {$student->name}'s balance across " . count($touchedVouchers) . ' voucher(s).';

            if (!$firstVoucher) {
                return back()->with('success', $successMsg);
            }

            // Show the first voucher's printable cumulative payment receipt.
            // All vouchers touched remain visible in per-voucher history.
            return redirect()->route('vouchers.receipt', $firstVoucher)
                ->with('success', $successMsg);
        }, 3);
    }

    public function destroy(Payment $payment)
    {
        return DB::transaction(function () use ($payment) {
            $student = Student::whereKey($payment->voucher->student_id)->lockForUpdate()->firstOrFail();
            $student->assertActiveEnrollment();
            $payment = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            $voucher = $payment->voucher;

            // Pending/rejected online submissions were never applied to the
            // voucher balance, so there's nothing to reverse on the voucher.
            if ($payment->isVerified()) {
                $voucher->decrement('paid_amount', $payment->amount);
            }

            app(StudentBalanceService::class)->restoreReversedAllocation($student, $payment, auth()->user()->name);

            $amount = $payment->amount;
            $payment->delete();
            VoucherController::recalcStatus($voucher->fresh());
            $this->refreshStudentFeeStatus($voucher->student);

            AuditLog::record('deleted', 'Payment', null, "Reversed payment of {$amount} on voucher {$voucher->voucher_no}");

            return back()->with('success', 'Payment reversed.');
        }, 3);
    }

    /**
     * Accounts verifies a student's online payment submission (proof
     * screenshot). Only now — once confirmed — does it actually apply to
     * the voucher's paid amount. The amount itself was fixed by the student
     * at submission time against the voucher's own balance; Accounts cannot
     * change it here, only accept or reject the claim.
     */
    public function verify(Payment $payment)
    {
        return DB::transaction(function () use ($payment) {
            $student = Student::whereKey($payment->voucher->student_id)->lockForUpdate()->firstOrFail();
            $student->assertActiveEnrollment();
            $payment = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            abort_unless($payment->isPending(), 400, 'This payment has already been processed.');

            $voucher = $payment->voucher;
            if ($voucher->status === 'cancelled' || $payment->amount > $voucher->balance()) {
                return back()->withErrors(['payment' => 'The voucher balance has changed. Reject this pending claim and record the confirmed funds as an extra payment so no voucher is overpaid.']);
            }

            $payment->update([
                'status'      => 'verified',
                'verified_by' => auth()->user()->name,
                'verified_at' => now(),
            ]);

            $voucher->increment('paid_amount', $payment->amount);
            VoucherController::recalcStatus($voucher->fresh());
            $this->refreshStudentFeeStatus($voucher->student);

            AuditLog::record('verified', 'Payment', $payment->id, "Verified online payment of {$payment->amount} for voucher {$voucher->voucher_no}");

            return back()->with('success', 'Payment verified and applied to the voucher.');
        }, 3);
    }

    public function reject(Request $request, Payment $payment)
    {
        return DB::transaction(function () use ($request, $payment) {
            Student::whereKey($payment->voucher->student_id)->lockForUpdate()->firstOrFail()->assertActiveEnrollment();
            $payment = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            abort_unless($payment->isPending(), 400, 'This payment has already been processed.');

            $validated = $request->validate(['rejection_reason' => 'nullable|string|max:255']);

            $payment->update([
                'status'            => 'rejected',
                'verified_by'       => auth()->user()->name,
                'verified_at'       => now(),
                'rejection_reason'  => $validated['rejection_reason'] ?? 'Payment proof could not be verified.',
            ]);

            AuditLog::record('rejected', 'Payment', $payment->id, "Rejected online payment claim of {$payment->amount} for voucher {$payment->voucher->voucher_no}");

            return back()->with('success', 'Payment submission rejected.');
        }, 3);
    }

    /**
     * Single "Receive Payment" screen for Accounts: type a voucher no. to
     * pay against that specific voucher (student name + amount owed
     * resolve automatically via VoucherController::lookup), or leave the
     * voucher no. blank and search the student directly to record an
     * extra amount that isn't tied to any voucher — that goes to the
     * student's credit balance instead.
     */
    public function receiveForm()
    {
        return view('accounts.receive-payment');
    }

    /** Apply extra payments to existing dues first; retain only the surplus as credit. */
    public function storeBalanceCredit(Request $request, Student $student)
    {
        $validated = $request->validate([
            'amount' => 'required|integer|min:1|max:9999999999',
            'method' => 'required|in:cash,bank_transfer,cheque,online',
            'paid_at' => 'nullable|date',
            'reference_no' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:5000',
            'idempotency_key' => 'required|uuid',
        ]);
        $result = app(StudentBalanceService::class)->receive($student, $validated, auth()->user()->name);
        $receiptUrl = route('payments.extra-receipts.show', [$student, $result['receipt_id']]);
        if ($result['duplicate']) {
            return redirect($receiptUrl)->with('success', 'This payment was already recorded. Here is the same receipt; no duplicate payment was added.');
        }
        return redirect($receiptUrl)->with('success', 'Rs.'.number_format($validated['amount'], 0).' received. Rs.'
            .number_format($result['applied'] / 100, 2).' applied to outstanding vouchers; Rs.'
            .number_format($result['remaining'] / 100, 2).' remains as available credit. Print or save the receipt below.');
    }

    /** Reconcile credit already received, without asking Accounts to enter the money twice. */
    public function applyBalanceCredit(Student $student)
    {
        $result = app(StudentBalanceService::class)->applyAvailable($student, auth()->user()->name);
        return redirect()->route('payments.extra-receipts.index', $student)
            ->with('success', 'Rs.'.number_format($result['applied'] / 100, 2)
                .' of existing credit applied to vouchers. Available credit: Rs.'
                .number_format($result['remaining'] / 100, 2)
                .'. No new money was received. Reprint the original payment from the receipt history below.');
    }

    protected function refreshStudentFeeStatus(Student $student): void
    {
        $student->fresh()->synchronizeFeeStatus();
    }
}

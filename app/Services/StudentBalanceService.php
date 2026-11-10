<?php

namespace App\Services;

use App\Http\Controllers\VoucherController;
use App\Models\{AuditLog, Payment, Student, StudentBalanceTransaction, Voucher};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentBalanceService
{
    /** Receive once, then settle oldest authoritative dues. Surplus remains available. */
    public function receive(Student $student, array $data, string $actor): array
    {
        return DB::transaction(function () use ($student, $data, $actor) {
            $student = Student::whereKey($student->id)->lockForUpdate()->firstOrFail();
            $student->assertActiveEnrollment();
            $key = $data['idempotency_key'] ?? null;
            if ($key && StudentBalanceTransaction::where('idempotency_key', $key)->exists()) {
                $existing = StudentBalanceTransaction::where('idempotency_key', $key)->firstOrFail();
                if ($existing->type !== 'receipt' || (int) $existing->student_id !== (int) $student->id ||
                    CreditAllocator::minor($existing->amount) !== CreditAllocator::minor($data['amount'])) {
                    throw ValidationException::withMessages(['amount' => 'This payment form was already used. Reload before recording another payment.']);
                }
                return ['duplicate' => true, 'applied' => 0, 'remaining' => CreditAllocator::minor($student->credit_balance), 'receipt_id' => (int) $existing->id];
            }
            $amount = CreditAllocator::minor($data['amount']);
            $balance = CreditAllocator::minor($student->credit_balance) + $amount;
            if ($balance > 999999999999) {
                throw ValidationException::withMessages(['amount' => 'The resulting credit balance is too large.']);
            }
            $student->forceFill(['credit_balance' => CreditAllocator::decimal($balance)])->save();
            $receipt = StudentBalanceTransaction::create([
                'student_id' => $student->id, 'type' => 'receipt',
                'amount' => CreditAllocator::decimal($amount), 'balance_after' => CreditAllocator::decimal($balance),
                'method' => $data['method'], 'paid_at' => $data['paid_at'] ?? now(),
                'reference_no' => $data['reference_no'] ?? null, 'notes' => $data['notes'] ?? null,
                'recorded_by' => $actor, 'idempotency_key' => $key,
            ]);
            AuditLog::record('paid', 'Student', $student->id,
                'Extra payment received: '.CreditAllocator::decimal($amount).'; method '.$data['method']);
            return $this->allocateLocked($student, $actor) + ['receipt_id' => (int) $receipt->id];
        }, 3);
    }

    /** Idempotent: spends only currently available credit, never records new cash. */
    public function applyAvailable(Student $student, string $actor = 'System'): array
    {
        return DB::transaction(function () use ($student, $actor) {
            $student = Student::whereKey($student->id)->lockForUpdate()->firstOrFail();
            $student->assertActiveEnrollment();
            return $this->allocateLocked($student, $actor);
        }, 3);
    }

    private function allocateLocked(Student $student, string $actor): array
    {
        $available = CreditAllocator::minor($student->credit_balance);
        // Never allocate display-only carry-forward amounts or superseded/legacy duplicates.
        $ids = $student->authoritativeVouchers()->pluck('id');
        $rows = Voucher::whereIn('id', $ids)->where('status', '!=', 'cancelled')
            ->orderByRaw('CASE WHEN due_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('due_date')->orderBy('id')->lockForUpdate()->get();
        $allocation = CreditAllocator::allocate($available, $rows->map(fn (Voucher $v) => [
            'id' => (int) $v->id, 'balance' => CreditAllocator::minor($v->balance()),
        ])->all());
        $byId = $rows->keyBy('id');
        $runningBalance = $available;
        foreach ($allocation['allocations'] as $portion) {
            $voucher = $byId->get($portion['id']);
            $amount = CreditAllocator::decimal($portion['amount']);
            $payment = Payment::create([
                'voucher_id' => $voucher->id, 'amount' => $amount, 'method' => 'balance_credit',
                'paid_at' => now(), 'received_by' => $actor, 'status' => 'verified',
                'notes' => 'Applied from existing student credit. This is not a new cash receipt.',
            ]);
            $voucher->increment('paid_amount', $amount);
            VoucherController::recalcStatus($voucher->fresh());
            $runningBalance -= $portion['amount'];
            StudentBalanceTransaction::create([
                'student_id' => $student->id, 'type' => 'allocation', 'amount' => '-'.$amount,
                'balance_after' => CreditAllocator::decimal($runningBalance), 'payment_id' => $payment->id,
                'method' => 'balance_credit', 'paid_at' => now(), 'recorded_by' => $actor,
                'notes' => 'Applied to voucher '.$voucher->voucher_no,
            ]);
        }
        $student->forceFill([
            'credit_balance' => CreditAllocator::decimal($allocation['remaining']),
        ])->save();
        $student->fresh()->synchronizeFeeStatus();
        if ($allocation['applied'] > 0) {
            AuditLog::record('updated', 'Student', $student->id,
                'Student credit applied to vouchers: '.CreditAllocator::decimal($allocation['applied'])
                .'; available credit: '.CreditAllocator::decimal($allocation['remaining']));
        }
        return $allocation + ['duplicate' => false];
    }

    /** Caller holds the student's and payment's locks inside one transaction. */
    public function restoreReversedAllocation(Student $student, Payment $payment, string $actor): void
    {
        if ($payment->method !== 'balance_credit' || ! $payment->isVerified()) return;
        $balance = CreditAllocator::minor($student->credit_balance) + CreditAllocator::minor($payment->amount);
        $student->forceFill(['credit_balance' => CreditAllocator::decimal($balance)])->save();
        StudentBalanceTransaction::create([
            'student_id' => $student->id, 'type' => 'reversal', 'amount' => $payment->amount,
            'balance_after' => CreditAllocator::decimal($balance), 'payment_id' => $payment->id,
            'method' => 'balance_credit', 'paid_at' => now(), 'recorded_by' => $actor,
            'notes' => 'Voucher allocation reversed; funds returned to available credit, not refunded in cash.',
        ]);
    }
}

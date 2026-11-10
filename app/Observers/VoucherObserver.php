<?php

namespace App\Observers;

use App\Models\InternshipApplication;
use App\Models\Voucher;
use App\Services\NotifierService;

/**
 * Watches every voucher for the moment it flips to 'paid'. If that voucher
 * happens to be an internship challan (linked from an InternshipApplication),
 * this is the single place that marks the application's fee as settled and
 * pushes the "Internship confirmed!" notification to the student — no matter
 * which screen recorded the payment (Accounts "Receive Payment", a verified
 * online submission, etc.), since they all funnel through Voucher::update().
 */
class VoucherObserver
{
    public function created(Voucher $voucher): void
    {
        $voucher->student?->synchronizeFeeStatus();
        $studentId = $voucher->student_id;
        \Illuminate\Support\Facades\DB::afterCommit(function () use ($studentId) {
            $student = \App\Models\Student::find($studentId);
            if ($student && (float) $student->credit_balance > 0) {
                app(\App\Services\StudentBalanceService::class)->applyAvailable($student);
            }
        });
    }

    public function deleted(Voucher $voucher): void
    {
        $voucher->student?->fresh()?->synchronizeFeeStatus();
    }

    public function updated(Voucher $voucher): void
    {
        if ($voucher->wasChanged(['paid_amount', 'amount', 'discount_amount', 'status', 'fee_assignment_id'])) {
            $voucher->student?->fresh()?->synchronizeFeeStatus();
        }

        if (! $voucher->wasChanged('status') || $voucher->status !== 'paid') {
            return;
        }

        $application = InternshipApplication::where('voucher_id', $voucher->id)
            ->where('status', 'admin_approved')
            ->where('fee_status', '!=', 'paid')
            ->first();

        if (! $application) {
            return;
        }

        $application->update(['fee_status' => 'paid']);

        $student = $application->student;
        if ($student?->user_id) {
            NotifierService::notify(
                $student->user_id,
                'Internship confirmed! Your fee for "' . $application->internship->title . '" has been received.',
                'fa-graduation-cap',
                'success'
            );
        }
    }
}

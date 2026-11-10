<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeeAssignment extends Model {
    use HasFactory;

    protected $fillable = [
        'student_id', 'fee_template_id', 'parent_fee_assignment_id', 'bank_account_id',
        'increase_amount', 'scholarship_amount', 'discount_amount', 'financial_aid_amount', 'cycle_increment_amount', 'installments',
        'parts_per_cycle', 'cycle_start', 'cycle_end', 'billing_mode', 'months', 'first_due_date',
        'frequency_days', 'cycle_interval_days', 'total_amount', 'batch_prefix', 'notes',
        'manual_items', 'carried_forward_amount', 'carried_forward_note',
        'lms_login_email', 'lms_login_password_plain',
        'manual_unlocked_cycles',
    ];

    protected $casts = [
        'months'                    => 'array',
        'manual_items'               => 'array',
        'manual_unlocked_cycles'     => 'array',
        'first_due_date'            => 'date',
        'increase_amount'           => 'decimal:2',
        'scholarship_amount'          => 'decimal:2',
        'discount_amount'           => 'decimal:2',
        'financial_aid_amount'      => 'decimal:2',
        'cycle_increment_amount'    => 'decimal:2',
        'total_amount'              => 'decimal:2',
        'carried_forward_amount'    => 'decimal:2',
    ];

    public function student()     { return $this->belongsTo(Student::class); }
    public function feeTemplate() { return $this->belongsTo(FeeTemplate::class); }
    public function bankAccount() { return $this->belongsTo(BankAccount::class); }
    public function vouchers()    { return $this->hasMany(Voucher::class); }
    public function parentAssignment() { return $this->belongsTo(FeeAssignment::class, 'parent_fee_assignment_id'); }
    public function renewals()    { return $this->hasMany(FeeAssignment::class, 'parent_fee_assignment_id'); }

    /** Vouchers grouped by installment number, in order. */
    public function installmentGroups()
    {
        return $this->vouchers()->with('feeHead')->orderBy('installment_no')->get()->groupBy('installment_no');
    }

    /**
     * Printable challans grouped by voucher number. Normally this is identical
     * to installmentGroups(), but an accountant can split one installment into
     * two payable challans while keeping both under the same installment/part.
     */
    public function challanGroups()
    {
        return $this->vouchers()
            ->with('feeHead')
            ->orderBy('installment_no')
            ->orderBy('split_sequence')
            ->orderBy('id')
            ->get()
            ->groupBy('voucher_no');
    }

    /** e.g. "Semester 1" (single cycle) or "Semester 1 - 8" (multiple cycles covered by this plan). */
    public function cycleRangeLabel(): string
    {
        $label = $this->feeTemplate?->cycleLabel() ?? 'Term';
        return $this->cycle_start == $this->cycle_end
            ? "{$label} {$this->cycle_start}"
            : "{$label} {$this->cycle_start} - {$this->cycle_end}";
    }

    /**
     * "Semester 1 of 8" / "Year 1 of 2" — the cycle a given installment
     * belongs to, against the student's actual program length. Used on
     * vouchers/slips so a student can see at a glance which semester/year
     * an installment is for and how many the whole program has, not just
     * an internal cycle number.
     */
    public function termLabelFor(int $cycleNo): string
    {
        $label = $this->feeTemplate?->cycleLabel() ?? 'Term';
        $program = $this->student?->program;
        $total = $this->feeTemplate?->total_cycles
            ?: ($program ? ($program->actualTermCount() ?? $program->duration_terms) : null);

        return $total ? "{$label} {$cycleNo} of {$total}" : "{$label} {$cycleNo}";
    }

    /**
     * "Semester 1 | 1st Installment" — the short, human label used on
     * voucher listings and printed challans, replacing the older
     * "Installment 1 of 4" phrasing so students see which semester/year
     * and which installment number an entry belongs to at a glance.
     */
    public function installmentLabelFor(int $cycleNo, ?int $installmentNo): string
    {
        $cycleWord = $this->feeTemplate?->cycleLabel() ?? 'Term';

        return semester_installment_label($cycleNo, $installmentNo, $cycleWord);
    }

    /** Every voucher belonging to one semester/year (cycle) of this plan. Relies on cycle_no (new plans only). */
    public function vouchersForCycle(int $cycleNo)
    {
        return $this->vouchers()->where('cycle_no', $cycleNo)->orderBy('part_no')->get();
    }

    /** Whether every voucher generated so far for this cycle has been fully paid. Null = nothing generated yet. */
    public function isCyclePaid(int $cycleNo): ?bool
    {
        $vouchers = $this->vouchersForCycle($cycleNo);
        if ($vouchers->isEmpty()) return null;
        return $vouchers->every(fn ($v) => $v->balance() <= 0);
    }

    /** Whether any voucher in this cycle already has a payment recorded against it (blocks re-splitting). */
    public function cycleHasPayments(int $cycleNo): bool
    {
        return $this->vouchersForCycle($cycleNo)->contains(fn ($v) => (float) $v->paid_amount > 0);
    }

    /**
     * "Active" here means the cycle's billing period has actually started
     * (its earliest due date has arrived), not merely that it exists on
     * the plan — a semester generated 6 months in advance isn't "active" yet.
     */
    public function isCycleActive(int $cycleNo): bool
    {
        $earliestDue = $this->vouchersForCycle($cycleNo)->min('due_date');
        return $earliestDue ? $earliestDue->lte(now()) : false;
    }

    /**
     * Whether it's safe to re-split $cycleNo into a new set of installments:
     * the cycle before it (in this plan, or the last cycle of the plan this
     * one continues from) must be fully paid, and nothing in $cycleNo itself
     * may have been paid against yet.
     */
    public function canSplitCycle(int $cycleNo): bool
    {
        return $this->splitLockReason($cycleNo) === null;
    }

    /** Whether an admin/accountant has manually force-unlocked this cycle, bypassing the automatic checks below. */
    public function isCycleManuallyUnlocked(int $cycleNo): bool
    {
        return in_array($cycleNo, $this->manual_unlocked_cycles ?? [], false);
    }

    /** Force-unlock a cycle so it can be re-split even though the automatic rule would otherwise block it. */
    public function unlockCycleManually(int $cycleNo): void
    {
        $cycles = $this->manual_unlocked_cycles ?? [];
        if (!in_array($cycleNo, $cycles, false)) {
            $cycles[] = $cycleNo;
        }
        $this->manual_unlocked_cycles = array_values($cycles);
        $this->save();
    }

    /** Undo a manual unlock, restoring the automatic lock rule for this cycle. */
    public function relockCycleManually(int $cycleNo): void
    {
        $cycles = collect($this->manual_unlocked_cycles ?? [])->reject(fn ($c) => (int) $c === $cycleNo)->values()->all();
        $this->manual_unlocked_cycles = $cycles;
        $this->save();
    }

    /**
     * Why $cycleNo can't be re-split right now, or null if it's unlocked.
     * Semester/Year 1 of the whole program never has an earlier cycle to
     * wait on, so it is never locked for that reason — only an actual
     * payment already recorded against it can lock it.
     *
     * A manual override (set by an admin/accountant via "Force Unlock" when
     * the automatic rule doesn't fit the real situation) always wins and
     * skips every check below.
     */
    public function splitLockReason(int $cycleNo): ?string
    {
        if ($this->isCycleManuallyUnlocked($cycleNo)) {
            return null;
        }

        if ($this->cycleHasPayments($cycleNo)) {
            return 'Payments have already been recorded against this semester.';
        }

        // The very first cycle of the entire program (absolute #1) can
        // never be blocked by "the previous semester still owes" — there
        // is no previous semester. This also guards against stray/legacy
        // parent_fee_assignment_id links accidentally locking cycle 1.
        if ($cycleNo <= 1) return null;

        if ($cycleNo <= $this->cycle_start) {
            // First cycle of THIS plan (cycle_start > 1) — check the plan
            // it renewed from, if any.
            if (!$this->parentAssignment) return null;
            $prevCycle = $this->parentAssignment->cycle_end;
            if ($this->parentAssignment->isCyclePaid($prevCycle) ?? true) return null;
            return "The previous semester (#{$prevCycle}) still has an outstanding balance.";
        }

        if ($this->isCyclePaid($cycleNo - 1) ?? true) return null;
        return 'The previous semester still has an outstanding balance.';
    }

    public static function generateBatchPrefix(): string
    {
        do {
            $prefix = 'PLAN-' . now()->format('ymd') . '-' . strtoupper(substr(uniqid(), -5));
        } while (static::where('batch_prefix', $prefix)->exists());
        return $prefix;
    }
}

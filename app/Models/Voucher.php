<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Voucher extends Model {
    use HasFactory;
    protected $fillable = [
        'voucher_no','student_id','fee_head_id','fee_template_id','fee_assignment_id','bank_account_id',
        'title','month',
        'amount','fine_amount','discount_amount','paid_amount','due_date','issued_at',
        'installment_no','installment_total','cycle_no','part_no','batch_id',
        'split_root_voucher_no','split_sequence','split_carry_cap','status','notes',
    ];
    protected $casts = [
        'due_date'  => 'date',
        'issued_at' => 'datetime',
        'amount'          => 'decimal:2',
        'fine_amount'     => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'paid_amount'     => 'decimal:2',
        'split_sequence'  => 'integer',
        'split_carry_cap' => 'decimal:2',
    ];

    public function student()     { return $this->belongsTo(Student::class); }
    public function feeHead()     { return $this->belongsTo(FeeHead::class); }
    public function feeTemplate() { return $this->belongsTo(FeeTemplate::class); }
    public function feeAssignment() { return $this->belongsTo(FeeAssignment::class); }
    public function bankAccount() { return $this->belongsTo(BankAccount::class); }
    public function payments()    { return $this->hasMany(Payment::class); }

    /** Total payable amount = amount + fine - discount */
    public function total(): float
    {
        return round((float) $this->amount - (float) $this->discount_amount, 2);
    }

    /** Remaining balance owed */
    public function balance(): float
    {
        return max(0, round($this->total() - (float) $this->paid_amount, 2));
    }

    public function isOverdue(): bool
    {
        return $this->status !== 'paid' && $this->status !== 'cancelled' && $this->due_date->isPast();
    }

    /**
     * Automated classification — Paid / Overdue / Due / Active / Upcoming —
     * derived purely from the installment plan's due date and the recorded
     * payment state. There is no manual "set status" control anywhere in
     * the app; this always reflects reality.
     *
     *  - Paid:     balance fully settled.
     *  - Overdue:  unpaid/partial and the due date has already passed.
     *  - Due:      unpaid/partial and due today.
     *  - Active:   unpaid/partial and due within the next 7 days.
     *  - Upcoming: unpaid/partial and due further ahead than that.
     */
    public function autoStatusLabel(int $activeWindowDays = 7): string
    {
        if ($this->status === 'cancelled') return 'Cancelled';
        if ($this->balance() <= 0)         return 'Paid';
        if (! $this->due_date)             return 'Upcoming';

        $today = now()->startOfDay();
        if ($this->due_date->lt($today))                                  return 'Overdue';
        if ($this->due_date->isSameDay($today))                           return 'Due';
        if ($this->due_date->lte($today->copy()->addDays($activeWindowDays))) return 'Active';

        return 'Upcoming';
    }

    public static function generateVoucherNo(): string
    {
        do {
            $no = 'VCH-' . now()->format('ymd') . '-' . strtoupper(substr(uniqid(), -5));
        } while (static::where('voucher_no', $no)->exists());
        return $no;
    }

    /**
     * A single voucher number can bundle several fee heads (e.g. Tuition
     * Fee + Examination Fee due the same installment) — each gets its own
     * row in this table so it has its own fee_head_id, amount, and
     * paid_amount, but they all share one voucher_no for the same
     * student. Every screen that shows "this voucher" (challan browse,
     * voucher detail, receipt, print) needs to treat that whole group as
     * ONE voucher, not just whichever single row happened to be clicked —
     * these helpers are the single source of truth for that grouping.
     */
    public function lineItems()
    {
        return self::where('voucher_no', $this->voucher_no)
                    ->where('student_id', $this->student_id)
                    ->with('feeHead')
                    ->orderBy('id')
                    ->get();
    }

    /** Every Payment recorded against any fee-head row in this voucher group, oldest first. */
    public function combinedPayments()
    {
        return Payment::whereIn('voucher_id', $this->lineItems()->pluck('id'))
                       ->orderBy('paid_at')
                       ->orderBy('id')
                       ->get();
    }

    /** Combined "Amount" (before fine/discount) across every fee-head row in this voucher group. */
    public function combinedAmount(): float
    {
        return round((float) $this->lineItems()->sum('amount'), 2);
    }

    public function combinedFine(): float
    {
        return 0.0;
    }

    public function combinedDiscount(): float
    {
        return round((float) $this->lineItems()->sum('discount_amount'), 2);
    }

    /** Combined total payable across every fee-head row sharing this voucher number. */
    public function combinedTotal(): float
    {
        return round($this->lineItems()->sum(fn (self $v) => $v->total()), 2);
    }

    public function combinedPaidAmount(): float
    {
        return round((float) $this->lineItems()->sum('paid_amount'), 2);
    }

    /** Combined remaining balance across every fee-head row sharing this voucher number. */
    public function combinedBalance(): float
    {
        return max(0, round($this->combinedTotal() - $this->combinedPaidAmount(), 2));
    }

    /**
     * Whether Accounts may turn this challan into two independently printable
     * challans. We deliberately allow this only before any real/pending
     * payment exists so a split can never rewrite money that has already been
     * received or is waiting for verification.
     */
    public function canSplitChallan(): bool
    {
        $items = $this->lineItems();

        // This feature is intentionally for fee-plan installment challans.
        // Standalone vouchers can be linked to other workflows (for example
        // internship/application fees) by voucher_id, where cloning the row
        // would break the meaning of that external link.
        if (! $this->fee_assignment_id) {
            return false;
        }

        if ($items->isEmpty() || $items->contains(fn (self $v) => $v->status === 'cancelled')) {
            return false;
        }

        if ($this->combinedBalance() <= 0.01 || $this->combinedPaidAmount() > 0) {
            return false;
        }

        // Rejected proof submissions have no accounting effect and do not
        // need to block a later challan split. Pending/verified payments do.
        if ($this->combinedPayments()->contains(fn (Payment $payment) => ! $payment->isRejected())) {
            return false;
        }

        return true;
    }

    /**
     * Outstanding balance from earlier installments in the same fee plan that
     * should be collected with this voucher once this voucher reaches its due
     * date. Nothing is copied into the database, so the student's ledger and
     * outstanding total are never double-counted; this is a live carry-forward
     * collection amount only.
     */
    public function carryForwardBalance(): float
    {
        if ($this->status === 'cancelled' || ! $this->fee_assignment_id || ! $this->due_date) {
            return 0.0;
        }


        $today = now()->startOfDay();
        $currentDue = $this->due_date->copy()->startOfDay();

        // A future installment does not absorb arrears early. The previous
        // balance is carried into it only on (or after) its own due date.
        if ($currentDue->gt($today)) {
            return 0.0;
        }

        $query = self::query()
            ->where('student_id', $this->student_id)
            ->where('fee_assignment_id', $this->fee_assignment_id)
            ->where('voucher_no', '!=', $this->voucher_no)
            ->where('status', '!=', 'cancelled')
            ->whereRaw('(COALESCE(amount,0) - COALESCE(discount_amount,0) - COALESCE(paid_amount,0)) > 0')
            ->whereDate('due_date', '<', $currentDue->toDateString());

        // New fee-plan rows are cycle-aware. Ignore old null-cycle rows when
        // the current voucher is cycle-aware so regenerated legacy rows cannot
        // be carried forward a second time.
        if ($this->cycle_no !== null) {
            $query->whereNotNull('cycle_no');
        }

        $actualOutstanding = max(0, round((float) $query->get()->sum(fn (self $v) => $v->balance()), 2));
        // Split siblings get independent collection allowances. They all refer
        // to the SAME earlier debts, never copied debt rows.
        if ($this->split_carry_cap !== null) {
            return min($actualOutstanding, max(0, (float) $this->split_carry_cap));
        }
        return $actualOutstanding;
    }

    /** Current voucher balance plus any arrears that have become carry-forward. */
    public function payableBalanceWithCarryForward(): float
    {
        return max(0, round($this->combinedBalance() + $this->carryForwardBalance(), 2));
    }

    /**
     * The row-level waterfall used when Accounts records a payment against a
     * voucher whose due date has arrived. Earlier unpaid rows are returned
     * first, followed by the selected voucher's own fee-head rows.
     */
    public function paymentRowsWithCarryForward()
    {
        $currentRows = $this->lineItems()->filter(fn (self $v) => $v->balance() > 0);

        if ($this->carryForwardBalance() <= 0) {
            return $currentRows->values();
        }

        $currentDue = $this->due_date->copy()->startOfDay();
        $priorQuery = self::query()
            ->where('student_id', $this->student_id)
            ->where('fee_assignment_id', $this->fee_assignment_id)
            ->where('voucher_no', '!=', $this->voucher_no)
            ->where('status', '!=', 'cancelled')
            ->whereRaw('(COALESCE(amount,0) - COALESCE(discount_amount,0) - COALESCE(paid_amount,0)) > 0')
            ->whereDate('due_date', '<', $currentDue->toDateString())
            ->orderBy('due_date')
            ->orderBy('installment_no')
            ->orderBy('id');

        if ($this->cycle_no !== null) {
            $priorQuery->whereNotNull('cycle_no');
        }

        return $priorQuery->get()
            ->concat($currentRows)
            ->unique('id')
            ->values();
    }

    /** True if this voucher_no bundles more than one fee-head row. */
    public function hasSiblings(): bool
    {
        return $this->lineItems()->count() > 1;
    }

    /** Combined Paid / Partially Paid / Overdue / Due / Active / Upcoming / Cancelled across the whole voucher group. */
    public function combinedStatusLabel(int $activeWindowDays = 7): string
    {
        $items = $this->lineItems();
        if ($items->every(fn (self $v) => $v->status === 'cancelled')) return 'Cancelled';
        if ($this->combinedBalance() <= 0)                             return 'Paid';
        if ($this->combinedPaidAmount() > 0)                           return 'Partially Paid';
        if (! $this->due_date)                                         return 'Upcoming';

        $today = now()->startOfDay();
        if ($this->due_date->lt($today))                                     return 'Overdue';
        if ($this->due_date->isSameDay($today))                               return 'Due';
        if ($this->due_date->lte($today->copy()->addDays($activeWindowDays))) return 'Active';

        return 'Upcoming';
    }
}

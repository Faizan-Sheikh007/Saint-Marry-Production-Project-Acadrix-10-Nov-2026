<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    /**
     * 'status' and 'roll_number' are admin-only fields (see ADMIN_ONLY_FIELDS
     * below) and are deliberately left out of mass-assignable $fillable.
     * Use adminFill() from admin-verified controllers when these need to change.
     */
    protected $fillable = [
        'user_id', 'parent_id', 'student_id', 'name', 'father_name', 'email', 'phone', 'gender', 'date_of_birth',
        'address', 'permanent_address', 'cnic', 'religion', 'marital_status',
        'guardian_name', 'guardian_occupation', 'guardian_relation', 'guardian_phone', 'guardian_cnic',
        'emergency_contact_name', 'emergency_contact_phone', 'emergency_contact_relation', 'emergency_contact_address',
        'school_class_id', 'program_id', 'shift', 'section', 'session', 'level', 'admission_date',
        'academic_level_id', 'academic_year_id', 'institution_id',
        'documents', 'education_qualifications', 'photo',
        'financial_aid_requested', 'financial_aid_type', 'financial_aid_notes',
        'id_card_number', 'id_card_issued_at', 'id_card_reissue_count',
        'attendance_percentage', 'ledger_number',
    ];

    protected const ADMIN_ONLY_FIELDS = ['status', 'fee_status', 'roll_number'];

    public function adminFill(array $attributes): static
    {
        $this->fill($attributes);

        foreach (self::ADMIN_ONLY_FIELDS as $field) {
            if (array_key_exists($field, $attributes)) {
                $this->$field = $attributes[$field];
            }
        }

        return $this;
    }

    protected $casts = [
        'date_of_birth'     => 'date',
        'admission_date'    => 'date',
        'id_card_issued_at' => 'date',
        'documents'         => 'array',
        'education_qualifications' => 'array',
        'financial_aid_requested'  => 'boolean',
        'attendance_percentage' => 'decimal:2',
        'credit_balance'    => 'decimal:2',
    ];

    /** Enrollment and access state, independent of any fee/payment state. */
    public function isActiveEnrollment(): bool
    {
        return \App\Support\StudentStatus::isActive($this->status);
    }

    public function statusLabel(): string
    {
        return \App\Support\StudentStatus::label($this->status);
    }

    public function statusBadge(): string
    {
        return \App\Support\StudentStatus::badge($this->status);
    }

    public function scopeActiveEnrollment(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where($this->qualifyColumn('status'), 'active');
    }

    public function assertActiveEnrollment(): void
    {
        if (! $this->isActiveEnrollment()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'student_id' => \App\Support\StudentStatus::lockedMessage($this->status),
            ]);
        }
    }

    // ── Core relationships (EMS side) ───────────────────────────
    public function user()           { return $this->belongsTo(User::class); }
    public function schoolClass()    { return $this->belongsTo(SchoolClass::class); }
    public function program()        { return $this->belongsTo(Program::class); }
    public function attendances()    { return $this->hasMany(Attendance::class); }
    public function examResults()    { return $this->hasMany(ExamResult::class); }
    public function studentReportRemarks() { return $this->hasMany(StudentReportRemark::class); }
    public function enrollments()    { return $this->hasMany(CourseEnrollment::class); }
    public function submissions()    { return $this->hasMany(AssignmentSubmission::class); }
    public function quizAttempts()   { return $this->hasMany(QuizAttempt::class); }
    public function lessonProgress() { return $this->hasMany(LessonProgress::class); }
    public function parentGuardian() { return $this->belongsTo(ParentModel::class, 'parent_id'); }
    public function academicLevel()  { return $this->belongsTo(AcademicLevel::class); }
    public function academicYear()   { return $this->belongsTo(AcademicYear::class); }
    public function institution()    { return $this->belongsTo(Institution::class); }
    public function directMessages() { return $this->hasMany(Message::class); }

    /**
     * Resolve the ID-card expiry date from the student's academic session.
     *
     * Prefer the exact AcademicYear end_date when configured. For legacy
     * students that only carry a session label such as "2023-2027", keep
     * the join-date day/month and use the final year from that session.
     * Example: 05-10-2023 + session 2023-2027 => 05-10-2027.
     */
    public function idCardExpiryDate()
    {
        $joinDate = $this->academicYear?->start_date ?? $this->admission_date;

        if ($this->academicYear) {
            $resolved = $this->academicYear->effectiveEndDate($joinDate);
            if ($resolved) {
                return $resolved;
            }
        }

        preg_match_all('/\b(?:19|20)\d{2}\b/', (string) $this->session, $matches);
        if (empty($matches[0]) || ! $joinDate) {
            return null;
        }

        $endYear = (int) end($matches[0]);
        $date = $joinDate instanceof \Carbon\CarbonInterface
            ? $joinDate->copy()
            : \Carbon\Carbon::parse($joinDate);

        $month = $date->month;
        $day = min($date->day, \Carbon\Carbon::create($endYear, $month, 1)->daysInMonth);

        return \Carbon\Carbon::create($endYear, $month, $day)->startOfDay();
    }

    // ── Accounts relationships (fee module, single source of truth) ────
    public function vouchers()       { return $this->hasMany(Voucher::class); }
    public function balanceTransactions() { return $this->hasMany(StudentBalanceTransaction::class); }
    public function feeAssignments() { return $this->hasMany(FeeAssignment::class); }

    // ── EMS helpers ──────────────────────────────────────────────
    public function attendancePercentage(): int
    {
        $total   = $this->attendances()->count();
        $present = $this->attendances()->where('status', 'present')->count();
        return $total > 0 ? round(($present / $total) * 100) : 0;
    }

    public function averageGrade(): int
    {
        return round($this->examResults()->avg('percentage') ?? 0);
    }

    public function enrolledCourses()
    {
        return Course::whereHas('enrollments', fn ($q) => $q->where('student_id', $this->id));
    }

    // ── Accounts helpers (replaces the old Fee-based pendingFees()) ────

    /**
     * Return the authoritative voucher rows for this student.
     *
     * A student can occasionally have more than one FeeAssignment covering
     * the same semester/year (for example after a plan was regenerated or
     * replaced). The semester grid already treats the newest assignment as
     * the owner of that cycle. Financial totals and lump-sum payments must
     * use the exact same rule, otherwise an older superseded assignment can
     * be counted a second time and inflate the student's outstanding balance.
     *
     * Standalone vouchers (no fee_assignment_id) are always included.
     */
    public function authoritativeVouchers(): \Illuminate\Database\Eloquent\Collection
    {
        $assignments = $this->relationLoaded('feeAssignments')
            ? $this->feeAssignments->sortBy([['cycle_start', 'asc'], ['id', 'asc']])->values()
            : $this->feeAssignments()->with('vouchers')->orderBy('cycle_start')->orderBy('id')->get();

        $assignments->loadMissing('vouchers');

        $cycleOwners = [];
        foreach ($assignments as $assignment) {
            $start = max(1, (int) ($assignment->cycle_start ?: 1));
            $end   = max($start, (int) ($assignment->cycle_end ?: $start));

            // Later assignment IDs overwrite earlier ones for the same cycle,
            // matching the logic used by the semester/year browse screens.
            for ($cycle = $start; $cycle <= $end; $cycle++) {
                $cycleOwners[$cycle] = $assignment;
            }
        }

        $owned = collect();
        foreach ($cycleOwners as $cycle => $assignment) {
            $hasExplicitCycleRows = $assignment->vouchers
                ->contains(fn ($voucher) => $voucher->cycle_no !== null);

            $rows = $assignment->vouchers->filter(function ($voucher) use ($cycle, $assignment, $hasExplicitCycleRows) {
                /*
                 * Do not mix old null-cycle rows with the newer cycle-aware
                 * rows from the same assignment.  That is exactly how a
                 * student can show Rs.45,000 pending in the semester card but
                 * Rs.70,000 in the top banner: the semester query only sees
                 * cycle_no=1, while the old balance helper was also adding
                 * legacy cycle_no=NULL rows.
                 *
                 * A fully legacy assignment (no cycle_no values at all) is
                 * still supported by counting its rows once in cycle_start.
                 */
                if ($voucher->cycle_no === null) {
                    return ! $hasExplicitCycleRows
                        && (int) ($assignment->cycle_start ?: 1) === (int) $cycle;
                }

                return (int) $voucher->cycle_no === (int) $cycle;
            });

            $owned = $owned->concat($rows);
        }

        $standalone = $this->relationLoaded('vouchers')
            ? $this->vouchers->whereNull('fee_assignment_id')->values()
            : $this->vouchers()->whereNull('fee_assignment_id')->get();

        /*
         * Old versions of the fee module could generate template/installment
         * vouchers without fee_assignment_id.  When the student was later
         * moved onto the current FeeAssignment workflow those legacy rows were
         * left in place.  They are useful as historical records, but counting
         * them again makes the profile banner show e.g. 70,000 outstanding
         * while the authoritative semester plan correctly shows 45,000.
         *
         * Once at least one FeeAssignment exists, treat standalone rows that
         * clearly look like an old generated PLAN as superseded:
         *   - generated from a fee template, or
         *   - part of an installment, or
         *   - generated in a batch.
         *
         * True one-off/manual charges (ID-card fee, internship fee, ad-hoc
         * voucher, etc.) have none of that plan metadata and are still added
         * to the student's outstanding balance.
         */
        if ($assignments->isNotEmpty()) {
            $standalone = $standalone->reject(function ($voucher) {
                return $voucher->fee_template_id !== null
                    || $voucher->installment_no !== null
                    || !empty($voucher->batch_id);
            })->values();
        }

        return new \Illuminate\Database\Eloquent\Collection(
            $owned->concat($standalone)->unique('id')->values()->all()
        );
    }

    /** Current amount genuinely owed, using the same active-plan ownership rule everywhere. */
    public function outstandingBalance(): float
    {
        return (float) round(
            $this->authoritativeVouchers()
                ->reject(fn ($voucher) => $voucher->status === 'cancelled')
                ->sum(fn ($voucher) => $voucher->balance()),
            0
        );
    }

    /**
     * Live fee status for directory badges. The saved fee_status can be stale
     * when vouchers are cleared through a different collection/credit route.
     * Never mark a student with no issued vouchers as fully paid.
     */
    public function currentFeeStatus(): string
    {
        $vouchers = $this->authoritativeVouchers()
            ->reject(fn ($voucher) => $voucher->status === 'cancelled');

        if ($vouchers->isEmpty()) {
            return $this->fee_status ?: 'unpaid';
        }

        $outstanding = $vouchers->sum(fn ($voucher) => $voucher->balance());
        if ($outstanding <= 0.005) {
            return 'paid';
        }

        return $vouchers->sum(fn ($voucher) => max(0, (float) $voucher->paid_amount)) > 0
            ? 'partial' : 'unpaid';
    }

    /** Persist the same authoritative fee state that Accounts and Admin display. */
    public function synchronizeFeeStatus(): void
    {
        $status = $this->currentFeeStatus();
        if ($this->fee_status !== $status) {
            $this->forceFill(['fee_status' => $status])->save();
        }
    }

    public function pendingDues()
    {
        return $this->outstandingBalance();
    }

    /** Alias kept for EMS views/controllers that referenced the old pendingFees() name. */
    public function pendingFees(): float
    {
        return $this->outstandingBalance();
    }

    public function totalPaid()
    {
        // Keep paid totals on the same source-of-truth set as outstanding
        // balance so superseded legacy plan rows cannot be counted twice.
        return round((float) $this->authoritativeVouchers()->sum('paid_amount'), 0);
    }

    /** Whether this student is currently held from exam-fee vouchers due to low attendance */
    public function isAttendanceHeld(): bool
    {
        if (! Setting::flag('attendance_hold_enabled', false)) return false;
        if ($this->attendance_percentage === null) return false;
        $threshold = (float) Setting::get('attendance_threshold', 75);
        return (float) $this->attendance_percentage < $threshold;
    }

    /**
     * Fee summary bucketed for the student/parent/teacher dashboards
     * ("Student Fee without Heads" view): Previous Balance, Active/Due,
     * Upcoming, and Paid history. No fee-head breakdown is included here
     * on purpose — this is the simplified at-a-glance view.
     *
     * - previous_balance: unpaid/partial/overdue vouchers whose due date has
     *   already passed (arrears carried forward). Highlighted in the UI.
     * - active: the nearest unpaid voucher(s) currently payable (due today
     *   or within the next {activeWindowDays} days). Highlighted in the UI.
     * - upcoming: unpaid vouchers due further ahead in the future.
     * - previous_paid: already-settled vouchers, most recent first.
     */
    public function feeSummary(int $activeWindowDays = 7): array
    {
        $today = \Carbon\Carbon::today();
        $dueSoonEnd = $today->copy()->addDays($activeWindowDays);

        // Status/date buckets must be computed from the COMPLETE voucher:
        // a single printed voucher can be represented by several fee-head
        // rows.  Separating a paid fee-head from its unpaid sibling makes the
        // same number incorrectly appear both "Paid" and "Upcoming".
        $groups = $this->authoritativeVouchers()
            ->reject(fn ($v) => $v->status === 'cancelled')
            ->groupBy(fn ($v) => $v->voucher_no !== null && (string) $v->voucher_no !== ''
                ? 'voucher:'.(string) $v->voucher_no : 'row:'.$v->id);

        $groupBalance = static fn ($lines) => round((float)
            $lines->sum(fn ($v) => $v->balance()), 2);
        $firstOutstandingDue = static fn ($lines) => $lines
            ->filter(fn ($v) => $v->balance() > 0)
            ->pluck('due_date')->filter()->sort()->first();
        $flatten = static function ($voucherGroups): \Illuminate\Database\Eloquent\Collection {
            return new \Illuminate\Database\Eloquent\Collection(
                $voucherGroups->flatMap(fn ($lines) => $lines->values())->values()->all()
            );
        };

        $owing = $groups
            ->filter(fn ($lines) => $groupBalance($lines) > 0.005)
            ->sortBy(fn ($lines) => $firstOutstandingDue($lines)?->getTimestamp() ?? PHP_INT_MAX);
        $previousGroups = $owing->filter(function ($lines) use ($today, $firstOutstandingDue) {
            $due = $firstOutstandingDue($lines);
            return $due && $due->lt($today);
        });
        $remainingGroups = $owing->filter(fn ($lines, $key) => !$previousGroups->has($key));
        $activeGroups = $remainingGroups->filter(function ($lines) use ($today, $dueSoonEnd, $firstOutstandingDue) {
            $due = $firstOutstandingDue($lines);
            return $due && $due->between($today, $dueSoonEnd);
        });
        $upcomingGroups = $remainingGroups->filter(fn ($lines, $key) => !$activeGroups->has($key));
        $paidGroups = $groups
            ->filter(fn ($lines) => $groupBalance($lines) <= 0.005)
            ->sortByDesc(fn ($lines) => $lines->pluck('due_date')->filter()->sortDesc()->first()?->getTimestamp() ?? 0)
            ->take(10);

        $previousBalance = $flatten($previousGroups);
        $active = $flatten($activeGroups);
        $upcoming = $flatten($upcomingGroups);
        $previousPaid = $flatten($paidGroups);

        foreach ([$previousBalance, $active, $upcoming, $previousPaid] as $rows) {
            $rows->loadMissing('feeAssignment');
        }

        return [
            'previous_balance'       => $previousBalance,
            'previous_balance_total' => round((float) $previousBalance->sum(fn ($v) => $v->balance()), 2),
            'active'                 => $active,
            'upcoming'               => $upcoming,
            'previous_paid'          => $previousPaid,
        ];
    }
}

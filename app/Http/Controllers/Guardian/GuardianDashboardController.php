<?php
namespace App\Http\Controllers\Guardian;

use App\Http\Controllers\Controller;
use App\Models\Examination;
use App\Models\ParentModel;
use App\Models\Payment;
use App\Models\Student;
use App\Models\FeeAssignment;
use App\Models\Voucher;

class GuardianDashboardController extends Controller
{
    public function index()
    {
        $user   = auth()->user();
        $parent = ParentModel::where('user_id', $user->id)->first();

        $children = $parent
            ? Student::where('parent_id', $parent->id)
                     ->with('schoolClass')
                     ->get()
            : collect();

        return view('guardian.dashboard', compact('children'));
    }

    public function childDetail(Student $student)
    {
        $this->authorizeChild($student);

        $student->load('schoolClass', 'program', 'academicYear', 'feeAssignments');

        $attendance  = $student->attendancePercentage();
        $examResults = $student->examResults()->with('examination')->latest()->take(10)->get();
        $feeSummary  = $student->feeSummary();
        $pendingCurrentSemesterTotal = $this->currentSemesterPendingTotal($feeSummary);

        // Fee data now comes from the Accounts module's vouchers (single source
        // of truth) — paid installments (with payment method + submit date) and
        // still-due installments for the semester currently in progress.
        $feeBreakdown = $this->currentSemesterFeeBreakdown($student);

        $upcomingExams = $student->isActiveEnrollment() && $student->school_class_id
            ? Examination::with('subject', 'teacher')
                ->where('school_class_id', $student->school_class_id)
                ->where('exam_date', '>=', now()->toDateString())
                ->where('status', '!=', 'cancelled')
                ->orderBy('exam_date')->orderBy('start_time')
                ->take(5)->get()
            : collect();

        // Previous (already-held) examinations for this child's class, most
        // recent first, with the result attached where one has been entered.
        $previousExams = $student->school_class_id
            ? Examination::with('subject', 'teacher')
                ->where('school_class_id', $student->school_class_id)
                ->where('exam_date', '<', now()->toDateString())
                ->where('status', '!=', 'cancelled')
                ->orderByDesc('exam_date')->orderByDesc('start_time')
                ->take(10)->get()
            : collect();
        $previousExamResults = $student->examResults()->with('examination')->get()->keyBy('examination_id');

        return view('guardian.child', compact(
            'student', 'attendance', 'examResults', 'feeBreakdown', 'upcomingExams', 'feeSummary',
            'pendingCurrentSemesterTotal', 'previousExams', 'previousExamResults'
        ));
    }

    /** Full "Upcoming / Due / Previous balance" fee view for one of this parent's own children. */
    public function childFees(Student $student)
    {
        $this->authorizeChild($student);

        $student->load('schoolClass', 'program', 'academicYear');

        $feeAssignments = $student->feeAssignments()->with('feeTemplate')->latest()->get();
        $vouchers       = $student->vouchers()->with('feeHead')->latest('due_date')->get();
        $feeSummary     = $student->feeSummary();

        // Total still owed for the semester currently in progress / next up —
        // previous arrears + what's due now + the nearest upcoming semester's
        // balance. Deliberately excludes the further-out "estimated" semesters.
        $pendingCurrentSemesterTotal = $this->currentSemesterPendingTotal($feeSummary);

        // Previous (already-held) examinations for this child's class, most
        // recent first, with the result attached where one has been entered.
        $previousExams = $student->school_class_id
            ? Examination::with('subject', 'teacher')
                ->where('school_class_id', $student->school_class_id)
                ->where('exam_date', '<', now()->toDateString())
                ->where('status', '!=', 'cancelled')
                ->orderByDesc('exam_date')->orderByDesc('start_time')
                ->take(20)->get()
            : collect();
        $previousExamResults = $student->examResults()->with('examination')->get()->keyBy('examination_id');

        return view('guardian.fees', compact(
            'student', 'feeAssignments', 'vouchers', 'feeSummary',
            'pendingCurrentSemesterTotal', 'previousExams', 'previousExamResults'
        ));
    }

    /**
     * Sum of everything still owed for the semester the student is
     * currently in (or about to start): overdue arrears + amounts due now
     * + the nearest not-yet-due semester's balance. Deliberately leaves out
     * the further-out semesters that the fee widget only shows as a flat
     * estimate, since those aren't "this semester"'s bill.
     */
    protected function currentSemesterPendingTotal(array $feeSummary): float
    {
        $previousBalance = collect($feeSummary['previous_balance'] ?? []);
        $active          = collect($feeSummary['active'] ?? []);
        $upcoming        = collect($feeSummary['upcoming'] ?? []);

        $label   = fn ($v) => $v->feeAssignment ? $v->feeAssignment->termLabelFor($v->cycle_no) : 'Other / One-off Vouchers';
        $sortKey = fn ($v) => $v->cycle_no ?? 9999;

        $runningLabel   = $upcoming->sortBy($sortKey)->map($label)->first();
        $runningUpcoming = $runningLabel
            ? $upcoming->filter(fn ($v) => $label($v) === $runningLabel)
            : collect();

        return round(
            $previousBalance->sum(fn ($v) => $v->balance())
            + $active->sum(fn ($v) => $v->balance())
            + $runningUpcoming->sum(fn ($v) => $v->balance()),
            2
        );
    }

    /** Full exam schedule + all results for one of this parent's own children. */
    public function childExams(Student $student)
    {
        $this->authorizeChild($student);

        $exams = $student->school_class_id
            ? Examination::with('subject', 'teacher')
                ->where('school_class_id', $student->school_class_id)
                ->where('status', '!=', 'cancelled')
                ->orderByDesc('exam_date')->orderBy('start_time')
                ->get()
            : collect();

        $results = $student->examResults()->with('examination.subject')->get()->keyBy('examination_id');

        return view('guardian.child-exams', compact('student', 'exams', 'results'));
    }

    /** Download/print the complete semester fee plan for an authorized child. */
    public function feePlan(FeeAssignment $feeAssignment)
    {
        $this->authorizeChild($feeAssignment->student);

        return app(\App\Services\FeePlanSummary::class)->render($feeAssignment);
    }

    /** Downloadable/printable voucher for one of this parent's own children. */
    public function voucher(Voucher $voucher)
    {
        $this->authorizeChild($voucher->student);

        $voucher->load('student.schoolClass', 'feeHead', 'payments');
        return view('vouchers.receipt', compact('voucher'));
    }

    /**
     * Splits the semester currently in progress into two lists — installments
     * already paid (with the payment's method + submit date) and installments
     * still due for that same semester — plus whether the whole semester is
     * settled (so the UI can say "you're proceeding to the next semester").
     *
     * "Currently in progress" = the lowest cycle/semester that still has an
     * unpaid balance; if every generated cycle is fully paid, falls back to
     * the most advanced cycle that has any vouchers at all.
     */
    protected function currentSemesterFeeBreakdown(Student $student): array
    {
        $empty = ['cycleLabel' => null, 'paid' => collect(), 'upcoming' => collect(), 'allSettled' => false, 'nextCycleLabel' => null];

        $vouchers = $student->vouchers()->with('feeAssignment')->get();
        if ($vouchers->isEmpty()) return $empty;

        $balance = fn ($v) => $v->balance();
        $byCycle = $vouchers->groupBy(fn ($v) => $v->cycle_no ?? 0)->sortKeys();

        $openCycleGroup = $byCycle->first(fn ($group) => $group->sum($balance) > 0);
        $currentCycleNo = $openCycleGroup ? $openCycleGroup->first()->cycle_no : $byCycle->keys()->last();

        $cycleVouchers  = $byCycle->get($currentCycleNo, collect());
        $feeAssignment  = $cycleVouchers->first()?->feeAssignment;
        $cycleLabel     = $feeAssignment ? $feeAssignment->termLabelFor($currentCycleNo) : "Semester {$currentCycleNo}";
        $partsPerCycle  = $feeAssignment?->parts_per_cycle;
        $partLabel      = fn ($partNo) => $partsPerCycle ? "Installment {$partNo} of {$partsPerCycle}" : "Installment {$partNo}";

        $groupedByVoucher = $cycleVouchers->groupBy('voucher_no');

        $paid = $groupedByVoucher
            ->filter(fn ($lines) => $lines->sum($balance) <= 0)
            ->map(function ($lines) use ($partLabel) {
                $first   = $lines->first();
                $latestPayment = Payment::whereIn('voucher_id', $lines->pluck('id'))->orderByDesc('paid_at')->first();
                return (object) [
                    'voucher_no'     => $first->voucher_no,
                    'amount'         => round($lines->sum('paid_amount'), 2),
                    'method'         => $latestPayment->method ?? '—',
                    'submitted_at'   => $latestPayment->paid_at ?? null,
                    'part_label'     => $partLabel($first->part_no),
                    'representative' => $first,
                ];
            })
            ->sortBy(fn ($row) => $row->representative->part_no)
            ->values();

        $upcoming = $groupedByVoucher
            ->filter(fn ($lines) => $lines->sum($balance) > 0)
            ->map(function ($lines) use ($partLabel) {
                $first = $lines->first();
                return (object) [
                    'voucher_no'     => $first->voucher_no,
                    'amount'         => round($lines->sum(fn ($v) => $v->balance()), 2),
                    'due_date'       => $first->due_date,
                    'part_label'     => $partLabel($first->part_no),
                    'representative' => $first,
                ];
            })
            ->sortBy(fn ($row) => $row->representative->part_no)
            ->values();

        $allSettled     = $upcoming->isEmpty() && $paid->isNotEmpty();
        $nextCycleLabel = ($allSettled && $feeAssignment) ? $feeAssignment->termLabelFor($currentCycleNo + 1) : null;

        return compact('cycleLabel', 'paid', 'upcoming', 'allSettled', 'nextCycleLabel');
    }

    /** Aborts with 403 unless the given student belongs to the logged-in parent. */
    protected function authorizeChild(Student $student): void
    {
        $parent = ParentModel::where('user_id', auth()->id())->first();
        abort_if(! $parent || $student->parent_id !== $parent->id, 403, 'You can only view your own children\'s records.');
    }
}

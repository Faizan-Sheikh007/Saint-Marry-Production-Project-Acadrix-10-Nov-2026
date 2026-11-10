<?php
namespace App\Http\Controllers\Student;
use App\Http\Controllers\Controller;
use App\Models\CourseEnrollment;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Examination;
use App\Models\FeeAssignment;
use App\Models\Voucher;
use App\Models\Payment;
use App\Services\StudentFeeLedger;
use App\Services\VoucherPaymentHistory;
use App\Models\Timetable;

class StudentDashboardController extends Controller
{
    public function index()
    {
        $student = auth()->user()->student;
        if (!$student) {
            return view("student.dashboard", [
                "student"             => null,
                "enrollments"         => collect(),
                "pendingAssignments"  => collect(),
                "recentGrades"        => collect(),
                "pendingFees"         => 0,
                "paidCreditedFees"    => 0,
                "overdueFees"         => 0,
                "attendancePct"       => 0,
                "todaysClasses"       => collect(),
                "todayName"           => now()->format('l'),
                "upcomingExams"       => collect(),
                "subjectAssignments"  => collect(),
            ]);
        }

        $enrollments = CourseEnrollment::with(["course.teacher", "course.subject"])
            ->where("student_id", $student->id)
            ->where("status", "active")
            ->get();

        $pendingAssignments = Assignment::visibleToStudent($student)->whereHas(
                "course.enrollments",
                fn($q) => $q->where("student_id", $student->id)
            )
            ->where("status", "active")
            ->where(fn ($q) => $q->whereNull("due_date")->orWhere("due_date", ">", now()))
            ->whereDoesntHave("submissions", fn($q) => $q->where("student_id", $student->id))
            ->with("course")
            ->orderBy("due_date")
            ->take(5)
            ->get();

        $recentGrades = AssignmentSubmission::with("assignment.course")
            ->where("student_id", $student->id)
            ->whereNotNull("marks_obtained")
            ->latest()
            ->take(5)
            ->get();

        // Use the exact same authoritative, current-plan ledger as My Fees.
        // Paid / Credited is the amount allocated to fees (not unused credit);
        // pending is the outstanding balance, including future installments.
        // Superseded and cancelled voucher rows are not counted twice.
        $feeVouchers = $student->authoritativeVouchers();
        $feeVouchers->loadMissing('feeHead', 'feeAssignment.feeTemplate');
        $feeTotals = StudentFeeLedger::summarize($feeVouchers, now())['totals'];
        $paidCreditedFees = $feeTotals['paid'];
        $pendingFees = $feeTotals['pending'];
        $overdueFees = $feeTotals['overdue'];
        $attendancePct = $student->attendancePercentage();

        $todayName = now()->format('l');

        $todaysClasses = $student->school_class_id
            ? Timetable::with('subject', 'teacher')
                ->where('school_class_id', $student->school_class_id)
                ->where('day', $todayName)
                ->orderBy('start_time')
                ->get()
            : collect();

        $upcomingExams = $student->school_class_id
            ? Examination::with('subject', 'teacher')
                ->where('school_class_id', $student->school_class_id)
                ->where('exam_date', '>=', now()->toDateString())
                ->where('status', '!=', 'cancelled')
                ->orderBy('exam_date')->orderBy('start_time')
                ->take(5)->get()
            : collect();

        $subjectAssignments = $student->school_class_id
            ? \App\Models\ClassSubject::with('subject', 'teacher')
                ->where('school_class_id', $student->school_class_id)
                ->get()
                ->sortBy(fn ($row) => $row->subject->name ?? '')
                ->values()
            : collect();

        return view("student.dashboard", compact(
            "student", "enrollments", "pendingAssignments",
            "recentGrades", "pendingFees", "paidCreditedFees", "overdueFees", "attendancePct",
            "todaysClasses", "todayName", "upcomingExams",
            "subjectAssignments"
        ));
    }

    /** LMS: assignment grades, quiz results and overall marks per enrolled course. */
    public function grades()
    {
        $student = auth()->user()->student;
        abort_unless($student, 404, 'Your student profile is not linked yet.');

        $enrollments = CourseEnrollment::with('course')
            ->where('student_id', $student->id)
            ->get();

        $assignmentGrades = AssignmentSubmission::with('assignment.course')
            ->where('student_id', $student->id)
            ->whereNotNull('marks_obtained')
            ->latest()
            ->get();

        $quizResults = \App\Models\QuizAttempt::with('quiz.course')
            ->where('student_id', $student->id)
            ->where('status', 'submitted')
            ->latest()
            ->get();

        // Overall percentage per course = average of (assignment %s + quiz %s) for that course.
        $overall = $enrollments->mapWithKeys(function ($enrollment) use ($assignmentGrades, $quizResults) {
            $courseId = $enrollment->course_id;
            $aPcts = $assignmentGrades->filter(fn($s) => $s->assignment && $s->assignment->course_id === $courseId)
                ->map(fn($s) => $s->assignment->total_marks > 0 ? $s->marks_obtained / $s->assignment->total_marks * 100 : null)
                ->filter();
            $qPcts = $quizResults->filter(fn($a) => $a->quiz && $a->quiz->course_id === $courseId)
                ->pluck('percentage');
            $all = $aPcts->merge($qPcts);
            return [$courseId => $all->isEmpty() ? null : round($all->avg(), 1)];
        });

        return view('student.grades', compact(
            'student', 'enrollments', 'assignmentGrades', 'quizResults', 'overall'
        ));
    }

    /** Full exam schedule + published results for this student's class. */
    public function exams()
    {
        $student = auth()->user()->student;
        abort_unless($student, 404, 'Your student profile is not linked yet.');

        $exams = $student->school_class_id
            ? Examination::with('subject', 'teacher')
                ->where('school_class_id', $student->school_class_id)
                ->where('status', '!=', 'cancelled')
                ->orderByDesc('exam_date')->orderBy('start_time')
                ->get()
            : collect();

        $results = $student->examResults()->with('examination.subject')->get()->keyBy('examination_id');

        return view('student.exams', compact('student', 'exams', 'results'));
    }

    /** Read-only list of this student's own installment plans + vouchers (Accounts module). */
    public function fees()
    {
        $student = auth()->user()->student;
        abort_unless($student, 404);

        $feeAssignments = $student->feeAssignments()->with('feeTemplate', 'vouchers')->latest()->get();
        $vouchers       = $student->vouchers()
            ->with('feeHead', 'feeAssignment.feeTemplate', 'payments')
            ->latest('due_date')->get();
        // Reuse these scoped, eager-loaded relations in feeSummary() and
        // authoritativeVouchers() to avoid repeating the same fee queries.
        $student->setRelation('feeAssignments', $feeAssignments);
        $student->setRelation('vouchers', $vouchers);
        $feeSummary = $student->feeSummary();

        // Current balances use active fee-plan ownership (no superseded rows).
        // The audit-style payment history still includes all historic receipts.
        $authoritative = $student->authoritativeVouchers();
        $authoritative->loadMissing('feeHead', 'feeAssignment.feeTemplate');
        $feeLedger = StudentFeeLedger::summarize($authoritative, now());
        $payments = Payment::with('voucher')->whereIn('voucher_id', $vouchers->pluck('id'))
            ->latest('id')->get();
        $paymentHistory = VoucherPaymentHistory::forStudent($vouchers, $payments);
        $financeCreditTransactions = $student->balanceTransactions()->latest('id')->limit(100)->get();
        $financeAvailableCredit = (float) $student->credit_balance;

        return view('student.fees', compact(
            'student', 'feeAssignments', 'vouchers', 'feeSummary', 'feeLedger', 'paymentHistory',
            'financeCreditTransactions', 'financeAvailableCredit'
        ));
    }

    /** Download/print the complete semester fee plan, not the individual challans. */
    public function feePlan(FeeAssignment $feeAssignment)
    {
        $this->authorizeOwn($feeAssignment->student_id);

        return app(\App\Services\FeePlanSummary::class)->render($feeAssignment);
    }

    public function voucher(Voucher $voucher)
    {
        $this->authorizeOwn($voucher->student_id);

        $voucher->load('student.schoolClass', 'feeHead', 'payments');
        return view('vouchers.receipt', compact('voucher'));
    }

    protected function authorizeOwn(int $studentId): void
    {
        $own = auth()->user()->student;
        abort_if(! $own || $own->id !== $studentId, 403, 'You can only view your own fee records.');
    }
}

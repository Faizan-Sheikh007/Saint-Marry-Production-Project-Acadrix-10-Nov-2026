<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Program;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Accounts-side, read-only views of student and teacher records. Accounts
 * needs to see who a student/teacher is and their financial standing (fee
 * summary, salary) but must never be able to create, edit, or delete an
 * admission or employee record — that stays entirely with Admin/EMS.
 */
class AccountsDirectoryController extends Controller
{
    /**
     * Read-only student directory. Default landing is the "Browse by
     * Session" drill-down (Session tabs -> Program cards -> Student list,
     * all AJAX — see students-index.blade.php / _browse-panel.blade.php).
     * The classic flat, filterable/searchable table (Session -> Program ->
     * Semester/Part cascade, plus Status and a "Group by Program & Session"
     * toggle) is still available on the "All Students" tab, reached by
     * stamping all=1 or using any of its filters/search — mirroring how
     * VoucherController's "Browse by Program" / "All Vouchers" tabs work.
     * Accounts only ever gets read/lookup controls here (View, Print,
     * Export) — creating, editing, or deleting an admission record stays
     * entirely with Admin/EMS.
     */
    public function students(Request $request)
    {
        if ($request->boolean('all') || $request->filled('search') || $request->filled('status')
            || $request->filled('school_class_id') || $request->filled('class') || $request->boolean('grouped')) {
            return $this->studentsTable($request);
        }

        // Step 2 — a program is selected: list its students (optionally scoped to a Session).
        if ($request->filled('program_id') || $request->filled('program')) {
            return $this->studentsProgramList($request);
        }

        // Step 1 (default landing) — browse by Session -> Program.
        return $this->studentsPrograms($request);
    }

    /** "All Students" tab: the classic flat, filterable/searchable table (unchanged behavior). */
    protected function studentsTable(Request $request)
    {
        $query = Student::with(['schoolClass', 'program', 'academicYear', 'feeAssignments.vouchers', 'vouchers']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('student_id', 'like', "%{$search}%")
                  ->orWhere('roll_number', 'like', "%{$search}%");
            });
        }

        if ($request->filled('academic_year')) $query->where('academic_year_id', $request->academic_year);
        if ($request->filled('program_id') || $request->filled('program')) $query->where('program_id', $request->program_id ?: $request->program);
        if ($request->filled('school_class_id') || $request->filled('class')) $query->where('school_class_id', $request->school_class_id ?: $request->class);
        if ($request->filled('session')) $query->where('session', $request->session);
        if ($request->filled('status')) $query->where('status', $request->status);

        $academicYears   = AcademicYear::with(['schoolClasses.program'])->ordered()->get();
        $filterStructure = $this->buildStudentFilterStructure($academicYears);

        if ($request->boolean('grouped')) {
            $students = $query->orderBy('program_id')->orderBy('academic_year_id')->orderBy('name')->get();

            $grouped = $students->groupBy(function ($student) {
                $programName = $student->program->name ?? 'No Program';
                $yearName    = $student->academicYear->name ?? ($student->session ?: 'No Session');
                return "{$programName}||{$yearName}";
            })->sortKeys();

            return view('accounts.students-index', ['mode' => 'table'] + compact('academicYears', 'filterStructure', 'grouped'))
                ->with('students', $students);
        }

        $students = $query->orderBy('name')->paginate(20)->withQueryString();

        return view('accounts.students-index', ['mode' => 'table'] + compact('students', 'academicYears', 'filterStructure'));
    }

    /**
     * Step 1 of "Browse by Session": Session tabs + Program cards, each
     * showing a student count and pending-balance badge, optionally scoped
     * to the selected Session (Academic Year). AJAX requests (session tab /
     * program card clicks) get back only the panel markup so the page
     * never has to reload.
     */
    protected function studentsPrograms(Request $request)
    {
        [$academicYears, $selectedYearId, $selectedYear] = $this->browseSessionContext($request);

        $studentCounts = Student::query()
            ->when($selectedYearId, fn ($q) => $q->where('academic_year_id', $selectedYearId))
            ->select('program_id', DB::raw('count(*) as cnt'))
            ->groupBy('program_id')->pluck('cnt', 'program_id');

        $authoritativePendingByProgram = Student::query()
            ->when($selectedYearId, fn ($q) => $q->where('academic_year_id', $selectedYearId))
            ->with(['feeAssignments.vouchers', 'vouchers'])
            ->get()
            ->groupBy('program_id')
            ->map(fn ($students) => round($students->sum(fn (Student $student) => $student->outstandingBalance()), 0));

        $programs = Program::orderBy('name')->get()->map(function ($p) use ($studentCounts, $authoritativePendingByProgram) {
            $p->student_count = (int) ($studentCounts[$p->id] ?? 0);
            $p->pending_total = (float) ($authoritativePendingByProgram[$p->id] ?? 0);
            return $p;
        });

        $data = [
            'mode' => 'programs', 'programs' => $programs,
            'academicYears' => $academicYears, 'selectedYearId' => $selectedYearId, 'selectedYear' => $selectedYear,
            'bulkInstallmentStructure' => $this->buildStudentFilterStructure($academicYears),
        ];

        if ($request->ajax()) {
            return view('accounts.students._browse-panel', $data);
        }

        return view('accounts.students-index', $data);
    }

    /** Step 2 of "Browse by Session": students within the selected program, optionally scoped to a Session. */
    protected function studentsProgramList(Request $request)
    {
        $program = Program::findOrFail($request->program_id ?: $request->program);

        [$academicYears, $selectedYearId, $selectedYear] = $this->browseSessionContext($request);

        $query = Student::where('program_id', $program->id)->with(['schoolClass', 'program', 'academicYear', 'feeAssignments.vouchers', 'vouchers']);
        if ($selectedYearId) {
            $query->where('academic_year_id', $selectedYearId);
        }
        if ($request->filled('student_search')) {
            $s = $request->student_search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('student_id', 'like', "%{$s}%")
                  ->orWhere('roll_number', 'like', "%{$s}%");
            });
        }
        $students = $query->orderBy('name')->get();

        $data = [
            'mode' => 'students', 'program' => $program, 'students' => $students,
            'academicYears' => $academicYears, 'selectedYearId' => $selectedYearId, 'selectedYear' => $selectedYear,
            'bulkInstallmentStructure' => $this->buildStudentFilterStructure($academicYears),
        ];

        if ($request->ajax()) {
            return view('accounts.students._browse-panel', $data);
        }

        return view('accounts.students-index', $data);
    }

    /**
     * Shared Session-tab context (Session -> Program -> Student browse) —
     * the list of Sessions (with an active student count each, for the tab
     * badges) plus which one, if any, is currently selected.
     */
    private function browseSessionContext(Request $request): array
    {
        $academicYears = AcademicYear::with('schoolClasses.program')->ordered()->get();

        $yearCounts = Student::query()
            ->select('academic_year_id', DB::raw('count(*) as cnt'))
            ->groupBy('academic_year_id')->pluck('cnt', 'academic_year_id');

        $academicYears->each(fn ($y) => $y->student_count = (int) ($yearCounts[$y->id] ?? 0));

        $selectedYearId = $request->filled('academic_year_id') ? (int) $request->academic_year_id : null;
        $selectedYear   = $selectedYearId ? $academicYears->firstWhere('id', $selectedYearId) : null;

        return [$academicYears, $selectedYearId, $selectedYear];
    }

    /**
     * JSON-ready Session -> Program -> Semester/Part cascade for the
     * Students filter bar. Kept identical to StudentController's version so
     * accounts staff see only the Program/Semester options actually linked
     * to the chosen Session.
     */
    private function buildStudentFilterStructure($academicYears): array
    {
        $structure = [];

        foreach ($academicYears as $year) {
            $classes = $year->schoolClasses
                ->filter(fn ($class) => $class->program)
                ->sortBy(fn ($class) => [
                    $class->program->name,
                    $class->term_number ?? 999,
                    $class->section ?? '',
                    $class->name,
                ])
                ->values();

            $programs = $classes->groupBy('program_id')->map(function ($programClasses) {
                $program = $programClasses->first()->program;
                $terms = $programClasses->map(function ($class) {
                    return [
                        'id'      => $class->id,
                        'name'    => $class->section_label,
                        'term_no' => $class->term_number,
                    ];
                })->values()->all();

                return [
                    'id'    => $program->id,
                    'name'  => $program->name,
                    'terms' => $terms,
                ];
            })->sortBy('name')->values()->all();

            $structure[(string) $year->id] = [
                'name'     => $year->name,
                'programs' => $programs,
            ];
        }

        return $structure;
    }

    /** Letterhead-style printable roster — reuses the same sheet as Admin's Students print. */
    public function studentsPrint(Request $request)
    {
        $query = Student::with('schoolClass', 'program', 'academicYear');

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('student_id', 'like', "%{$request->search}%")
                  ->orWhere('roll_number', 'like', "%{$request->search}%");
            });
        }

        if ($request->filled('academic_year')) $query->where('academic_year_id', $request->academic_year);
        if ($request->filled('program_id') || $request->filled('program')) $query->where('program_id', $request->program_id ?: $request->program);
        if ($request->filled('school_class_id') || $request->filled('class')) $query->where('school_class_id', $request->school_class_id ?: $request->class);
        if ($request->filled('status')) $query->where('status', $request->status);

        $students = $query->orderBy('program_id')->orderBy('name')->get();

        $filters = [
            'search'  => $request->search,
            'session' => $request->academic_year ? AcademicYear::find($request->academic_year)?->name : null,
            'program' => ($request->program_id ?: $request->program) ? Program::find($request->program_id ?: $request->program)?->name : null,
            'class'   => ($request->school_class_id ?: $request->class) ? SchoolClass::find($request->school_class_id ?: $request->class)?->section_label : null,
            'status'  => $request->status ? ucfirst($request->status) : null,
        ];

        return view('students.print', compact('students', 'filters'));
    }

    /** CSV export of the currently-filtered student list — mirrors Admin's Students export. */
    public function studentsExport(Request $request)
    {
        $query = Student::with('schoolClass', 'program', 'academicYear');

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('student_id', 'like', "%{$request->search}%")
                  ->orWhere('roll_number', 'like', "%{$request->search}%");
            });
        }

        if ($request->filled('academic_year')) $query->where('academic_year_id', $request->academic_year);
        if ($request->filled('program_id') || $request->filled('program')) $query->where('program_id', $request->program_id ?: $request->program);
        if ($request->filled('school_class_id') || $request->filled('class')) $query->where('school_class_id', $request->school_class_id ?: $request->class);
        if ($request->filled('status')) $query->where('status', $request->status);

        $students = $query->orderBy('program_id')->orderBy('name')->get();

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="students_' . now()->format('Y-m-d') . '.csv"',
        ];

        return response()->streamDownload(function () use ($students) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Student ID', 'Name', 'Program', 'Academic Year', 'Semester / Part', 'Section', 'Roll No', 'Guardian', 'Phone', 'Student Status', 'Pending Dues']);
            foreach ($students as $s) {
                fputcsv($out, [
                    $s->student_id,
                    $s->name,
                    $s->program->name ?? '',
                    $s->academicYear->name ?? '',
                    $s->schoolClass->name ?? '',
                    $s->section,
                    $s->roll_number,
                    $s->guardian_name,
                    $s->phone,
                    $s->statusLabel(),
                    $s->pendingDues(),
                ]);
            }
            fclose($out);
        }, 'students_' . now()->format('Y-m-d') . '.csv', $headers);
    }

    /** Read-only student profile + full fee summary (finance scope only). */
    public function studentShow(Student $student)
    {
        $student->load('schoolClass', 'program', 'academicYear', 'feeAssignments.feeTemplate');
        $vouchers   = $student->vouchers()
            ->with('feeHead', 'payments', 'feeAssignment.feeTemplate', 'feeAssignment.student.program')
            ->latest('due_date')
            ->get();
        $payments   = \App\Models\Payment::with('voucher')
            ->whereIn('voucher_id', $vouchers->pluck('id'))
            ->latest('id')->get();
        $paymentHistory = \App\Services\VoucherPaymentHistory::forStudent($vouchers, $payments);
        $paymentHistoryTotal = round((float) $paymentHistory->sum('received_amount'), 2);
        $paymentHistoryVoucherTotal = round((float) $paymentHistory->sum('voucher_amount'), 2);
        // One row per actual receipt (not per allocated voucher). Credit applied
        // later to a voucher is excluded to avoid counting received money twice.
        $extraReceipts = $student->balanceTransactions()
            ->where('type', 'receipt')->where('amount', '>', 0)->get();
        $receivedPayments = \App\Services\StudentReceivedPaymentHistory::forStudent($payments, $extraReceipts);
        $receivedPaymentsTotal = round((float) $receivedPayments->sum('amount'), 2);
        // Preserve the exact amounts of historic student-level collection
        // actions (e.g. Rs 22,000 + 3,000 + 200) from the original audit log.
        // Informational only: never add audit amounts to receipt/fee totals.
        $originalPaymentActions = \App\Services\LegacyStudentPaymentCollectionAudit::forStudent($student);
        // Semester/Part-by-semester breakdown (same cycle math as the admin
        // "Vouchers & Installments" browse view) so each installment shows
        // under its own semester card with a single voucher number.
        $program     = $student->program;
        $assignments = $student->feeAssignments()
            ->with(['vouchers.feeHead'])
            ->orderBy('cycle_start')->orderBy('id')
            ->get();

        $totalCycles = $program ? ($program->actualTermCount() ?? $program->duration_terms) : null;
        $totalCycles = $totalCycles ?: max(1, $assignments->max('cycle_end') ?? 1);
        $totalCycles = min((int) $totalCycles, 20);

        $cycleOwner = [];
        foreach ($assignments as $asg) {
            for ($c = $asg->cycle_start; $c <= $asg->cycle_end; $c++) {
                $cycleOwner[$c] = $asg;
            }
        }

        $standaloneVouchers = $student->vouchers()->whereNull('fee_assignment_id')->with('feeHead')->orderBy('due_date')->get();

        // Reuse the same authoritative current-voucher set as the student and
        // admin portals.  Old/superseded plan rows remain in PAYMENT history,
        // but are excluded from the current amount owed and due-date buckets.
        $student->setRelation('vouchers', $vouchers);
        $student->setRelation('feeAssignments', $assignments);
        $authoritativeVouchers = $student->authoritativeVouchers();
        $authoritativeVouchers->loadMissing('feeHead', 'feeAssignment.feeTemplate');
        $feeLedger = \App\Services\StudentFeeLedger::summarize($authoritativeVouchers, now());
        $feeSummary = $student->feeSummary();
        $outstandingTotal = $feeLedger['totals']['pending'];
        $overdueTotal = $feeLedger['totals']['overdue'];
        $scheduledTotal = $feeLedger['totals']['due_soon'] + $feeLedger['totals']['upcoming'];

        // "Semester Pending Fee" — unlike $outstandingTotal (every unpaid
        // voucher across every semester), this is just the earliest
        // not-fully-paid semester/part, i.e. the one the student is
        // currently sitting in. Shown up top next to Receive Payment so
        // accounts staff don't have to scroll to the semester grid below
        // to see what's due for the current term specifically.
        $currentSemesterLabel   = null;
        $currentSemesterPending = 0;
        foreach ($cycleOwner as $cycle => $asg) {
            $cycleVouchers = $asg->vouchersForCycle($cycle)
                ->reject(fn ($v) => $v->status === 'cancelled');
            if ($cycleVouchers->isEmpty()) {
                continue;
            }
            $pending = round($cycleVouchers->sum(fn ($v) => $v->balance()), 0);
            if ($pending > 0) {
                $currentSemesterLabel   = $asg->termLabelFor($cycle);
                $currentSemesterPending = $pending;
                break;
            }
        }

        $creditOverview = app(\App\Services\StudentCreditOverview::class)->forStudent($student, $cycleOwner, $totalCycles);

        return view('accounts.students-show', compact(
            'student', 'vouchers', 'feeSummary', 'payments', 'paymentHistory',
            'receivedPayments', 'receivedPaymentsTotal', 'originalPaymentActions',
            'paymentHistoryTotal', 'paymentHistoryVoucherTotal', 'outstandingTotal',
            'overdueTotal', 'scheduledTotal', 'feeLedger',
            'assignments', 'totalCycles', 'cycleOwner', 'standaloneVouchers',
            'currentSemesterLabel', 'currentSemesterPending', 'creditOverview'
        ));
    }

    /**
     * Set/change this student's Ledger Number — a manual cross-reference
     * into the physical/legacy ledger register. Accounts-owned field, kept
     * separate from the admission record EMS manages.
     */
    public function updateLedgerNumber(Request $request, Student $student)
    {
        $validated = $request->validate([
            'ledger_number' => 'nullable|string|max:50',
        ]);

        $student->update(['ledger_number' => $validated['ledger_number'] ?: null]);

        \App\Models\AuditLog::record(
            'updated', 'Student', $student->id,
            "Set ledger number for {$student->name} to " . ($student->ledger_number ?: '(cleared)')
        );

        return redirect()->route('accounts.students.show', $student)->with('success', 'Ledger number updated.');
    }

    /** Clear this student's Ledger Number. */
    public function destroyLedgerNumber(Student $student)
    {
        $student->update(['ledger_number' => null]);

        \App\Models\AuditLog::record('updated', 'Student', $student->id, "Cleared ledger number for {$student->name}");

        return redirect()->route('accounts.students.show', $student)->with('success', 'Ledger number cleared.');
    }

    /** JSON student search for Accounts-side pickers (e.g. the Receive Payment screen's extra-payment search). */
    public function studentsLookup(Request $request)
    {
        $q = trim((string) $request->get('q'));
        if ($q === '') {
            return response()->json([]);
        }

        $students = Student::with('schoolClass', 'program')
            ->where('status', 'active')
            ->where(function ($query) use ($q) {
                $query->where('student_id', 'like', "%{$q}%")
                      ->orWhere('name', 'like', "%{$q}%");
            })
            ->limit(10)
            ->get();

        return response()->json($students->map(function (Student $s) {
            return [
                'credit_balance' => (float) $s->credit_balance,
                'id'           => $s->id,
                'name'         => $s->name,
                'student_id'   => $s->student_id,
                'class_name'   => $s->schoolClass->name ?? null,
                'program_name' => $s->program->name ?? null,
            ];
        }));
    }

    /** Read-only teacher directory. */
    public function teachers(Request $request)
    {
        $query = Teacher::where('status', 'active');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('employee_id', 'like', "%{$search}%");
            });
        }

        $teachers = $query->orderBy('name')->paginate(20)->withQueryString();

        return view('accounts.teachers-index', compact('teachers'));
    }

    /** Read-only teacher profile — includes salary (view-only, per Accounts scope). */
    public function teacherShow(Teacher $teacher)
    {
        return view('accounts.teachers-show', compact('teacher'));
    }
}

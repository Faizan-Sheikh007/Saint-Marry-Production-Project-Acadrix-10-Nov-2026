<?php

namespace App\Http\Controllers;

use App\Models\FeeHead;
use App\Models\FeeTemplate;
use App\Models\InstallmentDateDefault;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Voucher;
use App\Models\Payment;
use App\Models\AuditLog;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VoucherController extends Controller
{
    use \App\Traits\BuildsAcademicCascade;

    public function index(Request $request)
    {
        $this->applyFines();

        // The classic searchable/filterable flat table is still available —
        // reached via the "All Vouchers" tab (which stamps all=1 on every
        // request it makes) or as soon as any of its filters are used.
        if ($request->boolean('all') || $request->filled('search') || $request->filled('status')
            || $request->filled('fee_state') || $request->filled('fee_head_id') || $request->filled('date_from') || $request->filled('date_to')
            || $request->filled('session') || $request->filled('school_class_id')) {
            return $this->indexTable($request);
        }

        // Step 3 — a student is selected: semester/part-wise installment view.
        if ($request->filled('student_id')) {
            return $this->indexStudent($request);
        }

        // Step 2 — a program is selected: list its students.
        if ($request->filled('program_id')) {
            return $this->indexProgramStudents($request);
        }

        // Step 1 (default landing) — browse by Session -> Program.
        return $this->indexPrograms($request);
    }

    /**
     * Step 1: Session tabs + Program cards with student / voucher /
     * pending-balance counts, optionally scoped to the selected Session
     * (Academic Year). AJAX requests (session tab / program card clicks)
     * get back only the panel markup so the page never has to reload.
     */
    protected function indexPrograms(Request $request)
    {
        [$academicYears, $selectedYearId, $selectedYear] = $this->browseSessionContext($request);

        $studentIds = Student::query()
            ->when($selectedYearId, fn ($q) => $q->where('academic_year_id', $selectedYearId))
            ->pluck('id');

        $agg = DB::table('vouchers')
            ->join('students', 'students.id', '=', 'vouchers.student_id')
            ->whereIn('vouchers.student_id', $studentIds)
            ->select(
                'students.program_id',
                DB::raw('COUNT(*) as voucher_count'),
                DB::raw("SUM(CASE WHEN vouchers.status IN ('unpaid','partial','overdue') THEN (vouchers.amount - vouchers.discount_amount - vouchers.paid_amount) ELSE 0 END) as pending_total")
            )
            ->groupBy('students.program_id')
            ->get()->keyBy('program_id');

        $studentCounts = Student::query()
            ->when($selectedYearId, fn ($q) => $q->where('academic_year_id', $selectedYearId))
            ->select('program_id', DB::raw('count(*) as cnt'))
            ->groupBy('program_id')->pluck('cnt', 'program_id');

        // The raw voucher aggregate above is useful for counts, but a money
        // balance must follow the same active-plan ownership rule as the
        // student detail screen. Eager loading keeps this to a small fixed
        // number of queries instead of one query per student.
        $authoritativePendingByProgram = Student::query()
            ->when($selectedYearId, fn ($q) => $q->where('academic_year_id', $selectedYearId))
            ->with(['feeAssignments.vouchers', 'vouchers'])
            ->get()
            ->groupBy('program_id')
            ->map(fn ($students) => round($students->sum(fn (Student $student) => $student->outstandingBalance()), 0));

        $programQuery = \App\Models\Program::orderBy('name');
        if ($selectedYearId) {
            $programQuery->forAcademicYear($selectedYearId);
        }

        $programs = $programQuery->get()->map(function ($p) use ($agg, $studentCounts, $authoritativePendingByProgram) {
            $row = $agg->get($p->id);
            $p->student_count = (int) ($studentCounts[$p->id] ?? 0);
            $p->voucher_count = (int) ($row->voucher_count ?? 0);
            $p->pending_total = (float) ($authoritativePendingByProgram[$p->id] ?? 0);
            return $p;
        });

        // Exact Session -> Program -> Semester/Part hierarchy for the filter
        // bar. Options are driven by academic_year_school_class, never by a
        // flat list of every class in the database.
        $filterStructure = $this->buildAcademicCascade($academicYears);

        $data = [
            'mode' => 'programs', 'programs' => $programs, 'filterStructure' => $filterStructure,
            'academicYears' => $academicYears, 'selectedYearId' => $selectedYearId, 'selectedYear' => $selectedYear,
        ];

        if ($request->ajax()) {
            return view('vouchers._browse-panel', $data);
        }

        return view('vouchers.index', $data);
    }

    /** Step 2: students in the selected program (optionally scoped to a Session), with a quick fee-status badge each. */
    protected function indexProgramStudents(Request $request)
    {
        $program = \App\Models\Program::findOrFail($request->program_id);

        [$academicYears, $selectedYearId, $selectedYear] = $this->browseSessionContext($request);

        $query = Student::where('program_id', $program->id)->with(['schoolClass', 'feeAssignments.vouchers', 'vouchers']);
        if ($selectedYearId) {
            $query->where('academic_year_id', $selectedYearId);
        }
        if ($request->filled('student_search')) {
            $s = $request->student_search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")->orWhere('student_id', 'like', "%{$s}%");
            });
        }
        $students = $query->orderBy('name')->get();

        $agg = DB::table('vouchers')
            ->whereIn('student_id', $students->pluck('id'))
            ->select(
                'student_id',
                DB::raw('COUNT(*) as voucher_count'),
                DB::raw("SUM(CASE WHEN status IN ('unpaid','partial','overdue') THEN (amount - discount_amount - paid_amount) ELSE 0 END) as pending_total")
            )
            ->groupBy('student_id')->get()->keyBy('student_id');

        $students->each(function ($s) use ($agg) {
            $row = $agg->get($s->id);
            $s->voucher_count = (int) ($row->voucher_count ?? 0);
            $s->pending_total = $s->outstandingBalance();
        });

        $filterStructure = $this->buildAcademicCascade($academicYears);

        $data = [
            'mode' => 'students', 'program' => $program, 'students' => $students,
            'academicYears' => $academicYears, 'selectedYearId' => $selectedYearId, 'selectedYear' => $selectedYear,
            'filterStructure' => $filterStructure,
        ];

        if ($request->ajax()) {
            return view('vouchers._browse-panel', $data);
        }

        return view('vouchers.index', $data);
    }

    /**
     * Shared Session-tab context (Session -> Program -> Student browse) for
     * both Step 1 and Step 2 above — the list of Sessions (with an active
     * student count each, for the tab badges) plus which one, if any, is
     * currently selected.
     */
    private function browseSessionContext(Request $request): array
    {
        $academicYears = $this->academicCascadeYears();

        $yearCounts = Student::query()
            ->select('academic_year_id', DB::raw('count(*) as cnt'))
            ->groupBy('academic_year_id')->pluck('cnt', 'academic_year_id');

        $academicYears->each(fn ($y) => $y->student_count = (int) ($yearCounts[$y->id] ?? 0));

        $selectedYearId = $request->filled('academic_year_id') ? (int) $request->academic_year_id : null;
        $selectedYear   = $selectedYearId ? $academicYears->firstWhere('id', $selectedYearId) : null;

        return [$academicYears, $selectedYearId, $selectedYear];
    }

    /** Step 3: one student, broken down semester-by-semester (Part/Semester 1 .. end). */
    protected function indexStudent(Request $request)
    {
        $student = Student::with('schoolClass', 'program', 'academicYear')->findOrFail($request->student_id);
        $program = $student->program;

        $assignments = $student->feeAssignments()
            ->with(['vouchers.feeHead', 'feeTemplate', 'parentAssignment'])
            ->orderBy('cycle_start')->orderBy('id')
            ->get();

        $standalone = $student->vouchers()
            ->whereNull('fee_assignment_id')
            ->with('feeHead')
            ->orderBy('due_date')
            ->get();

        $totalCycles = $program ? ($program->actualTermCount() ?? $program->duration_terms) : null;
        $totalCycles = $totalCycles ?: max(1, $assignments->max('cycle_end') ?? 1);
        $totalCycles = min((int) $totalCycles, 20); // sane display cap

        // Which assignment "owns" each semester/cycle number, for the 1..end grid below.
        $cycleOwner = [];
        foreach ($assignments as $asg) {
            for ($c = $asg->cycle_start; $c <= $asg->cycle_end; $c++) {
                $cycleOwner[$c] = $asg;
            }
        }

        // Needed for the "Add a Fee Head to this Semester" picker inside each
        // semester card's management form.
        $feeHeads = FeeHead::where('is_active', true)->orderBy('name')->get();

        // One source of truth for the balance banner. This deliberately
        // follows the same latest-assignment-per-cycle ownership rule as
        // $cycleOwner, so superseded fee plans cannot be counted twice.
        $studentPending = $student->outstandingBalance();
        $creditOverview = app(\App\Services\StudentCreditOverview::class)->forStudent($student, $cycleOwner, $totalCycles);

        // The page-wide "Bulk Generate" modal's Session -> Program ->
        // Semester/Part pickers need this on every mode, this deep-linked
        // single-student view included.
        $academicYears   = $this->academicCascadeYears();
        $filterStructure = $this->buildAcademicCascade($academicYears);

        return view('vouchers.index', [
            'mode' => 'student', 'program' => $program, 'student' => $student,
            'assignments' => $assignments, 'standalone' => $standalone,
            'totalCycles' => $totalCycles, 'cycleOwner' => $cycleOwner,
            'feeHeads' => $feeHeads, 'studentPending' => $studentPending, 'creditOverview' => $creditOverview,
            'academicYears' => $academicYears, 'filterStructure' => $filterStructure,
        ]);
    }

    /** The "All Vouchers" tab — same filters as before, but the results are now
     *  grouped Program -> Student -> Semester/Year instead of one flat table,
     *  so an accountant can drill down the same way the Browse tab works while
     *  still being able to search/filter globally first. */
    protected function indexTable(Request $request)
    {
        $query = Voucher::with('student.schoolClass', 'student.program', 'student.academicYear', 'feeHead', 'feeAssignment');

        if ($request->status)     $query->where('status', $request->status);
        if ($request->fee_state === 'pending') {
            $query->whereIn('status', ['unpaid', 'partial', 'overdue']);
        } elseif ($request->fee_state === 'paid') {
            $query->where('status', 'paid');
        }
        if ($request->student_id) $query->where('student_id', $request->student_id);
        if ($request->fee_head_id) $query->where('fee_head_id', $request->fee_head_id);
        if ($request->filled('program_id')) {
            $query->whereHas('student', fn ($s) => $s->where('program_id', $request->program_id));
        }
        if ($request->filled('academic_year_id')) {
            $query->whereHas('student', fn ($s) => $s->where('academic_year_id', $request->academic_year_id));
        }
        // Backward compatibility for old bookmarked URLs that still carry
        // the legacy free-text session parameter. New UI uses academic_year_id.
        if ($request->filled('session')) {
            $query->whereHas('student', fn ($s) => $s->where('session', $request->session));
        }
        if ($request->filled('school_class_id')) {
            $query->whereHas('student', fn ($s) => $s->where('school_class_id', $request->school_class_id));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('due_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('due_date', '<=', $request->date_to);
        }
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('voucher_no', 'like', "%{$request->search}%")
                  ->orWhereHas('student', fn ($s) => $s->where('name', 'like', "%{$request->search}%")
                                                        ->orWhere('student_id', 'like', "%{$request->search}%"));
            });
        }

        // Grouping happens in the view across the *whole* filtered set (not a
        // page of it), so results are capped rather than paginated — plenty
        // for a filtered accounts-office query; the banner below tells the
        // accountant to narrow further if they hit it.
        $matchingTotal = (clone $query)->count();
        $cap   = 2000;
        $items = $query->orderBy('due_date', 'desc')->limit($cap)->get();

        $feeHeads = FeeHead::where('is_active', true)->orderBy('name')->get();
        $academicYears = $this->academicCascadeYears();
        $filterStructure = $this->buildAcademicCascade($academicYears);

        return view('vouchers.index', ['mode' => 'table', 'matchingTotal' => $matchingTotal, 'cap' => $cap]
            + compact('items', 'feeHeads', 'academicYears', 'filterStructure'));
    }

    /**
     * Export the currently-filtered voucher list as CSV. Reuses exactly the
     * same filters as index() so "Export" always reflects what is on
     * screen — search, status, fee head, program, class, session, and due
     * date range — instead of dumping every voucher regardless of filters.
     */
    public function export(Request $request)
    {
        $query = Voucher::with('student.schoolClass', 'student.program', 'feeHead');

        if ($request->status)     $query->where('status', $request->status);
        if ($request->fee_state === 'pending') {
            $query->whereIn('status', ['unpaid', 'partial', 'overdue']);
        } elseif ($request->fee_state === 'paid') {
            $query->where('status', 'paid');
        }
        if ($request->student_id) $query->where('student_id', $request->student_id);
        if ($request->fee_head_id) $query->where('fee_head_id', $request->fee_head_id);
        if ($request->filled('program_id')) {
            $query->whereHas('student', fn ($s) => $s->where('program_id', $request->program_id));
        }
        if ($request->filled('academic_year_id')) {
            $query->whereHas('student', fn ($s) => $s->where('academic_year_id', $request->academic_year_id));
        }
        if ($request->filled('session')) {
            $query->whereHas('student', fn ($s) => $s->where('session', $request->session));
        }
        if ($request->filled('school_class_id')) {
            $query->whereHas('student', fn ($s) => $s->where('school_class_id', $request->school_class_id));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('due_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('due_date', '<=', $request->date_to);
        }
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('voucher_no', 'like', "%{$request->search}%")
                  ->orWhereHas('student', fn ($s) => $s->where('name', 'like', "%{$request->search}%")
                                                        ->orWhere('student_id', 'like', "%{$request->search}%"));
            });
        }

        $vouchers = $query->latest()->get();

        $header = ['Voucher No', 'Student', 'Student ID', 'Semester / Part', 'Fee Head', 'Title', 'Amount', 'Discount', 'Total', 'Paid', 'Balance', 'Due Date', 'Voucher Status', 'Student Status'];
        $csv = implode(',', $header) . "\n";
        foreach ($vouchers as $v) {
            $row = [
                $v->voucher_no,
                $v->student->name ?? '',
                $v->student->student_id ?? '',
                $v->student->schoolClass->name ?? '',
                $v->feeHead->name ?? '',
                $v->title,
                $v->amount,
                $v->discount_amount,
                $v->total(),
                $v->paid_amount,
                $v->balance(),
                $v->due_date->format('Y-m-d'),
                $v->status,
                \App\Support\StudentStatus::label($v->student?->status),
            ];
            $csv .= implode(',', array_map(fn ($val) => '"' . str_replace('"', '""', $val) . '"', $row)) . "\n";
        }

        $filename = 'vouchers-' . now()->format('Y-m-d') . '.csv';

        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Export a CSV summary (name, ID, class, voucher/pending counts) for a
     * hand-picked list of students — the "Export Selected" bulk action on
     * the program's student list.
     */
    public function exportSelectedStudents(Request $request)
    {
        $validated = $request->validate([
            'student_ids'   => 'required|array|min:1',
            'student_ids.*' => 'exists:students,id',
        ]);

        $students = Student::whereIn('id', $validated['student_ids'])->with('schoolClass', 'program')->orderBy('name')->get();

        $agg = DB::table('vouchers')
            ->whereIn('student_id', $students->pluck('id'))
            ->select(
                'student_id',
                DB::raw('COUNT(*) as voucher_count'),
                DB::raw("SUM(CASE WHEN status IN ('unpaid','partial','overdue') THEN (amount - discount_amount - paid_amount) ELSE 0 END) as pending_total")
            )
            ->groupBy('student_id')->get()->keyBy('student_id');

        $header = ['Student ID', 'Name', 'Program', 'Class/Semester', 'Vouchers', 'Pending Balance', 'Student Status'];
        $csv = implode(',', $header) . "\n";
        foreach ($students as $s) {
            $row = $agg->get($s->id);
            $line = [
                $s->student_id,
                $s->name,
                $s->program->name ?? '',
                $s->schoolClass->name ?? '',
                (int) ($row->voucher_count ?? 0),
                round((float) ($row->pending_total ?? 0), 2),
                $s->statusLabel(),
            ];
            $csv .= implode(',', array_map(fn ($val) => '"' . str_replace('"', '""', $val) . '"', $line)) . "\n";
        }

        $filename = 'selected-students-' . now()->format('Y-m-d_His') . '.csv';

        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function create(Request $request)
    {
        $students  = Student::where('status', 'active')->orderBy('name')->get();
        $feeHeads  = FeeHead::where('is_active', true)->orderBy('name')->get();
        $templates = FeeTemplate::where('is_active', true)->orderBy('name')->get();
        $classes   = SchoolClass::orderBy('name')->get();
        $student   = $request->student_id ? Student::find($request->student_id) : null;
        return view('vouchers.form', compact('students', 'feeHeads', 'templates', 'classes', 'student'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id'  => 'required|exists:students,id',
            'fee_head_id' => 'required|exists:fee_heads,id',
            'title'       => 'nullable|string|max:255',
            'amount'      => 'required|integer|min:0',
            'discount_amount' => 'nullable|integer|min:0',
            'due_date'    => 'required|date',
            'notes'       => 'nullable|string',
            'override_attendance_hold' => 'nullable|boolean',
        ]);

        $student = Student::findOrFail($validated['student_id']);
        $student->assertActiveEnrollment();
        $feeHead = FeeHead::findOrFail($validated['fee_head_id']);

        if ($feeHead->category === 'exam' && $student->isAttendanceHeld() && ! $request->boolean('override_attendance_hold')) {
            return back()->withInput()->with('error',
                "This student's attendance ({$student->attendance_percentage}%) is below the required threshold. Exam-fee voucher blocked by Attendance-linked Fee Hold. Check the override box to issue anyway.");
        }

        $voucher = Voucher::create([
            'voucher_no'      => Voucher::generateVoucherNo(),
            'student_id'      => $student->id,
            'fee_head_id'     => $feeHead->id,
            'title'           => $validated['title'] ?: $feeHead->name,
            'amount'          => $validated['amount'],
            'discount_amount' => $validated['discount_amount'] ?? 0,
            'due_date'        => $validated['due_date'],
            'issued_at'       => now(),
            'status'          => 'unpaid',
            'notes'           => $validated['notes'] ?? null,
        ]);

        AuditLog::record('created', 'Voucher', $voucher->id, "Generated voucher {$voucher->voucher_no} for {$student->name}");

        return redirect()->route('vouchers.show', $voucher)->with('success', 'Voucher generated!');
    }

    /**
     * Bulk-generate vouchers for every active student in a Program +
     * Semester/Part (one Fee Template), or — when "all Programs" is
     * ticked — for every Program's class in the chosen Session that
     * shares the same Semester/Part number (one Fee Template per class).
     *
     * Re-running the same selection for the same period is idempotent:
     * existing unpaid bulk-generated rows are replaced instead of
     * duplicated. Any challan that already has a payment is protected and
     * is never deleted or rewritten.
     */
    public function generateFromTemplate(Request $request)
    {
        $validated = $request->validate([
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'school_class_id'  => 'nullable|exists:school_classes,id',
            'term_no'          => 'nullable|integer|min:1',
            'due_date'         => 'required|date',
            'month'            => 'nullable|string|max:20',
            'installment_no'    => 'nullable|integer|min:1',
            'installment_total' => 'nullable|integer|min:1',
            // If present (from the "Generate Vouchers" bulk action on a
            // selected list of students), scope generation to exactly those
            // students instead of the whole class.
            'student_ids'      => 'nullable|array',
            'student_ids.*'    => 'exists:students,id',
            'after_generate'   => 'nullable|in:download',
        ]);

        $allPrograms = $request->boolean('all_programs');

        if ($allPrograms) {
            if (empty($validated['academic_year_id']) || empty($validated['term_no'])) {
                return redirect()->route('vouchers.index')->with('error', 'Choose a Session and a Semester / Part to generate for all Programs.');
            }

            $year = \App\Models\AcademicYear::with('schoolClasses')->findOrFail($validated['academic_year_id']);
            $classIds = $year->schoolClasses
                ->filter(fn ($class) => $class->term_number === (int) $validated['term_no'])
                ->pluck('id');
        } else {
            if (empty($validated['school_class_id'])) {
                return redirect()->route('vouchers.index')->with('error', 'Select a Program and Semester / Part to generate vouchers for.');
            }

            $classIds = collect([$validated['school_class_id']]);
        }

        $templates = FeeTemplate::with('items.feeHead')
            ->where('is_active', true)
            ->whereIn('school_class_id', $classIds)
            ->get();

        if ($templates->isEmpty()) {
            return redirect()->route('vouchers.index')->with('error', 'No active Fee Template is set up yet for the selected Program & Semester / Part.');
        }

        // Resolve the target students per template up front so we can bail
        // out with one clear message if the whole selection is empty.
        $studentsByTemplate = $templates->mapWithKeys(function ($template) use ($validated) {
            $students = !empty($validated['student_ids'])
                ? Student::whereIn('id', $validated['student_ids'])->where('school_class_id', $template->school_class_id)->where('status', 'active')->get()
                : ($template->school_class_id
                    ? Student::where('school_class_id', $template->school_class_id)->where('status', 'active')->get()
                    : Student::where('status', 'active')->get());

            return [$template->id => $students];
        });

        if ($studentsByTemplate->every(fn ($students) => $students->isEmpty())) {
            return redirect()->route('vouchers.index')->with('error', 'No active students matched the selected Program & Semester / Part.');
        }

        $month = isset($validated['month']) && trim((string) $validated['month']) !== ''
            ? trim((string) $validated['month'])
            : null;

        // A random suffix avoids two bulk runs started within the same second
        // accidentally sharing a batch id. This id is also used by the
        // "Download / Print Generated Vouchers" action after generation, and
        // is shared across every template in this run so one Print/Download
        // covers the whole "all Programs" batch too.
        $batchId = 'BATCH-' . now()->format('ymdHis') . '-' . strtoupper(substr(str_replace('-', '', (string) \Illuminate\Support\Str::uuid()), 0, 8));

        $count         = 0;
        $held          = 0;
        $replaced      = 0;
        $protected     = 0;
        $templateNames = [];

        DB::transaction(function () use ($templates, $studentsByTemplate, $validated, $month, $batchId, &$count, &$held, &$replaced, &$protected, &$templateNames) {
            // A class-wide request has no posted student IDs for the profile
            // middleware to lock. Re-read all selected students under the same
            // ascending lock order before replacing or creating any vouchers.
            $targetIds = $studentsByTemplate->flatten(1)->pluck('id')->unique()->sort()->values();
            $lockedStudents = Student::whereIn('id', $targetIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            foreach ($templates as $template) {
                $students = $studentsByTemplate[$template->id]
                    ->map(fn ($student) => $lockedStudents->get($student->id))
                    ->filter(fn ($student) => $student && $student->isActiveEnrollment())->values();
                if ($students->isEmpty()) {
                    continue;
                }

                $templateNames[] = $template->name;
                $protectedKey = [];

                /*
                 * "Same bulk generation" means the same fee template + target
                 * students + period. Month Label is the preferred period key. If
                 * it is blank, the due-date month becomes the key so another run
                 * in the same billing month replaces the first instead of duplicating it.
                 *
                 * Only rows created by bulk generation (batch_id present) and not
                 * owned by a fee assignment are candidates. This keeps manual and
                 * installment-plan vouchers out of the replacement scope.
                 */
                $existingQuery = Voucher::query()
                    ->where('fee_template_id', $template->id)
                    ->whereIn('student_id', $students->pluck('id'))
                    ->whereNull('fee_assignment_id')
                    ->whereNotNull('batch_id');

                if ($month !== null) {
                    $existingQuery->where('month', $month);
                } else {
                    $periodDate = \Carbon\Carbon::parse($validated['due_date']);
                    $existingQuery->whereNull('month')
                        ->whereYear('due_date', $periodDate->year)
                        ->whereMonth('due_date', $periodDate->month);
                }

                $existing = $existingQuery
                    ->withCount('payments')
                    ->orderBy('student_id')
                    ->orderBy('voucher_no')
                    ->get();

                // Protect a whole challan group if any line in it has money posted
                // against it. We never erase financial history during regeneration.
                $protectedGroups = $existing
                    ->groupBy(fn (Voucher $voucher) => $voucher->student_id . '|' . $voucher->voucher_no)
                    ->filter(function ($group) {
                        return $group->contains(function (Voucher $voucher) {
                            return (int) $voucher->payments_count > 0
                                || (float) $voucher->paid_amount > 0
                                || in_array($voucher->status, ['paid', 'partial'], true);
                        });
                    });

                foreach ($protectedGroups as $group) {
                    $protected += $group->count();
                    foreach ($group as $voucher) {
                        $protectedKey[$voucher->student_id . '|' . $voucher->fee_head_id] = true;
                    }
                }

                $protectedGroupKeys = $protectedGroups->keys()->flip();
                $replaceableIds = $existing
                    ->reject(fn (Voucher $voucher) => $protectedGroupKeys->has($voucher->student_id . '|' . $voucher->voucher_no))
                    ->pluck('id');

                if ($replaceableIds->isNotEmpty()) {
                    $replaced += $replaceableIds->count();
                    Voucher::whereIn('id', $replaceableIds)->delete();
                }

                foreach ($students as $student) {
                    foreach ($template->items as $item) {
                        $studentFeeKey = $student->id . '|' . $item->fee_head_id;

                        // If this exact fee head already belongs to a paid/partial
                        // challan for the same period, keep it and do not duplicate it.
                        if (isset($protectedKey[$studentFeeKey])) {
                            continue;
                        }

                        if ($item->feeHead->category === 'exam' && $student->isAttendanceHeld()) {
                            $held++;
                            continue;
                        }

                        Voucher::create([
                            'voucher_no'      => Voucher::generateVoucherNo(),
                            'student_id'      => $student->id,
                            'fee_head_id'     => $item->fee_head_id,
                            'fee_template_id' => $template->id,
                            'title'           => $item->feeHead->name . ' - ' . $template->name,
                            'month'           => $month,
                            'amount'          => $item->amount,
                            'due_date'        => $validated['due_date'],
                            'issued_at'       => now(),
                            'batch_id'        => $batchId,
                            'installment_no'    => $validated['installment_no'] ?? null,
                            'installment_total' => $validated['installment_total'] ?? null,
                            'status'          => 'unpaid',
                        ]);
                        $count++;
                    }
                }
            }
        });

        $templateLabel = count($templateNames) > 1
            ? count($templateNames) . ' Fee Templates (' . implode(', ', $templateNames) . ')'
            : ($templateNames[0] ?? 'Fee Template');

        AuditLog::record(
            'generated',
            'Voucher',
            null,
            "Bulk generated {$count} vouchers from {$templateLabel}"
                . ($replaced ? "; replaced {$replaced} previous unpaid row(s)" : '')
                . ($protected ? "; kept {$protected} paid/partial row(s)" : '')
                . ($held ? "; {$held} exam-fee row(s) held for low attendance" : '')
        );

        $msg = "{$count} voucher(s) generated.";
        if ($replaced)  $msg .= " {$replaced} previous unpaid voucher row(s) replaced.";
        if ($protected) $msg .= " {$protected} paid/partial voucher row(s) kept unchanged.";
        if ($held)      $msg .= " {$held} exam-fee voucher(s) skipped due to Attendance-linked Fee Hold.";

        if ($count > 0 && ($validated['after_generate'] ?? null) === 'download') {
            return redirect()->route('vouchers.generated-batch.print', $batchId);
        }

        $redirect = redirect()->route('vouchers.index')->with('success', $msg);
        if ($count > 0) {
            $redirect->with('generated_batch_id', $batchId);
        }

        return $redirect;
    }

    /**
     * Students-directory bulk installment printing. Read-only: every result
     * must already exist on that student's stored fee plan, for this exact
     * session/program/semester and installment part.
     */
    public function studentInstallmentOptions(Request $request, \App\Services\BulkInstallmentVoucherSelector $selector)
    {
        $data = $request->validate([
            'academic_year_id' => 'required|integer|exists:academic_years,id',
            'program_id' => 'required|integer|exists:programs,id',
            'school_class_id' => 'required|integer|exists:school_classes,id',
        ]);

        $scope = $selector->scope((int) $data['academic_year_id'], (int) $data['program_id'], (int) $data['school_class_id']);
        return response()->json($selector->options($scope));
    }

    public function printStudentInstallments(Request $request, \App\Services\BulkInstallmentVoucherSelector $selector)
    {
        $data = $request->validate([
            'academic_year_id' => 'required|integer|exists:academic_years,id',
            'program_id' => 'required|integer|exists:programs,id',
            'school_class_id' => 'required|integer|exists:school_classes,id',
            'installment_no' => 'required|integer|min:1|max:255',
        ]);

        $scope = $selector->scope((int) $data['academic_year_id'], (int) $data['program_id'], (int) $data['school_class_id']);
        $part = (int) $data['installment_no'];
        $vouchers = $selector->vouchers($scope, $part);
        abort_if($vouchers->isEmpty(), 404, 'No saved vouchers exist for this installment in the selected semester.');

        $vouchers->load([
            'student.schoolClass', 'student.program.institution', 'student.academicYear',
            'feeHead', 'bankAccount', 'feeAssignment.feeTemplate', 'feeAssignment.student.program',
        ]);

        $batchId = 'Semester ' . $scope['cycle'] . ' - Installment ' . $part;
        $batchTitle = $scope['year']->name . ' | ' . $scope['class']->program->name
            . ' | ' . $scope['class']->section_label . ' | Installment ' . $part;
        $fallbackBankAccounts = \App\Models\BankAccount::active();
        $feeNote = Setting::get('fee_summary_note');
        $declaration = Setting::get('fee_summary_declaration');
        $qrUrls = $vouchers->mapWithKeys(fn (Voucher $voucher) => [
            $voucher->id => $this->voucherQrUrl($voucher),
        ]);

        return view('vouchers.bulk-print', compact(
            'vouchers', 'batchId', 'batchTitle', 'fallbackBankAccounts', 'feeNote', 'declaration', 'qrUrls'
        ));
    }

    /**
     * Print every challan created by one bulk-template generation in a single
     * browser-printable document. The browser's Save as PDF option provides
     * one PDF containing the whole generated batch.
     */
    public function printGeneratedBatch(string $batchId)
    {
        $rows = Voucher::query()
            ->where('batch_id', $batchId)
            ->with([
                'student.schoolClass',
                'student.program.institution',
                'student.academicYear',
                'feeHead',
                'bankAccount',
                'feeAssignment.feeTemplate',
                'feeAssignment.student.program',
            ])
            ->orderBy('student_id')
            ->orderBy('voucher_no')
            ->orderBy('id')
            ->get();

        abort_if($rows->isEmpty(), 404);

        // Several fee-head rows can share one voucher number. Render that
        // challan only once; Voucher::lineItems() supplies the full breakdown.
        $vouchers = $rows
            ->unique(fn (Voucher $voucher) => $voucher->student_id . '|' . $voucher->voucher_no)
            ->values();

        $fallbackBankAccounts = \App\Models\BankAccount::active();
        $feeNote     = Setting::get('fee_summary_note');
        $declaration = Setting::get('fee_summary_declaration');
        $qrUrls      = $vouchers->mapWithKeys(fn (Voucher $voucher) => [
            $voucher->id => $this->voucherQrUrl($voucher),
        ]);

        return view('vouchers.bulk-print', compact(
            'vouchers',
            'batchId',
            'fallbackBankAccounts',
            'feeNote',
            'declaration',
            'qrUrls'
        ));
    }

    /**
     * Split a total fee amount into installments (max limit enforced from settings).
     */
    public function generateInstallments(Request $request)
    {
        $maxInstallments = (int) Setting::get('installment_max', 5);

        $validated = $request->validate([
            'student_id'  => 'required|exists:students,id',
            'fee_head_id' => 'required|exists:fee_heads,id',
            'total_amount' => 'required|numeric|min:1',
            'installments' => 'required|integer|min:2|max:' . max(2, $maxInstallments),
            'first_due_date' => 'required|date',
            'frequency_days'  => 'required|integer|min:1',
            'use_default_installment_dates' => 'nullable|boolean',
        ]);

        $student = Student::findOrFail($validated['student_id']);
        $student->assertActiveEnrollment();
        $feeHead = FeeHead::findOrFail($validated['fee_head_id']);
        $n       = $validated['installments'];
        $each    = round($validated['total_amount'] / $n, 2);
        $remainder = round($validated['total_amount'] - ($each * $n), 2);

        $batchId = 'INST-' . now()->format('ymdHis');
        $due     = \Carbon\Carbon::parse($validated['first_due_date']);

        $cycleNo = 1;
        if ($student->schoolClass && preg_match('/(\d+)/', $student->schoolClass->name, $m)) {
            $cycleNo = (int) $m[1];
        }
        $useDefaults = !$request->exists('use_default_installment_dates')
            || $request->boolean('use_default_installment_dates');
        $defaultDates = $useDefaults
            ? (InstallmentDateDefault::datesForStudentRange($student, $cycleNo, $cycleNo)[$cycleNo] ?? [])
            : [];

        DB::transaction(function () use ($n, $each, $remainder, $due, $validated, $student, $feeHead, $batchId, $defaultDates) {
            $previousDue = null;
            for ($i = 1; $i <= $n; $i++) {
                if (!empty($defaultDates[$i - 1])) {
                    $installmentDue = \Carbon\Carbon::parse($defaultDates[$i - 1]);
                } elseif ($previousDue) {
                    $installmentDue = $previousDue->copy()->addDays($validated['frequency_days']);
                } else {
                    $installmentDue = $due->copy();
                }
                $previousDue = $installmentDue->copy();

                Voucher::create([
                    'voucher_no'        => Voucher::generateVoucherNo(),
                    'student_id'        => $student->id,
                    'fee_head_id'       => $feeHead->id,
                    'title'             => $feeHead->name . " - Installment {$i}/{$n}",
                    'amount'            => $i === $n ? $each + $remainder : $each,
                    'due_date'          => $installmentDue,
                    'issued_at'         => now(),
                    'installment_no'    => $i,
                    'installment_total' => $n,
                    'batch_id'          => $batchId,
                    'status'            => 'unpaid',
                ]);
            }
        });

        AuditLog::record('generated', 'Voucher', null, "Generated {$n}-installment plan for {$student->name} ({$feeHead->name})");

        return redirect()->route('vouchers.index')->with('success', "{$n} installment vouchers generated for {$student->name}.");
    }

    public function show(Voucher $voucher)
    {
        $voucher->load('student.schoolClass', 'student.program', 'student.academicYear', 'feeHead', 'payments', 'feeAssignment');

        // A voucher number can bundle more than one fee head (Tuition +
        // Examination on the same installment, etc.) as separate rows —
        // load the whole group so the page shows one combined amount/
        // balance/payment-history instead of just this one row's slice.
        $lineItems        = $voucher->lineItems();
        $combinedPayments = $voucher->combinedPayments();

        return view('vouchers.show', compact('voucher', 'lineItems', 'combinedPayments'));
    }

    public function edit(Voucher $voucher)
    {
        $feeHeads = FeeHead::where('is_active', true)->orderBy('name')->get();
        return view('vouchers.edit', compact('voucher', 'feeHeads'));
    }

    public function update(Request $request, Voucher $voucher)
    {
        $validated = $request->validate([
            'title'           => 'required|string|max:255',
            'amount'          => 'required|integer|min:0',
            'discount_amount' => 'nullable|integer|min:0',
            'due_date'        => 'required|date',
            'notes'           => 'nullable|string',
        ]);

        $validated['fine_amount'] = 0;
        $voucher->update($validated);
        $this->recalcStatus($voucher);

        AuditLog::record('updated', 'Voucher', $voucher->id, "Updated voucher {$voucher->voucher_no}");

        return redirect()->route('vouchers.show', $voucher)->with('success', 'Voucher updated!');
    }

    public function destroy(Voucher $voucher)
    {
        $no = $voucher->voucher_no;
        $voucher->delete();
        AuditLog::record('deleted', 'Voucher', null, "Deleted voucher {$no}");
        return redirect()->route('vouchers.index')->with('success', 'Voucher deleted!');
    }

    /**
     * Cancels the whole installment — every sibling fee-head line sharing
     * this voucher_no (see Voucher::lineItems()), not just the one row
     * passed in. Accounts now sees/cancels installments as one combined
     * row, and payments.store() already treats the group as one payable
     * unit, so cancel needs to match or the other fee-head lines under
     * the same voucher_no would silently stay active. Already-paid lines
     * are left untouched — cancelling can't undo a recorded payment.
     */
    public function cancel(Voucher $voucher)
    {
        $group = $voucher->lineItems();
        foreach ($group as $row) {
            if ($row->status !== 'cancelled' && $row->status !== 'paid') {
                $row->update(['status' => 'cancelled']);
            }
        }
        AuditLog::record('cancelled', 'Voucher', $voucher->id, "Cancelled voucher {$voucher->voucher_no}");
        return back()->with('success', 'Voucher cancelled.');
    }

    /**
     * Split one unpaid challan into two real voucher numbers without changing
     * the student's total fee. Example: Rs.25,000 -> Rs.10,000 + Rs.15,000.
     * Both resulting voucher numbers remain under the same semester/installment
     * and can be viewed, paid and printed independently.
     */
    public function splitChallan(Request $request, Voucher $voucher)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);
        $requestedAmount = round((float) $validated['amount'], 2);

        $result = DB::transaction(function () use ($voucher, $requestedAmount) {
            // Lock every fee-head line behind this voucher number so two
            // accountants cannot split the same challan at the same time.
            $rows = Voucher::query()
                ->where('student_id', $voucher->student_id)
                ->where('voucher_no', $voucher->voucher_no)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($rows->isEmpty()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'amount' => 'This challan no longer exists.',
                ]);
            }

            if (! $rows->first()->fee_assignment_id) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'amount' => 'Only installment challans that belong to a fee plan can be split here.',
                ]);
            }

            if ($rows->contains(fn (Voucher $row) => $row->status === 'cancelled')) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'amount' => 'A cancelled challan cannot be split.',
                ]);
            }

            $rowIds = $rows->pluck('id');
            $hasActivePayment = Payment::query()
                ->whereIn('voucher_id', $rowIds)
                ->where(function ($q) {
                    $q->whereNull('status')->orWhere('status', '!=', 'rejected');
                })
                ->exists();

            if ($hasActivePayment || $rows->sum(fn (Voucher $row) => (float) $row->paid_amount) > 0) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'amount' => 'This challan already has a payment or a pending online payment, so it cannot be split.',
                ]);
            }

            $representative = $rows->first();
            $currentTotal = round($rows->sum(fn (Voucher $row) => $row->total()), 2);
            if ($currentTotal <= 0.01) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'amount' => 'This challan has no amount available to split.',
                ]);
            }

            // The user enters the total printable first challan amount, including
            // the arrears displayed on the original challan. Arrears are live
            // balances on earlier vouchers and must never be duplicated or
            // rewritten by splitting the current installment.
            $carryForward = round($representative->carryForwardBalance(), 2);
            $payableTotal = round($currentTotal + $carryForward, 2);
            // Allocate the administrator's requested printable amount between
            // original unpaid dues and the installment itself. There is no
            // minimum tied to carried-forward dues.
            if ($requestedAmount <= 0 || $requestedAmount >= $payableTotal) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'amount' => 'Enter an amount greater than zero and less than Rs. ' . number_format($payableTotal, 2) . '.',
                ]);
            }
            $firstCurrentAmount = min($currentTotal, max(0, round($requestedAmount - $carryForward, 2)));
            $firstCarry = round($requestedAmount - $firstCurrentAmount, 2);
            $secondCarry = round($carryForward - $firstCarry, 2);

            $allocations = $this->allocateChallanSplit($rows, $firstCurrentAmount, $currentTotal);
            $newVoucherNo = Voucher::generateVoucherNo();
            $rootVoucherNo = $representative->split_root_voucher_no ?: $representative->voucher_no;

            $existingMaxSequence = (int) Voucher::query()
                ->where('student_id', $representative->student_id)
                ->where('split_root_voucher_no', $rootVoucherNo)
                ->max('split_sequence');

            $currentSequence = (int) ($representative->split_sequence ?: 1);
            $newSequence = max($existingMaxSequence, $currentSequence) + 1;

            foreach ($rows as $index => $row) {
                $first = $allocations[$index];
                $original = [
                    'amount'          => round((float) $row->amount, 2),
                    'fine_amount'     => round((float) $row->fine_amount, 2),
                    'discount_amount' => round((float) $row->discount_amount, 2),
                ];

                $row->update([
                    'amount'                => $first['amount'],
                    'fine_amount'           => $first['fine_amount'],
                    'discount_amount'       => $first['discount_amount'],
                    'paid_amount'           => 0,
                    'split_root_voucher_no' => $rootVoucherNo,
                    'split_sequence'        => $currentSequence,
                    'split_carry_cap'       => $firstCarry,
                ]);
                self::recalcStatus($row->fresh());

                $clone = $row->replicate();
                $clone->voucher_no = $newVoucherNo;
                $clone->amount = max(0, round($original['amount'] - $first['amount'], 2));
                $clone->fine_amount = max(0, round($original['fine_amount'] - $first['fine_amount'], 2));
                $clone->discount_amount = max(0, round($original['discount_amount'] - $first['discount_amount'], 2));
                $clone->paid_amount = 0;
                $clone->issued_at = now();
                $clone->split_root_voucher_no = $rootVoucherNo;
                $clone->split_sequence = $newSequence;
                $clone->split_carry_cap = $secondCarry;
                $clone->save();
                self::recalcStatus($clone->fresh());
            }

            $remainingAmount = round($payableTotal - $requestedAmount, 2);

            AuditLog::record(
                'split',
                'Voucher',
                $representative->id,
                "Split challan {$representative->voucher_no} (Rs. {$payableTotal}, including Rs. {$carryForward} carried forward) into Rs. {$requestedAmount} and {$newVoucherNo} (Rs. {$remainingAmount})"
            );

            return [
                'first_no'  => $representative->voucher_no,
                'first'     => $requestedAmount,
                'second_no' => $newVoucherNo,
                'second'    => $remainingAmount,
            ];
        });

        return back()->with(
            'success',
            'Challan split successfully: ' . $result['first_no'] . ' = Rs. ' . number_format($result['first'], 0)
            . ' and ' . $result['second_no'] . ' = Rs. ' . number_format($result['second'], 0)
            . '. Both challans are now separately printable.'
        );
    }

    /**
     * Proportionally split amount/fine/discount across every fee-head line,
     * then correct rounding so the first challan totals exactly the amount the
     * accountant entered. The second challan gets the exact residual values.
     */
    private function allocateChallanSplit($rows, float $target, float $currentTotal): array
    {
        $ratio = $target / $currentTotal;
        $allocations = [];

        foreach ($rows as $row) {
            $allocations[] = [
                'amount'          => round((float) $row->amount * $ratio, 2),
                'fine_amount'     => round((float) $row->fine_amount * $ratio, 2),
                'discount_amount' => round((float) $row->discount_amount * $ratio, 2),
            ];
        }

        $allocatedTotal = round(collect($allocations)->sum(
            fn (array $part) => $part['amount'] + $part['fine_amount'] - $part['discount_amount']
        ), 2);
        $delta = round($target - $allocatedTotal, 2);

        // Correct the few cents that independent component rounding can add or
        // remove. Prefer base amount, then fine, then discount while never
        // exceeding the original component stored on that fee-head row.
        if (abs($delta) >= 0.01) {
            foreach ($rows as $index => $row) {
                if (abs($delta) < 0.01) break;

                $originalAmount = round((float) $row->amount, 2);
                $originalFine = round((float) $row->fine_amount, 2);
                $originalDiscount = round((float) $row->discount_amount, 2);

                if ($delta > 0) {
                    $take = min($delta, max(0, $originalAmount - $allocations[$index]['amount']));
                    $allocations[$index]['amount'] = round($allocations[$index]['amount'] + $take, 2);
                    $delta = round($delta - $take, 2);

                    if ($delta > 0) {
                        $take = min($delta, max(0, $originalFine - $allocations[$index]['fine_amount']));
                        $allocations[$index]['fine_amount'] = round($allocations[$index]['fine_amount'] + $take, 2);
                        $delta = round($delta - $take, 2);
                    }

                    if ($delta > 0) {
                        $take = min($delta, max(0, $allocations[$index]['discount_amount']));
                        $allocations[$index]['discount_amount'] = round($allocations[$index]['discount_amount'] - $take, 2);
                        $delta = round($delta - $take, 2);
                    }
                } else {
                    $need = abs($delta);
                    $take = min($need, max(0, $allocations[$index]['amount']));
                    $allocations[$index]['amount'] = round($allocations[$index]['amount'] - $take, 2);
                    $delta = round($delta + $take, 2);

                    if ($delta < 0) {
                        $need = abs($delta);
                        $take = min($need, max(0, $allocations[$index]['fine_amount']));
                        $allocations[$index]['fine_amount'] = round($allocations[$index]['fine_amount'] - $take, 2);
                        $delta = round($delta + $take, 2);
                    }

                    if ($delta < 0) {
                        $need = abs($delta);
                        $capacity = max(0, $originalDiscount - $allocations[$index]['discount_amount']);
                        $take = min($need, $capacity);
                        $allocations[$index]['discount_amount'] = round($allocations[$index]['discount_amount'] + $take, 2);
                        $delta = round($delta + $take, 2);
                    }
                }
            }
        }

        if (abs($delta) >= 0.01) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'amount' => 'The requested amount could not be allocated safely across this challan. Please choose a different split amount.',
            ]);
        }

        return $allocations;
    }

    /**
     * Bulk "Paid Fee Receipts" export — every voucher that received a
     * payment within the selected range (a single day, or a Mon–Sun
     * week), rendered as one printable document (one receipt per page)
     * that Accounts/Admin can print or "Download / Print PDF" via the
     * browser print dialog, same pattern as the single receipt view.
     *
     * range=day   + date=YYYY-MM-DD                → that single day
     * range=week  + week_start=YYYY-MM-DD (any day) → Mon–Sun of that week
     */
    public function bulkReceipts(Request $request)
    {
        $validated = $request->validate([
            'range'      => 'required|in:day,week',
            'date'       => 'nullable|date',
            'week_start' => 'nullable|date',
        ]);

        if ($validated['range'] === 'day') {
            $day  = \Carbon\Carbon::parse($validated['date'] ?? now())->startOfDay();
            $from = $day->copy();
            $to   = $day->copy()->endOfDay();
        } else {
            $anchor = \Carbon\Carbon::parse($validated['week_start'] ?? now());
            $from   = $anchor->copy()->startOfWeek();
            $to     = $anchor->copy()->endOfWeek();
        }

        // Every voucher that had at least one (verified) payment land in the range.
        $voucherIds = Payment::query()
            ->where(function ($q) { $q->whereNull('status')->orWhere('status', 'verified'); })
            ->whereBetween('paid_at', [$from, $to])
            ->pluck('voucher_id')
            ->unique();

        $rows = Voucher::whereIn('id', $voucherIds)
            ->with('student.schoolClass', 'student.program', 'student.academicYear', 'feeHead', 'payments')
            ->orderBy('voucher_no')
            ->get();

        // A voucher number can bundle more than one fee head as separate
        // rows — if two sibling rows both received a payment in this
        // range, that's still ONE receipt to print, not two, so collapse
        // to one representative row per (student, voucher_no).
        $vouchers = $rows->unique(fn (Voucher $v) => $v->student_id . '|' . $v->voucher_no)->values();

        return view('vouchers.bulk-receipts', [
            'vouchers' => $vouchers,
            'from'     => $from,
            'to'       => $to,
            'range'    => $validated['range'],
        ]);
    }

    public function receipt(Voucher $voucher)
    {
        $voucher->load('student.schoolClass', 'student.program', 'student.academicYear', 'feeHead', 'payments');
        return view('vouchers.receipt', compact('voucher'));
    }

    public function print(Voucher $voucher)
    {
        $voucher->load(
            'student.schoolClass',
            'student.program.institution',
            'student.academicYear',
            'feeHead',
            'bankAccount',
            'feeAssignment.feeTemplate',
            'feeAssignment.student.program'
        );

        $lineItems    = $voucher->lineItems();
        $bankAccounts = $voucher->bankAccount ? collect([$voucher->bankAccount]) : \App\Models\BankAccount::active();
        $feeNote      = \App\Models\Setting::get('fee_summary_note');
        $declaration  = \App\Models\Setting::get('fee_summary_declaration');
        $qrUrl        = $this->voucherQrUrl($voucher);

        return view('vouchers.print', compact('voucher', 'lineItems', 'bankAccounts', 'feeNote', 'declaration', 'qrUrl'));
    }

    /**
     * QR target used by the modern triplicate challan. Fee-plan vouchers use
     * the same signed public verification route as the installment-plan print;
     * standalone vouchers fall back to their authenticated detail page.
     */
    protected function voucherQrUrl(Voucher $voucher): string
    {
        $feeAssignment = $voucher->feeAssignment;

        if (! $feeAssignment) {
            return route('vouchers.show', $voucher);
        }

        $lastDue = Voucher::query()
            ->where('fee_assignment_id', $feeAssignment->id)
            ->max('due_date');

        $expiresAt = $lastDue
            ? \Carbon\Carbon::parse($lastDue)->addMonths(2)
            : now()->addYear();

        // Old vouchers should still produce a usable QR when reprinted.
        if ($expiresAt->isPast()) {
            $expiresAt = now()->addMonths(2);
        }

        return \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'fee-assignments.verify',
            $expiresAt,
            ['feeAssignment' => $feeAssignment->id]
        );
    }

    /** Recalculate paid/unpaid/partial status for a voucher after edits. */
    public static function recalcStatus(Voucher $voucher): void
    {
        if ($voucher->status === 'cancelled') return;
        $balance = $voucher->balance();
        if ($balance <= 0) {
            $voucher->status = 'paid';
        } elseif ($voucher->paid_amount > 0) {
            $voucher->status = 'partial';
        } elseif ($voucher->due_date->isPast()) {
            $voucher->status = 'overdue';
        } else {
            $voucher->status = 'unpaid';
        }
        $voucher->save();
    }

    /**
     * Used by the Receive Payment screen: accountant types a voucher
     * number (full or partial) and this resolves it to the student's name
     * and the amount currently owed on it, so nothing has to be looked up
     * or re-typed by hand. Only unpaid/partial/overdue vouchers are
     * returned — an already-paid or cancelled voucher isn't something to
     * receive money against.
     */
    public function lookup(Request $request)
    {
        $q = trim((string) $request->get('voucher_no'));
        if ($q === '') {
            return response()->json([]);
        }

        // The "Search" button on Receive Payment looks up one specific
        // voucher number exactly, across every status, so the accountant
        // gets an accurate "not found" / "already paid" message instead of
        // a silently empty autocomplete list.
        $exact = $request->boolean('exact');

        $query = Voucher::with('student', 'feeHead');

        if ($exact) {
            $query->where('voucher_no', $q);
        } else {
            $query->whereIn('status', ['unpaid', 'partial', 'overdue'])
                  ->where('voucher_no', 'like', "%{$q}%");
        }

        $vouchers = $query->orderBy('due_date')->limit(30)->get();

        // A voucher number can bundle more than one fee head as separate
        // rows behind the scenes — collapse each voucher_no down to ONE
        // combined result (combined total/paid/balance, fee heads joined
        // in the title) so Accounts always sees and pays against the same
        // single amount shown everywhere else, never just one row's slice.
        $grouped = $vouchers->groupBy('voucher_no')->map(function ($group) {
            /** @var \Illuminate\Support\Collection<int, Voucher> $group */
            $first = $group->first();
            $status = $group->contains(fn (Voucher $v) => $v->status !== 'paid' && $v->status !== 'cancelled')
                ? ($group->contains('status', 'overdue') ? 'overdue' : ($group->contains(fn ($v) => (float) $v->paid_amount > 0) ? 'partial' : 'unpaid'))
                : ($group->every(fn (Voucher $v) => $v->status === 'cancelled') ? 'cancelled' : 'paid');

            $carryForward = round($first->carryForwardBalance(), 0);
            if ($carryForward > 0 && $status === 'paid') {
                $status = $first->due_date && $first->due_date->lt(now()->startOfDay()) ? 'overdue' : 'unpaid';
            }

            return [
                'id'            => $first->id,
                'voucher_no'    => $first->voucher_no,
                'title'         => $group->map(fn (Voucher $v) => $v->feeHead->name ?? $v->title)->unique()->implode(', '),
                'status'        => $status,
                'student_id'    => $first->student_id,
                'student_name'  => $first->student->name,
                'student_code'  => $first->student->student_id,
                'total'         => round($group->sum(fn (Voucher $v) => $v->total()), 0),
                'paid_amount'   => round((float) $group->sum('paid_amount'), 0),
                // For a due/current installment this is the amount Accounts
                // can actually collect now: its own remaining balance plus
                // any unpaid earlier installment balance carried forward.
                'balance'       => round($first->payableBalanceWithCarryForward(), 0),
                'carry_forward' => $carryForward,
                'due_date'      => optional($first->due_date)->format('d M, Y'),
            ];
        })->values();

        if (!$exact) {
            $grouped = $grouped->filter(fn ($v) => $v['balance'] > 0)->values();
        }

        return response()->json($grouped);
    }

    /** Monetary overdue fines are intentionally disabled. */
    protected function applyFines(): void
    {
        // Keep overdue status/date tracking, but never add money to a voucher.
    }

}
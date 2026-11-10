<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\FeeAssignment;
use App\Models\Payment;
use App\Models\Program;
use App\Models\SchoolClass;
use App\Models\FeeReportRemark;
use App\Models\Student;
use App\Models\Voucher;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Admin Reporting Center — read-only cross-module reports for the school
 * head: scholarships/financial aid, pending & paid fee, student directory
 * search, and attendance, each sliceable monthly/yearly and by program,
 * with a professional printable (PDF-via-print) layout for every report.
 */
class ReportingController extends Controller
{
    /** Resolve a [from, to] Carbon range from period/month/year/from/to request inputs. */
    private function resolveRange(Request $request, string $default = 'month'): array
    {
        $period = $request->input('period', $default);

        if ($period === 'monthly') {
            $month = $request->input('month', now()->format('Y-m'));
            $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
            return [$start->copy()->startOfDay(), $start->copy()->endOfMonth()->endOfDay(), $period];
        }

        if ($period === 'yearly') {
            $year  = (int) $request->input('year', now()->year);
            $start = Carbon::create($year, 1, 1)->startOfDay();
            return [$start, $start->copy()->endOfYear()->endOfDay(), $period];
        }

        if ($period === 'range' && $request->filled('from') && $request->filled('to')) {
            return [Carbon::parse($request->from)->startOfDay(), Carbon::parse($request->to)->endOfDay(), $period];
        }

        return [null, null, 'all'];
    }

    private function programOptions()
    {
        return Program::where('is_active', true)->orderBy('name')->get();
    }

    // ─────────────────────────────────────────────────────────────────
    // 1) Scholarship / Financial Aid Report
    // ─────────────────────────────────────────────────────────────────
    /** Status label for aid granted at admission but not yet applied to a fee plan. */
    private const AID_AWAITING_PLAN = 'Granted (Awaiting Fee Plan)';

    public function scholarship(Request $request)
    {
        [$from, $to, $period] = $this->resolveRange($request, 'all');
        $programId = $request->input('program_id', 'all');
        $search = trim((string) $request->input('search', ''));

        $academicYearId = $request->input('academic_year_id', 'all');
        $academicYears = \App\Models\AcademicYear::orderByDesc('id')->get();
        $rows = $this->scholarshipRows($from, $to, $programId, $search, $academicYearId);

        $byProgram = $rows->groupBy('program')->map(function ($grp) {
            $allocated = $grp->where('status', 'Allocated');
            return [
                'students'          => $grp->pluck('student.id')->unique()->count(),
                'scholarship'       => round($grp->sum('scholarship_amount'), 2),
                'financial_aid'     => round($grp->sum('financial_aid_amount'), 2),
                'total_relief'      => round($grp->sum('relief_total'), 2),
                'avg_relief_amount' => $allocated->count() ? round($allocated->avg('relief_total'), 2) : 0,
            ];
        });

        $allocated = $rows->where('status', 'Allocated');

        $summary = [
            'students'            => $rows->pluck('student.id')->unique()->count(),
            'total_scholarship'   => round($rows->sum('scholarship_amount'), 2),
            'total_financial_aid' => round($rows->sum('financial_aid_amount'), 2),
            'total_relief'        => round($rows->sum('relief_total'), 2),
            'avg_relief_amount'   => $allocated->count() ? round($allocated->avg('relief_total'), 2) : 0,
            'awaiting_plan'       => $rows->where('status', self::AID_AWAITING_PLAN)->count(),
        ];

        $programs = $this->programOptions();

        return view('admin.reporting.scholarship', compact(
            'rows', 'byProgram', 'summary', 'programs', 'programId', 'period', 'from', 'to', 'search', 'academicYears', 'academicYearId'
        ));
    }

    /**
     * Build a student-level Scholarship + Financial Aid report from fee plans.
     * scholarship_amount and financial_aid_amount are stored separately on the
     * FeeAssignment so finance staff can see exactly which type of relief was
     * granted. Students who requested assistance during admission but do not
     * have an allocated fee plan yet remain visible as "Awaiting Fee Plan".
     */
    private function scholarshipRows($from, $to, $programId, string $search = '', $academicYearId = 'all')
    {
        $assignmentQuery = FeeAssignment::with(['student.program', 'student.schoolClass', 'student.academicYear', 'feeTemplate'])
            ->where(function ($q) {
                $q->where('scholarship_amount', '>', 0)
                  ->orWhere('financial_aid_amount', '>', 0);
            });

        if ($from && $to) $assignmentQuery->whereBetween('first_due_date', [$from, $to]);
        if ($programId !== 'all') $assignmentQuery->whereHas('student', fn ($q) => $q->where('program_id', $programId));
        if ($academicYearId !== 'all' && $academicYearId !== '') $assignmentQuery->whereHas('student', fn ($q) => $q->where('academic_year_id', $academicYearId));
        if ($search !== '') {
            $assignmentQuery->whereHas('student', function ($q) use ($search) {
                $q->where(function ($sq) use ($search) {
                    $sq->where('name', 'like', "%{$search}%")
                       ->orWhere('student_id', 'like', "%{$search}%")
                       ->orWhere('father_name', 'like', "%{$search}%")
                       ->orWhere('phone', 'like', "%{$search}%");
                });
            });
        }

        $assignments = $assignmentQuery
            ->orderByRaw('(scholarship_amount + financial_aid_amount) DESC')
            ->get();

        $allocatedRows = $assignments->map(function ($a) {
            $scholarship = round((float) $a->scholarship_amount, 2);
            $financialAid = round((float) $a->financial_aid_amount, 2);
            return [
                'student'              => $a->student,
                'program'              => $a->student->program->name ?? '—',
                'total'                => $a->total_amount,
                'scholarship_amount'   => $scholarship,
                'financial_aid_amount' => $financialAid,
                'relief_total'         => round($scholarship + $financialAid, 2),
                'cycle'                => $a->cycleRangeLabel(),
                'date'                 => $a->first_due_date,
                'type'                 => $a->student->financial_aid_type,
                'notes'                => $a->student->financial_aid_notes,
                'status'               => 'Allocated',
                'fee_assignment'       => $a,
            ];
        });

        $allocatedStudentIds = $assignments->pluck('student_id')->unique();

        $awaitingQuery = Student::where('financial_aid_requested', true)
            ->whereNotIn('id', $allocatedStudentIds)
            ->with('program', 'schoolClass', 'academicYear');

        if ($programId !== 'all') $awaitingQuery->where('program_id', $programId);
        if ($academicYearId !== 'all' && $academicYearId !== '') $awaitingQuery->where('academic_year_id', $academicYearId);
        if ($from && $to) $awaitingQuery->whereBetween('admission_date', [$from, $to]);
        if ($search !== '') {
            $awaitingQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('student_id', 'like', "%{$search}%")
                  ->orWhere('father_name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $awaitingRows = $awaitingQuery->get()->map(function ($s) {
            return [
                'student'              => $s,
                'program'              => $s->program->name ?? '—',
                'total'                => null,
                'scholarship_amount'   => 0,
                'financial_aid_amount' => 0,
                'relief_total'         => 0,
                'cycle'                => '—',
                'date'                 => $s->admission_date,
                'type'                 => $s->financial_aid_type,
                'notes'                => $s->financial_aid_notes,
                'status'               => self::AID_AWAITING_PLAN,
                'fee_assignment'       => null,
            ];
        });

        return $allocatedRows->concat($awaitingRows)
            ->sortByDesc(fn ($r) => $r['status'] === 'Allocated' ? $r['relief_total'] : -1)
            ->values();
    }

    public function scholarshipPrint(Request $request)
    {
        $data = $this->scholarshipData($request);
        return view('admin.reporting.print.scholarship', $data);
    }

    private function scholarshipData(Request $request): array
    {
        [$from, $to, $period] = $this->resolveRange($request, 'all');
        $programId = $request->input('program_id', 'all');
        $search = trim((string) $request->input('search', ''));

        $academicYearId = $request->input('academic_year_id', 'all');
        $rows = $this->scholarshipRows($from, $to, $programId, $search, $academicYearId);
        $allocated = $rows->where('status', 'Allocated');
        $program = $programId !== 'all' ? Program::find($programId) : null;

        return [
            'rows'       => $rows,
            'summary'    => [
                'students'            => $rows->pluck('student.id')->unique()->count(),
                'total_scholarship'   => round($rows->sum('scholarship_amount'), 2),
                'total_financial_aid' => round($rows->sum('financial_aid_amount'), 2),
                'total_relief'        => round($rows->sum('relief_total'), 2),
                'avg_relief_amount'   => $allocated->count() ? round($allocated->avg('relief_total'), 2) : 0,
                'awaiting_plan'       => $rows->where('status', self::AID_AWAITING_PLAN)->count(),
            ],
            'program'    => $program,
            'period'     => $period,
            'from'       => $from,
            'to'         => $to,
            'search'     => $search,
        ];
    }

    // ─────────────────────────────────────────────────────────────────
    // 2 & 3) Pending / Paid Fee Reports
    // ─────────────────────────────────────────────────────────────────
    /** Semester/Part options used by both financial fee reports. */
    private function semesterOptions()
    {
        return SchoolClass::with('program')->orderBy('program_id')->orderBy('name')->get();
    }

    /**
     * Build one row per fee assignment.  Voucher rows can contain several fee
     * heads for the same installment, so installment values are aggregated by
     * installment_no before they are exposed to the report.
     */
    private function feePlanRows(Request $request, string $reportType)
    {
        [$from, $to] = $this->resolveRange($request, $reportType === 'paid' ? 'monthly' : 'all');
        $programId   = $request->input('program_id', 'all');
        $semesterId  = $request->input('semester_id', 'all');
        $status      = $request->input('fee_status', $reportType === 'paid' ? 'all' : 'unpaid');
        $search      = trim((string) $request->input('search', ''));

        $query = FeeAssignment::with([
            'student.program', 'student.schoolClass', 'student.academicYear',
            'vouchers.payments',
        ]);

        if ($programId !== 'all') {
            $query->whereHas('student', fn ($q) => $q->where('program_id', $programId));
        }
        if ($semesterId !== 'all') {
            $query->whereHas('student', fn ($q) => $q->where('school_class_id', $semesterId));
        }
        if ($search !== '') {
            $query->whereHas('student', function ($q) use ($search) {
                $q->where(function ($sq) use ($search) {
                    $sq->where('name', 'like', "%{$search}%")
                       ->orWhere('student_id', 'like', "%{$search}%")
                       ->orWhere('father_name', 'like', "%{$search}%");
                });
            });
        }

        // Preserve the old report's period meaning without truncating plan totals.
        if ($from && $to) {
            if ($reportType === 'paid') {
                $query->whereHas('vouchers.payments', function ($q) use ($from, $to) {
                    $q->where('status', '!=', 'rejected')->whereBetween('paid_at', [$from, $to]);
                });
            } else {
                $query->whereHas('vouchers', function ($q) use ($from, $to) {
                    $q->where('status', '!=', 'cancelled')->whereBetween('due_date', [$from, $to]);
                });
            }
        }

        $assignments = $query->orderByDesc('id')->get();
        $remarks = FeeReportRemark::whereIn('fee_assignment_id', $assignments->pluck('id'))
            ->where('report_type', $reportType)
            ->pluck('remarks', 'fee_assignment_id');

        return $assignments->map(function (FeeAssignment $assignment) use ($remarks) {
            $vouchers = $assignment->vouchers->where('status', '!=', 'cancelled');
            $today = now()->startOfDay();

            $installments = $vouchers
                ->filter(fn ($v) => $v->installment_no !== null)
                ->groupBy('installment_no')
                ->map(function ($grp, $number) {
                    $dueDate = $grp->filter(fn ($v) => $v->due_date)->min('due_date');
                    $total   = round($grp->sum(fn ($v) => $v->total()), 2);
                    $paid    = round($grp->sum('paid_amount'), 2);
                    $pending = max(0, round($total - $paid, 2));
                    return [
                        'number'  => (int) $number,
                        'due_date'=> $dueDate,
                        'total'   => $total,
                        'paid'    => $paid,
                        'pending' => $pending,
                        'status'  => $pending <= 0 ? 'Paid' : (($dueDate && $dueDate->lt(now()->startOfDay())) ? 'Overdue' : 'Due'),
                    ];
                })->sortKeys();

            $totalFee = round((float) $assignment->total_amount, 2);
            // Old/imported plans may have total_amount=0; voucher totals remain authoritative fallback.
            if ($totalFee <= 0) $totalFee = round($vouchers->sum(fn ($v) => $v->total()), 2);
            $totalPaid = round($vouchers->sum('paid_amount'), 2);
            $totalPending = max(0, round($vouchers->sum(fn ($v) => $v->balance()), 2));
            $outstanding = round($vouchers->filter(fn ($v) => $v->due_date && $v->due_date->lt($today))->sum(fn ($v) => $v->balance()), 2);

            return [
                'assignment'       => $assignment,
                'student'          => $assignment->student,
                'total_fee'        => $totalFee,
                'total_installments'=> max((int) $assignment->installments, (int) ($installments->keys()->max() ?? 0)),
                'installments'     => $installments,
                'total_paid'       => $totalPaid,
                'total_pending'    => $totalPending,
                'outstanding'      => $outstanding,
                'fee_status'       => $totalPending <= 0 ? 'paid' : ($totalPaid > 0 ? 'partial' : 'unpaid'),
                'remarks'          => $remarks[$assignment->id] ?? null,
            ];
        })->filter(function ($row) use ($status) {
            return $status === 'all' || $row['fee_status'] === $status || ($status === 'unpaid' && $row['fee_status'] === 'partial');
        })->values();
    }

    private function feeReportData(Request $request, string $reportType): array
    {
        [$from, $to, $period] = $this->resolveRange($request, $reportType === 'paid' ? 'monthly' : 'all');
        $programId  = $request->input('program_id', 'all');
        $semesterId = $request->input('semester_id', 'all');
        $feeStatus  = $request->input('fee_status', $reportType === 'paid' ? 'all' : 'unpaid');
        $search     = trim((string) $request->input('search', ''));
        $rows       = $this->feePlanRows($request, $reportType);

        return [
            'rows'            => $rows,
            'maxInstallments' => max(1, (int) ($rows->max('total_installments') ?? 1)),
            'totalFee'        => round($rows->sum('total_fee'), 2),
            'totalPaid'       => round($rows->sum('total_paid'), 2),
            'totalPending'    => round($rows->sum('total_pending'), 2),
            'totalOutstanding'=> round($rows->sum('outstanding'), 2),
            'programs'        => $this->programOptions(),
            'semesters'       => $this->semesterOptions(),
            'programId'       => $programId,
            'semesterId'      => $semesterId,
            'feeStatus'       => $feeStatus,
            'period'          => $period,
            'from'            => $from,
            'to'              => $to,
            'search'          => $search,
            'program'         => $programId !== 'all' ? Program::find($programId) : null,
            'semester'        => $semesterId !== 'all' ? SchoolClass::find($semesterId) : null,
            'reportType'      => $reportType,
        ];
    }

    public function feePending(Request $request)
    {
        return view('admin.reporting.fee-pending', $this->feeReportData($request, 'pending'));
    }

    public function feePendingPrint(Request $request)
    {
        return view('admin.reporting.print.fee-pending', $this->feeReportData($request, 'pending'));
    }

    public function feePaid(Request $request)
    {
        return view('admin.reporting.fee-paid', $this->feeReportData($request, 'paid'));
    }

    public function feePaidPrint(Request $request)
    {
        return view('admin.reporting.print.fee-paid', $this->feeReportData($request, 'paid'));
    }

    /** Persist the manually-entered admin remarks shown on a financial report row. */
    public function saveFeeReportRemark(Request $request, string $type, FeeAssignment $feeAssignment)
    {
        abort_unless(in_array($type, ['pending', 'paid'], true), 404);
        $validated = $request->validate(['remarks' => ['nullable', 'string', 'max:2000']]);

        FeeReportRemark::updateOrCreate(
            ['report_type' => $type, 'fee_assignment_id' => $feeAssignment->id],
            ['remarks' => $validated['remarks'] ?? null, 'updated_by' => auth()->check() ? auth()->id() : null]
        );

        return back()->with('success', 'Report remarks saved.');
    }

    // ─────────────────────────────────────────────────────────────────
    // 4) Student Information Search
    // ─────────────────────────────────────────────────────────────────
    public function studentSearch(Request $request)
    {
        $programs = $this->programOptions();
        $students = collect();

        if ($request->filled('phone') || $request->filled('name') || $request->filled('father_name')
            || $request->filled('address') || $request->filled('program_id')) {

            $query = Student::with('program', 'schoolClass', 'academicYear');

            if ($request->filled('phone')) {
                $query->where(fn ($q) => $q->where('phone', 'like', "%{$request->phone}%")
                    ->orWhere('guardian_phone', 'like', "%{$request->phone}%")
                    ->orWhere('emergency_contact_phone', 'like', "%{$request->phone}%"));
            }
            if ($request->filled('name')) {
                $query->where(fn ($q) => $q->where('name', 'like', "%{$request->name}%")
                    ->orWhere('student_id', 'like', "%{$request->name}%"));
            }
            if ($request->filled('father_name')) {
                $query->where('father_name', 'like', "%{$request->father_name}%");
            }
            if ($request->filled('address')) {
                $query->where(fn ($q) => $q->where('address', 'like', "%{$request->address}%")
                    ->orWhere('permanent_address', 'like', "%{$request->address}%"));
            }
            if ($request->filled('program_id') && $request->program_id !== 'all') {
                $query->where('program_id', $request->program_id);
            }

            $students = $query->orderBy('name')->paginate(25)->withQueryString();
        }

        return view('admin.reporting.student-search', compact('students', 'programs'));
    }

    public function studentProfilePrint(Student $student)
    {
        $student->load('program', 'schoolClass', 'academicYear', 'institution');
        $fee = $student->feeSummary();
        $attendancePct = $student->attendancePercentage();

        $reliefPlans = FeeAssignment::with('feeTemplate')
            ->where('student_id', $student->id)
            ->where(function ($q) {
                $q->where('scholarship_amount', '>', 0)
                  ->orWhere('financial_aid_amount', '>', 0);
            })
            ->orderBy('cycle_start')
            ->get();

        $reliefSummary = [
            'scholarship' => round((float) $reliefPlans->sum('scholarship_amount'), 2),
            'financial_aid' => round((float) $reliefPlans->sum('financial_aid_amount'), 2),
        ];
        $reliefSummary['total'] = $reliefSummary['scholarship'] + $reliefSummary['financial_aid'];

        return view('admin.reporting.print.student-profile', compact(
            'student', 'fee', 'attendancePct', 'reliefPlans', 'reliefSummary'
        ));
    }

    // ─────────────────────────────────────────────────────────────────
    // 5) Attendance Report (monthly / yearly, program-wise or overall)
    // ─────────────────────────────────────────────────────────────────
    public function attendanceSummary(Request $request)
    {
        [$from, $to, $period] = $this->resolveRange($request, 'monthly');
        $programId = $request->input('program_id', 'all');

        $query = Attendance::with('student.program', 'student.schoolClass', 'student.academicYear')->whereBetween('date', [$from, $to]);
        if ($programId !== 'all') $query->whereHas('student', fn ($q) => $q->where('program_id', $programId));

        $records = $query->get();

        $perStudent = $records->groupBy('student_id')->map(function ($grp) {
            $total   = $grp->count();
            $present = $grp->where('status', 'present')->count();
            $absent  = $grp->where('status', 'absent')->count();
            $leave   = $grp->where('status', 'leave')->count();
            return [
                'student'  => $grp->first()->student,
                'total'    => $total,
                'present'  => $present,
                'absent'   => $absent,
                'leave'    => $leave,
                'pct'      => $total ? round($present / $total * 100, 1) : 0,
            ];
        })->sortBy(fn ($r) => $r['student']->name ?? '')->values();

        $byProgram = $records->groupBy(fn ($r) => $r->student->program->name ?? '—')->map(function ($grp) {
            $total   = $grp->count();
            $present = $grp->where('status', 'present')->count();
            return [
                'total'   => $total,
                'present' => $present,
                'pct'     => $total ? round($present / $total * 100, 1) : 0,
            ];
        });

        $overall = [
            'total'   => $records->count(),
            'present' => $records->where('status', 'present')->count(),
            'absent'  => $records->where('status', 'absent')->count(),
            'leave'   => $records->where('status', 'leave')->count(),
        ];
        $overall['pct'] = $overall['total'] ? round($overall['present'] / $overall['total'] * 100, 1) : 0;

        $programs = $this->programOptions();

        return view('admin.reporting.attendance-summary', compact(
            'perStudent', 'byProgram', 'overall', 'programs', 'programId', 'period', 'from', 'to'
        ));
    }

    public function attendanceSummaryPrint(Request $request)
    {
        [$from, $to, $period] = $this->resolveRange($request, 'monthly');
        $programId = $request->input('program_id', 'all');

        $query = Attendance::with('student.program', 'student.schoolClass', 'student.academicYear')->whereBetween('date', [$from, $to]);
        if ($programId !== 'all') $query->whereHas('student', fn ($q) => $q->where('program_id', $programId));
        $records = $query->get();

        $perStudent = $records->groupBy('student_id')->map(function ($grp) {
            $total   = $grp->count();
            $present = $grp->where('status', 'present')->count();
            return [
                'student' => $grp->first()->student,
                'total'   => $total,
                'present' => $present,
                'absent'  => $grp->where('status', 'absent')->count(),
                'leave'   => $grp->where('status', 'leave')->count(),
                'pct'     => $total ? round($present / $total * 100, 1) : 0,
            ];
        })->sortBy(fn ($r) => $r['student']->name ?? '')->values();

        $overall = [
            'total'   => $records->count(),
            'present' => $records->where('status', 'present')->count(),
        ];
        $overall['pct'] = $overall['total'] ? round($overall['present'] / $overall['total'] * 100, 1) : 0;

        $program = $programId !== 'all' ? Program::find($programId) : null;

        return view('admin.reporting.print.attendance-summary', compact('perStudent', 'overall', 'program', 'period', 'from', 'to'));
    }
}

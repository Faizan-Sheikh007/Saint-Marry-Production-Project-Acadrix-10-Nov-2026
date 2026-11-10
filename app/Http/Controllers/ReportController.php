<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\DailyLedgerEntry;
use App\Models\ExamResult;
use App\Models\Examination;
use App\Models\FeeAssignment;
use App\Models\Payment;
use App\Models\Program;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentReportRemark;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Voucher;
use App\Services\SimplePdfDocument;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ReportController extends Controller
{
    use \App\Traits\BuiltInAiTrait;

    private const BALANCE_SQL = '(COALESCE(amount,0) - COALESCE(discount_amount,0) - COALESCE(paid_amount,0))';

    public function index()
    {
        $canViewInstitutionReports = auth()->user()?->isAdmin() || auth('accounts')->check();

        $stats = $canViewInstitutionReports ? [
            'students'      => Student::count(),
            'programs'      => Program::where('is_active', true)->count(),
            'aidedStudents' => FeeAssignment::where(function ($q) {
                $q->where('scholarship_amount', '>', 0)->orWhere('financial_aid_amount', '>', 0);
            })->distinct('student_id')->count('student_id'),
            'pendingVouchers' => Voucher::where('status', '!=', 'cancelled')->whereRaw(self::BALANCE_SQL . ' > 0')->count(),
        ] : [];

        return view('reports.index', compact('stats'));
    }

    // ──────────────────────────────────────────────────────────────────────
    // Student report
    // ──────────────────────────────────────────────────────────────────────

    public function studentsReport(Request $request)
    {
        $query = Student::with([
                'schoolClass', 'program', 'academicYear',
                'feeAssignments.vouchers', 'vouchers',
            ])
            ->withCount('examResults')
            ->withAvg('examResults as exam_average', 'percentage')
            ->withCount([
                'attendances as attendance_total_count',
                'attendances as attendance_present_count' => fn ($q) => $q->where('status', 'present'),
            ]);

        $this->applyStudentFilters($query, $request);

        if ($request->filled('fee_status')) {
            $filtered = $this->filterStudentCollectionByFeeStatus($query->orderBy('name')->get(), (string) $request->fee_status);
            $students = $this->paginateCollection($filtered, 25, $request);
        } else {
            $students = $query->orderBy('name')->paginate(25)->withQueryString();
        }

        $classes = SchoolClass::with('program')->orderBy('program_id')->orderBy('name')->get();
        $programs = Program::orderBy('name')->get();
        $academicYears = AcademicYear::ordered()->get();

        $studentFeePopulation = Student::with(['feeAssignments.vouchers', 'vouchers'])->get();
        $totalStudents = $studentFeePopulation->count();
        $activeStudents = Student::where('status', 'active')->count();
        $feePendingStudents = $studentFeePopulation->filter(fn ($s) => $s->pendingDues() > 0)->count();
        $feeClearStudents = max(0, $totalStudents - $feePendingStudents);

        return view('reports.students', compact(
            'students', 'classes', 'programs', 'academicYears',
            'totalStudents', 'activeStudents', 'feePendingStudents', 'feeClearStudents'
        ));
    }

    public function studentStatusReport(Request $request)
    {
        $data = $this->studentStatusReportData($request, true);
        return view('reports.student-status', $data);
    }

    public function studentStatusReportPrint(Request $request)
    {
        $data = $this->studentStatusReportData($request, false);
        return view('reports.print.student-status', $data);
    }

    private function studentStatusReportData(Request $request, bool $paginate = true): array
    {
        $query = Student::with(['program', 'schoolClass', 'academicYear'])
            ->orderBy('name');
        $this->applyStudentFilters($query, $request);

        $students = $paginate
            ? $query->paginate(35)->withQueryString()
            : $query->get();

        // Status summary follows all selected filters except the status itself,
        // so Admin can see the population split and jump between statuses.
        $summaryRequest = $request->duplicate();
        $summaryRequest->query->remove('status');
        $summaryQuery = Student::query();
        $this->applyStudentFilters($summaryQuery, $summaryRequest);

        $statusCounts = $summaryQuery
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn ($value) => (int) $value);

        foreach (\App\Support\StudentStatus::VALUES as $status) {
            if (! $statusCounts->has($status)) $statusCounts->put($status, 0);
        }

        $classes = SchoolClass::with('program')->orderBy('program_id')->orderBy('name')->get();
        $programs = Program::orderBy('name')->get();
        $academicYears = AcademicYear::ordered()->get();
        $selectedStatus = $request->filled('status') ? (string) $request->status : '';

        return compact('students', 'statusCounts', 'classes', 'programs', 'academicYears', 'selectedStatus');
    }

    private function applyStudentFilters(Builder $query, Request $request): void
    {
        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('student_id', 'like', "%{$search}%")
                    ->orWhere('father_name', 'like', "%{$search}%")
                    ->orWhere('guardian_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('guardian_phone', 'like', "%{$search}%")
                    ->orWhere('roll_number', 'like', "%{$search}%");
            });
        }
        if ($request->filled('program_id')) $query->where('program_id', $request->program_id);
        if ($request->filled('class_id')) $query->where('school_class_id', $request->class_id);
        if ($request->filled('academic_year_id')) $query->where('academic_year_id', $request->academic_year_id);
        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('gender')) $query->where('gender', $request->gender);

    }

    private function filterStudentCollectionByFeeStatus(Collection $students, string $status): Collection
    {
        return $students->filter(function (Student $student) use ($status) {
            $authoritative = $student->authoritativeVouchers()->reject(fn ($v) => $v->status === 'cancelled');
            $pending = $authoritative->filter(fn ($v) => $v->balance() > 0);

            return match ($status) {
                'clear' => $pending->isEmpty(),
                'pending' => $pending->isNotEmpty(),
                'overdue' => $pending->contains(fn ($v) => $v->due_date && $v->due_date->lt(today())),
                default => true,
            };
        })->values();
    }

    public function studentReportShow(Request $request, Student $student)
    {
        return view('reports.student-show', $this->studentReportData($request, $student));
    }

    public function studentReportPrint(Request $request, Student $student)
    {
        return view('reports.print.student', $this->studentReportData($request, $student));
    }

    public function studentReportPdf(Request $request, Student $student)
    {
        $data = $this->studentReportData($request, $student);
        $pdf = new SimplePdfDocument('portrait');
        $institution = \App\Models\Setting::get('institution_name', 'Acadrix Institute');

        $pdf->title($institution . ' - Student Report')
            ->subtitle('Generated ' . now()->format('d M Y, h:i A'))
            ->section('Student Information')
            ->keyValue('Student ID', $student->student_id)
            ->keyValue('Name', $student->name)
            ->keyValue("Father's Name", $student->father_name)
            ->keyValue('Program', $student->program->name ?? '—')
            ->keyValue('Semester / Part', $student->schoolClass->section_label ?? '—')
            ->keyValue('Academic Year', $student->academicYear->name ?? '—')
            ->keyValue('Roll No', $student->roll_number)
            ->keyValue('Phone', $student->phone)
            ->keyValue('Guardian', trim(($student->guardian_name ?? '') . ' ' . ($student->guardian_phone ? '(' . $student->guardian_phone . ')' : '')))
            ->keyValue('Student Status', $student->statusLabel());

        $a = $data['attendanceSummary'];
        $e = $data['examSummary'];
        $f = $data['feeSummary'];
        $pdf->section('Key Performance Summary')
            ->keyValue('Attendance', $a['percentage'] . '% (' . $a['present'] . '/' . $a['total'] . ' present)')
            ->keyValue('Exams', $e['count'] . ' results; average ' . $e['average'] . '%; passed ' . $e['passed'])
            ->keyValue('Fee Status', $f['status'])
            ->keyValue('Total Charged', 'Rs. ' . number_format($f['charged'], 0))
            ->keyValue('Paid Fee', 'Rs. ' . number_format($f['paid'], 0))
            ->keyValue('Pending Fee', 'Rs. ' . number_format($f['pending'], 0))
            ->keyValue('Overdue Fee', 'Rs. ' . number_format($f['overdue'], 0))
            ->keyValue('Scholarship / Aid', 'Rs. ' . number_format($data['reliefSummary']['total'], 0));

        $pdf->section('Exam Results')
            ->table(
                ['Date', 'Exam', 'Subject', 'Type', 'Teacher', 'Marks', '%', 'Grade', 'Result / Remark'],
                $data['examResults']->map(function ($r) {
                    $passing = (float) ($r->examination?->passing_marks ?? 0);
                    $passed = (float) $r->marks_obtained >= $passing;
                    return [
                        $r->examination?->exam_date?->format('d-m-Y') ?? '—',
                        $r->examination?->name ?? '—',
                        $r->examination?->subject?->name ?? '—',
                        $r->examination?->type_label ?? '—',
                        $r->gradedBy?->name ?? $r->examination?->teacher?->name ?? '—',
                        number_format((float) $r->marks_obtained, 0) . '/' . ($r->examination?->total_marks ?? 0),
                        number_format((float) $r->percentage, 1),
                        $r->grade ?? ($passed ? 'Pass' : 'Fail'),
                        $r->remarks ?: ($passed ? 'Pass' : 'Fail'),
                    ];
                }),
                [9, 12, 12, 8, 12, 8, 5, 6, 17]
            );

        $pdf->section('Recent Verified Payments')
            ->table(
                ['Date', 'Voucher', 'Method', 'Reference', 'Amount'],
                $data['recentPayments']->map(fn ($payment) => [
                    $payment->paid_at?->format('d-m-Y') ?? '—',
                    $payment->voucher?->voucher_no ?? '—',
                    ucfirst(str_replace('_', ' ', $payment->method ?? '—')),
                    $payment->reference_no ?: '—',
                    'Rs.' . number_format((float) $payment->amount, 0),
                ]),
                [10, 16, 14, 22, 11]
            );

        $pdf->section('Recent Attendance')
            ->table(
                ['Date', 'Subject', 'Status', 'Remarks'],
                $data['attendanceRecords']->take(18)->map(fn ($r) => [
                    $r->date?->format('d-m-Y') ?? '—',
                    $r->subject?->name ?? 'General',
                    ucfirst($r->status ?? '—'),
                    $r->remarks ?? '—',
                ]),
                [11, 18, 10, 32]
            );

        $pdf->section('Outstanding Fees')
            ->table(
                ['Voucher', 'Title', 'Due', 'Balance'],
                $data['pendingVouchers']->map(fn ($v) => [
                    $v->voucher_no,
                    $v->title,
                    $v->due_date?->format('d-m-Y') ?? '—',
                    'Rs.' . number_format($v->balance(), 0),
                ]),
                [16, 31, 11, 13]
            );

        $pdf->section('Teacher / Academic Remarks');
        if ($data['remarks']->isEmpty()) {
            $pdf->text('No manual teacher remarks have been entered.');
        } else {
            foreach ($data['remarks'] as $remark) {
                $label = ($remark->remark_date?->format('d M Y') ?? 'Undated') . ' - ' . ($remark->teacher_name ?: 'Teacher / Academic Office');
                if ($remark->term) $label .= ' - ' . $remark->term;
                $pdf->text($label, 8, true)->text($remark->remarks, 8)->blank(3);
            }
        }

        $pdf->footer('System-generated student report. Verify source records before using it as an official transcript.');

        $filename = 'student-report-' . preg_replace('/[^A-Za-z0-9_-]+/', '-', $student->student_id ?: (string) $student->id) . '.pdf';
        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    private function studentReportData(Request $request, Student $student): array
    {
        $student->load([
            'program', 'schoolClass', 'academicYear', 'institution',
            'feeAssignments.vouchers', 'feeAssignments.feeTemplate', 'vouchers',
        ]);

        $from = $request->filled('from') ? Carbon::parse($request->from)->startOfDay() : null;
        $to = $request->filled('to') ? Carbon::parse($request->to)->endOfDay() : null;

        $attendanceQuery = Attendance::with('subject')->where('student_id', $student->id);
        if ($from) $attendanceQuery->whereDate('date', '>=', $from->toDateString());
        if ($to) $attendanceQuery->whereDate('date', '<=', $to->toDateString());
        $attendanceRecords = $attendanceQuery->orderByDesc('date')->get();
        $attendanceSummary = $this->attendanceSummaryFromCollection($attendanceRecords);

        $examQuery = ExamResult::with(['examination.subject', 'examination.teacher', 'gradedBy'])->where('student_id', $student->id);
        if ($request->filled('exam_type')) {
            $examQuery->whereHas('examination', fn ($q) => $q->where('type', $request->exam_type));
        }
        if ($request->filled('subject_id')) {
            $examQuery->whereHas('examination', fn ($q) => $q->where('subject_id', $request->subject_id));
        }
        if ($from) $examQuery->whereHas('examination', fn ($q) => $q->whereDate('exam_date', '>=', $from->toDateString()));
        if ($to) $examQuery->whereHas('examination', fn ($q) => $q->whereDate('exam_date', '<=', $to->toDateString()));
        $examResults = $examQuery->get()->sortByDesc(fn ($r) => optional($r->examination?->exam_date)->timestamp ?? 0)->values();

        $examCount = $examResults->count();
        $examSummary = [
            'count' => $examCount,
            'average' => $examCount ? round((float) $examResults->avg('percentage'), 1) : 0,
            'highest' => $examCount ? round((float) $examResults->max('percentage'), 1) : 0,
            'passed' => $examResults->filter(function ($r) {
                $passing = (float) ($r->examination?->passing_marks ?? 0);
                return (float) $r->marks_obtained >= $passing;
            })->count(),
        ];

        $authoritative = $student->authoritativeVouchers()->reject(fn ($v) => $v->status === 'cancelled')->values();
        $pendingVouchers = $authoritative->filter(fn ($v) => $v->balance() > 0)->sortBy(fn ($v) => optional($v->due_date)->timestamp ?? PHP_INT_MAX)->values();
        $paidVouchers = $authoritative->filter(fn ($v) => $v->balance() <= 0)->sortByDesc(fn ($v) => optional($v->due_date)->timestamp ?? 0)->values();
        $paid = (float) $authoritative->sum('paid_amount');
        $pending = (float) $pendingVouchers->sum(fn ($v) => $v->balance());
        $overdue = (float) $pendingVouchers->filter(fn ($v) => $v->due_date && $v->due_date->lt(today()))->sum(fn ($v) => $v->balance());
        $charged = (float) $authoritative->sum(fn ($v) => $v->total());
        $feeStatus = $pending <= 0 ? 'Paid / Clear' : ($paid > 0 ? ($overdue > 0 ? 'Partial / Overdue' : 'Partial / Pending') : ($overdue > 0 ? 'Unpaid / Overdue' : 'Unpaid'));
        $feeSummary = compact('charged', 'paid', 'pending', 'overdue');
        $feeSummary['status'] = $feeStatus;

        $voucherIds = $authoritative->pluck('id');
        $recentPayments = $voucherIds->isEmpty()
            ? collect()
            : Payment::with('voucher')->whereIn('voucher_id', $voucherIds)->where('status', 'verified')->orderByDesc('paid_at')->take(15)->get();

        $reliefPlans = $student->feeAssignments->filter(fn ($a) => (float) $a->scholarship_amount > 0 || (float) $a->financial_aid_amount > 0)->values();
        $reliefSummary = [
            'scholarship' => (float) $reliefPlans->sum('scholarship_amount'),
            'financial_aid' => (float) $reliefPlans->sum('financial_aid_amount'),
        ];
        $reliefSummary['total'] = $reliefSummary['scholarship'] + $reliefSummary['financial_aid'];

        $remarks = StudentReportRemark::with('createdBy')->where('student_id', $student->id)
            ->orderByDesc('remark_date')->orderByDesc('id')->get();

        $subjectIds = Examination::whereHas('results', fn ($q) => $q->where('student_id', $student->id))
            ->whereNotNull('subject_id')->distinct()->pluck('subject_id');
        $subjects = $subjectIds->isEmpty() ? collect() : Subject::whereIn('id', $subjectIds)->orderBy('name')->get();

        return compact(
            'student', 'from', 'to', 'attendanceRecords', 'attendanceSummary',
            'examResults', 'examSummary', 'feeSummary', 'pendingVouchers', 'paidVouchers',
            'recentPayments', 'reliefPlans', 'reliefSummary', 'remarks', 'subjects'
        );
    }

    public function storeStudentRemark(Request $request, Student $student)
    {
        $validated = $request->validate([
            'teacher_name' => ['nullable', 'string', 'max:150'],
            'term' => ['nullable', 'string', 'max:150'],
            'remark_date' => ['nullable', 'date'],
            'remarks' => ['required', 'string', 'max:3000'],
        ]);

        $student->studentReportRemarks()->create($validated + [
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', 'Teacher / academic remark added to the student report.');
    }

    public function destroyStudentRemark(Student $student, StudentReportRemark $remark)
    {
        abort_unless((int) $remark->student_id === (int) $student->id, 404);
        $remark->delete();
        return back()->with('success', 'Remark removed from the student report.');
    }

    // ──────────────────────────────────────────────────────────────────────
    // Attendance reports
    // ──────────────────────────────────────────────────────────────────────

    public function attendanceReport(Request $request)
    {
        [$baseQuery, $from, $to] = $this->attendanceQuery($request);
        $all = (clone $baseQuery)->get();
        $summary = $this->attendanceSummaryFromCollection($all);
        $perStudent = $all->groupBy('student_id')->map(function ($group) {
            $row = $this->attendanceSummaryFromCollection($group);
            $row['student'] = $group->first()->student;
            return $row;
        })->sortBy(fn ($r) => $r['student']->name ?? '')->values();

        $perStudent = $this->paginateCollection($perStudent, 30, $request, 'summary_page');
        $records = (clone $baseQuery)->orderByDesc('date')->orderByDesc('id')->paginate(40, ['*'], 'records_page')->withQueryString();

        $classes = SchoolClass::with('program')->orderBy('program_id')->orderBy('name')->get();
        $programs = Program::orderBy('name')->get();
        $academicYears = AcademicYear::ordered()->get();
        $subjects = Subject::orderBy('name')->get();

        return view('reports.attendance', compact(
            'records', 'perStudent', 'summary', 'classes', 'programs', 'academicYears', 'subjects', 'from', 'to'
        ));
    }

    public function attendanceReportPrint(Request $request)
    {
        [$baseQuery, $from, $to] = $this->attendanceQuery($request);
        $records = $baseQuery->orderByDesc('date')->get();
        $summary = $this->attendanceSummaryFromCollection($records);
        $perStudent = $records->groupBy('student_id')->map(function ($group) {
            $row = $this->attendanceSummaryFromCollection($group);
            $row['student'] = $group->first()->student;
            return $row;
        })->sortBy(fn ($r) => $r['student']->name ?? '')->values();

        return view('reports.print.attendance', compact('records', 'perStudent', 'summary', 'from', 'to'));
    }

    public function attendanceReportPdf(Request $request)
    {
        [$baseQuery, $from, $to] = $this->attendanceQuery($request);
        $records = $baseQuery->orderBy('student_id')->orderBy('date')->get();
        $summary = $this->attendanceSummaryFromCollection($records);
        $perStudent = $records->groupBy('student_id')->map(function ($group) {
            $s = $this->attendanceSummaryFromCollection($group);
            $student = $group->first()->student;
            return [
                $student->student_id ?? '—', $student->name ?? '—', \App\Support\StudentStatus::label($student?->status), $student->program->name ?? '—',
                $student->schoolClass->section_label ?? '—', $student->academicYear->name ?? '—',
                $s['total'], $s['present'], $s['absent'], $s['late'], $s['leave'], $s['percentage'] . '%',
            ];
        })->values();

        $pdf = new SimplePdfDocument('landscape');
        $pdf->title(\App\Models\Setting::get('institution_name', 'Acadrix Institute') . ' - Attendance Report')
            ->subtitle($from->format('d M Y') . ' to ' . $to->format('d M Y'))
            ->keyValue('Total Records', $summary['total'])
            ->keyValue('Attendance Rate', $summary['percentage'] . '%')
            ->keyValue('Present / Absent / Late / Leave', "{$summary['present']} / {$summary['absent']} / {$summary['late']} / {$summary['leave']}")
            ->section('Student-wise Summary')
            ->table(['ID', 'Student', 'Student Status', 'Program', 'Part', 'Year', 'Total', 'P', 'A', 'L', 'Lv', '%'], $perStudent, [14, 20, 14, 20, 11, 10, 5, 4, 4, 4, 4, 6])
            ->footer('Generated on ' . now()->format('d M Y, h:i A') . '.');

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="attendance-report-' . $from->format('Ymd') . '-' . $to->format('Ymd') . '.pdf"',
        ]);
    }

    public function studentAttendanceReport(Request $request, Student $student)
    {
        return view('reports.attendance-student', $this->studentAttendanceData($request, $student, true));
    }

    public function studentAttendancePdf(Request $request, Student $student)
    {
        $data = $this->studentAttendanceData($request, $student, false);
        $pdf = new SimplePdfDocument('landscape');
        $s = $data['summary'];
        $pdf->title(\App\Models\Setting::get('institution_name', 'Acadrix Institute') . ' - Student Attendance')
            ->subtitle(($student->student_id ?: '') . ' - ' . $student->name . ' | ' . $data['from']->format('d M Y') . ' to ' . $data['to']->format('d M Y'))
            ->keyValue('Student Status', $student->statusLabel())
            ->keyValue('Program / Semester', ($student->program->name ?? '—') . ' / ' . ($student->schoolClass->section_label ?? '—'))
            ->keyValue('Attendance Rate', $s['percentage'] . '%')
            ->keyValue('Present / Absent / Late / Leave', "{$s['present']} / {$s['absent']} / {$s['late']} / {$s['leave']}")
            ->section('Attendance Detail')
            ->table(
                ['Date', 'Subject', 'Status', 'Remarks'],
                $data['records']->map(fn ($r) => [$r->date?->format('d-m-Y') ?? '—', $r->subject?->name ?? 'General', ucfirst($r->status ?? '—'), $r->remarks ?? '—']),
                [12, 28, 12, 54]
            )
            ->footer('Generated on ' . now()->format('d M Y, h:i A') . '.');

        $filename = 'attendance-' . preg_replace('/[^A-Za-z0-9_-]+/', '-', $student->student_id ?: (string) $student->id) . '.pdf';
        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    private function studentAttendanceData(Request $request, Student $student, bool $paginate): array
    {
        $student->load('program', 'schoolClass', 'academicYear');
        $from = Carbon::parse($request->input('from', now()->startOfMonth()->toDateString()))->startOfDay();
        $to = Carbon::parse($request->input('to', now()->toDateString()))->endOfDay();
        $query = Attendance::with('subject')->where('student_id', $student->id)->whereBetween('date', [$from->toDateString(), $to->toDateString()]);
        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('subject_id')) $query->where('subject_id', $request->subject_id);

        $all = (clone $query)->orderByDesc('date')->get();
        $summary = $this->attendanceSummaryFromCollection($all);
        $records = $paginate ? $query->orderByDesc('date')->paginate(40)->withQueryString() : $all;
        $subjects = Subject::orderBy('name')->get();

        return compact('student', 'from', 'to', 'records', 'summary', 'subjects');
    }

    private function attendanceQuery(Request $request): array
    {
        $from = Carbon::parse($request->input('from', now()->startOfMonth()->toDateString()))->startOfDay();
        $to = Carbon::parse($request->input('to', now()->toDateString()))->endOfDay();

        $query = Attendance::with(['student.program', 'student.schoolClass', 'student.academicYear', 'subject'])
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()]);

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->whereHas('student', fn ($q) => $q->where(fn ($sq) => $sq->where('name', 'like', "%{$search}%")->orWhere('student_id', 'like', "%{$search}%")->orWhere('roll_number', 'like', "%{$search}%")));
        }
        if ($request->filled('program_id')) $query->whereHas('student', fn ($q) => $q->where('program_id', $request->program_id));
        if ($request->filled('class_id')) $query->where('school_class_id', $request->class_id);
        if ($request->filled('academic_year_id')) $query->whereHas('student', fn ($q) => $q->where('academic_year_id', $request->academic_year_id));
        if ($request->filled('subject_id')) $query->where('subject_id', $request->subject_id);
        if ($request->filled('status')) $query->where('status', $request->status);

        return [$query, $from, $to];
    }

    private function attendanceSummaryFromCollection(Collection $records): array
    {
        $total = $records->count();
        $present = $records->where('status', 'present')->count();
        return [
            'total' => $total,
            'present' => $present,
            'absent' => $records->where('status', 'absent')->count(),
            'late' => $records->where('status', 'late')->count(),
            'leave' => $records->where('status', 'leave')->count(),
            'percentage' => $total ? round($present / $total * 100, 1) : 0,
        ];
    }

    private function paginateCollection(Collection $items, int $perPage, Request $request, string $pageName = 'page'): LengthAwarePaginator
    {
        $page = LengthAwarePaginator::resolveCurrentPage($pageName);
        $slice = $items->forPage($page, $perPage)->values();
        return (new LengthAwarePaginator($slice, $items->count(), $perPage, $page, [
            'path' => $request->url(), 'pageName' => $pageName,
        ]))->appends($request->query());
    }

    // ──────────────────────────────────────────────────────────────────────
    // Financial reports
    // ──────────────────────────────────────────────────────────────────────

    public function collection(Request $request)
    {
        [$entries, $from, $to] = $this->collectionQuery($request);
        $total = (float) $entries->sum('amount');
        $count = $entries->count();
        $byMethod = $entries->groupBy('method')->map->sum('amount');
        $payments = $this->paginateCollection($entries->sortByDesc('paid_at')->values(), 40, $request);
        $programs = Program::orderBy('name')->get();
        $classes = SchoolClass::with('program')->orderBy('program_id')->orderBy('name')->get();
        return view('reports.collection', compact('payments', 'total', 'count', 'byMethod', 'from', 'to', 'programs', 'classes'));
    }

    private function collectionQuery(Request $request): array
    {
        $from = Carbon::parse($request->input('from', now()->startOfMonth()->toDateString()))->startOfDay();
        $to = Carbon::parse($request->input('to', now()->endOfMonth()->toDateString()))->endOfDay();
        $request->validate(['payment_status' => 'nullable|in:verified,pending,rejected,all']);
        $entries = \App\Services\CashReceiptLedger::entries($from, $to, $request->input('payment_status', 'verified'))
            ->filter(function ($entry) use ($request) {
                $student = $entry instanceof \App\Models\StudentBalanceTransaction ? $entry->student : $entry->voucher?->student;
                if ($request->filled('method') && $entry->method !== $request->method) return false;
                if ($request->filled('program_id') && (int) $student?->program_id !== $request->integer('program_id')) return false;
                if ($request->filled('class_id') && (int) $student?->school_class_id !== $request->integer('class_id')) return false;
                if ($request->filled('search')) {
                    $haystack = implode(' ', [$entry->reference_no, $student?->name, $student?->student_id,
                        $entry instanceof Payment ? $entry->voucher?->voucher_no : 'CR-'.$entry->id]);
                    if (! str_contains(mb_strtolower($haystack), mb_strtolower(trim((string) $request->search)))) return false;
                }
                return true;
            })->values();
        return [$entries, $from, $to];
    }

    public function collectionPrint(Request $request)
    {
        [$entries, $from, $to] = $this->collectionQuery($request);
        $payments = $entries->sortByDesc('paid_at')->values();
        $total = (float) $payments->sum('amount');
        return view('reports.print.collection', compact('payments', 'total', 'from', 'to'));
    }

    public function defaulters(Request $request)
    {
        return view('reports.defaulters', $this->defaultersData($request, true));
    }

    private function authoritativeVoucherRowsForStudents(Request $request): Collection
    {
        $students = Student::with([
            'program', 'schoolClass', 'academicYear',
            'feeAssignments.vouchers', 'vouchers',
        ]);

        if ($request->filled('program_id')) $students->where('program_id', $request->program_id);
        if ($request->filled('class_id')) $students->where('school_class_id', $request->class_id);

        return $students->get()->flatMap(function (Student $student) {
            return $student->authoritativeVouchers()->map(function (Voucher $voucher) use ($student) {
                $voucher->setRelation('student', $student);
                return $voucher;
            });
        })->values();
    }

    private function reportSearchMatchesVoucher(Voucher $voucher, string $search): bool
    {
        $search = mb_strtolower(trim($search));
        if ($search === '') return true;

        $student = $voucher->student;
        $haystacks = [
            $voucher->voucher_no, $voucher->title,
            $student?->name, $student?->student_id, $student?->phone, $student?->guardian_phone,
        ];

        foreach ($haystacks as $value) {
            if ($value !== null && str_contains(mb_strtolower((string) $value), $search)) return true;
        }
        return false;
    }

    private function defaultersData(Request $request, bool $paginate): array
    {
        $asOf = Carbon::parse($request->input('as_of', today()->toDateString()))->endOfDay();
        $rows = $this->authoritativeVoucherRowsForStudents($request)
            ->reject(fn (Voucher $v) => $v->status === 'cancelled')
            ->filter(fn (Voucher $v) => $v->due_date && $v->due_date->lt($asOf) && $v->balance() > 0);

        if ($request->filled('search')) {
            $search = (string) $request->search;
            $rows = $rows->filter(fn (Voucher $v) => $this->reportSearchMatchesVoucher($v, $search));
        }
        if ($request->filled('min_balance')) {
            $minimum = (float) $request->min_balance;
            $rows = $rows->filter(fn (Voucher $v) => $v->balance() >= $minimum);
        }
        if ($request->filled('aging')) {
            $rows = $rows->filter(function (Voucher $v) use ($request, $asOf) {
                $days = max(0, (int) $v->due_date->diffInDays($asOf, false));
                return match ((string) $request->aging) {
                    '1-30' => $days >= 1 && $days <= 30,
                    '31-60' => $days >= 31 && $days <= 60,
                    '61-90' => $days >= 61 && $days <= 90,
                    '90+' => $days >= 91,
                    default => true,
                };
            });
        }

        $rows = $rows->sortBy(fn (Voucher $v) => $v->due_date?->timestamp ?? PHP_INT_MAX)->values();
        $totalOutstanding = (float) $rows->sum(fn (Voucher $v) => $v->balance());
        $studentCount = $rows->pluck('student_id')->unique()->count();
        $vouchers = $paginate ? $this->paginateCollection($rows, 40, $request) : $rows;
        $programs = Program::orderBy('name')->get();
        $classes = SchoolClass::with('program')->orderBy('program_id')->orderBy('name')->get();

        return compact('vouchers', 'totalOutstanding', 'studentCount', 'asOf', 'programs', 'classes');
    }

    public function defaultersPrint(Request $request)
    {
        $data = $this->defaultersData($request, false);
        $data['byStudent'] = $data['vouchers']->groupBy('student_id');
        return view('reports.print.defaulters', $data);
    }

    public function classRevenue(Request $request)
    {
        $data = $this->classRevenueData($request);
        return view('reports.class-revenue', $data);
    }

    public function classRevenuePrint(Request $request)
    {
        return view('reports.print.class-revenue', $this->classRevenueData($request));
    }

    private function classRevenueData(Request $request): array
    {
        $from = $request->filled('from') ? Carbon::parse($request->from)->startOfDay() : null;
        $to = $request->filled('to') ? Carbon::parse($request->to)->endOfDay() : null;
        $classQuery = SchoolClass::with('program')->withCount('students');
        if ($request->filled('program_id')) $classQuery->where('program_id', $request->program_id);
        if ($request->filled('class_id')) $classQuery->whereKey($request->class_id);

        $classes = $classQuery->orderBy('program_id')->orderBy('name')->get()->map(function ($class) use ($from, $to) {
            $studentIds = $class->students()->pluck('id');
            $voucherQuery = Voucher::with('payments')->whereIn('student_id', $studentIds)->where('status', '!=', 'cancelled');
            if ($from) $voucherQuery->where('issued_at', '>=', $from);
            if ($to) $voucherQuery->where('issued_at', '<=', $to);
            $vouchers = $voucherQuery->get();

            $class->total_charged = (float) $vouchers->sum(fn ($v) => $v->total());
            $class->discounts = (float) $vouchers->sum('discount_amount');
            $class->fines = 0.0;
            $class->total_collected = (float) $vouchers->sum(function ($v) use ($from, $to) {
                if (! $from && ! $to) return (float) $v->paid_amount;
                return (float) $v->payments->filter(function ($p) use ($from, $to) {
                    if ($p->status !== 'verified') return false;
                    if ($from && $p->paid_at->lt($from)) return false;
                    if ($to && $p->paid_at->gt($to)) return false;
                    return true;
                })->sum('amount');
            });
            $class->outstanding = (float) $vouchers->sum(fn ($v) => $v->balance());
            $class->students_with_dues = $vouchers->filter(fn ($v) => $v->balance() > 0)->pluck('student_id')->unique()->count();
            $class->collection_rate = $class->total_charged > 0 ? round($class->total_collected / $class->total_charged * 100, 1) : 0;
            return $class;
        });

        if ($request->filled('min_outstanding')) {
            $classes = $classes->filter(fn ($c) => $c->outstanding >= (float) $request->min_outstanding)->values();
        }

        $totals = [
            'charged' => (float) $classes->sum('total_charged'),
            'collected' => (float) $classes->sum('total_collected'),
            'outstanding' => (float) $classes->sum('outstanding'),
            'students' => (int) $classes->sum('students_count'),
        ];
        $totals['rate'] = $totals['charged'] > 0 ? round($totals['collected'] / $totals['charged'] * 100, 1) : 0;

        $programs = Program::orderBy('name')->get();
        $classOptions = SchoolClass::with('program')->orderBy('program_id')->orderBy('name')->get();
        return compact('classes', 'totals', 'programs', 'classOptions', 'from', 'to');
    }

    public function installments(Request $request)
    {
        $data = $this->installmentsData($request, true);
        return view('reports.installments', $data);
    }

    private function installmentsData(Request $request, bool $paginate): array
    {
        $from = $request->filled('from') ? Carbon::parse($request->from)->startOfDay() : null;
        $to = $request->filled('to') ? Carbon::parse($request->to)->endOfDay() : null;

        $rows = $this->authoritativeVoucherRowsForStudents($request)
            ->reject(fn (Voucher $v) => $v->status === 'cancelled')
            ->filter(fn (Voucher $v) => $v->installment_no !== null && $v->balance() > 0);

        if ($request->filled('search')) {
            $search = (string) $request->search;
            $rows = $rows->filter(fn (Voucher $v) => $this->reportSearchMatchesVoucher($v, $search));
        }
        if ($request->filled('status')) {
            $status = (string) $request->status;
            $rows = $rows->filter(function (Voucher $v) use ($status) {
                if ($status === 'overdue') return $v->due_date && $v->due_date->lt(today());
                return $v->status === $status;
            });
        }
        if ($request->filled('installment_no')) {
            $number = (int) $request->installment_no;
            $rows = $rows->filter(fn (Voucher $v) => (int) $v->installment_no === $number);
        }
        if ($request->boolean('overdue_only')) {
            $rows = $rows->filter(fn (Voucher $v) => $v->due_date && $v->due_date->lt(today()));
        }
        if ($from) $rows = $rows->filter(fn (Voucher $v) => $v->due_date && $v->due_date->gte($from));
        if ($to) $rows = $rows->filter(fn (Voucher $v) => $v->due_date && $v->due_date->lte($to));

        $rows = $rows->sortBy(fn (Voucher $v) => $v->due_date?->timestamp ?? PHP_INT_MAX)->values();
        $totalPending = (float) $rows->sum(fn (Voucher $v) => $v->balance());
        $overduePending = (float) $rows->filter(fn (Voucher $v) => $v->due_date && $v->due_date->lt(today()))->sum(fn (Voucher $v) => $v->balance());
        $vouchers = $paginate ? $this->paginateCollection($rows, 40, $request) : $rows;
        $programs = Program::orderBy('name')->get();
        $classes = SchoolClass::with('program')->orderBy('program_id')->orderBy('name')->get();

        return compact('vouchers', 'totalPending', 'overduePending', 'programs', 'classes', 'from', 'to');
    }

    public function installmentsPrint(Request $request)
    {
        return view('reports.print.installments', $this->installmentsData($request, false));
    }

    public function discounts(Request $request)
    {
        [$query, $search] = $this->discountsQuery($request);
        $summaryRows = (clone $query)->get();
        $totalScholarship = (float) $summaryRows->sum('scholarship_amount');
        $totalFinancialAid = (float) $summaryRows->sum('financial_aid_amount');
        // FeeAssignment::total_amount is already NET payable. For report-wide
        // relief, use the discount actually booked on vouchers instead of
        // subtracting plan relief from that net figure a second time.
        $totalRelief = (float) $summaryRows->sum(function ($plan) {
            return $plan->vouchers->where('status', '!=', 'cancelled')->sum('discount_amount');
        });
        $studentCount = $summaryRows->pluck('student_id')->unique()->count();
        $plans = $query->orderByRaw('(scholarship_amount + financial_aid_amount) DESC')->paginate(40)->withQueryString();
        $programs = Program::orderBy('name')->get();
        $classes = SchoolClass::with('program')->orderBy('program_id')->orderBy('name')->get();

        return view('reports.discounts', compact('plans', 'totalScholarship', 'totalFinancialAid', 'totalRelief', 'studentCount', 'search', 'programs', 'classes'));
    }

    private function discountsQuery(Request $request): array
    {
        $search = trim((string) $request->input('search', ''));
        $query = FeeAssignment::with(['student.program', 'student.schoolClass', 'student.academicYear', 'feeTemplate', 'vouchers'])
            ->where(fn ($q) => $q->where('scholarship_amount', '>', 0)->orWhere('financial_aid_amount', '>', 0));

        if ($search !== '') {
            $query->whereHas('student', fn ($q) => $q->where(fn ($sq) => $sq->where('name', 'like', "%{$search}%")->orWhere('student_id', 'like', "%{$search}%")->orWhere('father_name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%")));
        }
        if ($request->filled('program_id')) $query->whereHas('student', fn ($q) => $q->where('program_id', $request->program_id));
        if ($request->filled('class_id')) $query->whereHas('student', fn ($q) => $q->where('school_class_id', $request->class_id));
        if ($request->filled('aid_type')) {
            if ($request->aid_type === 'scholarship') $query->where('scholarship_amount', '>', 0);
            if ($request->aid_type === 'financial_aid') $query->where('financial_aid_amount', '>', 0);
            if ($request->aid_type === 'both') $query->where('scholarship_amount', '>', 0)->where('financial_aid_amount', '>', 0);
        }
        if ($request->filled('cycle')) {
            $cycle = (int) $request->cycle;
            $query->where('cycle_start', '<=', $cycle)->where('cycle_end', '>=', $cycle);
        }
        if ($request->filled('min_relief')) {
            $query->whereRaw('(COALESCE(scholarship_amount,0) + COALESCE(financial_aid_amount,0)) >= ?', [(float) $request->min_relief]);
        }

        return [$query, $search];
    }

    public function discountsPrint(Request $request)
    {
        [$query, $search] = $this->discountsQuery($request);
        $plans = $query->orderByRaw('(scholarship_amount + financial_aid_amount) DESC')->get();
        $totalScholarship = (float) $plans->sum('scholarship_amount');
        $totalFinancialAid = (float) $plans->sum('financial_aid_amount');
        $totalRelief = (float) $plans->sum(function ($plan) {
            return $plan->vouchers->where('status', '!=', 'cancelled')->sum('discount_amount');
        });
        return view('reports.print.discounts', compact('plans', 'totalScholarship', 'totalFinancialAid', 'totalRelief', 'search'));
    }

    public function incomeExpense(Request $request)
    {
        $data = $this->incomeExpenseData($request);
        return view('reports.income-expense', $data);
    }

    public function incomeExpensePrint(Request $request)
    {
        return view('reports.print.income-expense', $this->incomeExpenseData($request, false));
    }

    private function incomeExpenseData(Request $request, bool $paginateEntries = true): array
    {
        $from = Carbon::parse($request->input('from', now()->startOfYear()->toDateString()))->startOfDay();
        $to = Carbon::parse($request->input('to', now()->endOfMonth()->toDateString()))->endOfDay();
        $group = in_array($request->input('group', 'monthly'), ['daily', 'monthly'], true) ? $request->input('group', 'monthly') : 'monthly';

        $payments = \App\Services\CashReceiptLedger::entries($from, $to);
        $ledgerQuery = DailyLedgerEntry::whereBetween('entry_date', [$from->toDateString(), $to->toDateString()]);
        if ($request->filled('category')) $ledgerQuery->where('category', $request->category);
        $entries = $ledgerQuery->orderByDesc('entry_date')->get();

        $cursor = $group === 'daily' ? $from->copy()->startOfDay() : $from->copy()->startOfMonth();
        $end = $group === 'daily' ? $to->copy()->startOfDay() : $to->copy()->startOfMonth();
        $breakdown = collect();
        while ($cursor->lte($end)) {
            $periodStart = $group === 'daily' ? $cursor->copy()->startOfDay() : $cursor->copy()->startOfMonth();
            $periodEnd = $group === 'daily' ? $cursor->copy()->endOfDay() : $cursor->copy()->endOfMonth();
            $label = $group === 'daily' ? $cursor->format('d M Y') : $cursor->format('M Y');

            $feeIncome = (float) $payments->filter(fn ($p) => $p->paid_at->between($periodStart, $periodEnd))->sum('amount');
            $periodEntries = $entries->filter(fn ($e) => $e->entry_date->between($periodStart, $periodEnd));
            $otherIncome = (float) $periodEntries->where('type', 'in')->sum('amount');
            $out = (float) $periodEntries->where('type', 'out')->sum('amount');
            $expense = (float) $periodEntries->where('type', 'expense')->sum('amount');
            $totalIncome = $feeIncome + $otherIncome;
            $totalExpense = $out + $expense;
            $breakdown->push(compact('label', 'feeIncome', 'otherIncome', 'out', 'expense', 'totalIncome', 'totalExpense') + ['net' => $totalIncome - $totalExpense]);

            $cursor = $group === 'daily' ? $cursor->addDay() : $cursor->addMonth();
        }

        $totalFeeIncome = (float) $payments->sum('amount');
        $totalOtherIncome = (float) $entries->where('type', 'in')->sum('amount');
        $totalOut = (float) $entries->where('type', 'out')->sum('amount');
        $totalExpense = (float) $entries->where('type', 'expense')->sum('amount');
        $totalIncome = $totalFeeIncome + $totalOtherIncome;
        $totalExpenses = $totalOut + $totalExpense;
        $net = $totalIncome - $totalExpenses;
        $expenseByCategory = $entries->whereIn('type', ['out', 'expense'])->groupBy(fn ($e) => $e->category ?: 'Uncategorised')->map->sum('amount')->sortDesc();
        $categories = DailyLedgerEntry::whereNotNull('category')->distinct()->orderBy('category')->pluck('category');
        $expenseEntries = $paginateEntries ? $this->paginateCollection($entries->whereIn('type', ['out', 'expense'])->values(), 30, $request, 'expense_page') : $entries->whereIn('type', ['out', 'expense'])->values();

        return compact(
            'from', 'to', 'group', 'breakdown', 'totalFeeIncome', 'totalOtherIncome', 'totalIncome',
            'totalOut', 'totalExpense', 'totalExpenses', 'net', 'expenseByCategory', 'expenseEntries', 'categories'
        );
    }

    // ──────────────────────────────────────────────────────────────────────
    // CSV exports
    // ──────────────────────────────────────────────────────────────────────

    public function export(Request $request, string $type)
    {
        $filename = 'report-' . $type . '-' . now()->format('Y-m-d') . '.csv';
        $header = [];
        $rows = collect();

        switch ($type) {
            case 'students':
                $query = Student::with(['schoolClass', 'program', 'academicYear', 'feeAssignments.vouchers', 'vouchers'])
                    ->withCount('examResults')->withAvg('examResults as exam_average', 'percentage')
                    ->withCount(['attendances as attendance_total_count', 'attendances as attendance_present_count' => fn ($q) => $q->where('status', 'present')]);
                $this->applyStudentFilters($query, $request);
                $studentRows = $query->orderBy('name')->get();
                if ($request->filled('fee_status')) {
                    $studentRows = $this->filterStudentCollectionByFeeStatus($studentRows, (string) $request->fee_status);
                }
                $rows = $studentRows->map(function ($s) {
                    $attendance = $s->attendance_total_count ? round($s->attendance_present_count / $s->attendance_total_count * 100, 1) : 0;
                    return [$s->student_id, $s->name, $s->program->name ?? '', $s->schoolClass->section_label ?? '', $s->academicYear->name ?? '', $s->roll_number, $attendance . '%', $s->exam_results_count, round((float) $s->exam_average, 1) . '%', $s->totalPaid(), $s->pendingDues(), $s->statusLabel()];
                });
                $header = ['Student ID', 'Name', 'Program', 'Semester/Part', 'Academic Year', 'Roll No', 'Attendance', 'Exam Count', 'Exam Average', 'Paid Fee', 'Pending Fee', 'Student Status'];
                break;

            case 'attendance':
                [$query] = $this->attendanceQuery($request);
                $rows = $query->orderByDesc('date')->get()->map(fn ($r) => [$r->student->student_id ?? '', $r->student->name ?? '', $r->student->program->name ?? '', $r->student->schoolClass->section_label ?? '', $r->student->academicYear->name ?? '', $r->subject->name ?? 'General', $r->date?->format('Y-m-d'), $r->status, $r->remarks, \App\Support\StudentStatus::label($r->student?->status)]);
                $header = ['Student ID', 'Student', 'Program', 'Semester/Part', 'Academic Year', 'Subject', 'Date', 'Attendance Status', 'Remarks', 'Student Status'];
                break;

            case 'collection':
                [$query] = $this->collectionQuery($request);
                $rows = $query->sortByDesc('paid_at')->values()->map(fn ($p) => [$p->paid_at?->format('Y-m-d H:i'), $p->student->student_id ?? $p->voucher->student->student_id ?? '', $p->student->name ?? $p->voucher->student->name ?? '', $p->student->program->name ?? $p->voucher->student->program->name ?? '', $p->student->schoolClass->section_label ?? $p->voucher->student->schoolClass->section_label ?? '', $p->voucher->voucher_no ?? ('Extra receipt CR-'.$p->id), $p->amount, $p->method, $p->status ?? 'verified', $p->reference_no, $p->received_by ?? $p->recorded_by, \App\Support\StudentStatus::label(($p->student ?? $p->voucher?->student)?->status)]);
                $header = ['Date', 'Student ID', 'Student', 'Program', 'Semester/Part', 'Voucher', 'Amount', 'Method', 'Payment Status', 'Reference', 'Received By', 'Student Status'];
                break;

            case 'defaulters':
                $data = $this->defaultersData($request, false);
                $asOf = $data['asOf'];
                $rows = $data['vouchers']->map(fn ($v) => [$v->student->student_id ?? '', $v->student->name ?? '', $v->student->program->name ?? '', $v->student->schoolClass->section_label ?? '', $v->voucher_no, $v->title, $v->total(), $v->paid_amount, $v->balance(), $v->due_date?->format('Y-m-d'), $v->due_date ? max(0, (int) $v->due_date->diffInDays($asOf, false)) : '', $v->student->guardian_phone ?? $v->student->phone ?? '', \App\Support\StudentStatus::label($v->student?->status)]);
                $header = ['Student ID', 'Student', 'Program', 'Semester/Part', 'Voucher', 'Title', 'Total', 'Paid', 'Balance', 'Due Date', 'Days Overdue', 'Contact', 'Student Status'];
                break;

            case 'class-revenue':
                $data = $this->classRevenueData($request);
                $rows = $data['classes']->map(fn ($c) => [$c->program->name ?? '', $c->section_label, $c->students_count, $c->total_charged, $c->total_collected, $c->outstanding, $c->students_with_dues, $c->collection_rate . '%']);
                $header = ['Program', 'Semester/Part', 'Students', 'Charged', 'Collected', 'Outstanding', 'Students With Dues', 'Collection Rate'];
                break;

            case 'installments':
                $data = $this->installmentsData($request, false);
                $rows = $data['vouchers']->map(fn ($v) => [$v->student->student_id ?? '', $v->student->name ?? '', $v->student->program->name ?? '', $v->student->schoolClass->section_label ?? '', $v->voucher_no, $v->installment_no, $v->installment_total, $v->total(), $v->paid_amount, $v->balance(), $v->due_date?->format('Y-m-d'), $v->autoStatusLabel(), \App\Support\StudentStatus::label($v->student?->status)]);
                $header = ['Student ID', 'Student', 'Program', 'Semester/Part', 'Voucher', 'Installment No', 'Installments Total', 'Payable', 'Paid', 'Pending', 'Due Date', 'Voucher Status', 'Student Status'];
                break;

            case 'discounts':
                [$query] = $this->discountsQuery($request);
                $rows = $query->get()->map(function ($p) {
                    $valid = $p->vouchers->where('status', '!=', 'cancelled');
                    $gross = (float) $valid->sum('amount');
                    $bookedRelief = (float) $valid->sum('discount_amount');
                    $net = max(0, $gross - $bookedRelief);
                    $paid = (float) $valid->sum('paid_amount');
                    $pending = (float) $valid->sum(fn ($v) => $v->balance());
                    return [$p->student->student_id ?? '', $p->student->name ?? '', $p->student->program->name ?? '', $p->student->schoolClass->section_label ?? '', $p->cycleRangeLabel(), $gross, $p->scholarship_amount, $p->financial_aid_amount, $bookedRelief, $net, $paid, $pending, \App\Support\StudentStatus::label($p->student?->status)];
                });
                $header = ['Student ID', 'Student', 'Program', 'Semester/Part', 'Cycle', 'Gross Fee', 'Scholarship', 'Financial Aid', 'Booked Relief', 'Net Payable', 'Paid', 'Pending', 'Student Status'];
                break;

            case 'income-expense':
                $data = $this->incomeExpenseData($request, false);
                $rows = $data['breakdown']->map(fn ($r) => [$r['label'], $r['feeIncome'], $r['otherIncome'], $r['totalIncome'], $r['out'], $r['expense'], $r['totalExpense'], $r['net']]);
                $header = ['Period', 'Fee Income', 'Ledger In', 'Total Income', 'Out', 'Expense', 'Total Expenses', 'Net'];
                break;

            default:
                abort(404);
        }

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, $header);
        foreach ($rows as $row) fputcsv($handle, $row);
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return response("\xEF\xBB\xBF" . $csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /** Executive summary combining EMS + Accounts data. */
    public function aiSummary()
    {
        abort_unless(auth()->user()?->isAdmin() || auth('accounts')->check(), 403);

        $students = Student::where('status', 'active')->count();
        $teachers = Teacher::where('status', 'active')->count();
        $collected = \App\Services\CashReceiptLedger::total(now()->startOfMonth(), now()->endOfMonth());
        $outstanding = Voucher::where('status', '!=', 'cancelled')->whereRaw(self::BALANCE_SQL . ' > 0')->get()->sum(fn ($v) => $v->balance());
        $attendToday = Attendance::whereDate('date', today())->count();
        $presentToday = Attendance::whereDate('date', today())->where('status', 'present')->count();
        $attendPct = $attendToday > 0 ? round($presentToday / $attendToday * 100) : 0;

        $summary = "School snapshot:\n"
            . "- Active students: {$students}, Active teachers: {$teachers}\n"
            . "- Fees collected this month: {$collected}, Outstanding: {$outstanding}\n"
            . "- Today's attendance: {$presentToday}/{$attendToday} ({$attendPct}%)";

        $insight = $this->builtInAI(
            'You are a school management consultant. Write a concise executive summary '
            . "(4–6 sentences) of school performance and highlight the top 2 areas needing attention:\n\n"
            . $summary
        );

        return response()->json(['insight' => $insight]);
    }
}

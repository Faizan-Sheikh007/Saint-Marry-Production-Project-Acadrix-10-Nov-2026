<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\Program;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Voucher;
use App\Services\OriByte\LicenseConnector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function index()
    {
        $totalStudents   = Student::where('status', 'active')->count();
        $totalCollected  = Voucher::sum('paid_amount');
        $totalOutstanding = Voucher::whereIn('status', ['unpaid', 'partial', 'overdue'])->get()->sum(fn ($v) => $v->balance());
        $overdueCount    = Voucher::where('status', 'overdue')->count();
        $collectedThisMonth = \App\Services\CashReceiptLedger::total(now()->startOfMonth(), now()->endOfMonth());

        $recentPayments  = Payment::with('voucher.student')->latest('paid_at')->take(6)->get();
        $recentStudents  = Student::with('schoolClass')->latest()->take(5)->get();
        $activities      = AuditLog::with('user')->latest()->take(8)->get();

        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $chartData[] = [
                'day'    => $date->format('D'),
                'amount' => \App\Services\CashReceiptLedger::total($date->copy()->startOfDay(), $date->copy()->endOfDay()),
            ];
        }

        /*
         * Lightweight Program -> Session -> Semester/Part metadata for the
         * Accounts dashboard student explorer. Only hierarchy/counts are
         * loaded here; student rows are fetched on demand after a Session is
         * selected, while Semester/Part is used as an optional filter.
         */
        $studentCounts = Student::query()
            ->select('program_id', 'academic_year_id', 'school_class_id', DB::raw('COUNT(*) as total'))
            ->whereNotNull('program_id')
            ->whereNotNull('academic_year_id')
            ->whereNotNull('school_class_id')
            ->groupBy('program_id', 'academic_year_id', 'school_class_id')
            ->get()
            ->keyBy(fn ($row) => $row->program_id . ':' . $row->academic_year_id . ':' . $row->school_class_id);

        $programExplorer = Program::query()
            ->with(['sections.academicYears'])
            ->orderBy('name')
            ->get()
            ->map(function (Program $program) use ($studentCounts) {
                $sessions = collect();

                foreach ($program->sections->sortBy(fn ($section) => [
                    $section->term_number ?? 999,
                    $section->section ?? '',
                    $section->name,
                ]) as $section) {
                    foreach ($section->academicYears->sortBy(fn ($year) => [
                        $year->sort_order ?? 0,
                        $year->name,
                    ]) as $year) {
                        $session = $sessions->get($year->id, [
                            'id'         => $year->id,
                            'name'       => $year->name,
                            'is_default' => (bool) $year->is_default,
                            'sort_order' => (int) ($year->sort_order ?? 0),
                            'terms'      => collect(),
                        ]);

                        $countKey = $program->id . ':' . $year->id . ':' . $section->id;
                        $studentCount = (int) optional($studentCounts->get($countKey))->total;

                        $session['terms']->push([
                            'id'            => $section->id,
                            'name'          => $section->section_label,
                            'term_number'   => $section->term_number,
                            'student_count' => $studentCount,
                        ]);

                        $sessions->put($year->id, $session);
                    }
                }

                $sessions = $sessions
                    ->sortBy(fn ($session) => [$session['sort_order'], $session['name']])
                    ->map(function ($session) {
                        $session['terms'] = $session['terms']
                            ->sortBy(fn ($term) => [$term['term_number'] ?? 999, $term['name']])
                            ->values();
                        $session['student_count'] = (int) $session['terms']->sum('student_count');
                        return $session;
                    })
                    ->values();

                return [
                    'id'            => $program->id,
                    'name'          => $program->name,
                    'code'          => $program->code,
                    'system_type'   => $program->system_type,
                    'is_active'     => (bool) $program->is_active,
                    'session_count' => $sessions->count(),
                    'student_count' => (int) $sessions->sum('student_count'),
                    'sessions'      => $sessions,
                ];
            });

        $licenseStatus    = 'unknown';
        $licenseExpiresAt = null;
        $licenseIsTrial   = false;
        $licenseDaysLeft  = null;
        $licensePlan      = null;

        try {
            $connector  = app(LicenseConnector::class);
            $validation = $connector->validate();

            $licenseStatus    = $validation['license_status'] ?? ($validation['valid'] ? 'active' : 'unknown');
            $licenseExpiresAt = $validation['expires_at']       ?? null;
            $licenseIsTrial   = ($validation['type']            ?? '') === 'trial';
            $licenseDaysLeft  = $validation['days_until_expiry'] ?? null;
            $licensePlan      = $validation['plan']['name']      ?? null;
        } catch (\Exception $e) {
            Log::debug('[Acardix] Dashboard license fetch skipped: ' . $e->getMessage());
        }

        return view('dashboard.index', compact(
            'totalStudents', 'totalCollected', 'totalOutstanding', 'overdueCount', 'collectedThisMonth',
            'recentPayments', 'recentStudents', 'activities', 'chartData', 'programExplorer',
            'licenseStatus', 'licenseExpiresAt', 'licenseIsTrial', 'licenseDaysLeft', 'licensePlan'
        ));
    }

    /**
     * AJAX table for the dashboard Student Explorer.
     *
     * Selecting a Session now loads every student in that Program + Session.
     * Semester / Part is an optional filter instead of a required drill-down
     * step, which keeps the session roster visible in one place.
     */
    public function studentExplorer(Request $request)
    {
        $validated = $request->validate([
            'program_id'       => ['required', 'integer', 'exists:programs,id'],
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'school_class_id'  => ['nullable', 'integer', 'exists:school_classes,id'],
            'view'             => ['nullable', 'in:student,enrollment,fees'],
            'search'           => ['nullable', 'string', 'max:100'],
        ]);

        // Only classes explicitly linked to this Program + Session can be
        // browsed or used by the Semester / Part filter.
        $allowedClassIds = SchoolClass::query()
            ->where('program_id', $validated['program_id'])
            ->whereHas('academicYears', fn ($query) => $query->where('academic_years.id', $validated['academic_year_id']))
            ->pluck('id');

        abort_if($allowedClassIds->isEmpty(), 404, 'No Semester / Part is linked to this Program and Session.');

        if (!empty($validated['school_class_id'])) {
            abort_unless(
                $allowedClassIds->contains((int) $validated['school_class_id']),
                422,
                'The selected Semester / Part does not belong to this Program and Session.'
            );
        }

        $query = Student::query()
            ->with(['program', 'schoolClass', 'academicYear', 'feeAssignments.vouchers', 'vouchers'])
            ->where('program_id', $validated['program_id'])
            ->where('academic_year_id', $validated['academic_year_id'])
            ->whereIn('school_class_id', $allowedClassIds);

        if (!empty($validated['school_class_id'])) {
            $query->where('school_class_id', $validated['school_class_id']);
        }

        if (!empty($validated['search'])) {
            $search = trim($validated['search']);
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('student_id', 'like', "%{$search}%")
                    ->orWhere('roll_number', 'like', "%{$search}%")
                    ->orWhere('father_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // A session click is intentionally an "all students" view. The table
        // itself scrolls, so there is no pagination hiding students from the
        // Select All checkbox or from a printed selection list.
        $students = $query
            ->orderBy('school_class_id')
            ->orderBy('student_id')
            ->orderBy('name')
            ->get();

        /*
         * Add the latest actually-received fee date for each loaded student.
         * Pending/rejected online proofs are not treated as paid fees. Older
         * payment rows with a null status remain valid for backwards
         * compatibility.
         */
        $studentIds = $students->pluck('id')->filter()->values();
        if ($studentIds->isNotEmpty()) {
            $lastPaidByStudent = Payment::query()
                ->join('vouchers', 'vouchers.id', '=', 'payments.voucher_id')
                ->whereIn('vouchers.student_id', $studentIds)
                ->whereNotNull('payments.paid_at')
                ->where(function ($paymentQuery) {
                    $paymentQuery->where('payments.status', 'verified')
                        ->orWhereNull('payments.status');
                })
                ->select('vouchers.student_id', DB::raw('MAX(payments.paid_at) as last_fee_paid_at'))
                ->groupBy('vouchers.student_id')
                ->pluck('last_fee_paid_at', 'vouchers.student_id');

            $students->transform(function (Student $student) use ($lastPaidByStudent) {
                $student->setAttribute('last_fee_paid_at', $lastPaidByStudent->get($student->id));
                return $student;
            });
        }

        return view('dashboard._student-explorer-table', [
            'students' => $students,
            'viewMode' => $validated['view'] ?? 'student',
        ]);
    }

    /**
     * Printable roster built only from the students checked in the Accounts
     * dashboard explorer. The Program / Session / Semester labels are derived
     * from the selected records so the printout cannot show stale filter text.
     */
    public function printSelectedStudents(Request $request)
    {
        $validated = $request->validate([
            'student_ids'   => ['required', 'array', 'min:1', 'max:1000'],
            'student_ids.*' => ['required', 'integer', 'distinct', 'exists:students,id'],
        ]);

        $students = Student::query()
            ->with(['program', 'schoolClass', 'academicYear', 'feeAssignments.vouchers', 'vouchers'])
            ->whereIn('id', $validated['student_ids'])
            ->get()
            ->sortBy(fn (Student $student) => [
                $student->program->name ?? '',
                $student->academicYear->name ?? '',
                $student->schoolClass->term_number ?? 999,
                $student->schoolClass->section_label ?? '',
                $student->name,
            ])
            ->values();

        abort_if($students->isEmpty(), 404, 'No students were selected.');

        return view('dashboard.student-explorer-print', compact('students'));
    }

}

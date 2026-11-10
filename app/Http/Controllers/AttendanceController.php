<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\Program;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    use \App\Traits\BuildsAcademicCascade;

    // ─── Index ───────────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        // Session -> Program -> Semester/Part -> Subject. The session/class
        // pivot is the source of truth, so a "Part 1" from another program or
        // intake can never appear in the current chain.
        $academicYears = $this->academicCascadeYears(true);
        $filterStructure = $this->buildAcademicCascade($academicYears, true);

        // Teachers only see subjects they are actually assigned to teach in
        // each Semester/Part; admins retain the full curriculum chain.
        if (! auth()->user()->isAdmin() && auth()->user()->teacher) {
            $teacherId = (int) auth()->user()->teacher->id;
            foreach ($filterStructure as &$sessionNode) {
                foreach ($sessionNode['programs'] as &$programNode) {
                    foreach ($programNode['terms'] as &$termNode) {
                        $termNode['subjects'] = array_values(array_filter(
                            $termNode['subjects'] ?? [],
                            fn ($subject) => collect($subject['teachers'] ?? [])->contains(fn ($teacher) => (int) $teacher['id'] === $teacherId)
                        ));
                    }
                    unset($termNode);
                }
                unset($programNode);
            }
            unset($sessionNode);
        }

        $defaultYear = $academicYears->firstWhere('is_default', true) ?? $academicYears->first();
        $selectedYearId = $request->filled('academic_year_id')
            ? (int) $request->academic_year_id
            : ($defaultYear?->id);
        $selectedYear = $selectedYearId ? $academicYears->firstWhere('id', $selectedYearId) : null;

        $programs = collect();
        $selectedProgram = null;
        if ($selectedYear) {
            $programs = Program::forAcademicYear($selectedYear->id)->orderBy('name')->get();
            if ($request->filled('program_id')) {
                $selectedProgram = $programs->firstWhere('id', (int) $request->program_id);
            }
        }

        $classes = collect();
        $selectedClass = null;
        if ($selectedYear && $selectedProgram) {
            $classes = SchoolClass::where('program_id', $selectedProgram->id)
                ->whereHas('academicYears', fn ($q) => $q->where('academic_years.id', $selectedYear->id))
                ->get()
                ->sortBy(fn ($class) => [$class->term_number ?? 999, $class->section ?? '', $class->name])
                ->values();

            if ($request->filled('class_id')) {
                $selectedClass = $classes->firstWhere('id', (int) $request->class_id);
            }
        }

        $date = $request->date ? Carbon::parse($request->date) : today();

        // Scope students by BOTH class and academic session. This prevents the
        // same Semester/Part from mixing students from different intakes.
        $students = ($selectedYear && $selectedClass)
            ? Student::where('school_class_id', $selectedClass->id)
                     ->where('academic_year_id', $selectedYear->id)
                     ->where('status', 'active')
                     ->orderBy('roll_number')
                     ->orderBy('name')
                     ->get()
            : collect();

        $subjects = collect();
        if ($selectedClass) {
            $subjectQuery = $selectedClass->subjectAssignments()->with('subject');
            if (! auth()->user()->isAdmin() && auth()->user()->teacher) {
                $subjectQuery->where('teacher_id', auth()->user()->teacher->id);
            }
            $subjects = $subjectQuery->get()->pluck('subject')->filter()->unique('id')->sortBy('name')->values();
        }

        $requestedSubjectId = $request->filled('subject_id') ? (int) $request->subject_id : null;
        $selectedSubjectId = $requestedSubjectId && $subjects->contains('id', $requestedSubjectId)
            ? $requestedSubjectId
            : null;

        $existingAttendance = ($selectedClass && $students->isNotEmpty())
            ? Attendance::where('school_class_id', $selectedClass->id)
                        ->whereDate('date', $date)
                        ->whereIn('student_id', $students->pluck('id'))
                        ->when($selectedSubjectId, fn ($q) => $q->where('subject_id', $selectedSubjectId), fn ($q) => $q->whereNull('subject_id'))
                        ->pluck('status', 'student_id')
            : collect();

        $monthlyMap = [];
        if ($selectedClass && $students->isNotEmpty()) {
            $monthStart = $date->copy()->startOfMonth()->toDateString();
            $monthEnd   = $date->copy()->endOfMonth()->toDateString();

            $monthRecords = Attendance::where('school_class_id', $selectedClass->id)
                ->whereBetween('date', [$monthStart, $monthEnd])
                ->whereIn('student_id', $students->pluck('id'))
                ->when($selectedSubjectId, fn ($q) => $q->where('subject_id', $selectedSubjectId), fn ($q) => $q->whereNull('subject_id'))
                ->get(['student_id', 'date', 'status']);

            foreach ($monthRecords as $rec) {
                $monthlyMap[$rec->student_id][$rec->date instanceof \Carbon\Carbon
                    ? $rec->date->toDateString()
                    : \Carbon\Carbon::parse($rec->date)->toDateString()
                ] = $rec->status;
            }
        }

        return view('attendance.index', compact(
            'academicYears', 'filterStructure', 'selectedYearId', 'selectedYear',
            'programs', 'selectedProgram', 'classes', 'selectedClass', 'date',
            'students', 'existingAttendance', 'monthlyMap', 'subjects', 'selectedSubjectId'
        ));
    }

    // ─── Print ───────────────────────────────────────────────────────────────
    /** Letterhead-style printable attendance sheet — mirrors the Reporting Center's print sheets. */
    public function print(Request $request)
    {
        $classes       = SchoolClass::orderBy('name')->get();
        $selectedClass = $request->class_id
            ? SchoolClass::find($request->class_id)
            : $classes->first();

        $date = $request->date ? Carbon::parse($request->date) : today();
        $selectedSubjectId = $request->filled('subject_id') ? (int) $request->subject_id : null;
        $selectedSubject    = $selectedSubjectId ? \App\Models\Subject::find($selectedSubjectId) : null;

        $academicYearId = $request->filled('academic_year_id') ? (int) $request->academic_year_id : null;

        $students = $selectedClass
            ? Student::where('school_class_id', $selectedClass->id)
                     ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId))
                     ->where(function ($q) use ($selectedClass, $date, $selectedSubjectId) {
                         $q->where('status', 'active')->orWhereHas('attendances', function ($a) use ($selectedClass, $date, $selectedSubjectId) {
                             $a->where('school_class_id', $selectedClass->id)->whereDate('date', $date)
                               ->when($selectedSubjectId, fn ($q) => $q->where('subject_id', $selectedSubjectId), fn ($q) => $q->whereNull('subject_id'));
                         });
                     })
                     ->orderBy('roll_number')
                     ->get()
            : collect();

        $existingAttendance = ($selectedClass && $students->isNotEmpty())
            ? Attendance::where('school_class_id', $selectedClass->id)
                        ->whereDate('date', $date)
                        ->whereIn('student_id', $students->pluck('id'))
                        ->when($selectedSubjectId, fn ($q) => $q->where('subject_id', $selectedSubjectId), fn ($q) => $q->whereNull('subject_id'))
                        ->pluck('status', 'student_id')
            : collect();

        return view('attendance.print', compact(
            'selectedClass', 'date', 'students', 'existingAttendance', 'selectedSubject'
        ));
    }

    // ─── Save ────────────────────────────────────────────────────────────────
    public function save(Request $request)
    {
        $validated = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'school_class_id'  => 'required|exists:school_classes,id',
            'subject_id'       => 'nullable|exists:subjects,id',
            'date'             => 'required|date',
            'attendance'       => 'required|array',
            'attendance.*'     => 'required|in:present,absent,leave,late',
        ]);

        $classIsLinked = SchoolClass::whereKey($validated['school_class_id'])
            ->whereHas('academicYears', fn ($q) => $q->where('academic_years.id', $validated['academic_year_id']))
            ->exists();
        abort_unless($classIsLinked, 422, 'The selected Semester / Part is not linked to this Academic Session.');

        $allowedStudentIds = Student::where('school_class_id', $validated['school_class_id'])
            ->where('academic_year_id', $validated['academic_year_id'])
            ->where('status', 'active')
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();

        $submittedStudentIds = array_map('strval', array_keys($validated['attendance']));
        abort_if(array_diff($submittedStudentIds, $allowedStudentIds), 422, 'One or more students do not belong to the selected Session / Semester.');

        $subjectId = $validated['subject_id'] ?? null;

        foreach ($validated['attendance'] as $studentId => $status) {
            Attendance::updateOrCreate(
                [
                    'student_id'      => $studentId,
                    'date'            => $validated['date'],
                    'school_class_id' => $validated['school_class_id'],
                    'subject_id'      => $subjectId,
                ],
                ['status' => $status]
            );
        }

        $formattedDate = Carbon::parse($validated['date'])->format('d M Y');
        $subjectLabel  = $subjectId ? ' (' . (\App\Models\Subject::find($subjectId)->name ?? 'Subject') . ')' : '';

        if (auth()->user()->isAdmin()) {
            return back()->with('success', 'Attendance saved successfully for ' . $formattedDate . $subjectLabel);
        }

        return back()->with('success', 'Attendance submitted to admin successfully for ' . $formattedDate . $subjectLabel);
    }

    // ─── Report ──────────────────────────────────────────────────────────────
    public function report(Request $request)
    {
        $classes = SchoolClass::orderBy('name')->get();

        $selectedClass = $request->class_id
            ? SchoolClass::find($request->class_id)
            : $classes->first();

        $filterType = $request->filter_type ?? 'month';

        if ($filterType === 'date' && $request->specific_date) {
            $date = Carbon::parse($request->specific_date);
        } elseif ($filterType === 'month' && $request->month_date) {
            $date = Carbon::parse($request->month_date . '-01');
        } else {
            $date = today();
        }

        $startDate = $filterType === 'date' ? $date->copy()->startOfDay()  : $date->copy()->startOfMonth();
        $endDate   = $filterType === 'date' ? $date->copy()->endOfDay()    : $date->copy()->endOfMonth();

        $academicYearId = $request->filled('academic_year_id') ? (int) $request->academic_year_id : null;

        $students = $selectedClass
            ? Student::where('school_class_id', $selectedClass->id)
                     ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId))
                     ->orderBy('roll_number')
                     ->get()
            : collect();

        $attendanceRecords = ($selectedClass && $students->isNotEmpty())
            ? Attendance::where('school_class_id', $selectedClass->id)
                        ->whereIn('student_id', $students->pluck('id'))
                        ->whereBetween('date', [
                            $startDate->toDateString(),
                            $endDate->toDateString(),
                        ])
                        ->get()
            : collect();

        $summary = [];
        foreach ($students as $student) {
            $summary[$student->id] = ['present' => 0,'absent' => 0,'late' => 0,'leave' => 0,'total' => 0];
        }
        foreach ($attendanceRecords as $record) {
            if (isset($summary[$record->student_id])) {
                $summary[$record->student_id][$record->status]++;
                $summary[$record->student_id]['total']++;
            }
        }

        $workingDays = $filterType === 'date'
            ? ($attendanceRecords->isNotEmpty() ? 1 : 0)
            : $attendanceRecords->pluck('date')
                  ->map(fn($d) => Carbon::parse($d)->toDateString())
                  ->unique()->count();

        if ($request->download) {
            return $this->downloadReport(
                $students, $summary, $selectedClass, $date, $workingDays, $filterType
            );
        }

        return view('attendance.report', compact(
            'classes', 'selectedClass', 'date', 'startDate', 'endDate',
            'students', 'summary', 'workingDays', 'filterType'
        ));
    }

    // ─── Show (individual record + monthly map) ──────────────────────────────
    public function show($id)
    {
        $attendance = Attendance::with('student', 'schoolClass')->findOrFail($id);

        $monthlyRecord = [];
        if ($attendance->student_id && $attendance->date) {
            $date       = $attendance->date instanceof Carbon
                ? $attendance->date : Carbon::parse($attendance->date);
            $records = Attendance::where('student_id', $attendance->student_id)
                ->whereBetween('date', [
                    $date->copy()->startOfMonth()->toDateString(),
                    $date->copy()->endOfMonth()->toDateString(),
                ])
                ->get(['date', 'status']);
            foreach ($records as $rec) {
                $monthlyRecord[Carbon::parse($rec->date)->toDateString()] = $rec->status;
            }
        }

        return view('attendance.show', compact('attendance', 'monthlyRecord'));
    }

    // ─── Download CSV ─────────────────────────────────────────────────────────
    private function downloadReport($students, $summary, $selectedClass, $date, $workingDays, $filterType = 'month')
    {
        $label    = $filterType === 'date' ? $date->format('Y_m_d') : $date->format('Y_m');
        $filename = 'attendance_' . ($selectedClass?->name ?? 'all') . '_' . $label . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($students, $summary, $selectedClass, $date, $workingDays, $filterType) {
            $handle      = fopen('php://output', 'w');
            $periodLabel = $filterType === 'date' ? $date->format('d F Y') : $date->format('F Y');

            fputcsv($handle, ['Attendance Report - ' . ($selectedClass?->name ?? 'All') . ' - ' . $periodLabel]);
            fputcsv($handle, ['Working Days: ' . $workingDays]);
            fputcsv($handle, []);
            fputcsv($handle, ['#','Student Name','Roll No','Present','Absent','Late','Leave','Total','Attendance %','Student Status']);

            foreach ($students as $i => $student) {
                $s          = $summary[$student->id];
                $percentage = $workingDays > 0
                    ? round(($s['present'] + $s['late']) / $workingDays * 100, 1)
                    : 0;

                fputcsv($handle, [
                    $i + 1, $student->name, $student->roll_number,
                    $s['present'], $s['absent'], $s['late'], $s['leave'],
                    $s['total'], $percentage . '%', $student->statusLabel(),
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}

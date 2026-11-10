<?php
namespace App\Http\Controllers;
use App\Models\Timetable;
use App\Models\SchoolClass;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\AcademicYear;
use App\Models\Program;
use Illuminate\Http\Request;

class TimetableController extends Controller
{
    use \App\Traits\BuildsAcademicCascade;

    /** Common day ordering used across every timetable query. */
    protected function dayOrdered($query)
    {
        return $query->orderByRaw("FIELD(day, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday')")
                      ->orderBy('start_time');
    }

    /** All periods for a given class/section, chronologically ordered. */
    protected function scheduleForClass(?int $schoolClassId)
    {
        if (!$schoolClassId) return collect();

        return $this->dayOrdered(
            Timetable::with('subject', 'teacher')->where('school_class_id', $schoolClassId)
        )->get();
    }

    /** All periods a given teacher takes across every class, chronologically ordered. */
    protected function scheduleForTeacher(int $teacherId)
    {
        return $this->dayOrdered(
            Timetable::with('subject', 'teacher', 'schoolClass.program')->where('teacher_id', $teacherId)
        )->get();
    }

    /** Printable timetable for a single student (their class schedule). Admin use. */
    public function forStudent(Student $student)
    {
        $items = $this->scheduleForClass($student->school_class_id);

        return view('timetable.print', [
            'items'      => $items,
            'context'    => 'class',
            'heading'    => $student->name . "'s Timetable",
            'subheading' => trim(($student->schoolClass->display_name ?? $student->schoolClass->name ?? 'No class assigned') . ' • ' . $student->student_id),
        ]);
    }

    /** Printable timetable for a single teacher (their teaching schedule). Admin use. */
    public function forTeacher(Teacher $teacher)
    {
        $items = $this->scheduleForTeacher($teacher->id);

        return view('timetable.print', [
            'items'      => $items,
            'context'    => 'teacher',
            'heading'    => $teacher->name . "'s Teaching Schedule",
            'subheading' => trim(($teacher->subject->name ?? 'Teacher') . ' • ' . $teacher->employee_id),
        ]);
    }

    /** Full-page in-app view of the logged-in teacher's own weekly schedule. */
    public function myTeacherSchedulePage()
    {
        $teacher = auth()->user()->teacher;
        $items   = $teacher ? $this->scheduleForTeacher($teacher->id) : collect();

        return view('teacher.timetable', compact('items', 'teacher'));
    }

    /** Printable version of the logged-in teacher's own weekly schedule. */
    public function myTeacherSchedulePrint()
    {
        $teacher = auth()->user()->teacher;
        abort_unless($teacher, 404, 'No teacher profile linked to this account.');

        return $this->forTeacher($teacher);
    }

    /** Full-page in-app view of the logged-in student's own class schedule. */
    public function myStudentSchedulePage()
    {
        $student = auth()->user()->student;
        $items   = $student ? $this->scheduleForClass($student->school_class_id) : collect();

        return view('student.timetable', compact('items', 'student'));
    }

    /** Printable version of the logged-in student's own class schedule. */
    public function myStudentSchedulePrint()
    {
        $student = auth()->user()->student;
        abort_unless($student, 404, 'No student profile linked to this account.');

        return $this->forStudent($student);
    }

    public function index(Request $request)
    {
        // One canonical hierarchy for the page and Add Period modal:
        // Session -> Program -> Semester/Part -> Subject.  The academic-year
        // pivot is authoritative, so a repeated label such as "Part 1" from
        // another intake/program can never appear in the wrong dropdown.
        $academicYears = $this->academicCascadeYears(true);
        $filterStructure = $this->buildAcademicCascade($academicYears, true);

        $defaultYear = $academicYears->firstWhere('is_default', true) ?? $academicYears->first();
        $requestedYearId = (string) $request->input('academic_year_id', '');
        $selectedYearId = $requestedYearId !== '' && isset($filterStructure[$requestedYearId])
            ? (int) $requestedYearId
            : ($defaultYear?->id);

        $yearNode = $selectedYearId ? ($filterStructure[(string) $selectedYearId] ?? null) : null;
        $requestedProgramId = (string) $request->input('program_id', '');
        $programNode = $yearNode
            ? collect($yearNode['programs'] ?? [])->first(fn ($program) => (string) $program['id'] === $requestedProgramId)
            : null;
        $selectedProgramId = $programNode ? (int) $programNode['id'] : null;

        $requestedClassId = (string) $request->input('class_id', '');
        $termNode = $programNode
            ? collect($programNode['terms'] ?? [])->first(fn ($term) => (string) $term['id'] === $requestedClassId)
            : null;
        $selectedClassId = $termNode ? (int) $termNode['id'] : null;

        $teachers = Teacher::where('status', 'active')->orderBy('name')->get();

        // Do not merge multiple semesters/parts into one timetable grid. The
        // grid only loads after the complete Session -> Program -> Term chain
        // is selected, which makes every visible period unambiguous.
        $items = Timetable::with('schoolClass.program', 'subject', 'teacher')
            ->when($selectedClassId, fn ($q) => $q->where('school_class_id', $selectedClassId))
            ->when(!$selectedClassId, fn ($q) => $q->whereRaw('1 = 0'))
            ->when($request->day, fn ($q) => $q->where('day', $request->day))
            ->orderByRaw("FIELD(day, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday')")
            ->orderBy('start_time')
            ->paginate(100)
            ->withQueryString();

        // Conflict checking must consider a teacher's timetable across every
        // class, not just the currently displayed semester.
        $allEntries = Timetable::with('subject')->get();

        $breakLabel = \App\Models\Setting::get('timetable_break_label', '🕌 Juma Prayer (Friday) / Lunch Break');
        $breakTime  = \App\Models\Setting::get('timetable_break_time', '12:00-12:45');

        return view('timetable.index', compact(
            'items', 'allEntries', 'teachers', 'academicYears', 'filterStructure',
            'selectedYearId', 'selectedProgramId', 'selectedClassId',
            'breakLabel', 'breakTime'
        ));
    }

    /** Assign (or clear) a manual timetable color for a teacher. */
    public function updateTeacherColor(Request $request, Teacher $teacher)
    {
        $data = $request->validate([
            'timetable_color' => 'nullable|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        $teacher->update(['timetable_color' => $data['timetable_color'] ?? null]);

        return back()->with('success', "Color updated for {$teacher->name}!");
    }

    /** Update the label/time shown on the special break row (Juma prayer / lunch, etc). */
    public function updateBreak(Request $request)
    {
        $data = $request->validate([
            'break_label' => 'required|string|max:255',
            'break_time'  => 'required|string|max:50',
        ]);

        \App\Models\Setting::set('timetable_break_label', $data['break_label']);
        \App\Models\Setting::set('timetable_break_time', $data['break_time']);

        return back()->with('success', 'Break period updated!');
    }

    /**
     * Printable admin timetable. Two modes are supported from the timetable page:
     *  - selected: the currently selected Session / Program / Part
     *  - merged:   every class in the selected Session, merged into one compact grid
     */
    public function printAll(Request $request)
    {
        $mode = in_array($request->input('mode'), ['selected', 'merged'], true)
            ? $request->input('mode')
            : 'selected';

        $yearId    = $request->integer('academic_year_id') ?: null;
        $programId = $request->integer('program_id') ?: null;
        $classId   = $request->integer('class_id') ?: null;

        $year    = $yearId ? AcademicYear::find($yearId) : null;
        $program = $programId ? Program::find($programId) : null;
        $class   = $classId ? SchoolClass::with('program')->find($classId) : null;

        $query = Timetable::with('schoolClass.program', 'schoolClass.academicYears', 'subject', 'teacher')
            ->when($yearId, function ($q) use ($yearId) {
                $q->whereHas('schoolClass.academicYears', fn ($yearQ) => $yearQ->where('academic_years.id', $yearId));
            })
            ->when($request->day, fn ($q) => $q->where('day', $request->day));

        if ($mode === 'selected') {
            $query->when($programId, function ($q) use ($programId) {
                $q->whereHas('schoolClass', fn ($classQ) => $classQ->where('program_id', $programId));
            });
            $query->when($classId, fn ($q) => $q->where('school_class_id', $classId));
        }

        $items = $this->dayOrdered($query)->get();

        $isMerged = $mode === 'merged';
        $partLabel = $class ? $class->section_label : 'All Parts / Semesters';

        return view('timetable.print', [
            'items'       => $items,
            'context'     => $isMerged || !$class ? 'merged' : 'class',
            'heading'     => $isMerged
                ? 'Merged Timetable — All Classes'
                : (($program?->name ?? $class?->program?->name ?? 'Selected Program') . ($class ? ' — ' . $class->section_label : '') . ' Timetable'),
            'subheading'  => $isMerged ? 'All classes combined on one weekly grid' : 'Selected timetable',
            'mergedPrint' => $isMerged,
            'meta'        => [
                'Session' => $year?->name ?? 'All Sessions',
                'Program' => $isMerged ? 'All Programs' : ($program?->name ?? $class?->program?->name ?? 'All Programs'),
                'Part'    => $isMerged ? 'All Parts / Semesters' : $partLabel,
            ],
        ]);
    }

    public function store(Request $request)
    {
        // 'day' now arrives as an array so the same subject/teacher/time slot
        // can be scheduled across any combination of days in one go — e.g.
        // Monday + Tuesday for one lecture, and Wednesday–Saturday for a
        // different lecture in that same slot.
        $validated = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'program_id'       => 'required|exists:programs,id',
            'school_class_id'  => 'required|exists:school_classes,id',
            'subject_id'       => 'required|exists:subjects,id',
            'teacher_id'       => 'nullable|exists:teachers,id',
            'day'              => 'required|array|min:1',
            'day.*'            => 'in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday',
            'start_time'       => 'required',
            'end_time'         => 'required',
        ]);

        $schoolClass = SchoolClass::with('academicYears', 'subjectAssignments')->findOrFail($validated['school_class_id']);

        if ((int) $schoolClass->program_id !== (int) $validated['program_id']) {
            return back()->withErrors([
                'school_class_id' => 'The selected Semester / Part does not belong to the selected Program.',
            ])->withInput();
        }

        if (!$schoolClass->academicYears->contains('id', (int) $validated['academic_year_id'])) {
            return back()->withErrors([
                'school_class_id' => 'The selected Semester / Part is not linked to the selected Academic Session.',
            ])->withInput();
        }

        if (!$schoolClass->subjectAssignments->contains('subject_id', (int) $validated['subject_id'])) {
            return back()->withErrors([
                'subject_id' => 'The selected Subject is not assigned to this Semester / Part.',
            ])->withInput();
        }

        $days = array_unique($validated['day']);

        foreach ($days as $day) {
            Timetable::create([
                'school_class_id' => $validated['school_class_id'],
                'subject_id'      => $validated['subject_id'],
                'teacher_id'      => $validated['teacher_id'] ?? null,
                'day'             => $day,
                'start_time'      => $validated['start_time'],
                'end_time'        => $validated['end_time'],
            ]);
        }

        $message = count($days) > 1
            ? 'Timetable entry added for ' . count($days) . ' days!'
            : 'Timetable entry added!';

        return back()->with('success', $message);
    }

    public function update(Request $request, Timetable $timetable)
    {
        $timetable->update($request->only('teacher_id', 'start_time', 'end_time'));
        return back()->with('success', 'Timetable updated!');
    }

    public function destroy(Timetable $timetable)
    {
        $timetable->delete();
        return back()->with('success', 'Entry removed!');
    }
}

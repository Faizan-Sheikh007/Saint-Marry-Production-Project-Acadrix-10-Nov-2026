<?php
namespace App\Http\Controllers;

use App\Models\ClassSubject;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    public function index()
    {
        // Subject catalog (flat list) — still needed for editing/deleting the subjects themselves.
        $items = Subject::withCount('classAssignments')->orderBy('name')->get();

        // Classes -> Subjects -> Teacher, for the hierarchical curriculum view.
        // Grouped by Program, then ordered naturally within each program
        // (Part 1, Part 2 ... / Semester 1, Semester 2 ... Semester 10) so
        // classes from different programs never interleave and "Semester 10"
        // doesn't sort ahead of "Semester 2" the way a plain alphabetical
        // sort would.
        $classes = SchoolClass::with(['program', 'subjectAssignments.subject', 'subjectAssignments.teacher'])
            ->get();

        $classesByProgram = $classes
            ->groupBy(fn ($c) => $c->program->id ?? 0)
            ->sortBy(fn ($group) => $group->first()->program->name ?? 'Zzz - No Program')
            ->map(fn ($group) => $group->sortBy(fn ($c) => $c->name, SORT_NATURAL | SORT_FLAG_CASE)->values());

        $teachers = Teacher::where('status', 'active')->orderBy('name')->get();

        // Lookup map so the curriculum view can link each subject-in-class
        // row straight to its LMS course (for a "View Content" shortcut)
        // without an N+1 query per row.
        $courseMap = \App\Models\Course::query()
            ->get(['id', 'subject_id', 'school_class_id'])
            ->keyBy(fn ($c) => $c->school_class_id . '-' . $c->subject_id);

        return view('subjects.index', compact('items', 'classes', 'classesByProgram', 'teachers', 'courseMap'));
    }

    public function create() { return view('subjects.form'); }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'code'         => 'nullable|string|max:20|unique:subjects',
            'description'  => 'nullable|string',
            'credit_hours' => 'nullable|integer|min:1',
        ]);
        Subject::create($validated);
        return redirect()->route('subjects.index')->with('success', 'Subject created!');
    }

    public function show(Subject $subject)
    {
        $subject->load(['classAssignments.schoolClass.program', 'classAssignments.teacher']);
        return view('subjects.show', compact('subject'));
    }

    public function edit(Subject $subject) { return view('subjects.form', compact('subject')); }

    public function update(Request $request, Subject $subject)
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'code'         => 'nullable|string|max:20|unique:subjects,code,' . $subject->id,
            'description'  => 'nullable|string',
            'credit_hours' => 'nullable|integer|min:1',
        ]);
        $subject->update($validated);
        return redirect()->route('subjects.index')->with('success', 'Subject updated!');
    }

    public function destroy(Subject $subject)
    {
        $subject->delete();
        return redirect()->route('subjects.index')->with('success', 'Subject deleted!');
    }

    /**
     * Assign a subject to one or more classes at once, with an optional
     * teacher who will teach it in every one of those classes.
     */
    public function assign(Request $request)
    {
        $data = $request->validate([
            'subject_id'   => 'required|exists:subjects,id',
            'teacher_id'   => 'nullable|exists:teachers,id',
            'class_ids'    => 'required|array|min:1',
            'class_ids.*'  => 'exists:school_classes,id',
        ]);

        foreach ($data['class_ids'] as $classId) {
            $assignment = ClassSubject::updateOrCreate(
                ['school_class_id' => $classId, 'subject_id' => $data['subject_id']],
                ['teacher_id' => $data['teacher_id'] ?? null]
            );

            // Keep the student-facing LMS course in sync with this assignment
            // so the subject (and its real teacher) shows up under "My
            // Courses" immediately, with the class's students auto-enrolled.
            \App\Models\Course::syncForAssignment($assignment);
        }

        $subject = Subject::find($data['subject_id']);
        $count   = count($data['class_ids']);

        return back()->with('success', "\"{$subject->name}\" assigned to {$count} class" . ($count === 1 ? '' : 'es') . '!');
    }

    /** Change just the teacher on an existing class-subject assignment (inline dropdown). */
    public function updateAssignment(Request $request, ClassSubject $assignment)
    {
        $data = $request->validate(['teacher_id' => 'nullable|exists:teachers,id']);
        $assignment->update($data);

        \App\Models\Course::syncForAssignment($assignment);

        return back()->with('success', 'Teacher updated!');
    }

    /** Remove a subject from a single class. */
    public function unassign(ClassSubject $assignment)
    {
        \App\Models\Course::archiveForUnassignment($assignment->subject_id, $assignment->school_class_id);

        $assignment->delete();
        return back()->with('success', 'Subject removed from class!');
    }
}

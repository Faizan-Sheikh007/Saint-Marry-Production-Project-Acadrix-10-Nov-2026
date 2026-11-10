<?php

namespace App\Http\Controllers;

use App\Models\Examination;
use App\Models\ExamResult;
use App\Models\ParentModel;
use App\Models\ResultPublication;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Http\Request;

class ResultController extends Controller
{
    /* ───────────────────────── Shared helpers ───────────────────────── */

    /**
     * Grade a percentage using Admin's custom grading formula (Settings ->
     * Grading Scale). This is the single source of truth for grading and is
     * used both when a teacher submits marks and when the compiled
     * marksheet's overall grade is displayed.
     */
    public static function gradeFor(float $percentage): string
    {
        return \App\Models\GradingScale::gradeFor($percentage);
    }

    /**
     * Resolve the examinations belonging to a "batch" for one class. Real
     * batches use the UUID batch_id; a lone exam scheduled without a batch
     * is addressed here as "single-{examination id}" (see index()).
     */
    private function examinationsQuery(string $batchId, int $schoolClassId)
    {
        if (str_starts_with($batchId, 'single-')) {
            $examId = (int) substr($batchId, 7);
            return Examination::where('id', $examId)->where('school_class_id', $schoolClassId);
        }
        return Examination::where('batch_id', $batchId)->where('school_class_id', $schoolClassId);
    }

    /**
     * Build the full marksheet payload for one student within one batch+class:
     * subject rows, totals, overall grade/status, and — among classmates who
     * also have a complete set of marks for this batch — their class position.
     */
    private function buildMarksheet(string $batchId, SchoolClass $schoolClass, Student $student): array
    {
        $examinations = $this->examinationsQuery($batchId, $schoolClass->id)
            ->with('subject', 'teacher')
            ->orderBy('subject_id')
            ->get();

        $results = ExamResult::whereIn('examination_id', $examinations->pluck('id'))
            ->where('student_id', $student->id)
            ->get()
            ->keyBy('examination_id');

        $subjects = $examinations->map(function ($exam) use ($results) {
            $result = $results->get($exam->id);
            return [
                'subject'        => $exam->subject->name ?? $exam->name,
                'teacher'        => $exam->teacher->name ?? null,
                'total_marks'    => (float) $exam->total_marks,
                'passing_marks'  => (float) $exam->passing_marks,
                'marks_obtained' => $result?->marks_obtained,
                'grade'          => $result?->grade,
                'status'         => $result ? ($result->marks_obtained >= $exam->passing_marks ? 'Pass' : 'Fail') : null,
                'remarks'        => $result?->remarks,
            ];
        });

        $graded    = $subjects->filter(fn ($s) => $s['marks_obtained'] !== null);
        $totalObt  = $graded->sum('marks_obtained');
        $totalMax  = $subjects->sum('total_marks');
        $percentage = $totalMax > 0 ? round(($totalObt / $totalMax) * 100, 2) : 0;
        $complete   = $graded->count() === $subjects->count() && $subjects->count() > 0;
        $anyFail    = $graded->contains(fn ($s) => $s['status'] === 'Fail');

        return [
            'student'       => $student,
            'school_class'  => $schoolClass,
            'subjects'      => $subjects,
            'total_obtained'=> $totalObt,
            'total_max'     => $totalMax,
            'percentage'    => $percentage,
            'overall_grade' => self::gradeFor($percentage),
            'complete'      => $complete,
            'overall_status'=> $complete ? ($anyFail ? 'Fail' : 'Pass') : 'Incomplete',
            'position'      => null, // filled in by callers that compute class rank
        ];
    }

    /** Rank active students; retain inactive students with existing batch results as read-only history. */
    private function rankClass(string $batchId, SchoolClass $schoolClass): \Illuminate\Support\Collection
    {
        $examIds = $this->examinationsQuery($batchId, $schoolClass->id)->select('id');
        $students = Student::where('school_class_id', $schoolClass->id)
            ->where(fn ($q) => $q->where('status', 'active')
                ->orWhereHas('examResults', fn ($r) => $r->whereIn('examination_id', $examIds)))
            ->orderBy('name')->get();

        $sheets = $students->map(fn ($s) => $this->buildMarksheet($batchId, $schoolClass, $s));

        $ranked = $sheets->filter(fn ($s) => $s['complete'] && $s['student']->isActiveEnrollment())
            ->sortByDesc('percentage')
            ->values();

        $position = 1;
        foreach ($ranked as $i => $sheet) {
            if ($i > 0 && $ranked[$i - 1]['percentage'] == $sheet['percentage']) {
                // tie — same position as previous
            } else {
                $position = $i + 1;
            }
            $rankedStudentId = $sheet['student']->id;
            $sheets = $sheets->map(function ($s) use ($rankedStudentId, $position) {
                if ($s['student']->id === $rankedStudentId) $s['position'] = $position;
                return $s;
            });
        }

        return $sheets->sortBy(fn ($s) => $s['student']->name);
    }

    /** Completion stats (subjects marked / total subject-slots) for a batch+class. */
    private function classCompletion(string $batchId, SchoolClass $schoolClass): array
    {
        $examinations = $this->examinationsQuery($batchId, $schoolClass->id)
            ->withCount('results')
            ->get();

        $studentCount = Student::where('school_class_id', $schoolClass->id)->where('status', 'active')->count();
        $expectedSlots = $examinations->count() * max($studentCount, 1);
        $filledSlots   = $examinations->sum('results_count');

        return [
            'subjects_total'  => $examinations->count(),
            'students_total'  => $studentCount,
            'expected_slots'  => $expectedSlots,
            'filled_slots'    => $filledSlots,
            'percent'         => $expectedSlots > 0 ? round(($filledSlots / $expectedSlots) * 100) : 0,
            'ready'           => $expectedSlots > 0 && $filledSlots >= $expectedSlots,
        ];
    }

    /* ───────────────────────── Admin: batch/class listing ───────────────────────── */

    public function index(Request $request)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        // Every batch/class pairing that has at least one examination.
        $pairs = Examination::select('batch_id', 'school_class_id', 'name')
            ->whereNotNull('batch_id')
            ->groupBy('batch_id', 'school_class_id', 'name')
            ->orderByDesc('batch_id')
            ->get();

        // Examinations scheduled without a batch (single ad-hoc exam) — group each into its own pseudo-batch.
        $singles = Examination::whereNull('batch_id')->get()
            ->map(fn ($e) => (object) ['batch_id' => 'single-' . $e->id, 'school_class_id' => $e->school_class_id, 'name' => $e->name]);

        $rows = $pairs->concat($singles)
            ->when($request->search, fn ($c) => $c->filter(fn ($r) => str_contains(strtolower($r->name), strtolower($request->search))))
            ->when($request->school_class_id, fn ($c) => $c->filter(fn ($r) => (int) $r->school_class_id === (int) $request->school_class_id));

        $classes = SchoolClass::with('program')->orderBy('name')->get()->keyBy('id');

        $batches = $rows->map(function ($row) use ($classes) {
            $class = $classes->get($row->school_class_id);
            if (!$class) return null;

            $completion  = $this->classCompletion($row->batch_id, $class);
            $publication = ResultPublication::for($row->batch_id, $class->id);

            return [
                'batch_id'   => $row->batch_id,
                'exam_name'  => $row->name,
                'class'      => $class,
                'completion' => $completion,
                'published'  => $publication?->isPublished() ?? false,
                'published_at' => $publication?->published_at,
            ];
        })->filter()->values()->sortByDesc(fn ($b) => (string) $b['published_at'])->values();

        return view('results.index', compact('batches', 'classes'));
    }

    /** Admin: full roster + subject matrix for one batch/class, with publish controls. */
    public function classShow(string $batchId, SchoolClass $schoolClass)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $examinations = $this->examinationsQuery($batchId, $schoolClass->id)
            ->with('subject', 'teacher')
            ->orderBy('subject_id')
            ->get();

        abort_if($examinations->isEmpty(), 404);

        $examName    = $examinations->first()->name;
        $completion  = $this->classCompletion($batchId, $schoolClass);
        $publication = ResultPublication::for($batchId, $schoolClass->id);
        $sheets      = $this->rankClass($batchId, $schoolClass);

        return view('results.class', compact(
            'batchId', 'schoolClass', 'examinations', 'examName', 'completion', 'publication', 'sheets'
        ));
    }

    /** Admin: search a specific student across all their batches/classes. */
    public function byStudent(Request $request)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $student = null;
        $batches = collect();

        if ($request->filled('student_id')) {
            $student = Student::find($request->student_id);
        } elseif ($request->filled('search')) {
            $student = Student::where('name', 'like', '%' . $request->search . '%')
                ->orWhere('student_id', 'like', '%' . $request->search . '%')
                ->first();
        }

        if ($student && $student->school_class_id) {
            $class = $student->schoolClass;
            $batchIds = Examination::where('school_class_id', $student->school_class_id)
                ->whereNotNull('batch_id')
                ->select('batch_id', 'name')
                ->groupBy('batch_id', 'name')
                ->get();

            $batches = $batchIds->map(function ($b) use ($class, $student) {
                $publication = ResultPublication::for($b->batch_id, $class->id);
                $sheet = $this->buildMarksheet($b->batch_id, $class, $student);
                return [
                    'batch_id'  => $b->batch_id,
                    'exam_name' => $b->name,
                    'sheet'     => $sheet,
                    'published' => $publication?->isPublished() ?? false,
                ];
            });
        }

        $students = $request->filled('search')
            ? Student::where('name', 'like', '%' . $request->search . '%')->orWhere('student_id', 'like', '%' . $request->search . '%')->limit(10)->get()
            : collect();

        return view('results.by-student', compact('student', 'batches', 'students'));
    }

    /* ───────────────────────── Publish / Unpublish ───────────────────────── */

    public function publish(Request $request, string $batchId, SchoolClass $schoolClass)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $examName = $this->examinationsQuery($batchId, $schoolClass->id)->value('name');
        abort_if(!$examName, 404);

        $request->validate(['notes' => 'nullable|string|max:2000']);

        ResultPublication::updateOrCreate(
            ['batch_id' => $batchId, 'school_class_id' => $schoolClass->id],
            [
                'published_at' => now(),
                'published_by' => auth()->id(),
                'notes'        => $request->notes,
                'exam_name'    => $examName,
            ]
        );

        return back()->with('success', "Result published! Students and parents of {$schoolClass->display_name} can now view and download their marksheet.");
    }

    public function unpublish(string $batchId, SchoolClass $schoolClass)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        ResultPublication::where('batch_id', $batchId)->where('school_class_id', $schoolClass->id)
            ->update(['published_at' => null]);

        return back()->with('success', 'Result unpublished. It is hidden from students and parents until republished.');
    }

    /* ───────────────────────── Marksheet: view / download ───────────────────────── */

    /** Admin preview or download of one student's marksheet (works whether published or not). */
    public function studentMarksheet(string $batchId, SchoolClass $schoolClass, Student $student)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $sheet       = $this->buildMarksheet($batchId, $schoolClass, $student);
        $publication = ResultPublication::for($batchId, $schoolClass->id);
        $examName    = $this->examinationsQuery($batchId, $schoolClass->id)->value('name');

        return view('results.marksheet', [
            'sheets'      => collect([$sheet]),
            'publication' => $publication,
            'batchId'     => $batchId,
            'examName'    => $examName,
        ]);
    }

    /** Admin bulk download — every active student in the class, one printable document. */
    public function classBulk(string $batchId, SchoolClass $schoolClass)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $sheets      = $this->rankClass($batchId, $schoolClass);
        $publication = ResultPublication::for($batchId, $schoolClass->id);
        $examName    = $this->examinationsQuery($batchId, $schoolClass->id)->value('name');

        return view('results.marksheet', compact('sheets', 'publication', 'batchId', 'examName'));
    }

    /* ───────────────────────── Student portal ───────────────────────── */

    public function myResults()
    {
        $student = auth()->user()->student;
        abort_unless($student, 404, 'Your student profile is not linked yet.');
        abort_unless($student->school_class_id, 404);

        $publications = ResultPublication::where('school_class_id', $student->school_class_id)
            ->whereNotNull('published_at')
            ->orderByDesc('published_at')
            ->get();

        return view('student.results', compact('student', 'publications'));
    }

    public function myMarksheet(string $batchId)
    {
        $student = auth()->user()->student;
        abort_unless($student, 404);

        $schoolClass = $student->schoolClass;
        abort_unless($schoolClass, 404);

        $publication = ResultPublication::for($batchId, $schoolClass->id);
        abort_unless($publication?->isPublished(), 404, 'This result has not been published yet.');

        $sheet = $this->buildMarksheet($batchId, $schoolClass, $student);

        return view('results.marksheet', [
            'sheets'      => collect([$sheet]),
            'publication' => $publication,
            'batchId'     => $batchId,
        ]);
    }

    /* ───────────────────────── Parent portal ───────────────────────── */

    public function childResults(Student $student)
    {
        $this->authorizeChild($student);
        abort_unless($student->school_class_id, 404);

        $publications = ResultPublication::where('school_class_id', $student->school_class_id)
            ->whereNotNull('published_at')
            ->orderByDesc('published_at')
            ->get();

        return view('guardian.child-results', compact('student', 'publications'));
    }

    public function childMarksheet(Student $student, string $batchId)
    {
        $this->authorizeChild($student);

        $schoolClass = $student->schoolClass;
        abort_unless($schoolClass, 404);

        $publication = ResultPublication::for($batchId, $schoolClass->id);
        abort_unless($publication?->isPublished(), 404, 'This result has not been published yet.');

        $sheet = $this->buildMarksheet($batchId, $schoolClass, $student);

        return view('results.marksheet', [
            'sheets'      => collect([$sheet]),
            'publication' => $publication,
            'batchId'     => $batchId,
        ]);
    }

    private function authorizeChild(Student $student): void
    {
        $parent = ParentModel::where('user_id', auth()->id())->first();
        abort_unless($parent && (int) $student->parent_id === (int) $parent->id, 403);
    }
}

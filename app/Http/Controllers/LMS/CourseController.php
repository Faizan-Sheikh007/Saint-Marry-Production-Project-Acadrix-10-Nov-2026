<?php
namespace App\Http\Controllers\LMS;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Subject;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\CourseEnrollment;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    use \App\Traits\PlanLimitTrait;
    public function index()
    {
        $teacher = auth()->user()->teacher;
        $isAdmin = auth()->user()->isAdmin();

        // Teacher "My Subjects" is driven exclusively by Admin subject assignments,
        // not free-form LMS course records. Timetable and students belong to that class.
        if (auth()->user()->isTeacher()) {
            $assignments = $teacher
                ? app(\App\Services\SemesterCurriculum::class)->forTeacher((int) $teacher->id)
                : collect();
            $coursesByAssignment = $assignments->mapWithKeys(function ($assignment) {
                $course = Course::syncForAssignment($assignment);
                return [$assignment->school_class_id.':'.$assignment->subject_id => $course];
            });
            $teacherPeriods = $teacher ? \App\Models\Timetable::with(['subject', 'schoolClass.program'])
                ->where('teacher_id', $teacher->id)->orderBy('start_time')->get()
                ->sortBy(fn ($row) => array_search($row->day, ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']))
                : collect();

            $classIds = $assignments->pluck('school_class_id')->unique()->values();
            $studentsByClass = $classIds->isEmpty() ? collect() : Student::query()
                ->whereIn('school_class_id', $classIds)
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'school_class_id', 'student_id', 'roll_number', 'name',
                    'father_name', 'email', 'phone', 'section', 'shift', 'session'])
                ->groupBy('school_class_id');

            $timetableByAssignment = $teacherPeriods->groupBy(
                fn ($row) => $row->school_class_id . ':' . $row->subject_id
            );

            return view('lms.courses.teacher-subjects', compact(
                'assignments', 'studentsByClass', 'timetableByAssignment', 'coursesByAssignment'
            ));
        }
        $student = auth()->user()->isStudent() ? auth()->user()->student : null;
        abort_if(auth()->user()->isStudent() && ! $student, 403, 'No student profile is linked to this account.');

        if ($student && $student->school_class_id) {
            // Self-healing safety net: every subject assigned to this
            // student's class should have a matching LMS course they're
            // enrolled in. This normally happens the moment an admin
            // assigns/edits a subject (Course::syncForAssignment), but if
            // that ever gets missed (e.g. data imported directly, or a
            // future migration is skipped) the subject would silently
            // never appear here. Re-running the sync on every page load is
            // cheap (a handful of assignments per class, all idempotent
            // updateOrCreate calls) and guarantees this can't happen again.
            app(\App\Services\SemesterCurriculum::class)->forClass($student->schoolClass)
                ->each(fn ($assignment) => Course::syncForAssignment($assignment));
        }

        $query = Course::withCount(['lessons', 'enrollments']);
        $query->withCount($student ? [
            'assignments' => fn ($q) => $q->visibleToStudent($student),
            'quizzes' => fn ($q) => $q->visibleToStudent($student),
        ] : ['assignments', 'quizzes']);

        if ($student) {
            // "My Subjects" for a student must be real, live enrollment data —
            // not every course in the system (was previously unfiltered here).
            $enrolledIds = CourseEnrollment::where("student_id", $student->id)->pluck("course_id");
            $query->whereIn("id", $enrolledIds)->where("status", "published")->whereHas("enrollments", fn ($q) => $q->where("student_id", $student->id)->where("status", "active"))->with("teacher", "subject");
        } elseif ($teacher && !$isAdmin) {
            $query->where("teacher_id", $teacher->id);
        }

        $courses = $query->latest()->get();
        return view("lms.courses.index", compact("courses"));
    }

    public function create()
    {
        $subjects = Subject::all();
        $classes  = SchoolClass::all();
        return view("lms.courses.form", compact("subjects", "classes"));
    }

    public function store(Request $request)
    {
        $teacher = auth()->user()->teacher;
        $data = $request->validate([
            "title"           => "required|string|max:255",
            "description"     => "nullable|string",
            "subject_id"      => "nullable|exists:subjects,id",
            "school_class_id" => "nullable|exists:school_classes,id",
            "status"          => "required|in:draft,published,archived",
        ]);
        $data["teacher_id"] = $teacher?->id;
        $course = Course::create($data);

        if ($course->school_class_id && $course->status === "published") {
            Student::activeEnrollment()->where("school_class_id", $course->school_class_id)->each(function ($s) use ($course) {
                CourseEnrollment::firstOrCreate(
                    ["course_id" => $course->id, "student_id" => $s->id],
                    ["enrolled_at" => now(), "status" => "active"]
                );
            });
        }
        return redirect()->route("lms.courses.show", $course)->with("success", "Course created!");
    }

    public function show(Course $course)
    {
        app(\App\Services\LearningAccess::class)->read($course);
        $course->load([
            "lessons"                        => fn($q) => $q->orderBy("order"),
            "assignments.submissions.student",
            "quizzes.questions",
            "quizzes.attempts.student",
            "enrollments.student",
            "lmsAnnouncements"                => fn($q) => $q->latest(),
            "materials"                       => fn($q) => $q->orderBy("order"),
            "questions"                       => fn($q) => $q->with(["student", "teacher"])->latest(),
        ]);

        // Only the assigned teacher gets management/write UI. Admin is read-only
        // (monitoring), even though they can view everything a teacher can see.
        $isTeacher = auth()->user()->isTeacher();
        $isAdmin   = auth()->user()->isAdmin();
        $student   = auth()->user()->isStudent() ? auth()->user()->student : null;

        if ($student) {
            $course->setRelation('assignments', $course->assignments->filter(fn ($task) => $task->visibleFor($student))->values());
            $course->setRelation('quizzes', $course->quizzes->filter(fn ($task) => $task->visibleFor($student))->values());
            foreach ($course->assignments as $task) $task->setRelation('submissions', $task->submissions->where('student_id', $student->id)->values());
            foreach ($course->quizzes as $task) $task->setRelation('attempts', $task->attempts->where('student_id', $student->id)->values());
            $course->setRelation('enrollments', $course->enrollments->where('student_id', $student->id)->values());
        }

        $questionsAskedToday = $student
            ? \App\Models\LmsQuestion::where("student_id", $student->id)
                ->whereDate("created_at", now()->toDateString())
                ->count()
            : 0;

        // Split lessons into two tabs: text/file "Lessons & Notes" vs. video "Video Lectures".
        $notesLessons = $course->lessons->whereIn("type", ["text", "file"]);
        $videoLessons = $course->lessons->whereIn("type", ["video", "mixed"]);

        // All submissions across every assignment in this course (for the Submissions tab).
        $allSubmissions = $course->assignments->flatMap->submissions;

        // Per-student overall grade % = average of (assignment %s + quiz %s) in this course.
        $studentGrades = $course->enrollments->mapWithKeys(function ($enrollment) use ($course) {
            $studentId = $enrollment->student_id;
            $aPcts = $course->assignments->flatMap->submissions
                ->where("student_id", $studentId)
                ->whereNotNull("marks_obtained")
                ->map(function ($s) {
                    $total = $s->assignment->total_marks ?? 0;
                    return $total > 0 ? $s->marks_obtained / $total * 100 : null;
                })->filter();
            $qPcts = $course->quizzes->flatMap->attempts
                ->where("student_id", $studentId)
                ->where("status", "submitted")
                ->pluck("percentage");
            $all = $aPcts->merge($qPcts);
            return [$studentId => $all->isEmpty() ? null : round($all->avg(), 1)];
        });

        return view("lms.courses.show", compact(
            "course", "isTeacher", "isAdmin", "student", "questionsAskedToday",
            "notesLessons", "videoLessons", "allSubmissions", "studentGrades"
        ));
    }

    public function edit(Course $course)
    {
        app(\App\Services\LearningAccess::class)->manage($course);
        $subjects = Subject::all();
        $classes  = SchoolClass::all();
        return view("lms.courses.form", compact("course", "subjects", "classes"));
    }

    public function update(Request $request, Course $course)
    {
        app(\App\Services\LearningAccess::class)->manage($course);
        $wasPublished = $course->status === "published";

        $data = $request->validate([
            "title"           => "required|string|max:255",
            "description"     => "nullable|string",
            "subject_id"      => "nullable|exists:subjects,id",
            "school_class_id" => "nullable|exists:school_classes,id",
            "status"          => "required|in:draft,published,archived",
        ]);
        $course->update($data);

        // If this course just became published (or already was, and its class
        // changed), make sure every active student in its class is enrolled —
        // mirrors the auto-enroll that happens at creation time in store().
        if ($course->school_class_id && $course->status === "published" && !$wasPublished) {
            Student::activeEnrollment()->where("school_class_id", $course->school_class_id)->each(function ($s) use ($course) {
                CourseEnrollment::firstOrCreate(
                    ["course_id" => $course->id, "student_id" => $s->id],
                    ["enrolled_at" => now(), "status" => "active"]
                );
            });
        }

        return redirect()->route("lms.courses.show", $course)->with("success", "Course updated!");
    }

    public function destroy(Course $course)
    {
        app(\App\Services\LearningAccess::class)->manage($course);
        $course->delete();
        return redirect()->route("lms.courses.index")->with("success", "Course deleted!");
    }

    public function browse()
    {
        // Courses are now auto-created and auto-enrolled per subject
        // assignment (see Course::syncForAssignment) — there's nothing left
        // for a student to "browse" and self-enroll into. Keep the route
        // alive (in case it's bookmarked/linked anywhere) but send people to
        // the real subject list instead of an always-empty browse page.
        return redirect()->route("lms.courses.index");
    }

    public function enroll(Course $course)
    {
        abort_unless(auth()->user()->student && $course->status === 'published'
            && (int) auth()->user()->student->school_class_id === (int) $course->school_class_id, 403);
        $student = auth()->user()->student;
        CourseEnrollment::firstOrCreate(
            ["course_id" => $course->id, "student_id" => $student->id],
            ["enrolled_at" => now(), "status" => "active"]
        );
        return redirect()->route("lms.courses.show", $course)->with("success", "Enrolled successfully!");
    }
}

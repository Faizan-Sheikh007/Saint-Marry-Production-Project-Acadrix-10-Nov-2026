<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Practical;
use App\Models\Project;
use App\Models\Quiz;
use App\Models\Survey;
use App\Models\Teacher;
use App\Models\Tour;
use App\Services\NotifierService;
use Illuminate\Http\Request;

class TeacherActivityController extends Controller
{
    /** One page, tabbed: Tours / Projects / Practicals / Assignments / Surveys. */
    public function index(Request $request)
    {
        $teachers = Teacher::where('status', 'active')->orderBy('name')->get(['id', 'name']);
        $subjects = \App\Models\Subject::orderBy('name')->get(['id', 'name']);
        app(\App\Services\SemesterCurriculum::class)->syncCourses();
        $courses = \App\Models\Course::with('subject', 'schoolClass.program')->where('status', 'published')
            ->whereNotNull('teacher_id')->orderBy('title')->get();

        $filterTeacherId = $request->integer('teacher_id') ?: null;
        $limit = $filterTeacherId ? 500 : 50;

        $tours      = Tour::with('teacher', 'subject')->when($filterTeacherId, fn ($q) => $q->where('teacher_id', $filterTeacherId))->latest()->take($limit)->get();
        $projects   = Project::with('teacher', 'subject')->when($filterTeacherId, fn ($q) => $q->where('teacher_id', $filterTeacherId))->latest()->take($limit)->get();
        $practicals = Practical::with('teacher', 'subject')->when($filterTeacherId, fn ($q) => $q->where('teacher_id', $filterTeacherId))->latest()->take($limit)->get();
        $assignments = Assignment::adminRequests()->with('teacher', 'course.subject')->when($filterTeacherId, fn ($q) => $q->where('teacher_id', $filterTeacherId))->latest()->take($limit)->get();
        $quizzes = Quiz::adminRequests()->with('teacher', 'course.subject')->when($filterTeacherId, fn ($q) => $q->where('teacher_id', $filterTeacherId))->latest()->take($limit)->get();
        $surveys    = Survey::with('teacher', 'subject')->when($filterTeacherId, fn ($q) => $q->where('teacher_id', $filterTeacherId))->latest()->take($limit)->get();

        return view('admin.activities.index', compact(
            'teachers', 'subjects', 'tours', 'projects', 'practicals', 'assignments', 'quizzes', 'courses', 'surveys', 'filterTeacherId'
        ));
    }

    /** Inbox: quiz / assignment / practical paragraphs teachers have submitted, awaiting admin review. */
    public function requests()
    {
        $assignments = Assignment::adminRequests()->with('teacher', 'course.subject')->latest()->get();
        $quizzes     = Quiz::adminRequests()->with('teacher', 'course.subject')->latest()->get();
        $practicals  = Practical::whereNotNull('paragraph')->with('teacher', 'subject')->latest()->get();

        return view('admin.activities.requests', compact('assignments', 'quizzes', 'practicals'));
    }

    // ── Tours ────────────────────────────────────────────────────────────
    public function storeTour(Request $request)
    {
        $data = $request->validate([
            'teacher_id' => ['required', 'exists:teachers,id'],
            'subject_id' => ['nullable', 'exists:subjects,id'],
            'name'       => ['required', 'string', 'max:190'],
            'tour_date'  => ['required', 'date'],
            'place'      => ['required', 'string', 'max:190'],
            'notes'      => ['nullable', 'string', 'max:2000'],
        ]);
        $data['assigned_by'] = auth()->id();
        $tour = Tour::create($data);

        $this->notifyTeacher($tour->teacher, 'New tour assigned: ' . $tour->name, 'fa-bus');

        return back()->with('success', 'Tour assigned.');
    }

    // ── Projects ─────────────────────────────────────────────────────────
    public function storeProject(Request $request)
    {
        $data = $request->validate([
            'teacher_id'  => ['required', 'exists:teachers,id'],
            'subject_id'  => ['nullable', 'exists:subjects,id'],
            'type'        => ['required', 'in:thesis,review_article'],
            'title'       => ['required', 'string', 'max:190'],
            'description' => ['nullable', 'string', 'max:5000'],
            'due_date'    => ['nullable', 'date'],
        ]);
        $data['assigned_by'] = auth()->id();
        $project = Project::create($data);

        $this->notifyTeacher($project->teacher, 'New project assigned: ' . $project->title, 'fa-graduation-cap');

        return back()->with('success', 'Project assigned.');
    }

    // ── Practicals (paragraph) ──────────────────────────────────────────
    public function storePractical(Request $request)
    {
        $data = $request->validate([
            'teacher_id'   => ['required', 'exists:teachers,id'],
            'subject_id'   => ['nullable', 'exists:subjects,id'],
            'title'        => ['required', 'string', 'max:190'],
            'instructions' => ['nullable', 'string', 'max:5000'],
            'due_date'     => ['nullable', 'date'],
        ]);
        $data['assigned_by'] = auth()->id();
        $practical = Practical::create($data);

        $this->notifyTeacher($practical->teacher, 'New practical requested: ' . $practical->title, 'fa-flask');

        return back()->with('success', 'Practical requested from teacher.');
    }

    public function reviewPractical(Practical $practical)
    {
        $practical->update(['status' => 'reviewed']);
        return back()->with('success', 'Marked as reviewed.');
    }

    // ── Surveys ──────────────────────────────────────────────────────────
    public function storeSurvey(Request $request)
    {
        $data = $request->validate([
            'teacher_id'  => ['required', 'exists:teachers,id'],
            'subject_id'  => ['nullable', 'exists:subjects,id'],
            'type'        => ['required', 'in:hospital,industry'],
            'title'       => ['required', 'string', 'max:190'],
            'description' => ['nullable', 'string', 'max:5000'],
            'due_date'    => ['nullable', 'date'],
        ]);
        $data['assigned_by'] = auth()->id();
        $survey = Survey::create($data);

        $this->notifyTeacher($survey->teacher, 'New survey assigned: ' . $survey->title, 'fa-clipboard-list');

        return back()->with('success', 'Survey assigned.');
    }

    /** Assign subject-specific work to its teacher; only the teacher publishes to students. */
    public function storeAssignmentRequest(Request $request)
    {
        $data = $this->taskRequestData($request);
        $assignment = Assignment::create([
            'teacher_id' => $data['teacher_id'], 'course_id' => $data['course_id'],
            'title' => $data['title'], 'instructions' => $data['instructions'] ?? null,
            'due_date' => $data['due_date'] ?? null, 'total_marks' => $data['total_marks'],
            'status' => 'closed', 'published_at' => null,
            'is_admin_request' => true, 'assigned_by' => auth()->id(), 'request_status' => 'pending',
        ]);
        $this->notifyTeacher($assignment->teacher, 'Admin assigned student work for preparation: '.$assignment->title, 'fa-file-lines');
        return back()->with('success', 'Assignment allocated to the subject teacher. It stays private until the teacher publishes it to students.');
    }

    public function storeQuizRequest(Request $request)
    {
        $data = $this->taskRequestData($request);
        $quiz = Quiz::create([
            'teacher_id' => $data['teacher_id'], 'course_id' => $data['course_id'],
            'title' => $data['title'], 'description' => $data['instructions'] ?? null,
            'due_date' => $data['due_date'] ?? null, 'total_marks' => $data['total_marks'],
            'status' => 'draft', 'published_at' => null,
            'is_admin_request' => true, 'assigned_by' => auth()->id(), 'request_status' => 'pending',
        ]);
        $this->notifyTeacher($quiz->teacher, 'Admin assigned a quiz for preparation: '.$quiz->title, 'fa-circle-question');
        return back()->with('success', 'Quiz allocated to the subject teacher. The teacher can add questions and assign it to students.');
    }

    private function taskRequestData(Request $request): array
    {
        $data = $request->validate([
            'teacher_id' => 'required|exists:teachers,id', 'course_id' => 'required|exists:courses,id',
            'title' => 'required|string|max:190', 'instructions' => 'nullable|string|max:5000',
            'due_date' => 'nullable|date', 'total_marks' => 'required|integer|min:1|max:100000',
        ]);
        $course = \App\Models\Course::findOrFail($data['course_id']);
        if ((int) $course->teacher_id !== (int) $data['teacher_id'] || $course->status !== 'published') {
            throw \Illuminate\Validation\ValidationException::withMessages(['course_id' => 'Choose a subject assigned to the selected teacher.']);
        }
        return $data;
    }

    public function reviewAssignment(Assignment $assignment)
    {
        $assignment->update(['request_status' => 'reviewed', 'admin_reviewed_at' => now()]);
        return back()->with('success', 'Marked as reviewed.');
    }

    public function reviewQuiz(Quiz $quiz)
    {
        $quiz->update(['request_status' => 'reviewed', 'admin_reviewed_at' => now()]);
        return back()->with('success', 'Marked as reviewed.');
    }

    private function notifyTeacher(?Teacher $teacher, string $message, string $icon): void
    {
        if ($teacher?->user_id) {
            NotifierService::notify($teacher->user_id, $message, $icon, 'primary');
        }
    }
}

<?php
namespace App\Http\Controllers\Teacher;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Assignment;
use App\Models\Examination;
use App\Models\Student;
use App\Models\Timetable;

class TeacherDashboardController extends Controller
{
    public function index()
    {
        $teacher    = auth()->user()->teacher;
        $courses    = $teacher
            ? Course::where("teacher_id", $teacher->id)
                    ->withCount(["lessons", "enrollments"])
                    ->get()
            : collect();

        $pending = $teacher
            ? Assignment::whereHas("course", fn($q) => $q->where("teacher_id", $teacher->id))
                        ->withCount(["submissions as ungraded" => fn($q) => $q->whereNull("marks_obtained")])
                        ->with("course")
                        ->get()
            : collect();

        $myStudents = $teacher
            ? Student::whereIn("school_class_id", $teacher->classes()->pluck("school_classes.id"))
                     ->where("status", "active")->count()
            : 0;

        $todayName = now()->format('l');

        $todaysClasses = $teacher
            ? Timetable::with('subject', 'schoolClass')
                ->where('teacher_id', $teacher->id)
                ->where('day', $todayName)
                ->orderBy('start_time')
                ->get()
            : collect();

        // Exam duties: this teacher's own assigned examinations — upcoming first,
        // plus which ones are still waiting on marks to be entered.
        $upcomingExams = $teacher
            ? Examination::with('schoolClass', 'subject')
                ->where('teacher_id', $teacher->id)
                ->where('exam_date', '>=', now()->toDateString())
                ->orderBy('exam_date')->orderBy('start_time')
                ->take(5)->get()
            : collect();

        $pendingMarksExams = $teacher
            ? Examination::withCount('results')
                ->with('schoolClass', 'subject')
                ->where('teacher_id', $teacher->id)
                ->where('exam_date', '<', now()->toDateString())
                ->whereNull('locked_at')
                ->orderByDesc('exam_date')
                ->get()
                ->filter(function ($exam) {
                    $expected = Student::where('school_class_id', $exam->school_class_id)->where('status', 'active')->count();
                    return $exam->results_count < $expected;
                })
                ->take(5)
            : collect();

        // Exam papers this teacher still needs to create/upload (due or pending).
        $pendingPapers = $teacher
            ? \App\Models\ExamPaper::forTeacher($teacher->id)
                ->whereIn('status', ['due', 'pending'])
                ->with('examination.subject', 'examination.schoolClass')
                ->get()
            : collect();

        // ── Engagement widgets: announcements, assigned activities, chat ────
        $announcements = \App\Models\TeacherAnnouncement::query()
            ->when($teacher, fn ($q) => $q->visibleTo($teacher->id), fn ($q) => $q->where('audience', 'all'))
            ->orderByDesc('pinned')->latest()->take(4)->get();

        $activityCounts = [
            'tours'       => $teacher ? \App\Models\Tour::where('teacher_id', $teacher->id)->where('status', 'assigned')->count() : 0,
            'projects'    => $teacher ? \App\Models\Project::where('teacher_id', $teacher->id)->where('status', 'assigned')->count() : 0,
            'practicals'  => $teacher ? \App\Models\Practical::where('teacher_id', $teacher->id)->whereNull('paragraph')->count() : 0,
            'surveys'     => $teacher ? \App\Models\Survey::where('teacher_id', $teacher->id)->where('status', 'assigned')->count() : 0,
            'assignments' => $teacher ? \App\Models\Assignment::adminRequests()->where('teacher_id', $teacher->id)->where('request_status', 'pending')->count() : 0,
            'quizzes'     => $teacher ? \App\Models\Quiz::adminRequests()->where('teacher_id', $teacher->id)->where('request_status', 'pending')->count() : 0,
        ];

        $unreadMessagesCount = $teacher
            ? \App\Models\TeacherMessage::where('teacher_id', $teacher->id)->where('is_read', false)
                ->whereHas('sender', fn ($q) => $q->where('role', 'admin'))->count()
            : 0;

        $latestMessage = $teacher
            ? \App\Models\TeacherMessage::where('teacher_id', $teacher->id)->latest()->first()
            : null;

        return view("teacher.dashboard", compact(
            "teacher", "courses", "pending", "myStudents", "todaysClasses", "todayName",
            "upcomingExams", "pendingMarksExams", "pendingPapers",
            "announcements", "activityCounts", "unreadMessagesCount", "latestMessage"
        ));
    }

    /**
     * Read-only fee overview for every student (including status-labelled archives) in this teacher's own
     * classes — "Student Fee without Heads": each row's previous-balance
     * total is highlighted so teachers can remind students/parents.
     */
    public function studentFees()
    {
        $teacher = auth()->user()->teacher;

        $students = $teacher
            ? Student::whereIn('school_class_id', $teacher->classes()->pluck('school_classes.id'))
                ->with('schoolClass', 'program')
                ->orderBy('name')
                ->get()
                ->map(function ($student) {
                    $summary = $student->feeSummary();
                    $student->fee_previous_balance_total = $summary['previous_balance_total'];
                    $student->fee_has_active             = $summary['active']->isNotEmpty();
                    return $student;
                })
            : collect();

        return view('teacher.student-fees', compact('students'));
    }

    /** Full fee summary for a single one of this teacher's own students. */
    public function studentFeeDetail(Student $student)
    {
        $teacher = auth()->user()->teacher;
        $isMine  = $teacher && $student->school_class_id
            && $teacher->classes()->where('school_classes.id', $student->school_class_id)->exists();

        abort_unless($isMine || auth()->user()->isAdmin(), 403, 'You can only view fees for your own students.');

        $feeSummary = $student->feeSummary();

        return view('teacher.student-fee-detail', compact('student', 'feeSummary'));
    }
}

<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Practical;
use App\Models\Project;
use App\Models\Quiz;
use App\Models\Survey;
use App\Models\Tour;
use App\Services\NotifierService;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function index()
    {
        $teacher = auth()->user()->teacher;

        $courses = $teacher ? app(\App\Services\SemesterCurriculum::class)->syncCourses((int) $teacher->id) : collect();
        $tours       = $teacher ? Tour::where('teacher_id', $teacher->id)->with('subject')->latest()->get() : collect();
        $projects    = $teacher ? Project::where('teacher_id', $teacher->id)->with('subject')->latest()->get() : collect();
        $practicals  = $teacher ? Practical::where('teacher_id', $teacher->id)->with('subject')->latest()->get() : collect();
        $surveys     = $teacher ? Survey::where('teacher_id', $teacher->id)->with('subject')->latest()->get() : collect();
        $assignments = $teacher ? Assignment::adminRequests()->where('teacher_id', $teacher->id)->with('course.subject')->latest()->get() : collect();
        $quizzes     = $teacher ? Quiz::adminRequests()->where('teacher_id', $teacher->id)->with('course.subject')->latest()->get() : collect();

        return view('teacher.activities.index', compact(
            'tours', 'projects', 'practicals', 'surveys', 'assignments', 'quizzes', 'courses'
        ));
    }

    public function linkAssignment(Request $request, Assignment $assignment)
    {
        return $this->linkTask($request, $assignment, 'assignments');
    }

    public function linkQuiz(Request $request, Quiz $quiz)
    {
        return $this->linkTask($request, $quiz, 'quizzes');
    }

    private function linkTask(Request $request, $task, string $kind)
    {
        abort_unless($this->ownsTeacherRecord($task) && $task->is_admin_request && ! $task->course_id, 403);
        $data = $request->validate(['course_id' => 'required|exists:courses,id']);
        $course = \App\Models\Course::findOrFail($data['course_id']);
        app(\App\Services\LearningAccess::class)->manage($course);
        $task->update(['course_id' => $course->id]);
        return redirect()->route('lms.'.$kind.'.edit', [$course, $task]);
    }

    private function ownsTeacherRecord($model): bool
    {
        $teacher = auth()->user()->teacher;
        return $teacher && (int) $model->teacher_id === (int) $teacher->id;
    }

    public function submitProject(Request $request, Project $project)
    {
        abort_unless($this->ownsTeacherRecord($project), 403);
        $data = $request->validate(['teacher_submission' => ['required', 'string', 'max:10000']]);
        $project->update(['teacher_submission' => $data['teacher_submission'], 'status' => 'submitted']);
        NotifierService::notifyAdmins($project->teacher->name . ' submitted project: ' . $project->title, 'fa-graduation-cap', 'green');
        return back()->with('success', 'Project submission saved.');
    }

    public function submitSurvey(Request $request, Survey $survey)
    {
        abort_unless($this->ownsTeacherRecord($survey), 403);
        $data = $request->validate(['teacher_submission' => ['required', 'string', 'max:10000']]);
        $survey->update(['teacher_submission' => $data['teacher_submission'], 'status' => 'submitted']);
        NotifierService::notifyAdmins($survey->teacher->name . ' submitted survey: ' . $survey->title, 'fa-clipboard-list', 'green');
        return back()->with('success', 'Survey submission saved.');
    }

    public function submitPractical(Request $request, Practical $practical)
    {
        abort_unless($this->ownsTeacherRecord($practical), 403);
        $data = $request->validate(['paragraph' => ['required', 'string', 'max:10000']]);
        $practical->update(['paragraph' => $data['paragraph'], 'status' => 'submitted']);
        NotifierService::notifyAdmins($practical->teacher->name . ' submitted practical paragraph: ' . $practical->title, 'fa-flask', 'green');
        return back()->with('success', 'Practical paragraph saved.');
    }

    public function submitAssignment(Request $request, Assignment $assignment)
    {
        abort_unless($this->ownsTeacherRecord($assignment), 403);
        $data = $request->validate(['teacher_response' => ['required', 'string', 'max:10000']]);
        $assignment->update(['teacher_response' => $data['teacher_response'], 'request_status' => 'submitted']);
        NotifierService::notifyAdmins($assignment->teacher->name . ' submitted assignment paragraph: ' . $assignment->title, 'fa-file-lines', 'green');
        return back()->with('success', 'Assignment paragraph saved.');
    }

    public function submitQuiz(Request $request, Quiz $quiz)
    {
        abort_unless($this->ownsTeacherRecord($quiz), 403);
        $data = $request->validate(['teacher_response' => ['required', 'string', 'max:10000']]);
        $quiz->update(['teacher_response' => $data['teacher_response'], 'request_status' => 'submitted']);
        NotifierService::notifyAdmins($quiz->teacher->name . ' submitted quiz paragraph: ' . $quiz->title, 'fa-file-lines', 'green');
        return back()->with('success', 'Quiz paragraph saved.');
    }

    public function acknowledgeTour(Tour $tour)
    {
        abort_unless($this->ownsTeacherRecord($tour), 403);
        $tour->update(['status' => 'acknowledged']);
        return back()->with('success', 'Tour acknowledged.');
    }
}

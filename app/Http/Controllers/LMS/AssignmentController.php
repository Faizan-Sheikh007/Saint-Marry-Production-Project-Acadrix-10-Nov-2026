<?php

namespace App\Http\Controllers\LMS;

use App\Http\Controllers\Controller;
use App\Models\{Assignment, AssignmentSubmission, Course};
use App\Services\LearningAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Storage};

class AssignmentController extends Controller
{
    public function __construct(private LearningAccess $access) {}

    public function create(Course $course)
    {
        $this->access->manage($course);
        return view('lms.assignments.form', ['course' => $course, 'students' => $this->access->students($course)]);
    }

    public function store(Request $request, Course $course)
    {
        $this->access->manage($course);
        $data = $this->validated($request, $course);
        $assignment = Assignment::create($data + ['course_id' => $course->id, 'teacher_id' => $course->teacher_id,
            'published_at' => $data['status'] === 'active' ? now() : null]);
        if ($assignment->published_at) $this->access->notifyPublication($assignment, $course);
        return redirect()->route('lms.courses.show', $course)->with('success', 'Assignment saved. Active assignments are visible to the selected students.');
    }

    public function show(Course $course, Assignment $assignment)
    {
        $this->access->task($course, $assignment);
        $student = auth()->user()->isStudent() ? auth()->user()->student : null;
        $submission = $student ? $assignment->submissionBy($student->id) : null;
        $submissions = $student ? collect() : $assignment->submissions()->with('student')->get();
        return view('lms.assignments.show', compact('course', 'assignment', 'student', 'submission', 'submissions'));
    }

    public function edit(Course $course, Assignment $assignment)
    {
        $this->access->task($course, $assignment, true);
        return view('lms.assignments.form', ['course' => $course, 'assignment' => $assignment,
            'students' => $this->access->students($course)]);
    }

    public function update(Request $request, Course $course, Assignment $assignment)
    {
        $this->access->task($course, $assignment, true);
        $data = $this->validated($request, $course);
        $firstPublish = ! $assignment->published_at && $data['status'] === 'active';
        if ($data['status'] === 'active') {
            $data['published_at'] = $assignment->published_at ?? now();
            if ($assignment->is_admin_request) $data['request_status'] = 'submitted';
        }
        $assignment->update($data);
        if ($firstPublish) $this->access->notifyPublication($assignment, $course);
        return redirect()->route('lms.courses.show', $course)->with('success', 'Assignment updated. Active published work is shown in the student portal.');
    }

    private function validated(Request $request, Course $course): array
    {
        $data = $request->validate([
            'title' => 'required|string|max:255', 'description' => 'nullable|string',
            'instructions' => 'nullable|string', 'due_date' => 'nullable|date',
            'total_marks' => 'required|integer|min:1|max:100000', 'status' => 'required|in:active,closed',
            'attachment' => 'nullable|file|max:51200|mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,txt,zip',
        ]);
        $data['target_student_ids'] = $this->access->audience($request, $course);
        if ($request->hasFile('attachment')) {
            $data['file_attachment'] = $request->file('attachment')->store('lms/assignments/'.$course->id, 'public');
        }
        unset($data['attachment']);
        return $data;
    }

    public function destroy(Course $course, Assignment $assignment)
    {
        $this->access->task($course, $assignment, true);
        $assignment->delete();
        return redirect()->route('lms.courses.show', $course)->with('success', 'Assignment deleted.');
    }

    public function submit(Request $request, Course $course, Assignment $assignment)
    {
        $this->access->task($course, $assignment);
        abort_unless($assignment->status === 'active', 403, 'This assignment is closed.');
        $student = auth()->user()->student;
        $data = $request->validate([
            'content' => 'nullable|string|max:100000',
            'file' => 'nullable|file|max:51200|mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,txt,zip,jpg,jpeg,png',
        ]);
        if (empty($data['content']) && ! $request->hasFile('file')) {
            return back()->withErrors(['content' => 'Please write a submission or attach a file.']);
        }
        return DB::transaction(function () use ($request, $data, $student, $assignment) {
            \App\Models\Student::whereKey($student->id)->lockForUpdate()->firstOrFail();
            $existing = $assignment->submissions()->where('student_id', $student->id)->first();
            if ($existing) return back()->with('success', 'Your submission is already recorded.');
            $payload = ['content' => $data['content'] ?? null, 'submitted_at' => now(),
                'status' => $assignment->due_date && now()->gt($assignment->due_date) ? 'late' : 'submitted'];
            if ($request->hasFile('file')) $payload['file_path'] = $request->file('file')->store('lms/submissions/'.$assignment->id, 'public');
            $assignment->submissions()->create($payload + ['student_id' => $student->id]);
            return back()->with('success', 'Assignment submitted.');
        }, 3);
    }

    public function grade(Request $request, Course $course, Assignment $assignment, AssignmentSubmission $submission)
    {
        $this->access->task($course, $assignment, true);
        abort_unless((int) $submission->assignment_id === (int) $assignment->id, 404);
        $data = $request->validate(['marks_obtained' => 'required|integer|min:0|max:'.$assignment->total_marks,
            'feedback' => 'nullable|string']);
        $submission->update($data + ['status' => 'graded']);
        return back()->with('success', 'Submission graded.');
    }
}

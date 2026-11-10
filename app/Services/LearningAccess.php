<?php

namespace App\Services;

use App\Models\{Course, Student};
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LearningAccess
{
    public function read(Course $course): void
    {
        $user = auth()->user();
        abort_unless($user, 403);
        if ($user->isAdmin()) return;
        if ($user->isTeacher()) {
            abort_unless($user->teacher && (int) $course->teacher_id === (int) $user->teacher->id, 403);
            return;
        }
        $student = $user->isStudent() ? $user->student : null;
        abort_unless($student && $student->isActiveEnrollment() && $course->status === 'published' &&
            $course->enrollments()->where('student_id', $student->id)->where('status', 'active')->exists(), 403);
    }

    public function manage(Course $course): void
    {
        abort_unless(auth()->user()?->isTeacher(), 403);
        $this->read($course);
    }

    public function task(Course $course, $task, bool $write = false): void
    {
        abort_unless((int) $task->course_id === (int) $course->id, 404);
        $write ? $this->manage($course) : $this->read($course);
        if (auth()->user()->isStudent()) {
            abort_unless($task->visibleFor(auth()->user()->student), 404);
        }
    }

    public function audience(Request $request, Course $course): ?array
    {
        $request->validate([
            'audience' => 'required|in:all,selected',
            'target_student_ids' => 'nullable|array',
            'target_student_ids.*' => 'integer|distinct',
        ]);
        if ($request->input('audience') === 'all') return null;
        $ids = array_values(array_unique(array_map('intval', $request->input('target_student_ids', []))));
        $count = $course->enrollments()->where('status', 'active')
            ->whereHas('student', fn ($q) => $q->activeEnrollment())->whereIn('student_id', $ids)->count();
        if (! count($ids) || $count !== count($ids)) {
            throw ValidationException::withMessages(['target_student_ids' => 'Select at least one actively enrolled student from this subject.']);
        }
        return $ids;
    }

    public function students(Course $course)
    {
        return Student::activeEnrollment()->whereHas('enrollments', fn ($q) => $q->where('course_id', $course->id)->where('status', 'active'))
            ->orderBy('name')->get();
    }

    public function notifyPublication($task, Course $course): void
    {
        $kind = $task instanceof \App\Models\Quiz ? 'quiz' : 'assignment';
        foreach ($this->students($course) as $student) {
            if ($student->user_id && $task->visibleFor($student)) {
                NotifierService::notify($student->user_id, 'New '.$kind.' in '.$course->title.': '.$task->title,
                    $kind === 'quiz' ? 'fa-circle-question' : 'fa-file-lines', 'primary');
            }
        }
    }
}

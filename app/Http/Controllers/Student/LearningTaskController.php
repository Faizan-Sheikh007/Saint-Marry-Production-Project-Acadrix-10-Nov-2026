<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\{ClassSubject, Course};
use Illuminate\Http\Request;

class LearningTaskController extends Controller
{
    public function index(Request $request)
    {
        $student = auth()->user()->student;
        abort_unless($student, 403);
        if ($student->school_class_id) {
            app(\App\Services\SemesterCurriculum::class)->forClass($student->schoolClass)
                ->each(fn ($assignment) => Course::syncForAssignment($assignment));
        }
        $courses = Course::where('status', 'published')
            ->whereHas('enrollments', fn ($q) => $q->where('student_id', $student->id)->where('status', 'active'))
            ->with(['teacher', 'subject', 'schoolClass.program',
                'assignments' => fn ($q) => $q->visibleToStudent($student)->with(['submissions' => fn ($s) => $s->where('student_id', $student->id)])->orderBy('due_date'),
                'quizzes' => fn ($q) => $q->visibleToStudent($student)->with(['attempts' => fn ($a) => $a->where('student_id', $student->id)->latest('id')])->orderBy('due_date'),
            ])->orderBy('title')->get();
        $teachers = $courses->pluck('teacher')->filter()->unique('id')->sortBy('name');
        if ($request->filled('teacher_id')) $courses = $courses->where('teacher_id', $request->integer('teacher_id'));
        if ($request->filled('subject_id')) $courses = $courses->where('subject_id', $request->integer('subject_id'));
        $byTeacher = $courses->groupBy(fn ($course) => $course->teacher_id ?: 'unassigned');
        return view('student.learning-tasks', compact('student', 'byTeacher', 'teachers'));
    }
}

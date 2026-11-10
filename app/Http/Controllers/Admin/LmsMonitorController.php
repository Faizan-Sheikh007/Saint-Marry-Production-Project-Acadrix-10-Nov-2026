<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Models\Subject;

class LmsMonitorController extends Controller
{
    /** Step 1: list all teachers with how many LMS courses each one runs. */
    public function teachers()
    {
        $teachers = Teacher::withCount('courses')
            ->with('subject')
            ->orderBy('name')
            ->get();

        return view('admin.lms.teachers', compact('teachers'));
    }

    /** Step 2: the subjects this teacher has LMS courses in (grouped, with a class/course count each). */
    public function teacherSubjects(Teacher $teacher)
    {
        $subjects = $teacher->courses()
            ->with('subject')
            ->get()
            ->groupBy('subject_id')
            ->map(function ($courses) {
                return (object) [
                    'subject' => $courses->first()->subject,
                    'courses_count' => $courses->count(),
                    'lessons_count' => $courses->sum(fn($c) => $c->lessons()->count()),
                    'students_count' => $courses->sum(fn($c) => $c->enrollments()->count()),
                ];
            })
            ->values();

        return view('admin.lms.teacher-subjects', compact('teacher', 'subjects'));
    }

    /** Step 3: the classes/programs (courses) this teacher runs for one specific subject. */
    public function teacherCourses(Teacher $teacher, int $subjectId)
    {
        $subject = $subjectId ? Subject::find($subjectId) : null;

        $courses = $teacher->courses()
            ->when($subjectId, fn($q) => $q->where('subject_id', $subjectId), fn($q) => $q->whereNull('subject_id'))
            ->with(['subject', 'schoolClass'])
            ->withCount(['lessons', 'assignments', 'quizzes', 'enrollments'])
            ->latest()
            ->get();

        return view('admin.lms.teacher-courses', compact('teacher', 'subject', 'courses'));
    }
}

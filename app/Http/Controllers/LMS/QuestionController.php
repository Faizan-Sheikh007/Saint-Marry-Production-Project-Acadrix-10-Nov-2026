<?php
namespace App\Http\Controllers\LMS;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\LmsQuestion;
use Illuminate\Http\Request;

class QuestionController extends Controller
{
    const DAILY_LIMIT = 3;

    /** Student-only: ask the course teacher a question, capped at 3/day. */
    public function store(Request $request, Course $course)
    {
        $student = auth()->user()->student;
        abort_unless($student, 403);

        $askedToday = LmsQuestion::where('student_id', $student->id)
            ->whereDate('created_at', now()->toDateString())
            ->count();

        if ($askedToday >= self::DAILY_LIMIT) {
            return back()->with('error', 'You have reached your limit of ' . self::DAILY_LIMIT . ' questions for today. Please try again tomorrow.');
        }

        $data = $request->validate([
            'question' => 'required|string|max:2000',
        ]);

        LmsQuestion::create([
            'course_id'  => $course->id,
            'student_id' => $student->id,
            'teacher_id' => $course->teacher_id,
            'question'   => $data['question'],
            'status'     => 'pending',
        ]);

        return back()->with('success', 'Your question has been sent to the teacher.');
    }

    /** Teacher-only: answer a student's question. */
    public function answer(Request $request, Course $course, LmsQuestion $question)
    {
        $data = $request->validate([
            'answer' => 'required|string|max:4000',
        ]);

        $question->update([
            'answer'      => $data['answer'],
            'answered_at' => now(),
            'status'      => 'answered',
        ]);

        return back()->with('success', 'Answer posted!');
    }
}

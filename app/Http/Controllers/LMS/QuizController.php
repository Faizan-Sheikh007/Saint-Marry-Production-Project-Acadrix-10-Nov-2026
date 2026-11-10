<?php

namespace App\Http\Controllers\LMS;

use App\Http\Controllers\Controller;
use App\Models\{Course, Quiz, QuizAttempt, Student};
use App\Services\LearningAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuizController extends Controller
{
    public function __construct(private LearningAccess $access) {}

    public function create(Course $course)
    {
        $this->access->manage($course);
        return view('lms.quizzes.form', ['course' => $course, 'students' => $this->access->students($course)]);
    }

    public function edit(Course $course, Quiz $quiz)
    {
        $this->access->task($course, $quiz, true);
        return view('lms.quizzes.form', ['course' => $course, 'quiz' => $quiz->load('questions'),
            'students' => $this->access->students($course)]);
    }

    public function store(Request $request, Course $course)
    {
        $this->access->manage($course);
        $quiz = $this->saveQuiz($request, $course);
        return redirect()->route('lms.quizzes.show', [$course, $quiz])->with('success', 'Quiz saved. Active quizzes are available to the selected students.');
    }

    public function update(Request $request, Course $course, Quiz $quiz)
    {
        $this->access->task($course, $quiz, true);
        $this->saveQuiz($request, $course, $quiz);
        return redirect()->route('lms.quizzes.show', [$course, $quiz])->with('success', 'Quiz updated.');
    }

    private function saveQuiz(Request $request, Course $course, ?Quiz $quiz = null): Quiz
    {
        $data = $request->validate([
            'title' => 'required|string|max:255', 'description' => 'nullable|string',
            'time_limit' => 'required|integer|min:1|max:480',
            'passing_marks' => 'required|integer|min:1', 'attempts_allowed' => 'required|integer|min:1|max:20',
            'status' => 'required|in:draft,active,closed', 'due_date' => 'nullable|date',
            'questions' => 'required|array|min:1|max:200',
            'questions.*.question' => 'required|string|max:10000',
            'questions.*.type' => 'required|in:mcq,true_false,short',
            'questions.*.correct_answer' => 'required|string|max:255',
            'questions.*.marks' => 'required|integer|min:1|max:1000',
            'questions.*.options' => 'nullable|array|max:4',
            'questions.*.options.*' => 'nullable|string|max:2000',
        ]);
        $data['target_student_ids'] = $this->access->audience($request, $course);
        $questions = $data['questions'];
        unset($data['questions']);
        $data['total_marks'] = array_sum(array_column($questions, 'marks'));
        if ($data['passing_marks'] > $data['total_marks']) {
            throw ValidationException::withMessages(['passing_marks' => 'Passing marks cannot exceed the sum of question marks.']);
        }
        foreach ($questions as $i => &$question) {
            $question['options'] = array_values(array_filter($question['options'] ?? [], fn ($v) => is_string($v) && trim($v) !== ''));
            $correct = mb_strtolower(trim($question['correct_answer']));
            if ($question['type'] === 'mcq' && (count($question['options']) < 2 ||
                ! in_array($correct, array_map(fn ($v) => mb_strtolower(trim($v)), $question['options']), true))) {
                throw ValidationException::withMessages(["questions.$i.correct_answer" => 'An MCQ needs at least two options and a correct answer matching one option.']);
            }
            if ($question['type'] === 'true_false' && ! in_array($correct, ['true', 'false'], true)) {
                throw ValidationException::withMessages(["questions.$i.correct_answer" => 'Use true or false as the correct answer.']);
            }
        }
        unset($question);
        return DB::transaction(function () use ($quiz, $course, $data, $questions) {
            if ($quiz) {
                $quiz = Quiz::whereKey($quiz->id)->lockForUpdate()->firstOrFail();
                if ($quiz->attempts()->exists()) {
                    throw ValidationException::withMessages(['title' => 'This quiz already has student attempts. Create a new quiz to change its questions or settings without rewriting existing results.']);
                }
            }
            $firstPublish = ! $quiz?->published_at && $data['status'] === 'active';
            $data['published_at'] = $quiz?->published_at ?? ($data['status'] === 'active' ? now() : null);
            if ($quiz?->is_admin_request && $data['status'] === 'active') $data['request_status'] = 'submitted';
            if ($quiz) $quiz->update($data);
            else $quiz = Quiz::create($data + ['course_id' => $course->id, 'teacher_id' => $course->teacher_id]);
            $quiz->questions()->delete();
            foreach ($questions as $i => $question) $quiz->questions()->create($question + ['order' => $i + 1]);
            if ($firstPublish) $this->access->notifyPublication($quiz, $course);
            return $quiz;
        }, 3);
    }

    public function show(Course $course, Quiz $quiz)
    {
        $this->access->task($course, $quiz);
        $quiz->load('questions');
        $student = auth()->user()->isStudent() ? auth()->user()->student : null;
        $attempt = $student ? $quiz->attemptByStudent($student->id) : null;
        $attemptsUsed = $student ? $quiz->attempts()->where('student_id', $student->id)->where('status', 'submitted')->count() : 0;
        $canAttempt = $quiz->status === 'active' && (! $quiz->due_date || now()->lte($quiz->due_date))
            && $attemptsUsed < $quiz->attempts_allowed;
        $allAttempts = $student ? collect() : $quiz->attempts()->with('student')->latest()->get();
        return view('lms.quizzes.show', compact('course', 'quiz', 'student', 'attempt', 'allAttempts', 'canAttempt', 'attemptsUsed'));
    }

    public function take(Course $course, Quiz $quiz)
    {
        $this->access->task($course, $quiz);
        abort_unless($quiz->status === 'active' && (! $quiz->due_date || now()->lte($quiz->due_date)), 403, 'This quiz is closed or past its due date.');
        $student = auth()->user()->student;
        $attempt = DB::transaction(function () use ($student, $quiz) {
            $quiz = Quiz::whereKey($quiz->id)->lockForUpdate()->firstOrFail();
            Student::whereKey($student->id)->lockForUpdate()->firstOrFail();
            $attempt = $quiz->attempts()->where('student_id', $student->id)->where('status', 'in_progress')->latest('id')->first();
            if ($attempt) return $attempt;
            abort_if($quiz->attempts()->where('student_id', $student->id)->where('status', 'submitted')->count() >= $quiz->attempts_allowed,
                403, 'You have used all allowed attempts.');
            return $quiz->attempts()->create(['student_id' => $student->id, 'started_at' => now(),
                'total_marks' => $quiz->total_marks, 'status' => 'in_progress']);
        }, 3);
        $quiz->load('questions');
        $deadline = $attempt->started_at->copy()->addMinutes($quiz->time_limit);
        if ($quiz->due_date && $quiz->due_date->lt($deadline)) $deadline = $quiz->due_date;
        $remainingSeconds = max(0, (int) now()->diffInSeconds($deadline, false));
        return view('lms.quizzes.take', compact('course', 'quiz', 'attempt', 'remainingSeconds'));
    }

    public function submit(Request $request, Course $course, Quiz $quiz)
    {
        $this->access->task($course, $quiz);
        abort_unless($quiz->status === 'active', 403, 'This quiz is closed.');
        $data = $request->validate(['attempt_id' => 'required|integer', 'answers' => 'nullable|array', 'answers.*' => 'nullable|string|max:2000']);
        $student = auth()->user()->student;
        return DB::transaction(function () use ($course, $quiz, $student, $data) {
            $quiz = Quiz::whereKey($quiz->id)->lockForUpdate()->firstOrFail();
            $attempt = $quiz->attempts()->where('student_id', $student->id)->whereKey($data['attempt_id'])->lockForUpdate()->firstOrFail();
            if ($attempt->status === 'submitted') {
                return redirect()->route('lms.quizzes.show', [$course, $quiz])->with('success', 'This attempt is already submitted.');
            }
            $deadline = $attempt->started_at->copy()->addMinutes($quiz->time_limit);
            if ($quiz->due_date && $quiz->due_date->lt($deadline)) $deadline = $quiz->due_date;
            // Small delivery grace for the timer's automatic submit. Late payloads cannot gain marks.
            $expired = now()->gt($deadline->copy()->addSeconds(10));
            $answers = $data['answers'] ?? [];
            $score = 0;
            $recordedAnswers = [];
            foreach ($quiz->questions as $question) {
                $given = trim($answers[$question->id] ?? '');
                $recordedAnswers[$question->id] = $given;
                if (! $expired && $given !== '' && mb_strtolower($given) === mb_strtolower(trim($question->correct_answer))) $score += $question->marks;
            }
            $percentage = $quiz->total_marks > 0 ? round($score / $quiz->total_marks * 100, 2) : 0;
            $attempt->update(['answers' => $recordedAnswers, 'score' => $score, 'percentage' => $percentage,
                'submitted_at' => now(), 'status' => 'submitted']);
            return redirect()->route('lms.quizzes.show', [$course, $quiz])->with('success',
                $expired ? 'Attempt closed because its time limit expired.' : "Quiz submitted. Score: $score/{$quiz->total_marks} ($percentage%).");
        }, 3);
    }

    public function destroy(Course $course, Quiz $quiz)
    {
        $this->access->task($course, $quiz, true);
        $quiz->delete();
        return redirect()->route('lms.courses.show', $course)->with('success', 'Quiz deleted.');
    }
}

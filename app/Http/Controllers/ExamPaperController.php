<?php

namespace App\Http\Controllers;

use App\Models\ExamPaper;
use App\Models\ExamPaperQuestion;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExamPaperController extends Controller
{
    private function isAdmin(): bool
    {
        return auth()->user()->isAdmin();
    }

    private function currentTeacher(): ?Teacher
    {
        return auth()->user()->teacher;
    }

    /**
     * Admin view — every teacher who has at least one exam paper, grouped, with
     * their papers listed subject by subject (a teacher who teaches 5 subjects
     * shows 5 papers here, each with its own due/pending/uploaded status).
     */
    public function adminIndex(Request $request)
    {
        $teachers = Teacher::whereHas('examPapers')
            ->with(['examPapers' => function ($q) {
                $q->with(['examination.subject', 'examination.schoolClass'])->latest();
            }])
            ->orderBy('name')
            ->get();

        $totals = [
            'due'      => ExamPaper::where('status', 'due')->count(),
            'pending'  => ExamPaper::where('status', 'pending')->count(),
            'uploaded' => ExamPaper::where('status', 'uploaded')->count(),
        ];

        return view('admin.exam-papers.index', compact('teachers', 'totals'));
    }

    /** Teacher view — their own papers only, across every subject they teach. */
    public function teacherIndex()
    {
        $teacher = $this->currentTeacher();

        $papers = $teacher
            ? ExamPaper::forTeacher($teacher->id)
                ->with(['examination.subject', 'examination.schoolClass'])
                ->get()
                ->sortBy(fn ($p) => $p->status === 'due' ? 0 : ($p->status === 'pending' ? 1 : 2))
            : collect();

        return view('teacher.exam-papers.index', compact('papers'));
    }

    /** Teacher builds/edits the question paper: they choose how many questions of each type. */
    public function build(ExamPaper $examPaper)
    {
        $this->authorizeTeacherOwnsPaper($examPaper);

        $examPaper->load(['examination.subject', 'examination.schoolClass', 'questions']);
        $shortQuestions      = $examPaper->questions->where('type', 'short')->values();
        $longQuestions       = $examPaper->questions->where('type', 'long')->values();
        $mcqQuestions        = $examPaper->questions->where('type', 'mcq')->values();
        $fillBlankQuestions  = $examPaper->questions->where('type', 'fill_blank')->values();
        $trueFalseQuestions  = $examPaper->questions->where('type', 'true_false')->values();

        return view('teacher.exam-papers.build', compact(
            'examPaper', 'shortQuestions', 'longQuestions',
            'mcqQuestions', 'fillBlankQuestions', 'trueFalseQuestions'
        ));
    }

    /** Save as draft (status: pending) or submit for good (status: uploaded). */
    public function save(Request $request, ExamPaper $examPaper)
    {
        $this->authorizeTeacherOwnsPaper($examPaper);

        $data = $request->validate([
            'instructions'            => 'nullable|string|max:2000',

            'short_questions'         => 'nullable|array',
            'short_questions.*'       => 'nullable|string|max:2000',
            'short_marks'             => 'nullable|array',
            'short_marks.*'           => 'nullable|integer|min:0|max:1000',
            'short_images.*'          => 'nullable|image|max:4096',

            'long_questions'          => 'nullable|array',
            'long_questions.*'        => 'nullable|string|max:4000',
            'long_marks'              => 'nullable|array',
            'long_marks.*'            => 'nullable|integer|min:0|max:1000',
            'long_images.*'           => 'nullable|image|max:4096',

            'mcq_questions'           => 'nullable|array',
            'mcq_questions.*'         => 'nullable|string|max:2000',
            'mcq_option_a'            => 'nullable|array',
            'mcq_option_b'            => 'nullable|array',
            'mcq_option_c'            => 'nullable|array',
            'mcq_option_d'            => 'nullable|array',
            'mcq_correct'             => 'nullable|array',
            'mcq_correct.*'           => 'nullable|in:a,b,c,d',
            'mcq_marks'               => 'nullable|array',
            'mcq_marks.*'             => 'nullable|integer|min:0|max:1000',
            'mcq_images.*'            => 'nullable|image|max:4096',

            'fillblank_questions'     => 'nullable|array',
            'fillblank_questions.*'   => 'nullable|string|max:2000',
            'fillblank_answers'       => 'nullable|array',
            'fillblank_answers.*'     => 'nullable|string|max:255',
            'fillblank_marks'         => 'nullable|array',
            'fillblank_marks.*'       => 'nullable|integer|min:0|max:1000',
            'fillblank_images.*'      => 'nullable|image|max:4096',

            'truefalse_questions'     => 'nullable|array',
            'truefalse_questions.*'   => 'nullable|string|max:2000',
            'truefalse_correct'       => 'nullable|array',
            'truefalse_correct.*'     => 'nullable|in:true,false',
            'truefalse_marks'         => 'nullable|array',
            'truefalse_marks.*'       => 'nullable|integer|min:0|max:1000',
            'truefalse_images.*'      => 'nullable|image|max:4096',

            'action'                  => 'required|in:draft,submit',
        ]);

        DB::transaction(function () use ($data, $request, $examPaper) {
            // Delete old question images from disk before wiping the rows.
            foreach ($examPaper->questions as $old) {
                if ($old->image_path) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($old->image_path);
                }
            }
            $examPaper->questions()->delete();

            $order = 0;
            foreach (($data['short_questions'] ?? []) as $i => $text) {
                if (trim((string) $text) === '') continue;
                ExamPaperQuestion::create([
                    'exam_paper_id' => $examPaper->id,
                    'type'          => 'short',
                    'question'      => $text,
                    'marks'         => $data['short_marks'][$i] ?? null,
                    'image_path'    => $this->storeQuestionImage($request, 'short_images', $i),
                    'order_no'      => $order++,
                ]);
            }

            $order = 0;
            foreach (($data['long_questions'] ?? []) as $i => $text) {
                if (trim((string) $text) === '') continue;
                ExamPaperQuestion::create([
                    'exam_paper_id' => $examPaper->id,
                    'type'          => 'long',
                    'question'      => $text,
                    'marks'         => $data['long_marks'][$i] ?? null,
                    'image_path'    => $this->storeQuestionImage($request, 'long_images', $i),
                    'order_no'      => $order++,
                ]);
            }

            $order = 0;
            foreach (($data['mcq_questions'] ?? []) as $i => $text) {
                if (trim((string) $text) === '') continue;
                $options = [
                    'a' => $data['mcq_option_a'][$i] ?? null,
                    'b' => $data['mcq_option_b'][$i] ?? null,
                    'c' => $data['mcq_option_c'][$i] ?? null,
                    'd' => $data['mcq_option_d'][$i] ?? null,
                ];
                ExamPaperQuestion::create([
                    'exam_paper_id'  => $examPaper->id,
                    'type'           => 'mcq',
                    'question'       => $text,
                    'options'        => $options,
                    'correct_answer' => $data['mcq_correct'][$i] ?? null,
                    'marks'          => $data['mcq_marks'][$i] ?? null,
                    'image_path'     => $this->storeQuestionImage($request, 'mcq_images', $i),
                    'order_no'       => $order++,
                ]);
            }

            $order = 0;
            foreach (($data['fillblank_questions'] ?? []) as $i => $text) {
                if (trim((string) $text) === '') continue;
                ExamPaperQuestion::create([
                    'exam_paper_id'  => $examPaper->id,
                    'type'           => 'fill_blank',
                    'question'       => $text,
                    'correct_answer' => $data['fillblank_answers'][$i] ?? null,
                    'marks'          => $data['fillblank_marks'][$i] ?? null,
                    'image_path'     => $this->storeQuestionImage($request, 'fillblank_images', $i),
                    'order_no'       => $order++,
                ]);
            }

            $order = 0;
            foreach (($data['truefalse_questions'] ?? []) as $i => $text) {
                if (trim((string) $text) === '') continue;
                ExamPaperQuestion::create([
                    'exam_paper_id'  => $examPaper->id,
                    'type'           => 'true_false',
                    'question'       => $text,
                    'correct_answer' => $data['truefalse_correct'][$i] ?? null,
                    'marks'          => $data['truefalse_marks'][$i] ?? null,
                    'image_path'     => $this->storeQuestionImage($request, 'truefalse_images', $i),
                    'order_no'       => $order++,
                ]);
            }

            $examPaper->instructions = $data['instructions'] ?? null;

            if ($data['action'] === 'submit') {
                $examPaper->status       = 'uploaded';
                $examPaper->submitted_at = now();
            } else {
                $examPaper->status = $examPaper->questions()->exists() ? 'pending' : 'due';
            }

            $examPaper->save();
        });

        if ($data['action'] === 'submit') {
            \App\Services\NotifierService::notifyAdmins(
                ($examPaper->teacher->name ?? 'A teacher') . ' uploaded the paper for '
                    . ($examPaper->examination->name ?? 'an examination') . ' — '
                    . ($examPaper->examination->subject->name ?? ''),
                'fa-file-circle-check', 'success'
            );
            return redirect()->route('teacher.examinations.papers.index')->with('success', 'Paper submitted successfully!');
        }

        return back()->with('success', 'Draft saved.');
    }

    /** Stores an uploaded question image (if present at this array index) and returns its storage path. */
    private function storeQuestionImage(Request $request, string $field, int $index): ?string
    {
        $file = $request->file($field . '.' . $index);
        return $file ? $file->store('exam-papers/questions', 'public') : null;
    }

    /** Marksheet-styled printable / downloadable version of the paper (no photo/logo image). */
    public function print(ExamPaper $examPaper)
    {
        if (!$this->isAdmin()) {
            $this->authorizeTeacherOwnsPaper($examPaper);
        }

        $examPaper->load(['examination.subject', 'examination.academicYear', 'examination.schoolClass.program', 'examination.schoolClass.academicYears', 'examination.program', 'teacher', 'questions']);
        $shortQuestions     = $examPaper->questions->where('type', 'short')->values();
        $longQuestions      = $examPaper->questions->where('type', 'long')->values();
        $mcqQuestions       = $examPaper->questions->where('type', 'mcq')->values();
        $fillBlankQuestions = $examPaper->questions->where('type', 'fill_blank')->values();
        $trueFalseQuestions = $examPaper->questions->where('type', 'true_false')->values();

        return view('exam-papers.print', compact(
            'examPaper', 'shortQuestions', 'longQuestions',
            'mcqQuestions', 'fillBlankQuestions', 'trueFalseQuestions'
        ));
    }

    private function authorizeTeacherOwnsPaper(ExamPaper $examPaper): void
    {
        if ($this->isAdmin()) return;
        $teacher = $this->currentTeacher();
        abort_unless($teacher && (int) $examPaper->teacher_id === (int) $teacher->id, 403);
    }
}

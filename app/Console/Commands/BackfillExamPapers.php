<?php

namespace App\Console\Commands;

use App\Models\ExamPaper;
use App\Models\Examination;
use Illuminate\Console\Command;

class BackfillExamPapers extends Command
{
    /**
     * php artisan exam-papers:backfill
     *
     * One-off fix for examinations that were scheduled BEFORE the exam_papers
     * table/feature existed. Those rows have no ExamPaper, so they never show
     * up on Admin > Examination Papers or in the teacher's portal. Going
     * forward this is no longer needed — ExaminationObserver creates the
     * paper automatically the moment an examination is created — but this
     * catches the ones that already slipped through.
     */
    protected $signature = 'exam-papers:backfill';

    protected $description = 'Create missing ExamPaper records for examinations that predate the exam papers feature';

    public function handle(): int
    {
        $missing = Examination::whereDoesntHave('examPaper')->get();

        if ($missing->isEmpty()) {
            $this->info('Nothing to do — every examination already has an exam paper.');
            return self::SUCCESS;
        }

        $count = 0;
        foreach ($missing as $examination) {
            ExamPaper::firstOrCreate(
                ['examination_id' => $examination->id],
                ['teacher_id' => $examination->teacher_id, 'status' => 'due']
            );
            $count++;
        }

        $this->info("Created {$count} missing exam paper record(s).");
        return self::SUCCESS;
    }
}

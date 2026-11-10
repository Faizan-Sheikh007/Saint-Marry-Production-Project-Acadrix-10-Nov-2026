<?php

namespace App\Observers;

use App\Models\Examination;
use App\Models\ExamPaper;

class ExaminationObserver
{
    /**
     * Every examination — no matter where it's created from (the scheduling
     * form, an import, a seeder, etc.) — must automatically get its own exam
     * paper record so it shows up in Admin > Examination Papers and in the
     * teacher's portal straight away. firstOrCreate keeps this idempotent so
     * it's safe even if a caller also creates the paper itself.
     */
    public function created(Examination $examination): void
    {
        ExamPaper::firstOrCreate(
            ['examination_id' => $examination->id],
            [
                'teacher_id' => $examination->teacher_id,
                'status'     => 'due',
            ]
        );
    }

    /**
     * If the exam's teacher assignment changes later, keep the (still unsubmitted)
     * paper's teacher_id in sync so it doesn't get orphaned from the teacher portal.
     */
    public function updated(Examination $examination): void
    {
        if (! $examination->wasChanged('teacher_id')) {
            return;
        }

        $paper = $examination->examPaper;
        if ($paper && $paper->status !== 'uploaded') {
            $paper->update(['teacher_id' => $examination->teacher_id]);
        }
    }
}

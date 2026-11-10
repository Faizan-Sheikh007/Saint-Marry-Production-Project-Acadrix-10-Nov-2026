<?php

namespace App\Console\Commands;

use App\Models\ClassSubject;
use App\Models\Course;
use Illuminate\Console\Command;

/**
 * One-time (or re-runnable) backfill: creates/updates the LMS Course for
 * every existing subject-to-class assignment, and enrolls that class's
 * active students. Needed because Course::syncForAssignment() only started
 * firing automatically on NEW assignments made after this feature shipped —
 * this catches up every assignment made before it.
 *
 * Usage: php artisan subjects:sync-courses
 */
class SyncSubjectCourses extends Command
{
    protected $signature   = 'subjects:sync-courses';
    protected $description = 'Sync LMS courses + student enrollment for every existing subject-to-class assignment';

    public function handle(): int
    {
        $assignments = ClassSubject::with('subject', 'schoolClass')->get();

        if ($assignments->isEmpty()) {
            $this->info('No subject-to-class assignments found — nothing to sync.');
            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($assignments->count());
        $bar->start();

        foreach ($assignments as $assignment) {
            Course::syncForAssignment($assignment);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Synced {$assignments->count()} subject assignment(s) into LMS courses, with students auto-enrolled.");

        return self::SUCCESS;
    }
}

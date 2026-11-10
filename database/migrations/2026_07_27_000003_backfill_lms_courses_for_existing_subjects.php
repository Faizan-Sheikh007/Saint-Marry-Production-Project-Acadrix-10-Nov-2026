<?php

use App\Models\ClassSubject;
use App\Models\Course;
use Illuminate\Database\Migrations\Migration;

/**
 * One-time data migration: creates/updates the LMS Course for every
 * subject-to-class assignment that existed before Course::syncForAssignment()
 * started firing automatically on new assignments. Without this, subjects
 * assigned prior to that fix (e.g. via the admin Subjects page) never got a
 * matching Course row, so students in those classes saw an empty "My
 * Subjects" / course list even though the curriculum showed subjects
 * assigned and a teacher attached.
 *
 * This runs automatically on `php artisan migrate` so the fix ships with the
 * deploy instead of depending on someone remembering to run
 * `php artisan subjects:sync-courses` by hand.
 */
return new class extends Migration
{
    public function up(): void
    {
        ClassSubject::with('subject', 'schoolClass')->get()->each(
            fn (ClassSubject $assignment) => Course::syncForAssignment($assignment)
        );
    }

    public function down(): void
    {
        // Intentionally a no-op — this migration only backfills derived LMS
        // data from existing curriculum assignments; it doesn't create
        // anything that should be torn down on rollback.
    }
};

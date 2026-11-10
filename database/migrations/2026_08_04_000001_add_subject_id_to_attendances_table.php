<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Lets a teacher mark attendance per-subject (e.g. "Physics — Period 2")
 * instead of only once for the whole class/day. subject_id is nullable so
 * the existing class-wide attendance flow (admin, or a teacher marking
 * general/homeroom attendance) keeps working exactly as before — those
 * rows simply have subject_id = null.
 *
 * Written defensively (hasColumn / index-existence checks) because on some
 * installs this column/index was already added by hand or by an earlier
 * partial run of this same migration — without these guards a fresh
 * `php artisan migrate` on such a database fails with "Duplicate column
 * name 'subject_id'" even though nothing is actually broken.
 *
 * Index ordering matters here too: MySQL/MariaDB refuses to drop
 * `attendances_student_id_date_unique` while it's still the only index
 * backing the `student_id` foreign key ("Cannot drop index ... needed in
 * a foreign key constraint"). The new composite unique index below also
 * starts with student_id, so it can stand in for that foreign key — but
 * only if it's created BEFORE the old one is dropped, never after.
 */
return new class extends Migration {
    public function up()
    {
        if (! Schema::hasColumn('attendances', 'subject_id')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->foreignId('subject_id')->nullable()->after('school_class_id')->constrained()->nullOnDelete();
            });
        }

        // Create the new subject-aware unique index FIRST (while the old
        // one still exists) so there's always at least one index covering
        // student_id for the foreign key to lean on.
        if (! $this->indexExists('attendances', 'attendances_student_date_subject_unique')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->unique(['student_id', 'date', 'subject_id'], 'attendances_student_date_subject_unique');
            });
        }

        // Now it's safe to drop the old (student_id, date) unique index.
        if ($this->indexExists('attendances', 'attendances_student_id_date_unique')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->dropUnique('attendances_student_id_date_unique');
            });
        }
    }

    public function down()
    {
        // Same ordering concern in reverse: recreate the old index before
        // dropping the new one, so the student_id foreign key is never
        // left without a supporting index in between.
        if (! $this->indexExists('attendances', 'attendances_student_id_date_unique')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->unique(['student_id', 'date'], 'attendances_student_id_date_unique');
            });
        }

        if ($this->indexExists('attendances', 'attendances_student_date_subject_unique')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->dropUnique('attendances_student_date_subject_unique');
            });
        }

        if (Schema::hasColumn('attendances', 'subject_id')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->dropConstrainedForeignId('subject_id');
            });
        }
    }

    /** Works across MySQL/MariaDB/SQLite/Postgres without needing doctrine/dbal. */
    private function indexExists(string $table, string $indexName): bool
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            $rows = DB::select("PRAGMA index_list(" . $table . ")");
            foreach ($rows as $row) {
                if (($row->name ?? '') === $indexName) {
                    return true;
                }
            }
            return false;
        }

        if ($driver === 'pgsql') {
            return DB::table('pg_indexes')
                ->where('tablename', $table)
                ->where('indexname', $indexName)
                ->exists();
        }

        // mysql / mariadb
        $rows = DB::select('SHOW INDEX FROM `' . $table . '` WHERE Key_name = ?', [$indexName]);
        return count($rows) > 0;
    }
};

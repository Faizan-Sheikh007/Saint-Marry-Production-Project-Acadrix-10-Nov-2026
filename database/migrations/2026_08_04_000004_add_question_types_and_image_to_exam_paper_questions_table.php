<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Widens exam_paper_questions.type beyond short/long to also support
 * mcq, fill_blank, and true_false, and adds the extra columns those
 * question types need (options, correct_answer) plus an optional
 * uploaded image per question (diagrams, charts, etc — any type can use it).
 *
 * type was a MySQL/SQLite ENUM, so we can't just ->change() it without
 * doctrine/dbal. Instead we widen it to a plain string column in a way
 * that works on both SQLite (rebuild via temp column) and MySQL/Postgres
 * (raw MODIFY), preserving all existing 'short'/'long' rows.
 */
return new class extends Migration {
    public function up()
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            Schema::table('exam_paper_questions', function (Blueprint $table) {
                $table->string('type_new', 20)->nullable()->after('type');
            });
            DB::statement('UPDATE exam_paper_questions SET type_new = type');
            Schema::table('exam_paper_questions', function (Blueprint $table) {
                $table->dropColumn('type');
            });
            Schema::table('exam_paper_questions', function (Blueprint $table) {
                $table->renameColumn('type_new', 'type');
            });
        } else {
            DB::statement("ALTER TABLE exam_paper_questions MODIFY type VARCHAR(20) NOT NULL DEFAULT 'short'");
        }

        Schema::table('exam_paper_questions', function (Blueprint $table) {
            // MCQ choices, stored as a JSON array of option strings.
            $table->json('options')->nullable()->after('question');
            // MCQ (option text/letter), fill-in-the-blank (expected answer),
            // or true/false ("true" / "false") correct answer.
            $table->string('correct_answer')->nullable()->after('options');
            // Optional diagram/chart/image attached to the question (any type).
            $table->string('image_path')->nullable()->after('correct_answer');
        });
    }

    public function down()
    {
        Schema::table('exam_paper_questions', function (Blueprint $table) {
            $table->dropColumn(['options', 'correct_answer', 'image_path']);
        });
    }
};

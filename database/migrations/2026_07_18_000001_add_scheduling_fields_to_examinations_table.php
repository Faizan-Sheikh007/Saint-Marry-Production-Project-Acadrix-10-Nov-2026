<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::table('examinations', function (Blueprint $table) {
            $table->foreignId('program_id')->nullable()->after('school_class_id')
                  ->constrained('programs')->nullOnDelete();
            $table->foreignId('teacher_id')->nullable()->after('subject_id')
                  ->constrained('teachers')->nullOnDelete();
            $table->time('start_time')->nullable()->after('exam_date');
            $table->time('end_time')->nullable()->after('start_time');
            $table->string('room')->nullable()->after('end_time');
            // Groups exams scheduled together in one "batch" (e.g. Mid Term 2025
            // created for several programs/classes/subjects in a single wizard run).
            $table->string('batch_id', 36)->nullable()->after('id');
            // Once results are finalized/locked by admin, teachers can no longer edit marks.
            $table->timestamp('locked_at')->nullable()->after('status');

            $table->index('batch_id');
            $table->index(['teacher_id', 'exam_date']);
            $table->index(['school_class_id', 'exam_date']);
        });
    }
    public function down() {
        Schema::table('examinations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('program_id');
            $table->dropConstrainedForeignId('teacher_id');
            $table->dropColumn(['start_time', 'end_time', 'room', 'batch_id', 'locked_at']);
        });
    }
};

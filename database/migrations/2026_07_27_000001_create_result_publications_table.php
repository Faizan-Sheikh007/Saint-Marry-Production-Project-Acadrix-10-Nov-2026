<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tracks the "publish" state of a result batch for one class.
 *
 * An exam batch (Examination.batch_id) can span several classes at once
 * (e.g. "Mid Term Exam" scheduled for DPT Part 1 AND Part 2 in one wizard
 * run). Results must be reviewable/publishable per CLASS independently —
 * admin might finish collecting DPT Part 1's marks before Part 2's are in —
 * so publication is scoped to (batch_id, school_class_id), not the batch
 * alone.
 *
 * While unpublished, students/parents never see the compiled marksheet,
 * even if every subject already has marks entered. Admin explicitly
 * publishes once satisfied, which is the moment the marksheet becomes
 * visible/downloadable on the Student and Parent portals.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('result_publications', function (Blueprint $table) {
            $table->id();
            $table->string('batch_id');
            $table->foreignId('school_class_id')->constrained()->cascadeOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();   // shown on every marksheet in this batch/class (e.g. grading policy, promotion note)
            $table->string('exam_name')->nullable(); // snapshot, in case examinations get renamed/removed later
            $table->timestamps();

            $table->unique(['batch_id', 'school_class_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('result_publications');
    }
};

<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Course materials — lecture notes (PDF/Doc/PPT/etc.), uploaded files, or external links.
        // Kept separate from `lessons` so teachers can share reference material that
        // isn't tied to a single lesson (e.g. a syllabus, a reading list, a shared link).
        Schema::create('course_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['file', 'link'])->default('file');
            $table->string('file_path')->nullable();
            $table->string('file_type', 20)->nullable(); // pdf, doc, docx, ppt, pptx, xls, xlsx, etc.
            $table->string('url')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        // Student questions to the course teacher — capped at 3/day per student (enforced in controller).
        Schema::create('lms_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained()->nullOnDelete();
            $table->text('question');
            $table->text('answer')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->enum('status', ['pending', 'answered'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lms_questions');
        Schema::dropIfExists('course_materials');
    }
};

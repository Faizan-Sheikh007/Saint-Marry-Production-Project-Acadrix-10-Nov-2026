<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Add user_id to teachers
        if (!Schema::hasColumn('teachers', 'user_id')) {
            Schema::table('teachers', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete();
            });
        }
        // Add user_id to students
        if (!Schema::hasColumn('students', 'user_id')) {
            Schema::table('students', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete();
            });
        }

        // Courses
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('subject_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('school_class_id')->nullable()->constrained()->nullOnDelete();
            $table->string('thumbnail')->nullable();
            $table->enum('status', ['draft','published','archived'])->default('draft');
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        // Lessons
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->longText('content')->nullable();
            $table->string('video_url')->nullable();
            $table->string('file_attachment')->nullable();
            $table->enum('type', ['text','video','file','mixed'])->default('text');
            $table->integer('order')->default(0);
            $table->integer('duration_minutes')->default(0);
            $table->boolean('is_free')->default(false);
            $table->timestamps();
        });

        // Lesson progress
        Schema::create('lesson_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->boolean('completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->integer('time_spent')->default(0);
            $table->timestamps();
            $table->unique(['lesson_id','student_id']);
        });

        // Assignments
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->longText('instructions')->nullable();
            $table->timestamp('due_date')->nullable();
            $table->integer('total_marks')->default(100);
            $table->string('file_attachment')->nullable();
            $table->enum('status', ['active','closed'])->default('active');
            $table->timestamps();
        });

        // Assignment submissions
        Schema::create('assignment_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->text('content')->nullable();
            $table->string('file_path')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->integer('marks_obtained')->nullable();
            $table->text('feedback')->nullable();
            $table->enum('status', ['submitted','graded','late'])->default('submitted');
            $table->timestamps();
            $table->unique(['assignment_id','student_id']);
        });

        // Quizzes
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->integer('time_limit')->default(30)->comment('minutes');
            $table->integer('total_marks')->default(100);
            $table->integer('passing_marks')->default(40);
            $table->integer('attempts_allowed')->default(1);
            $table->enum('status', ['draft','active','closed'])->default('draft');
            $table->timestamp('due_date')->nullable();
            $table->timestamps();
        });

        // Quiz questions
        Schema::create('quiz_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained()->cascadeOnDelete();
            $table->text('question');
            $table->enum('type', ['mcq','true_false','short'])->default('mcq');
            $table->json('options')->nullable();
            $table->string('correct_answer');
            $table->integer('marks')->default(1);
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        // Quiz attempts
        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->json('answers')->nullable();
            $table->integer('score')->default(0);
            $table->integer('total_marks')->default(0);
            $table->decimal('percentage', 5, 2)->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->enum('status', ['in_progress','submitted'])->default('in_progress');
            $table->timestamps();
        });

        // Course enrollments
        Schema::create('course_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->timestamp('enrolled_at')->nullable();
            $table->integer('progress_percent')->default(0);
            $table->enum('status', ['active','completed','dropped'])->default('active');
            $table->timestamps();
            $table->unique(['course_id','student_id']);
        });

        // LMS announcements
        Schema::create('lms_announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('content');
            $table->boolean('pinned')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lms_announcements');
        Schema::dropIfExists('course_enrollments');
        Schema::dropIfExists('quiz_attempts');
        Schema::dropIfExists('quiz_questions');
        Schema::dropIfExists('quizzes');
        Schema::dropIfExists('assignment_submissions');
        Schema::dropIfExists('assignments');
        Schema::dropIfExists('lesson_progress');
        Schema::dropIfExists('lessons');
        Schema::dropIfExists('courses');
    }
};

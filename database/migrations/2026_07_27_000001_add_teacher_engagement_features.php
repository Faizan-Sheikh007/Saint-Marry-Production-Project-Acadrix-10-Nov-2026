<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ── Admin -> specific teacher announcements (shown on teacher dashboard) ──
        if (! Schema::hasTable('teacher_announcements')) {
            Schema::create('teacher_announcements', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->longText('content');
                $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
                // 'all' = every teacher, 'specific' = only the linked teacher below
                $table->string('audience')->default('all');
                $table->foreignId('teacher_id')->nullable()->constrained('teachers')->cascadeOnDelete();
                $table->boolean('pinned')->default(false);
                $table->timestamps();

                $table->index(['audience', 'teacher_id']);
            });
        }

        // ── Tours (Subject, Name, Date, Place) ─────────────────────────────
        if (! Schema::hasTable('tours')) {
            Schema::create('tours', function (Blueprint $table) {
                $table->id();
                $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
                $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
                $table->string('name');
                $table->date('tour_date');
                $table->string('place');
                $table->text('notes')->nullable();
                $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('status')->default('assigned'); // assigned, acknowledged, completed
                $table->timestamps();
            });
        }

        // ── Projects (Thesis, Review Articles) ─────────────────────────────
        if (! Schema::hasTable('projects')) {
            Schema::create('projects', function (Blueprint $table) {
                $table->id();
                $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
                $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
                $table->string('type')->default('thesis'); // thesis, review_article
                $table->string('title');
                $table->text('description')->nullable();
                $table->longText('teacher_submission')->nullable();
                $table->date('due_date')->nullable();
                $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('status')->default('assigned'); // assigned, submitted, reviewed
                $table->timestamps();
            });
        }

        // ── Practicals (paragraph) ──────────────────────────────────────────
        if (! Schema::hasTable('practicals')) {
            Schema::create('practicals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
                $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
                $table->string('title');
                $table->text('instructions')->nullable();
                $table->longText('paragraph')->nullable(); // filled in by teacher
                $table->date('due_date')->nullable();
                $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('status')->default('assigned'); // assigned, submitted, reviewed
                $table->timestamps();
            });
        }

        // ── Surveys (hospital / industry) ───────────────────────────────────
        if (! Schema::hasTable('surveys')) {
            Schema::create('surveys', function (Blueprint $table) {
                $table->id();
                $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
                $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
                $table->string('type')->default('industry'); // hospital, industry
                $table->string('title');
                $table->text('description')->nullable();
                $table->longText('teacher_submission')->nullable();
                $table->date('due_date')->nullable();
                $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('status')->default('assigned'); // assigned, submitted, reviewed
                $table->timestamps();
            });
        }

        // ── Extend Assignments: allow direct admin -> teacher paragraph requests ──
        if (Schema::hasTable('assignments')) {
            DB::statement('ALTER TABLE assignments MODIFY course_id BIGINT UNSIGNED NULL');

            Schema::table('assignments', function (Blueprint $table) {
                if (! Schema::hasColumn('assignments', 'teacher_id')) {
                    $table->foreignId('teacher_id')->nullable()->after('course_id')->constrained('teachers')->nullOnDelete();
                }
                if (! Schema::hasColumn('assignments', 'is_admin_request')) {
                    $table->boolean('is_admin_request')->default(false)->after('teacher_id');
                }
                if (! Schema::hasColumn('assignments', 'assigned_by')) {
                    $table->foreignId('assigned_by')->nullable()->after('is_admin_request')->constrained('users')->nullOnDelete();
                }
                if (! Schema::hasColumn('assignments', 'teacher_response')) {
                    $table->longText('teacher_response')->nullable()->after('instructions');
                }
                if (! Schema::hasColumn('assignments', 'request_status')) {
                    // pending (waiting on teacher), submitted (teacher wrote paragraph), reviewed (admin saw it)
                    $table->string('request_status')->nullable()->after('teacher_response');
                }
                if (! Schema::hasColumn('assignments', 'admin_reviewed_at')) {
                    $table->timestamp('admin_reviewed_at')->nullable()->after('request_status');
                }
            });
        }

        // ── Extend Quizzes: allow direct admin -> teacher paragraph requests ──
        if (Schema::hasTable('quizzes')) {
            DB::statement('ALTER TABLE quizzes MODIFY course_id BIGINT UNSIGNED NULL');

            Schema::table('quizzes', function (Blueprint $table) {
                if (! Schema::hasColumn('quizzes', 'teacher_id')) {
                    $table->foreignId('teacher_id')->nullable()->after('course_id')->constrained('teachers')->nullOnDelete();
                }
                if (! Schema::hasColumn('quizzes', 'is_admin_request')) {
                    $table->boolean('is_admin_request')->default(false)->after('teacher_id');
                }
                if (! Schema::hasColumn('quizzes', 'assigned_by')) {
                    $table->foreignId('assigned_by')->nullable()->after('is_admin_request')->constrained('users')->nullOnDelete();
                }
                if (! Schema::hasColumn('quizzes', 'teacher_response')) {
                    $table->longText('teacher_response')->nullable()->after('description');
                }
                if (! Schema::hasColumn('quizzes', 'request_status')) {
                    $table->string('request_status')->nullable()->after('teacher_response');
                }
                if (! Schema::hasColumn('quizzes', 'admin_reviewed_at')) {
                    $table->timestamp('admin_reviewed_at')->nullable()->after('request_status');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('surveys');
        Schema::dropIfExists('practicals');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('tours');
        Schema::dropIfExists('teacher_announcements');

        if (Schema::hasTable('assignments')) {
            Schema::table('assignments', function (Blueprint $table) {
                $table->dropConstrainedForeignId('teacher_id');
                $table->dropConstrainedForeignId('assigned_by');
                $table->dropColumn(['is_admin_request', 'teacher_response', 'request_status', 'admin_reviewed_at']);
            });
        }

        if (Schema::hasTable('quizzes')) {
            Schema::table('quizzes', function (Blueprint $table) {
                $table->dropConstrainedForeignId('teacher_id');
                $table->dropConstrainedForeignId('assigned_by');
                $table->dropColumn(['is_admin_request', 'teacher_response', 'request_status', 'admin_reviewed_at']);
            });
        }
    }
};

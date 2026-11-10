<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration {
    public function up(): void
    {
        foreach (['assignments', 'quizzes'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->timestamp('published_at')->nullable();
                $table->json('target_student_ids')->nullable(); // null/[] = all active course students
            });
            // Existing student-facing work stays visible; private admin requests do not.
            DB::table($name)->where('is_admin_request', false)->whereNotNull('course_id')
                ->whereIn('status', ['active', 'closed'])
                ->update(['published_at' => DB::raw('COALESCE(created_at, CURRENT_TIMESTAMP)')]);
        }
    }
    public function down(): void
    {
        foreach (['assignments', 'quizzes'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->dropColumn(['published_at', 'target_student_ids']));
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('teacher_messages', function (Blueprint $table) {
            // Add missing columns if they don't exist
            if (!Schema::hasColumn('teacher_messages', 'title')) {
                $table->string('title')->nullable()->after('sender_id');
            }
            if (!Schema::hasColumn('teacher_messages', 'message')) {
                $table->longText('message')->nullable()->after('title');
            }
            if (!Schema::hasColumn('teacher_messages', 'type')) {
                $table->string('type')->default('announcement')->after('message');
            }
            if (!Schema::hasColumn('teacher_messages', 'audience')) {
                $table->string('audience')->default('individual')->after('type');
            }
            if (!Schema::hasColumn('teacher_messages', 'status')) {
                $table->string('status')->default('sent')->after('audience');
            }
            if (!Schema::hasColumn('teacher_messages', 'sent_at')) {
                $table->timestamp('sent_at')->nullable()->after('status');
            }
            if (!Schema::hasColumn('teacher_messages', 'scheduled_at')) {
                $table->timestamp('scheduled_at')->nullable()->after('sent_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teacher_messages', function (Blueprint $table) {
            $table->dropColumn([
                'title',
                'message',
                'type',
                'audience',
                'status',
                'sent_at',
                'scheduled_at',
            ]);
        });
    }
};

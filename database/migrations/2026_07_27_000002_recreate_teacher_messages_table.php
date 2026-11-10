<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `teacher_messages` was created in 2024_01_01_000022, extended twice, then
 * dropped entirely by 2026_07_23_000001 (which removed the old Teacher
 * Chat/Communication module as a product decision). We're reintroducing
 * Admin <-> Teacher chat, so rebuild the table here with the full column
 * set the old migrations had, rather than editing an already-ran migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('teacher_messages')) {
            return;
        }

        Schema::create('teacher_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('teacher_messages')->cascadeOnDelete();

            $table->string('title')->nullable();
            $table->string('subject')->nullable();
            $table->longText('message')->nullable();

            $table->string('message_type')->default('general'); // notice, warning, circular, general, chat
            $table->string('type')->default('announcement');
            $table->string('priority')->default('normal'); // low, normal, high, urgent
            $table->string('audience')->default('individual');
            $table->string('status')->default('sent');

            $table->timestamp('sent_at')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->boolean('is_read')->default(false)->index();
            $table->timestamp('read_at')->nullable();

            $table->timestamps();

            $table->index(['teacher_id', 'is_read']);
            $table->index(['teacher_id', 'created_at']);
            $table->index('message_type');
            $table->index('priority');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_messages');
    }
};

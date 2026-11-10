<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teacher_messages', function (Blueprint $table) {
            if (!Schema::hasColumn('teacher_messages', 'parent_id')) {
                // Self-referencing: a reply points to its parent message
                $table->unsignedBigInteger('parent_id')->nullable()->after('sender_id');

                $table->foreign('parent_id')
                      ->references('id')
                      ->on('teacher_messages')
                      ->onDelete('cascade');
            }
        });
    }

    public function down(): void
    {
        Schema::table('teacher_messages', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn('parent_id');
        });
    }
};

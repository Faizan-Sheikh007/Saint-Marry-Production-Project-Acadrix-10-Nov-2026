<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('teacher_id');
            $table->unsignedBigInteger('sender_id');
            $table->string('subject');
            $table->longText('message');
            $table->string('message_type')->default('general'); // notice, warning, circular, general
            $table->string('priority')->default('normal'); // low, normal, high, urgent
            $table->boolean('is_read')->default(false)->index();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            // Indexes for faster queries
            $table->index(['teacher_id', 'is_read']);
            $table->index(['teacher_id', 'created_at']);
            $table->index('message_type');
            $table->index('priority');
            
            // Foreign key constraints
            $table->foreign('teacher_id')
                  ->references('id')
                  ->on('teachers')
                  ->onDelete('cascade');
                  
            $table->foreign('sender_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_messages');
    }
};

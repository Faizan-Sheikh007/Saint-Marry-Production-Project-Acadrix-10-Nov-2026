<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_attendances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('teacher_id');
            $table->date('date');
            // Must match controller: present, absent, leave, holiday
            $table->enum('status', ['present', 'absent', 'leave', 'holiday'])->default('present');
            // Must match controller: $request->input("notes.{$teacherId}")
            $table->text('note')->nullable();
            // Must match controller: auth()->id()
            $table->unsignedBigInteger('marked_by')->nullable();
            $table->timestamps();

            // One record per teacher per date - prevents duplicates on updateOrCreate
            $table->unique(['teacher_id', 'date']);

            $table->foreign('teacher_id')
                  ->references('id')
                  ->on('teachers')
                  ->onDelete('cascade');

            $table->foreign('marked_by')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_attendances');
    }
};

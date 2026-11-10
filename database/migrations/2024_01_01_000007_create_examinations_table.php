<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up() {
        Schema::create('examinations', function (Blueprint $table) {
            $table->id(); $table->string('name');
            $table->foreignId('school_class_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained()->nullOnDelete();
            $table->date('exam_date'); $table->integer('total_marks')->default(100);
            $table->integer('passing_marks')->default(40);
            $table->enum('type', ['midterm','final','quiz','unit_test'])->default('midterm');
            $table->enum('status', ['scheduled','completed','cancelled'])->default('scheduled');
            $table->timestamps();
        });
    }
    public function down() { Schema::dropIfExists('examinations'); }
};

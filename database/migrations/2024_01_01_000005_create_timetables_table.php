<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up() {
        Schema::create('timetables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_class_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('day', ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday']);
            $table->time('start_time'); $table->time('end_time'); $table->string('room')->nullable();
            $table->timestamps();
        });
    }
    public function down() { Schema::dropIfExists('timetables'); }
};

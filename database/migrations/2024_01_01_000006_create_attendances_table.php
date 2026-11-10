<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up() {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('school_class_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->enum('status', ['present','absent','leave','late'])->default('present');
            $table->text('remarks')->nullable(); $table->timestamps();
            $table->unique(['student_id','date']);
        });
    }
    public function down() { Schema::dropIfExists('attendances'); }
};

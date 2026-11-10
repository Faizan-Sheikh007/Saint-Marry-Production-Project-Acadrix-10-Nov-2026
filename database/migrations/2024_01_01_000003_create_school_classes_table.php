<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up() {
        Schema::create('school_classes', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g. "Grade 10", "BSCS - Semester 3"
            $table->string('section')->nullable();
            $table->enum('level', ['college','university'])->default('college');
            $table->integer('capacity')->default(40);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }
    public function down() { Schema::dropIfExists('school_classes'); }
};

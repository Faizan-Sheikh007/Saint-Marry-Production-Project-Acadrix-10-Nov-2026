<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->string('name');                 // e.g. "BSCS", "Grade 10", "FSc Pre-Medical"
            $table->string('code')->nullable()->unique(); // e.g. "BSCS", "G10"
            $table->enum('level', ['college', 'university'])->default('college');
            $table->unsignedInteger('duration_terms')->nullable(); // e.g. 8 semesters, or null for school grades
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('programs');
    }
};

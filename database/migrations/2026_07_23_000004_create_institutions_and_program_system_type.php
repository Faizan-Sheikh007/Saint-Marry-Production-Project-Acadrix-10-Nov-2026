<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('institutions', function (Blueprint $table) {
            $table->id();
            // e.g. "Punjab Medical Facility", "Government College University Faisalabad"
            $table->string('name');
            // Default disclaimer text for this affiliated institution/facility.
            // Individual Programs (departments) can override this — see
            // programs.department_disclaimer.
            $table->text('disclaimer')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('programs', function (Blueprint $table) {
            // Only relevant when programs.level = 'university'. Selecting an
            // Institution here is what triggers showing its facility name +
            // disclaimer on admission forms / installment slips for this
            // Program (department).
            $table->foreignId('institution_id')->nullable()->after('level')
                ->constrained('institutions')->nullOnDelete();

            // Department (Program)-specific disclaimer override. Defaults to
            // the Institution's disclaimer but admin can customize it per
            // program/department.
            $table->text('department_disclaimer')->nullable()->after('institution_id');

            // Annual (Part 1 / Part 2) vs Semester (8 or 10 semesters) system.
            $table->enum('system_type', ['annual', 'semester'])->default('semester')->after('duration_terms');
        });
    }

    public function down()
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('institution_id');
            $table->dropColumn(['department_disclaimer', 'system_type']);
        });

        Schema::dropIfExists('institutions');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Wires the new admin-managed Academic Level / Academic Year lists onto the
 * Student, and lets a student admitted at a University-level Academic Level
 * record which affiliated Institution they belong to (selected directly on
 * the admission form, ahead of picking a Program).
 *
 * The legacy `level` and `session` string columns are kept as-is (other
 * parts of the app already read them) and are now populated automatically
 * from the selected Academic Level / Academic Year — see StudentController.
 */
return new class extends Migration {
    public function up()
    {
        Schema::table('students', function (Blueprint $table) {
            $table->foreignId('academic_level_id')->nullable()->after('level')
                ->constrained('academic_levels')->nullOnDelete();
            $table->foreignId('academic_year_id')->nullable()->after('session')
                ->constrained('academic_years')->nullOnDelete();
            $table->foreignId('institution_id')->nullable()->after('program_id')
                ->constrained('institutions')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropConstrainedForeignId('academic_level_id');
            $table->dropConstrainedForeignId('academic_year_id');
            $table->dropConstrainedForeignId('institution_id');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Links an Academic Session to the exact Program Parts/Semesters offered in
 * that session. A SchoolClass already belongs to a Program, so this pivot is
 * the single source of truth for Session -> Program -> Part/Semester.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('academic_year_school_class', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->foreignId('school_class_id')->constrained('school_classes')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['academic_year_id', 'school_class_id'], 'academic_year_school_class_unique');
            $table->index(['school_class_id', 'academic_year_id'], 'school_class_academic_year_idx');
        });

        // Backward compatibility: before this relation existed, every
        // Program/Part/Semester was effectively selectable in every session.
        // Preserve that behaviour for existing data; Admin can then narrow
        // each session from Academic Sessions -> Edit.
        $yearIds = DB::table('academic_years')->pluck('id');
        $classIds = DB::table('school_classes')->pluck('id');
        $now = now();

        foreach ($yearIds as $yearId) {
            $rows = $classIds->map(fn ($classId) => [
                'academic_year_id' => $yearId,
                'school_class_id'  => $classId,
                'created_at'       => $now,
                'updated_at'       => $now,
            ])->all();

            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table('academic_year_school_class')->insertOrIgnore($chunk);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_year_school_class');
    }
};

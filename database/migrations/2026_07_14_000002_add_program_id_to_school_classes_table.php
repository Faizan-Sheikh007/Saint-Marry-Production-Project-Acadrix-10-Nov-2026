<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up()
    {
        Schema::table('school_classes', function (Blueprint $table) {
            $table->foreignId('program_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });

        // Backfill: every existing class/section that has no program yet gets grouped
        // under an auto-created Program of the same name, so nothing already in the
        // system (students, examinations, attendance, fee templates, timetable...)
        // loses its link.
        $classes = DB::table('school_classes')->whereNull('program_id')->get();

        foreach ($classes as $class) {
            $programId = DB::table('programs')->where('name', $class->name)->value('id');

            if (! $programId) {
                $programId = DB::table('programs')->insertGetId([
                    'name'       => $class->name,
                    'level'      => $class->level ?? 'college',
                    'is_active'  => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('school_classes')->where('id', $class->id)->update(['program_id' => $programId]);

            // Give the section itself a sensible name if it was blank/duplicate of the program name
            if (empty($class->section)) {
                DB::table('school_classes')->where('id', $class->id)->update(['section' => 'A']);
            }
        }
    }

    public function down()
    {
        Schema::table('school_classes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('program_id');
        });
    }
};

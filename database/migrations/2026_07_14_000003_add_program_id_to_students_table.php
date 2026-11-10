<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up()
    {
        Schema::table('students', function (Blueprint $table) {
            $table->foreignId('program_id')->nullable()->after('school_class_id')->constrained()->nullOnDelete();
        });

        // Backfill each student's program from the program of their current section/class
        DB::table('students')
            ->join('school_classes', 'students.school_class_id', '=', 'school_classes.id')
            ->update(['students.program_id' => DB::raw('school_classes.program_id')]);
    }

    public function down()
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropConstrainedForeignId('program_id');
        });
    }
};

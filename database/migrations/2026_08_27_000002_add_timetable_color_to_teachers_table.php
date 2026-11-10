<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('teachers', function (Blueprint $table) {
            // Manually-assigned hex color (e.g. "#8b0c22") for this teacher's
            // periods on the timetable grid. Null means "not assigned yet" —
            // the grid falls back to its auto-generated golden-angle hue.
            $table->string('timetable_color', 7)->nullable()->after('status');
        });
    }

    public function down()
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->dropColumn('timetable_color');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration {
    public function up(): void
    {
        Schema::table('examinations', function (Blueprint $table) {
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
        });
        // Backfill only unambiguous class/session links; never guess between several sessions.
        $links = DB::table('academic_year_school_class')->select('school_class_id')
            ->selectRaw('MIN(academic_year_id) AS academic_year_id')
            ->groupBy('school_class_id')->havingRaw('COUNT(DISTINCT academic_year_id) = 1')->get();
        foreach ($links as $link) {
            DB::table('examinations')->where('school_class_id', $link->school_class_id)
                ->whereNull('academic_year_id')->update(['academic_year_id' => $link->academic_year_id]);
        }
    }
    public function down(): void
    {
        Schema::table('examinations', fn (Blueprint $table) => $table->dropConstrainedForeignId('academic_year_id'));
    }
};

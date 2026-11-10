<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('installment_date_defaults', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            // NULL means the schedule applies to every program in the selected session.
            // A program-specific schedule overrides the all-program schedule for that student.
            $table->foreignId('program_id')->nullable()->constrained('programs')->cascadeOnDelete();
            $table->unsignedTinyInteger('cycle_no');
            $table->json('dates');
            $table->timestamps();

            $table->index(['academic_year_id', 'cycle_no']);
            $table->index(['academic_year_id', 'program_id', 'cycle_no'], 'installment_defaults_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('installment_date_defaults');
    }
};

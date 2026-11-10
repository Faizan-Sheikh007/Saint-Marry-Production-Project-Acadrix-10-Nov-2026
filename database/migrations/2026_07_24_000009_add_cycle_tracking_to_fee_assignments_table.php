<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::table('fee_assignments', function (Blueprint $table) {
            // Which semester/year number(s) this plan covers, e.g. cycle_start=1,
            // cycle_end=8 for a plan generated for the whole 8-semester program,
            // or cycle_start=cycle_end=3 for a single-semester renewal.
            $table->unsignedSmallInteger('cycle_start')->default(1)->after('installments');
            $table->unsignedSmallInteger('cycle_end')->default(1)->after('cycle_start');

            // Days between the start of one semester/year cycle and the next,
            // used when generating multiple cycles at once (bulk or renewal).
            $table->unsignedSmallInteger('cycle_interval_days')->default(180)->after('frequency_days');

            // Links a renewed/rolled-forward plan back to the plan it continues from,
            // so the fee history for a student can be traced across years.
            $table->foreignId('parent_fee_assignment_id')->nullable()
                ->after('fee_template_id')->constrained('fee_assignments')->nullOnDelete();
        });
    }
    public function down() {
        Schema::table('fee_assignments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_fee_assignment_id');
            $table->dropColumn(['cycle_start', 'cycle_end', 'cycle_interval_days']);
        });
    }
};

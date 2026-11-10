<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::table('fee_templates', function (Blueprint $table) {
            // How many payments make up ONE semester/year for this template,
            // e.g. 2 = "Part 1" and "Part 2" due within each semester.
            $table->unsignedTinyInteger('parts_per_cycle')->default(1)->after('billing_cycle');

            // How many semesters/years the whole program runs for, e.g. 8 for
            // a 4-year BS program billed per semester, or 10 for 5 years.
            // Used to bulk-generate every semester/year's plan in one click.
            $table->unsignedTinyInteger('total_cycles')->nullable()->after('parts_per_cycle');
        });
    }
    public function down() {
        Schema::table('fee_templates', function (Blueprint $table) {
            $table->dropColumn(['parts_per_cycle', 'total_cycles']);
        });
    }
};

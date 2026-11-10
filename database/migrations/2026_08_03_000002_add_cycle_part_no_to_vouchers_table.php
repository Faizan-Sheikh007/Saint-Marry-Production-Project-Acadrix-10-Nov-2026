<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Which semester/year (cycle_no) and which part within it (part_no)
     * a voucher belongs to. Installment numbers on a bulk-generated plan
     * run sequentially across every cycle (1..N), so there was previously
     * no reliable way to ask "which vouchers belong to Semester 3" without
     * parsing the free-text `month` label. New vouchers are stamped with
     * both from here on; older rows are left null (they still work
     * everywhere else — this is additive, not a hard dependency).
     */
    public function up()
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->unsignedSmallInteger('cycle_no')->nullable()->after('installment_total');
            $table->unsignedTinyInteger('part_no')->nullable()->after('cycle_no');
            $table->index(['fee_assignment_id', 'cycle_no']);
        });
    }

    public function down()
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropIndex(['fee_assignment_id', 'cycle_no']);
            $table->dropColumn(['cycle_no', 'part_no']);
        });
    }
};

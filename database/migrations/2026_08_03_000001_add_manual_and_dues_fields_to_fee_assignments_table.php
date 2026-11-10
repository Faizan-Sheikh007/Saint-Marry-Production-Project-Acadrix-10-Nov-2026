<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('fee_assignments', function (Blueprint $table) {
            // Parts each semester/year cycle was split into when this plan was
            // generated. Needed (together with vouchers.cycle_no/part_no) to
            // work out which installments belong to which cycle later on,
            // e.g. when re-splitting one active cycle into more installments.
            $table->unsignedTinyInteger('parts_per_cycle')->default(1)->after('installments');

            // Ad-hoc fee lines the admin typed in by hand at generation time
            // (name/amount/one_time), on top of whatever the fee template
            // already charges. Kept as JSON purely for display/audit on the
            // plan slip — the actual charge is a normal Voucher row.
            $table->json('manual_items')->nullable()->after('notes');

            // Snapshot of the student's outstanding balance (from vouchers
            // outside this plan) that was rolled into this plan's first
            // installment as a "Previous Dues" line, and a human note of
            // where it came from (e.g. "Semester 2 arrears").
            $table->decimal('carried_forward_amount', 12, 2)->default(0)->after('manual_items');
            $table->text('carried_forward_note')->nullable()->after('carried_forward_amount');
        });
    }

    public function down()
    {
        Schema::table('fee_assignments', function (Blueprint $table) {
            $table->dropColumn(['parts_per_cycle', 'manual_items', 'carried_forward_amount', 'carried_forward_note']);
        });
    }
};

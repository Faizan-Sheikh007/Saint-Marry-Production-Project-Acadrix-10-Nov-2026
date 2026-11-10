<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('students', function (Blueprint $table) {
            // Accounts-owned reference number (physical ledger / register
            // page number) an accountant assigns to a student for their own
            // bookkeeping. Not part of the EMS-managed admission profile —
            // set, changed, or cleared entirely from the Accounts side.
            $table->string('ledger_number', 50)->nullable()->after('credit_balance');
        });
    }

    public function down()
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('ledger_number');
        });
    }
};

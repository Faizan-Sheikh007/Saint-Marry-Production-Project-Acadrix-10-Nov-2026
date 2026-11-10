<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('students', function (Blueprint $table) {
            // Money a student/guardian has paid in advance, over and above
            // what any issued voucher currently asks for (e.g. they hand
            // over more than the challan amount, or pay before a voucher
            // even exists). Recorded here rather than against any single
            // voucher — Accounts can see it and draw it down manually
            // against future dues.
            $table->decimal('credit_balance', 12, 2)->default(0)->after('fee_status');
        });
    }

    public function down()
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('credit_balance');
        });
    }
};

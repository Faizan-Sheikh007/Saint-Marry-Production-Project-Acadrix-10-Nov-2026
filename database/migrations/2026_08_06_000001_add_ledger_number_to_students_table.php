<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Accounts-side manual reference number — cross-references this
     * student's entry in the physical/legacy ledger register. Purely a
     * finance-side bookkeeping field; not part of the EMS admission record.
     */
    public function up(): void
    {
        // Guarded — a previous partial run (or a manually-added column)
        // can leave 'ledger_number' present on students without this
        // migration being recorded in the migrations table, which would
        // otherwise throw "Duplicate column name" on the next migrate.
        if (! Schema::hasColumn('students', 'ledger_number')) {
            Schema::table('students', function (Blueprint $table) {
                $table->string('ledger_number', 50)->nullable()->after('credit_balance');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('students', 'ledger_number')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropColumn('ledger_number');
            });
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Every increase / discount / financial aid / per-cycle escalation on a
     * Fee Assignment is now entered and stored as a flat Rupee amount
     * instead of a percentage, so the same figure the operator typed is
     * exactly what gets charged/waived — it no longer needs a calculator
     * to translate a percentage against an amount that changes template
     * to template.
     */
    public function up(): void
    {
        Schema::table('fee_assignments', function (Blueprint $table) {
            $table->decimal('increase_amount', 12, 2)->default(0)->after('percentage_increase');
            $table->decimal('discount_amount', 12, 2)->default(0)->after('discount_percentage');
            $table->decimal('financial_aid_amount', 12, 2)->default(0)->after('financial_aid_percentage');
            $table->decimal('cycle_increment_amount', 12, 2)->default(0)->after('increase_amount');
        });

        // Best-effort backfill for any existing rows: turn the stored
        // percentage into an equivalent flat amount against that plan's
        // own recorded total, so historical plans still show a sensible
        // figure instead of a bare 0.
        DB::table('fee_assignments')->orderBy('id')->chunk(200, function ($rows) {
            foreach ($rows as $row) {
                $base = (float) $row->total_amount;
                DB::table('fee_assignments')->where('id', $row->id)->update([
                    'increase_amount'      => round($base * ((float) $row->percentage_increase) / 100, 2),
                    'discount_amount'      => round($base * ((float) $row->discount_percentage) / 100, 2),
                    'financial_aid_amount' => round($base * ((float) $row->financial_aid_percentage) / 100, 2),
                ]);
            }
        });

        Schema::table('fee_assignments', function (Blueprint $table) {
            $table->dropColumn(['percentage_increase', 'discount_percentage', 'financial_aid_percentage']);
        });
    }

    public function down(): void
    {
        Schema::table('fee_assignments', function (Blueprint $table) {
            $table->decimal('percentage_increase', 6, 2)->default(0);
            $table->decimal('discount_percentage', 6, 2)->default(0);
            $table->decimal('financial_aid_percentage', 6, 2)->default(0);
        });

        Schema::table('fee_assignments', function (Blueprint $table) {
            $table->dropColumn(['increase_amount', 'discount_amount', 'financial_aid_amount', 'cycle_increment_amount']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Fee template items originally required an amount up front. Admins now
     * need to add several fee heads to a template at once and fill in
     * amounts later, so the column must accept NULL. A plain MODIFY (no
     * doctrine/dbal needed) is enough on MySQL/Postgres; SQLite already
     * supports this via Laravel's own grammar.
     */
    public function up()
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE `fee_template_items` MODIFY `amount` DECIMAL(12,2) NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE fee_template_items ALTER COLUMN amount DROP NOT NULL');
        } else {
            Schema::table('fee_template_items', function ($table) {
                $table->decimal('amount', 12, 2)->nullable()->change();
            });
        }
    }

    public function down()
    {
        // Backfill any NULLs before re-adding the NOT NULL constraint so the migration is reversible.
        DB::table('fee_template_items')->whereNull('amount')->update(['amount' => 0]);

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE `fee_template_items` MODIFY `amount` DECIMAL(12,2) NOT NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE fee_template_items ALTER COLUMN amount SET NOT NULL');
        } else {
            Schema::table('fee_template_items', function ($table) {
                $table->decimal('amount', 12, 2)->nullable(false)->change();
            });
        }
    }
};

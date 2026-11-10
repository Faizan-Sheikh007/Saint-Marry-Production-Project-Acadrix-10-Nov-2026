<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * The 30-day fallback spacing was previously baked in as the column's
     * default value. Every caller now sends frequency_days explicitly
     * (it is a required field on both the Fee Assignment and Renew
     * forms), so the implicit "30" default is removed rather than
     * silently applied whenever a value is missing.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            // SQLite has no ALTER COLUMN DROP DEFAULT; only column defaults
            // used for *future* inserts matter here, and existing rows are
            // untouched either way, so this is a safe no-op on SQLite.
            return;
        }

        DB::statement('ALTER TABLE fee_assignments ALTER COLUMN frequency_days DROP DEFAULT');
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE fee_assignments ALTER COLUMN frequency_days SET DEFAULT 30');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * The examination controller's signature is now stored directly in the
     * database as a base64 data URI (rather than as a path to a file on
     * disk), so it survives on hosts where the storage disk isn't
     * persistent. A base64-encoded PNG can comfortably exceed MySQL's
     * 64KB TEXT limit, so widen the column to LONGTEXT. SQLite's TEXT
     * type has no such limit and needs no change.
     */
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE settings MODIFY value LONGTEXT NULL');
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE settings MODIFY value TEXT NULL');
        }
    }
};

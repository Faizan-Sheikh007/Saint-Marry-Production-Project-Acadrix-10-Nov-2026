<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * The original table made only guardian_name and guardian_relation
     * non-nullable. All the other guardian/emergency columns are already
     * nullable, so changing just these two preserves every existing record.
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('guardian_name')->nullable()->change();
            $table->string('guardian_relation')->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        // Make rollback safe even for records created after this migration.
        DB::table('students')->whereNull('guardian_name')->update(['guardian_name' => '']);
        DB::table('students')->whereNull('guardian_relation')->update(['guardian_relation' => 'father']);

        Schema::table('students', function (Blueprint $table) {
            $table->string('guardian_name')->nullable(false)->change();
            $table->string('guardian_relation')->nullable(false)->default('father')->change();
        });
    }
};

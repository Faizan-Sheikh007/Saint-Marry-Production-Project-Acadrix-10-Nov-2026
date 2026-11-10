<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::table('fee_assignments', function (Blueprint $table) {
            // Cycle numbers an admin/accountant has manually force-unlocked,
            // overriding the automatic splitLockReason() check (e.g. when the
            // auto-lock is wrong for this plan). e.g. [1, 2].
            $table->json('manual_unlocked_cycles')->nullable()->after('notes');
        });
    }
    public function down() {
        Schema::table('fee_assignments', function (Blueprint $table) {
            $table->dropColumn('manual_unlocked_cycles');
        });
    }
};

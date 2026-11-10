<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('payments', function (Blueprint $table) {
            // Students pay online from their dashboard and upload a
            // screenshot/photo of the payment proof. Those submissions sit
            // as 'pending' (not yet applied to the voucher balance) until
            // an Accounts user verifies or rejects them.
            $table->enum('status', ['verified', 'pending', 'rejected'])->default('verified')->after('notes');
            $table->string('proof_path')->nullable()->after('status');
            $table->string('submitted_by')->nullable()->after('proof_path'); // 'student' or null (recorded directly by Accounts)
            $table->string('verified_by')->nullable()->after('submitted_by');
            $table->dateTime('verified_at')->nullable()->after('verified_by');
            $table->text('rejection_reason')->nullable()->after('verified_at');
        });
    }

    public function down()
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['status', 'proof_path', 'submitted_by', 'verified_by', 'verified_at', 'rejection_reason']);
        });
    }
};

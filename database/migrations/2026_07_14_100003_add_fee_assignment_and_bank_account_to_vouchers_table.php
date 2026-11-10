<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->foreignId('fee_assignment_id')->nullable()->after('fee_template_id')->constrained()->nullOnDelete();
            $table->foreignId('bank_account_id')->nullable()->after('fee_assignment_id')->constrained()->nullOnDelete();
        });
    }
    public function down() {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('fee_assignment_id');
            $table->dropConstrainedForeignId('bank_account_id');
        });
    }
};

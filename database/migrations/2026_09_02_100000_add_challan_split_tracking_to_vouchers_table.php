<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->string('split_root_voucher_no')->nullable()->after('batch_id');
            $table->unsignedSmallInteger('split_sequence')->nullable()->after('split_root_voucher_no');
            $table->index(['student_id', 'split_root_voucher_no']);
        });
    }

    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropIndex(['student_id', 'split_root_voucher_no']);
            $table->dropColumn(['split_root_voucher_no', 'split_sequence']);
        });
    }
};

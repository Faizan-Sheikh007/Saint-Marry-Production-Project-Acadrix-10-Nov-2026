<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->decimal('split_carry_cap', 12, 2)->nullable();
        });
    }
    public function down(): void {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropColumn('split_carry_cap');
        });
    }
};

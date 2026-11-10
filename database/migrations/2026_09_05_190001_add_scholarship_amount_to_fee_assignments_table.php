<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fee_assignments', function (Blueprint $table) {
            $table->decimal('scholarship_amount', 12, 2)->default(0)->after('increase_amount');
        });
    }

    public function down(): void
    {
        Schema::table('fee_assignments', function (Blueprint $table) {
            $table->dropColumn('scholarship_amount');
        });
    }
};

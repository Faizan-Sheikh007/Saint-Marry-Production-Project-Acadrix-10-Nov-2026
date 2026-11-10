<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::table('fee_assignments', function (Blueprint $table) {
            $table->decimal('financial_aid_percentage', 6, 2)->default(0)->after('discount_percentage');
        });
    }
    public function down() {
        Schema::table('fee_assignments', function (Blueprint $table) {
            $table->dropColumn('financial_aid_percentage');
        });
    }
};

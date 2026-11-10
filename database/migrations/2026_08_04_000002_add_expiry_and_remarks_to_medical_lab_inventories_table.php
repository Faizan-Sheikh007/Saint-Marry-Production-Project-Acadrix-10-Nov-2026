<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('medical_lab_inventories', function (Blueprint $table) {
            $table->date('expiry_date')->nullable()->after('min_quantity');
            $table->text('remarks')->nullable()->after('expiry_date');
        });
    }

    public function down()
    {
        Schema::table('medical_lab_inventories', function (Blueprint $table) {
            $table->dropColumn(['expiry_date', 'remarks']);
        });
    }
};

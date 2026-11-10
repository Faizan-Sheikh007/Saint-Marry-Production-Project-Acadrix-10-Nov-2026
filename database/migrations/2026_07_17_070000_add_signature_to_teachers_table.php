<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::table('teachers', function (Blueprint $table) {
            if (!Schema::hasColumn('teachers', 'signature')) {
                $table->string('signature')->nullable()->after('photo');
            }
        });
    }
    public function down() {
        Schema::table('teachers', function (Blueprint $table) {
            if (Schema::hasColumn('teachers', 'signature')) {
                $table->dropColumn('signature');
            }
        });
    }
};

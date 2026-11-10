<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('fee_assignments', function (Blueprint $table) {
            // Snapshot of the LMS login issued at admission time, carried
            // over from the "credentials" flash so it can be printed on the
            // installment plan slip. Nullable — only populated when a fresh
            // LMS login was just issued during the admission flow.
            $table->string('lms_login_email')->nullable()->after('notes');
            $table->string('lms_login_password_plain')->nullable()->after('lms_login_email');
        });
    }

    public function down()
    {
        Schema::table('fee_assignments', function (Blueprint $table) {
            $table->dropColumn(['lms_login_email', 'lms_login_password_plain']);
        });
    }
};

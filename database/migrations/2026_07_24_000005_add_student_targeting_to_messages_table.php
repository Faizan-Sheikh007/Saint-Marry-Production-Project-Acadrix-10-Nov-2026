<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lets admin send a one-off message to a specific student's own email
 * and/or their parent's email (as opposed to the existing broadcast-style
 * announcement/audience fields already on this table).
 */
return new class extends Migration {
    public function up()
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->foreignId('student_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->foreignId('sent_by')->nullable()->after('student_id')->constrained('users')->nullOnDelete();
            $table->string('recipient_student_email')->nullable()->after('audience');
            $table->string('recipient_parent_email')->nullable()->after('recipient_student_email');
        });
    }

    public function down()
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('student_id');
            $table->dropConstrainedForeignId('sent_by');
            $table->dropColumn(['recipient_student_email', 'recipient_parent_email']);
        });
    }
};

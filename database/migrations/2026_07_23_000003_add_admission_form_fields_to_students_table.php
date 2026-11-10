<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('students', function (Blueprint $table) {
            // Admission-form fields requested for the new application form.
            $table->string('father_name')->nullable()->after('name');
            $table->text('permanent_address')->nullable()->after('address');
            $table->enum('marital_status', ['single', 'married', 'divorced', 'widowed'])->nullable()->after('religion');
            $table->enum('shift', ['morning', 'evening'])->nullable()->after('program_id');
            $table->string('emergency_contact_address')->nullable()->after('emergency_contact_relation');

            // Financial aid / scholarship section on the admission form.
            $table->boolean('financial_aid_requested')->default(false)->after('fee_status');
            $table->string('financial_aid_type')->nullable()->after('financial_aid_requested');
            $table->text('financial_aid_notes')->nullable()->after('financial_aid_type');

            // Educational qualifications: one row per level (Matriculation,
            // Intermediate, Bachelor's, Professional, Master's, Degree/
            // Diploma/Certificate), each with institution/board/grade/year —
            // stored as JSON since the set of levels is fixed and small.
            $table->json('education_qualifications')->nullable()->after('documents');
        });
    }

    public function down()
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'father_name', 'permanent_address', 'marital_status', 'shift',
                'emergency_contact_address', 'financial_aid_requested',
                'financial_aid_type', 'financial_aid_notes', 'education_qualifications',
            ]);
        });
    }
};

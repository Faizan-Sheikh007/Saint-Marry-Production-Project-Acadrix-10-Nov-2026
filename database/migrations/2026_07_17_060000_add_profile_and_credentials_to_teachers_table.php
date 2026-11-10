<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::table('teachers', function (Blueprint $table) {
            // ── Login credentials (kept so an admin can view/reprint them) ──
            if (!Schema::hasColumn('teachers', 'login_password')) {
                $table->text('login_password')->nullable()->after('employee_id'); // encrypted cast
            }
            if (!Schema::hasColumn('teachers', 'password_reset_at')) {
                $table->timestamp('password_reset_at')->nullable()->after('login_password');
            }

            // ── Detailed profile fields ──
            if (!Schema::hasColumn('teachers', 'father_name'))        $table->string('father_name')->nullable()->after('name');
            if (!Schema::hasColumn('teachers', 'date_of_birth'))      $table->date('date_of_birth')->nullable()->after('gender');
            if (!Schema::hasColumn('teachers', 'blood_group'))        $table->string('blood_group', 5)->nullable()->after('date_of_birth');
            if (!Schema::hasColumn('teachers', 'marital_status'))     $table->enum('marital_status', ['single','married','other'])->nullable()->after('blood_group');
            if (!Schema::hasColumn('teachers', 'emergency_contact'))  $table->string('emergency_contact')->nullable()->after('phone');
            if (!Schema::hasColumn('teachers', 'city'))               $table->string('city')->nullable()->after('address');

            // ── Employment details ──
            if (!Schema::hasColumn('teachers', 'designation'))        $table->string('designation')->nullable()->after('subject_id');
            if (!Schema::hasColumn('teachers', 'department'))         $table->string('department')->nullable()->after('designation');
            if (!Schema::hasColumn('teachers', 'employment_type'))    $table->enum('employment_type', ['permanent','contract','visiting','probation'])->default('permanent')->after('department');

            // ── Bank / payroll details ──
            if (!Schema::hasColumn('teachers', 'bank_name'))          $table->string('bank_name')->nullable()->after('salary');
            if (!Schema::hasColumn('teachers', 'bank_account_title')) $table->string('bank_account_title')->nullable()->after('bank_name');
            if (!Schema::hasColumn('teachers', 'bank_account_number'))$table->string('bank_account_number')->nullable()->after('bank_account_title');
        });
    }
    public function down() {
        Schema::table('teachers', function (Blueprint $table) {
            $cols = [
                'login_password','password_reset_at','father_name','blood_group','marital_status',
                'emergency_contact','city','designation','department','employment_type',
                'bank_name','bank_account_title','bank_account_number',
            ];
            foreach ($cols as $c) {
                if (Schema::hasColumn('teachers', $c)) $table->dropColumn($c);
            }
        });
    }
};

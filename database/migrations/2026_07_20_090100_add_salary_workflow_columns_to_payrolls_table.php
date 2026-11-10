<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        // teacher_id was NOT NULL in the original migration. A payroll
        // row must now be able to belong to a Staff member instead, so it
        // needs to be nullable. Doctrine/DBAL isn't installed, so we can't
        // use Blueprint::change() — a plain MODIFY works fine on MySQL.
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE `payrolls` MODIFY `teacher_id` BIGINT UNSIGNED NULL');
        }

        Schema::table('payrolls', function (Blueprint $table) {
            $table->foreignId('staff_id')->nullable()->after('teacher_id')->constrained('staff')->cascadeOnDelete();
            $table->string('payee_type', 20)->default('teacher')->after('staff_id'); // teacher | staff

            // Attendance-driven deduction (teacher side; auto-computed from
            // teacher_attendances but always editable by the admin).
            $table->unsignedSmallInteger('working_days')->nullable()->after('month');
            $table->unsignedSmallInteger('present_days')->nullable()->after('working_days');
            $table->unsignedSmallInteger('absent_days')->nullable()->after('present_days');
            $table->unsignedSmallInteger('leave_days')->nullable()->after('absent_days');
            $table->decimal('absent_deduction', 12, 2)->default(0)->after('leave_days');

            // Fines (late arrivals, damages, policy violations, ...) —
            // aggregated from salary_fines at generation time but stored
            // as a snapshot so a payslip never silently changes later.
            $table->decimal('fine_amount', 12, 2)->default(0)->after('absent_deduction');
            $table->text('fine_reason')->nullable()->after('fine_amount');

            $table->decimal('bonus', 12, 2)->default(0)->after('fine_reason');

            // Admin -> Accounts request/approval/delivery workflow.
            // Kept as a separate string column (not a DB enum) so new
            // states can be added without a risky enum ALTER later.
            // Values: draft, requested, issued, delivered, rejected.
            $table->string('workflow_status', 20)->default('draft')->after('status');
            $table->foreignId('requested_by')->nullable()->after('workflow_status')->constrained('users')->nullOnDelete();
            $table->timestamp('requested_at')->nullable()->after('requested_by');
            $table->text('request_note')->nullable()->after('requested_at');
            $table->text('rejection_reason')->nullable()->after('request_note');
        });
        // Note: the `->default('teacher')` above already backfills every
        // pre-existing row correctly (all payrolls were teacher-owned
        // under the old schema), so no separate data migration is needed.
    }

    public function down()
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropForeign(['staff_id']);
            $table->dropForeign(['requested_by']);
            $table->dropColumn([
                'staff_id', 'payee_type', 'working_days', 'present_days', 'absent_days',
                'leave_days', 'absent_deduction', 'fine_amount', 'fine_reason', 'bonus',
                'workflow_status', 'requested_by', 'requested_at', 'request_note', 'rejection_reason',
            ]);
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE `payrolls` MODIFY `teacher_id` BIGINT UNSIGNED NOT NULL');
        }
    }
};

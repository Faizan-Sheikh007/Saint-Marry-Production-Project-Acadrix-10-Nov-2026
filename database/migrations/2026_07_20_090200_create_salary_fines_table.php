<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('salary_fines', function (Blueprint $table) {
            $table->id();

            // Polymorphic-by-hand (payee_type: 'teacher'|'staff') rather
            // than Eloquent morphs(), so it matches the same payee_type
            // convention already used on payrolls.
            $table->string('payee_type', 20);
            $table->unsignedBigInteger('payee_id');

            // Once pulled into a payslip, the fine is linked here so it
            // isn't accidentally charged twice on a later payroll.
            $table->foreignId('payroll_id')->nullable()->constrained('payrolls')->nullOnDelete();

            $table->decimal('amount', 12, 2);
            $table->string('reason');
            $table->date('date');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['payee_type', 'payee_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('salary_fines');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('employee_id')->unique();
            $table->string('name');
            $table->string('father_name')->nullable();
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('emergency_contact')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('blood_group')->nullable();
            $table->string('marital_status')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();

            // Sensitive — stored as an RSA+AES envelope, never plaintext.
            // Column is `text` because the encrypted envelope is far
            // longer than the original value.
            $table->text('cnic')->nullable();
            $table->text('bank_account_number')->nullable();

            $table->string('qualification')->nullable();
            $table->integer('experience')->nullable();

            // What distinguishes staff from teachers: a free-form role
            // instead of a subject — Accountant, Security Guard, Driver,
            // Librarian Assistant, Peon, Lab Attendant, etc.
            $table->string('designation');
            $table->string('department')->nullable();
            $table->enum('employment_type', ['full_time', 'part_time', 'contract', 'daily_wage'])->default('full_time');

            $table->decimal('salary', 12, 2)->default(0);
            $table->string('bank_name')->nullable();
            $table->string('bank_account_title')->nullable();

            $table->date('joining_date')->nullable();
            $table->string('photo')->nullable();

            $table->enum('status', ['active', 'inactive', 'terminated'])->default('active');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('staff');
    }
};

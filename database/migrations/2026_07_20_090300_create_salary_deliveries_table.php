<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('salary_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_id')->constrained('payrolls')->cascadeOnDelete();

            // The Accounts officer who actually paid out the salary.
            $table->foreignId('issued_by')->constrained('users')->cascadeOnDelete();

            $table->enum('payment_method', ['bank_transfer', 'cash', 'cheque', 'online_wallet'])->default('bank_transfer');
            $table->string('transaction_reference')->nullable();
            $table->decimal('amount_paid', 12, 2);
            $table->text('officer_note')->nullable();

            // Proof screenshots — paths on the PRIVATE 'local' disk only,
            // served through an authenticated/role-gated route, never a
            // public URL. Stored as a JSON array to allow more than one.
            $table->json('proof_screenshots')->nullable();

            // Feedback from the receiver (the teacher/staff who was
            // paid) confirming they got the money. Teachers can submit
            // this themselves from their own portal; for staff (who
            // don't have portal logins) the Accounts officer records it
            // on their behalf after collecting it directly.
            $table->text('receiver_feedback')->nullable();
            $table->unsignedTinyInteger('receiver_rating')->nullable(); // 1–5
            $table->timestamp('receiver_feedback_at')->nullable();
            $table->boolean('feedback_recorded_by_officer')->default(false);
            $table->boolean('acknowledged')->default(false);

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('salary_deliveries');
    }
};

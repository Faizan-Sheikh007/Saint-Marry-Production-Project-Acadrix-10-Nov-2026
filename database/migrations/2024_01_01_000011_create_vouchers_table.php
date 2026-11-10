<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up() {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('voucher_no')->unique();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fee_head_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('fee_template_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title'); // display label, e.g. "Monthly Fee - Jul 2026"
            $table->string('month')->nullable(); // for recurring monthly vouchers
            $table->decimal('amount', 12, 2);
            $table->decimal('fine_amount', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->date('due_date');
            $table->dateTime('issued_at')->nullable();
            $table->unsignedTinyInteger('installment_no')->nullable();
            $table->unsignedTinyInteger('installment_total')->nullable();
            $table->string('batch_id')->nullable(); // groups vouchers generated together (bulk)
            $table->enum('status', ['unpaid','partial','paid','overdue','cancelled'])->default('unpaid');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }
    public function down() { Schema::dropIfExists('vouchers'); }
};

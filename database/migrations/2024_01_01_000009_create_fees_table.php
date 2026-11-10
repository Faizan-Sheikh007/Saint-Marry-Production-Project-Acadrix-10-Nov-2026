<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up() {
        Schema::create('fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 2); $table->string('fee_type')->default('Monthly Fee');
            $table->date('due_date'); $table->timestamp('paid_at')->nullable();
            $table->enum('status', ['paid','unpaid','partial'])->default('unpaid');
            $table->string('month')->nullable(); $table->text('description')->nullable();
            $table->string('receipt_no')->nullable()->unique(); $table->timestamps();
        });
    }
    public function down() { Schema::dropIfExists('fees'); }
};

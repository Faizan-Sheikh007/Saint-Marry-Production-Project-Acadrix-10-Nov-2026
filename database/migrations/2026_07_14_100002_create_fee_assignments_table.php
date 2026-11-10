<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('fee_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fee_template_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('bank_account_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('percentage_increase', 6, 2)->default(0);
            $table->decimal('discount_percentage', 6, 2)->default(0);
            $table->unsignedTinyInteger('installments');
            $table->enum('billing_mode', ['split', 'monthly_recurring'])->default('monthly_recurring');
            $table->json('months')->nullable(); // e.g. ["November 2026","December 2026",...]
            $table->date('first_due_date');
            $table->unsignedSmallInteger('frequency_days')->default(30);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->string('batch_prefix')->unique();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }
    public function down() { Schema::dropIfExists('fee_assignments'); }
};

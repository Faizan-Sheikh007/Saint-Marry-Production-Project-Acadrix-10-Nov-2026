<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('transport_challans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transport_request_id')->constrained('transport_requests')->cascadeOnDelete();
            $table->string('challan_no')->unique();
            $table->string('month')->nullable();
            $table->decimal('amount', 10, 2);
            $table->date('due_date')->nullable();

            // Payment / proof submitted by the requester (student or teacher).
            $table->string('payment_method')->nullable();
            $table->string('payment_reference')->nullable();
            $table->decimal('paid_amount', 10, 2)->nullable();
            $table->string('proof_screenshot')->nullable();
            $table->timestamp('submitted_at')->nullable();

            $table->enum('status', ['unpaid', 'submitted', 'paid', 'rejected'])->default('unpaid');
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }
    public function down() { Schema::dropIfExists('transport_challans'); }
};

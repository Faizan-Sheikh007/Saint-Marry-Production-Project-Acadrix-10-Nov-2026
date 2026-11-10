<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('student_balance_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30); // receipt, allocation, reversal
            $table->decimal('amount', 12, 2); // signed change to available credit
            $table->decimal('balance_after', 12, 2);
            // Keep the historical payment ID even when a payment is reversed/deleted.
            $table->unsignedBigInteger('payment_id')->nullable()->index();
            $table->string('method', 30)->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->string('reference_no', 100)->nullable();
            $table->string('recorded_by')->nullable();
            $table->text('notes')->nullable();
            $table->uuid('idempotency_key')->nullable()->unique();
            $table->timestamps();
            $table->index(['student_id', 'created_at']);
        });
        Schema::table('payments', function (Blueprint $table) {
            $table->enum('method', ['cash', 'bank_transfer', 'cheque', 'online', 'balance_credit'])
                ->default('cash')->change();
        });
    }

    public function down(): void
    {
        // Never silently relabel an existing credit allocation as cash.
        if (\Illuminate\Support\Facades\DB::table('payments')->where('method', 'balance_credit')->exists()
            || \Illuminate\Support\Facades\DB::table('student_balance_transactions')->exists()) {
            throw new \RuntimeException('Credit allocations exist. Restore a database backup instead of rolling back this financial migration.');
        }
        Schema::table('payments', function (Blueprint $table) {
            $table->enum('method', ['cash', 'bank_transfer', 'cheque', 'online'])->default('cash')->change();
        });
        Schema::dropIfExists('student_balance_transactions');
    }
};

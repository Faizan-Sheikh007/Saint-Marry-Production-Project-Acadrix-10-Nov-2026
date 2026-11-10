<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up() {
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained()->cascadeOnDelete();
            $table->decimal('basic_salary', 10, 2); $table->decimal('allowances', 10, 2)->default(0);
            $table->decimal('deductions', 10, 2)->default(0); $table->decimal('net_salary', 10, 2);
            $table->string('month'); $table->enum('status', ['pending','paid'])->default('pending');
            $table->timestamp('paid_at')->nullable(); $table->timestamps();
        });
    }
    public function down() { Schema::dropIfExists('payrolls'); }
};

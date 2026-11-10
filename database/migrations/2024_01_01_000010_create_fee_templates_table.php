<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up() {
        Schema::create('fee_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g. "BSCS Semester Fee Plan"
            $table->foreignId('school_class_id')->nullable()->constrained()->nullOnDelete(); // null = applies to all
            $table->enum('billing_cycle', ['one_time','monthly','semester','annual'])->default('monthly');
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('fee_template_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fee_template_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fee_head_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->timestamps();
        });
    }
    public function down() {
        Schema::dropIfExists('fee_template_items');
        Schema::dropIfExists('fee_templates');
    }
};

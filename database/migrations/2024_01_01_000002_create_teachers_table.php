<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up() {
        Schema::create('teachers', function (Blueprint $table) {
            $table->id(); $table->string('employee_id')->unique(); $table->string('name');
            $table->string('email')->unique(); $table->string('phone')->nullable();
            $table->enum('gender', ['male','female','other']); $table->date('date_of_birth')->nullable();
            $table->text('address')->nullable(); $table->string('cnic')->nullable();
            $table->string('qualification')->nullable(); $table->integer('experience')->nullable();
            $table->decimal('salary', 10, 2)->nullable();
            $table->foreignId('subject_id')->nullable()->constrained()->nullOnDelete();
            $table->date('joining_date')->nullable(); $table->string('photo')->nullable();
            $table->enum('status', ['active','inactive'])->default('active'); $table->timestamps();
        });
    }
    public function down() { Schema::dropIfExists('teachers'); }
};

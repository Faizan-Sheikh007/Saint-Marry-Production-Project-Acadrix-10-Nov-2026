<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('book_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->nullable()->constrained()->nullOnDelete();

            // Snapshot of the student's details at the time of issuing, so the
            // record stays accurate even if the student's own profile changes later.
            $table->string('student_name');
            $table->string('student_roll_no')->nullable();
            $table->string('class_name')->nullable();
            $table->string('program_name')->nullable();
            $table->string('semester')->nullable();
            $table->string('contact_number')->nullable();

            $table->date('issue_date');
            $table->unsignedInteger('days');
            $table->date('due_date');
            $table->decimal('fine_per_day', 8, 2)->default(0);

            $table->enum('status', ['issued', 'returned'])->default('issued');
            $table->date('returned_date')->nullable();
            $table->decimal('fine_amount', 8, 2)->default(0);

            $table->timestamps();
        });
    }
    public function down() {
        Schema::dropIfExists('book_issues');
    }
};

<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up() {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('student_id')->unique();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->enum('gender', ['male','female','other']);
            $table->date('date_of_birth')->nullable();
            $table->text('address')->nullable();
            $table->string('cnic')->nullable();
            $table->string('religion')->nullable();

            // Guardian / parent info
            $table->string('guardian_name');
            $table->string('guardian_occupation')->nullable();
            $table->string('guardian_relation')->default('father');
            $table->string('guardian_phone')->nullable();
            $table->string('guardian_cnic')->nullable();

            // Emergency contact
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->string('emergency_contact_relation')->nullable();

            // Program / class / batch
            $table->foreignId('school_class_id')->nullable()->constrained()->nullOnDelete();
            $table->string('section')->nullable();
            $table->string('roll_number')->nullable();
            $table->string('session')->nullable();
            $table->enum('level', ['college','university'])->default('college');
            $table->date('admission_date')->nullable();

            // Uploaded documents (stored paths, JSON list)
            $table->json('documents')->nullable();

            // Status
            $table->enum('status', ['active','inactive','suspended','graduated','dropped','transferred'])->default('active');
            $table->enum('fee_status', ['paid','unpaid','partial'])->default('unpaid');
            $table->string('photo')->nullable();

            // ID Card
            $table->string('id_card_number')->nullable()->unique();
            $table->date('id_card_issued_at')->nullable();
            $table->unsignedTinyInteger('id_card_reissue_count')->default(0);

            // Attendance-linked fee hold
            $table->decimal('attendance_percentage', 5, 2)->nullable();

            $table->timestamps();
        });
    }
    public function down() { Schema::dropIfExists('students'); }
};

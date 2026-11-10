<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('internship_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internship_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();

            // Captured at the moment of applying (roll number / student ID the
            // student typed in on the application form).
            $table->string('roll_number')->nullable();

            // pending          -> just submitted, awaiting admin review
            // admin_approved   -> admin approved; challan generated, sent to accounts
            // admin_rejected   -> admin declined
            $table->enum('status', ['pending', 'admin_approved', 'admin_rejected'])->default('pending');
            $table->text('admin_notes')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('reviewed_at')->nullable();

            // The challan issued for this application once approved — reuses
            // the existing Voucher/Accounts payment pipeline entirely, so
            // Accounts collects it exactly like any other fee voucher.
            $table->foreignId('voucher_id')->nullable()->constrained('vouchers')->nullOnDelete();
            $table->enum('fee_status', ['not_applicable', 'unpaid', 'paid'])->default('not_applicable');

            $table->timestamps();

            $table->unique(['internship_id', 'student_id']);
        });
    }
    public function down() { Schema::dropIfExists('internship_applications'); }
};

<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('internships', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();
            $table->decimal('fee_amount', 12, 2)->default(0);
            $table->date('due_date')->nullable();

            // Who this posting is visible to. 'all' = every student.
            // 'programs' = only students in the listed program IDs.
            // 'students' = only the specifically listed student IDs.
            // Entirely admin-controlled — students never choose visibility.
            $table->enum('target_type', ['all', 'programs', 'students'])->default('all');
            $table->json('target_program_ids')->nullable();
            $table->json('target_student_ids')->nullable();

            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }
    public function down() { Schema::dropIfExists('internships'); }
};

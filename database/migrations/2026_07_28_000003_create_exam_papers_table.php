<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('exam_papers', function (Blueprint $table) {
            $table->id();
            // One paper per examination row (each row is already a unique class/subject/teacher pairing).
            $table->foreignId('examination_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('status', ['due', 'pending', 'uploaded'])->default('due');
            $table->text('instructions')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->index(['teacher_id', 'status']);
        });
    }
    public function down() { Schema::dropIfExists('exam_papers'); }
};

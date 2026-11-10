<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('exam_paper_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_paper_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['short', 'long']);
            $table->text('question');
            $table->unsignedInteger('marks')->nullable();
            $table->unsignedInteger('order_no')->default(0);
            $table->timestamps();
        });
    }
    public function down() { Schema::dropIfExists('exam_paper_questions'); }
};

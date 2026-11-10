<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('student_report_remarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('teacher_name', 150)->nullable();
            $table->string('term', 150)->nullable();
            $table->date('remark_date')->nullable();
            $table->text('remarks');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['student_id', 'remark_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_report_remarks');
    }
};

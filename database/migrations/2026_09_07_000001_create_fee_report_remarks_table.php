<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('fee_report_remarks', function (Blueprint $table) {
            $table->id();
            $table->string('report_type', 20);
            $table->foreignId('fee_assignment_id')->constrained()->cascadeOnDelete();
            $table->text('remarks')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['report_type', 'fee_assignment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_report_remarks');
    }
};

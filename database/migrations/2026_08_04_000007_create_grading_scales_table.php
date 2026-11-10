<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up() {
        Schema::create('grading_scales', function (Blueprint $table) {
            $table->id();
            $table->string('grade');                 // e.g. "A+"
            $table->decimal('min_percentage', 5, 2);  // inclusive lower bound
            $table->decimal('max_percentage', 5, 2)->nullable(); // inclusive upper bound, null = open-ended top band
            $table->string('remarks')->nullable();    // e.g. "Excellent", "Fail"
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }
    public function down() { Schema::dropIfExists('grading_scales'); }
};

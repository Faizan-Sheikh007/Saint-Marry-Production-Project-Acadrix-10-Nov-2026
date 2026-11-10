<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up() {
        Schema::create('activities', function (Blueprint $table) {
            $table->id(); $table->text('description'); $table->string('icon')->default('fas fa-circle');
            $table->string('color')->default('primary');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('model_type')->nullable(); $table->unsignedBigInteger('model_id')->nullable();
            $table->timestamps();
        });
    }
    public function down() { Schema::dropIfExists('activities'); }
};

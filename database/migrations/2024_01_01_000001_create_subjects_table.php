<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up() {
        Schema::create('subjects', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('code')->nullable()->unique();
            $table->text('description')->nullable(); $table->integer('credit_hours')->default(1);
            $table->timestamps();
        });
    }
    public function down() { Schema::dropIfExists('subjects'); }
};

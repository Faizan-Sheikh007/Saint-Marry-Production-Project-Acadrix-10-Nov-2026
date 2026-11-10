<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up() {
        Schema::create('books', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('isbn')->nullable()->unique();
            $table->string('author')->nullable(); $table->string('category')->nullable();
            $table->integer('copies')->default(1); $table->integer('available_copies')->default(1);
            $table->string('rack')->nullable(); $table->timestamps();
        });
    }
    public function down() { Schema::dropIfExists('books'); }
};

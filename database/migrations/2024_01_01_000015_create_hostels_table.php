<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up() {
        Schema::create('hostels', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->enum('type', ['boys','girls']);
            $table->integer('capacity')->default(10); $table->integer('occupied')->default(0);
            $table->string('warden')->nullable(); $table->decimal('fee', 10, 2)->nullable();
            $table->text('description')->nullable(); $table->timestamps();
        });
    }
    public function down() { Schema::dropIfExists('hostels'); }
};

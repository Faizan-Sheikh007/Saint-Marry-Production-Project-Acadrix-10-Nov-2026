<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up() {
        Schema::create('parents', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('email')->nullable();
            $table->string('phone'); $table->string('cnic')->nullable();
            $table->string('occupation')->nullable(); $table->text('address')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }
    public function down() { Schema::dropIfExists('parents'); }
};

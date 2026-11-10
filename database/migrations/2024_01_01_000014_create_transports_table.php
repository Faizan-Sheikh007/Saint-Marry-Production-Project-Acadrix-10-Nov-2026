<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up() {
        Schema::create('transports', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('route');
            $table->string('vehicle_number'); $table->string('driver_name')->nullable();
            $table->string('driver_phone')->nullable(); $table->integer('capacity')->default(30);
            $table->enum('status', ['active','inactive'])->default('active'); $table->timestamps();
        });
    }
    public function down() { Schema::dropIfExists('transports'); }
};

<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('hostel_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hostel_id')->constrained('hostels')->cascadeOnDelete();
            $table->string('room_number');
            $table->string('floor')->nullable();
            $table->integer('capacity')->default(2);
            $table->decimal('monthly_rent', 10, 2)->default(0);
            $table->enum('status', ['active', 'maintenance', 'inactive'])->default('active');
            $table->timestamps();

            $table->unique(['hostel_id', 'room_number']);
        });
    }
    public function down() { Schema::dropIfExists('hostel_rooms'); }
};

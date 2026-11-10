<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('hostel_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hostel_booking_id')->constrained('hostel_bookings')->cascadeOnDelete();
            $table->enum('type', ['check_in', 'check_out']);
            $table->timestamp('recorded_at');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('remarks')->nullable();
            $table->timestamps();

            $table->index(['hostel_booking_id', 'recorded_at']);
        });
    }
    public function down() { Schema::dropIfExists('hostel_attendances'); }
};

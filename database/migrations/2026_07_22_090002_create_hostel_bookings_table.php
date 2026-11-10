<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('hostel_bookings', function (Blueprint $table) {
            $table->id();
            // Polymorphic: the student or teacher who requested hostel accommodation.
            $table->string('requestable_type');
            $table->unsignedBigInteger('requestable_id');
            $table->foreignId('hostel_id')->constrained('hostels')->cascadeOnDelete();
            $table->foreignId('hostel_room_id')->nullable()->constrained('hostel_rooms')->nullOnDelete();

            $table->boolean('meal_breakfast')->default(false);
            $table->boolean('meal_lunch')->default(false);
            $table->boolean('meal_dinner')->default(false);
            $table->text('notes')->nullable();

            $table->enum('status', ['pending', 'approved', 'rejected', 'cancel_requested', 'cancelled'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('cancel_reason')->nullable();
            $table->timestamp('cancel_requested_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['requestable_type', 'requestable_id']);
        });
    }
    public function down() { Schema::dropIfExists('hostel_bookings'); }
};

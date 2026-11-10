<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('transport_requests', function (Blueprint $table) {
            $table->id();
            // Polymorphic: the student or teacher who requested transport.
            $table->string('requestable_type');
            $table->unsignedBigInteger('requestable_id');
            $table->foreignId('transport_route_id')->constrained('transport_routes')->cascadeOnDelete();
            $table->foreignId('transport_bus_id')->nullable()->constrained('transport_buses')->nullOnDelete();
            $table->string('pickup_stop')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancel_requested', 'cancelled'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('cancel_reason')->nullable();
            $table->timestamp('cancel_requested_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('card_number')->nullable()->unique();
            $table->timestamp('card_issued_at')->nullable();
            $table->timestamps();

            $table->index(['requestable_type', 'requestable_id']);
        });
    }
    public function down() { Schema::dropIfExists('transport_requests'); }
};
